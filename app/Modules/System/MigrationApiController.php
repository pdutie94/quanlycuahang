<?php

namespace App\Modules\System;

use App\Shared\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MigrationApiController
{
    public function info(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $info = $this->getMigrationInfo();

        return ApiResponse::success($response, [
            'current_version' => $info['currentVersion'],
            'latest_version' => $info['latestVersion'],
            'pending_versions' => $info['pendingVersions'],
            'all_versions' => $info['allVersions'],
        ]);
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $info = $this->getMigrationInfo();
        $pending = $info['pendingVersions'];
        $files = $info['files'];

        if (empty($pending)) {
            return ApiResponse::error($response, 'Khong co migration nao can chay', 422);
        }

        $pdo = \Database::getInstance();

        try {
            $this->ensureSchemaVersionTable($pdo);

            foreach ($pending as $version) {
                if (!isset($files[$version])) {
                    throw new \Exception('File migration khong ton tai: ' . $version);
                }

                $path = $files[$version];
                $sql = file_get_contents($path);
                if ($sql === false) {
                    throw new \Exception('Khong doc duoc file: ' . $path);
                }

                if (trim($sql) !== '') {
                    $pdo->exec($sql);
                }

                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
            }

            return ApiResponse::success($response, null, 'Da chay migration thanh cong');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Loi migration: ' . $e->getMessage(), 422);
        }
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->normalizePayload($request);
        $version = isset($payload['version']) ? trim((string) $payload['version']) : '';
        if ($version === '') {
            return ApiResponse::error($response, 'Thieu version can chay', 422);
        }

        $info = $this->getMigrationInfo();
        $files = $info['files'];
        if (!isset($files[$version])) {
            return ApiResponse::error($response, 'Khong tim thay file cho version ' . $version, 422);
        }

        $pdo = \Database::getInstance();

        try {
            $this->ensureSchemaVersionTable($pdo);

            $path = $files[$version];
            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new \Exception('Khong doc duoc file: ' . $path);
            }

            if (trim($sql) !== '') {
                $pdo->exec($sql);
            }

            $current = $this->getCurrentVersion($pdo);
            if (version_compare($version, $current, '>')) {
                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
            }

            return ApiResponse::success($response, null, 'Da chay lai version ' . $version . ' thanh cong');
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Loi khi chay version ' . $version . ': ' . $e->getMessage(), 422);
        }
    }

    private function getMigrationInfo(): array
    {
        $pdo = \Database::getInstance();
        $this->ensureSchemaVersionTable($pdo);

        $currentVersion = $this->getCurrentVersion($pdo);

        $baseDir = realpath(__DIR__ . '/../../..');
        $sqlDir = $baseDir ? $baseDir . '/sql' : null;

        $files = [];
        if ($sqlDir && is_dir($sqlDir)) {
            $pattern = $sqlDir . DIRECTORY_SEPARATOR . '*.sql';
            foreach (glob($pattern) as $path) {
                $name = basename($path, '.sql');
                if (preg_match('/^\d+\.\d+\.\d+$/', $name)) {
                    $files[$name] = $path;
                }
            }
        }

        $versions = array_keys($files);
        usort($versions, 'version_compare');

        $latestVersion = $currentVersion;
        if (!empty($versions)) {
            $latestVersion = end($versions);
        }

        $pendingVersions = [];
        foreach ($versions as $version) {
            if (version_compare($version, $currentVersion, '>')) {
                $pendingVersions[] = $version;
            }
        }

        return [
            'currentVersion' => $currentVersion,
            'latestVersion' => $latestVersion,
            'pendingVersions' => $pendingVersions,
            'allVersions' => $versions,
            'files' => $files,
        ];
    }

    private function getCurrentVersion(\PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT version FROM schema_version ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        if ($row && !empty($row['version'])) {
            return $row['version'];
        }
        return '1.0.0';
    }

    private function ensureSchemaVersionTable(\PDO $pdo): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS schema_version (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(50) NOT NULL,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $pdo->exec($sql);
    }

    private function normalizePayload(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
