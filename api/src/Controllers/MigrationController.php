<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MigrationController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $info = $this->getMigrationInfo();

        return $this->success($response, [
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
            return $this->success($response, [
                'applied_versions' => [],
            ], 'Không có migration nào cần chạy');
        }

        $pdo = Database::getInstance($this->config['db']);
        $pdo->beginTransaction();

        try {
            $this->ensureSchemaVersionTable($pdo);
            $appliedVersions = [];

            foreach ($pending as $version) {
                if (!isset($files[$version])) {
                    throw new \RuntimeException('File migration không tồn tại: ' . $version);
                }

                $path = $files[$version];
                $sql = file_get_contents($path);
                if ($sql === false) {
                    throw new \RuntimeException('Không đọc được file: ' . $path);
                }

                if (trim($sql) !== '') {
                    $pdo->exec($sql);
                }

                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
                $appliedVersions[] = $version;
            }

            $pdo->commit();

            return $this->success($response, [
                'applied_versions' => $appliedVersions,
            ], 'Đã chạy migration thành công');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return $this->error($response, 'Lỗi migration: ' . $e->getMessage(), 500);
        }
    }

    public function run(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) ($request->getParsedBody() ?? []);
        $version = trim((string) ($body['version'] ?? ''));
        if ($version === '') {
            return $this->error($response, 'Thiếu version cần chạy', 400, ['version' => 'Required']);
        }

        $info = $this->getMigrationInfo();
        $files = $info['files'];
        if (!isset($files[$version])) {
            return $this->error($response, 'Không tìm thấy file cho version ' . $version, 404);
        }

        $pdo = Database::getInstance($this->config['db']);
        $pdo->beginTransaction();

        try {
            $this->ensureSchemaVersionTable($pdo);

            $path = $files[$version];
            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new \RuntimeException('Không đọc được file: ' . $path);
            }

            if (trim($sql) !== '') {
                $pdo->exec($sql);
            }

            $current = $this->getCurrentVersion($pdo);
            if (version_compare($version, $current, '>')) {
                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
            }

            $pdo->commit();

            return $this->success($response, [
                'version' => $version,
            ], 'Đã chạy version ' . $version . ' thành công');
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return $this->error($response, 'Lỗi khi chạy version ' . $version . ': ' . $e->getMessage(), 500);
        }
    }

    private function getMigrationInfo(): array
    {
        $pdo = Database::getInstance($this->config['db']);
        $this->ensureSchemaVersionTable($pdo);

        $currentVersion = $this->getCurrentVersion($pdo);
        $projectRoot = dirname(__DIR__, 3);
        $sqlDir = $projectRoot . '/sql';

        $files = [];
        if (is_dir($sqlDir)) {
            $pattern = $sqlDir . DIRECTORY_SEPARATOR . '*.sql';
            $paths = glob($pattern);
            foreach ($paths === false ? [] : $paths as $path) {
                $name = basename($path, '.sql');
                if (preg_match('/^\d+\.\d+\.\d+$/', $name) === 1) {
                    $files[$name] = $path;
                }
            }
        }

        $versions = array_keys($files);
        usort($versions, 'version_compare');

        $latestVersion = !empty($versions) ? (string) end($versions) : $currentVersion;
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

    private function getCurrentVersion(PDO $pdo): string
    {
        $stmt = $pdo->query('SELECT version FROM schema_version ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();

        return ($row && !empty($row['version'])) ? (string) $row['version'] : '1.0.0';
    }

    private function ensureSchemaVersionTable(PDO $pdo): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS schema_version (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(50) NOT NULL,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $pdo->exec($sql);
    }
}
