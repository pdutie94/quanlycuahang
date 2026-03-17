<?php

class MigrationController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $info = $this->getMigrationInfo();

        $error = isset($_GET['error']) ? $_GET['error'] : '';
        $success = isset($_GET['success']) && $_GET['success'] === '1';

        $this->render('migration/index', [
            'title' => 'Migration',
            'currentVersion' => $info['currentVersion'],
            'latestVersion' => $info['latestVersion'],
            'pendingVersions' => $info['pendingVersions'],
            'allVersions' => $info['allVersions'],
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function apply()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('migration');
        }

        $this->verifyCsrfToken();

        $info = $this->getMigrationInfo();
        $pending = $info['pendingVersions'];
        $files = $info['files'];

        if (empty($pending)) {
            $this->respondMigration(false, 'Không có migration nào cần chạy');
        }

        $pdo = Database::getInstance();

        try {
            $this->ensureSchemaVersionTable($pdo);

            foreach ($pending as $version) {
                if (!isset($files[$version])) {
                    throw new Exception('File migration không tồn tại: ' . $version);
                }

                $path = $files[$version];
                $sql = file_get_contents($path);
                if ($sql === false) {
                    throw new Exception('Không đọc được file: ' . $path);
                }

                if (trim($sql) !== '') {
                    $pdo->exec($sql);
                }

                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
            }

            $this->respondMigration(true, 'Đã chạy migration thành công');
        } catch (Exception $e) {
            $this->respondMigration(false, 'Lỗi migration: ' . $e->getMessage());
        }
    }

    public function run()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('migration');
        }

        $this->verifyCsrfToken();

        $version = isset($_POST['version']) ? trim($_POST['version']) : '';
        if ($version === '') {
            $this->respondMigration(false, 'Thiếu version cần chạy');
        }

        $info = $this->getMigrationInfo();
        $files = $info['files'];

        if (!isset($files[$version])) {
            $this->respondMigration(false, 'Không tìm thấy file cho version ' . $version);
        }

        $pdo = Database::getInstance();

        try {
            $this->ensureSchemaVersionTable($pdo);

            $path = $files[$version];
            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new Exception('Không đọc được file: ' . $path);
            }

            if (trim($sql) !== '') {
                $pdo->exec($sql);
            }

            $current = $this->getCurrentVersion($pdo);
            if (version_compare($version, $current, '>')) {
                $stmt = $pdo->prepare('INSERT INTO schema_version (version, applied_at) VALUES (?, NOW())');
                $stmt->execute([$version]);
            }

            $this->respondMigration(true, 'Đã chạy lại version ' . $version . ' thành công');
        } catch (Exception $e) {
            $this->respondMigration(false, 'Lỗi khi chạy version ' . $version . ': ' . $e->getMessage());
        }
    }

    protected function getMigrationInfo()
    {
        $pdo = Database::getInstance();
        $this->ensureSchemaVersionTable($pdo);

        $currentVersion = $this->getCurrentVersion($pdo);

        $baseDir = realpath(__DIR__ . '/..');
        $sqlDir = $baseDir ? $baseDir . '/../sql' : null;

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

    protected function getCurrentVersion(PDO $pdo)
    {
        $stmt = $pdo->query('SELECT version FROM schema_version ORDER BY id DESC LIMIT 1');
        $row = $stmt->fetch();
        if ($row && !empty($row['version'])) {
            return $row['version'];
        }
        return '1.0.0';
    }

    protected function ensureSchemaVersionTable(PDO $pdo)
    {
        $sql = 'CREATE TABLE IF NOT EXISTS schema_version (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version VARCHAR(50) NOT NULL,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $pdo->exec($sql);
    }

    protected function respondMigration($success, $message)
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
            exit;
        }

        if ($success) {
            $this->redirect('migration');
        } else {
            $msg = urlencode($message);
            $this->redirect('migration?error=' . $msg);
        }
    }
}
