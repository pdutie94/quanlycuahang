<?php

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/Core/Database.php';

$results = [];

$results['db'] = [
    'ok' => false,
    'message' => '',
];

try {
    $pdo = Database::getInstance();
    $pdo->query('SELECT 1');
    $results['db']['ok'] = true;
    $results['db']['message'] = 'Kết nối cơ sở dữ liệu thành công';
} catch (Throwable $e) {
    $results['db']['ok'] = false;
    $results['db']['message'] = 'Lỗi kết nối cơ sở dữ liệu';
}

$results['migration'] = [
    'ok' => false,
    'current_version' => '1.0.0',
    'latest_version' => '1.0.0',
    'pending_count' => 0,
    'message' => '',
];

try {
    $currentVersion = '1.0.0';
    $stmt = $pdo->query('SELECT version FROM schema_version ORDER BY id DESC LIMIT 1');
    $row = $stmt->fetch();
    if ($row && !empty($row['version'])) {
        $currentVersion = $row['version'];
    }

    $baseDir = realpath(__DIR__ . '/..');
    $sqlDir = $baseDir ? $baseDir . DIRECTORY_SEPARATOR . 'sql' : null;

    $files = [];
    if ($sqlDir && is_dir($sqlDir)) {
        $pattern = $sqlDir . DIRECTORY_SEPARATOR . '*.sql';
        foreach (glob($pattern) as $path) {
            $name = basename($path, '.sql');
            if (preg_match('/^\d+\.\d+\.\d+$/', $name)) {
                $files[] = $name;
            }
        }
    }

    $latestVersion = $currentVersion;
    if (!empty($files)) {
        usort($files, 'version_compare');
        $latestVersion = end($files);
    }

    $pendingCount = 0;
    foreach ($files as $version) {
        if (version_compare($version, $currentVersion, '>')) {
            $pendingCount++;
        }
    }

    $results['migration']['ok'] = true;
    $results['migration']['current_version'] = $currentVersion;
    $results['migration']['latest_version'] = $latestVersion;
    $results['migration']['pending_count'] = $pendingCount;
    $results['migration']['message'] = $pendingCount > 0 ? 'Còn migration chưa chạy' : 'Đã ở phiên bản mới nhất';
} catch (Throwable $e) {
    $results['migration']['ok'] = false;
    $results['migration']['message'] = 'Không thể kiểm tra trạng thái migration';
}

$results['filesystem'] = [
    'ok' => false,
    'message' => '',
    'paths' => [],
];

$baseDir = realpath(__DIR__ . '/..');
$publicDir = $baseDir ? $baseDir . DIRECTORY_SEPARATOR . 'public' : null;
$uploadPaths = [];

if ($publicDir) {
    $uploadPaths[] = $publicDir . DIRECTORY_SEPARATOR . 'uploads';
    $uploadPaths[] = $publicDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
}

$fsOk = true;
$fsMessages = [];
$fsPathStatuses = [];

foreach ($uploadPaths as $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    $fsPathStatuses[] = [
        'path' => $path,
        'exists' => $exists,
        'writable' => $writable,
    ];
    if (!$exists || !$writable) {
        $fsOk = false;
    }
}

if (empty($uploadPaths)) {
    $fsOk = false;
    $fsMessages[] = 'Không xác định được thư mục uploads';
} else {
    if ($fsOk) {
        $fsMessages[] = 'Thư mục upload tồn tại và ghi được';
    } else {
        $fsMessages[] = 'Thư mục upload thiếu hoặc không ghi được';
    }
}

$results['filesystem']['ok'] = $fsOk;
$results['filesystem']['message'] = implode('; ', $fsMessages);
$results['filesystem']['paths'] = $fsPathStatuses;

// Disk space check
$diskTotal = @disk_total_space($baseDir);
$diskFree = @disk_free_space($baseDir);
$diskPercentFree = null;
$diskOk = false;
$diskMessage = 'Không thể kiểm tra dung lượng ổ đĩa';
if ($diskTotal > 0 && $diskFree !== false) {
    $diskPercentFree = ($diskFree / $diskTotal) * 100;
    $diskOk = $diskPercentFree >= 10; // yêu cầu ít nhất 10% trống
    $diskMessage = sprintf('Ổ đĩa trống: %.2f%% (%s / %s)',
        $diskPercentFree,
        number_format($diskFree / 1024 / 1024, 2) . 'MB',
        number_format($diskTotal / 1024 / 1024, 2) . 'MB'
    );
}
$results['disk'] = [
    'ok' => $diskOk,
    'message' => $diskMessage,
    'total_bytes' => $diskTotal,
    'free_bytes' => $diskFree,
    'free_percent' => $diskPercentFree,
];

// Memory and CPU/system stats
$memoryUsage = memory_get_usage(true);
$memoryPeak = memory_get_peak_usage(true);
$memoryLimit = ini_get('memory_limit');
$memoryOk = true;
$memoryMessage = sprintf('Đã sử dụng: %s, đỉnh: %s, limit: %s',
    number_format($memoryUsage / 1024 / 1024, 2) . ' MB',
    number_format($memoryPeak / 1024 / 1024, 2) . ' MB',
    $memoryLimit
);

$cpuOk = true;
$cpuMessage = 'Không hỗ trợ sys_getloadavg';
$cpuLoad = null;
if (function_exists('sys_getloadavg')) {
    $load = sys_getloadavg();
    $cpuLoad = isset($load[0]) ? (float) $load[0] : null;
    $cpuMessage = sprintf('Load 1min: %.2f, 5min: %.2f, 15min: %.2f', $load[0], $load[1], $load[2]);
    // 4 CPUs threshold
    $cpuOk = $cpuLoad !== null ? $cpuLoad <= 4.0 : true;
}

$results['system'] = [
    'ok' => $memoryOk && $cpuOk,
    'message' => trim($memoryMessage . '; ' . $cpuMessage),
    'memory_usage' => $memoryUsage,
    'memory_peak' => $memoryPeak,
    'memory_limit' => $memoryLimit,
    'cpu_load_1m' => $cpuLoad,
];

$overallOk = true;
foreach ($results as $section) {
    if (isset($section['ok']) && !$section['ok']) {
        $overallOk = false;
        break;
    }
}

http_response_code($overallOk ? 200 : 500);

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Health Check</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 16px; }
        .card { max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(15,23,42,0.12); }
        h1 { font-size: 18px; margin: 0 0 12px; color: #0f172a; }
        .section { margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
        .section-title { font-size: 13px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
        .status { font-size: 13px; margin-bottom: 4px; }
        .status-ok { color: #15803d; }
        .status-fail { color: #b91c1c; }
        .kv { font-size: 13px; color: #374151; margin: 0; }
        .kv span { color: #6b7280; }
        .path-item { font-size: 12px; color: #4b5563; margin: 2px 0; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 999px; font-size: 11px; font-weight: 500; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fee2e2; color: #b91c1c; }
        .overall-ok { color: #166534; }
        .overall-fail { color: #b91c1c; }
    </style>
</head>
<body>
<div class="card">
    <h1>Health Check</h1>
    <div class="status <?php echo $overallOk ? 'overall-ok' : 'overall-fail'; ?>">
        Trạng thái chung:
        <span class="badge <?php echo $overallOk ? 'badge-ok' : 'badge-fail'; ?>">
            <?php echo $overallOk ? 'OK' : 'LỖI'; ?>
        </span>
    </div>

    <div class="section">
        <div class="section-title">Cơ sở dữ liệu</div>
        <div class="status <?php echo $results['db']['ok'] ? 'status-ok' : 'status-fail'; ?>">
            <?php echo htmlspecialchars($results['db']['message']); ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Migration</div>
        <div class="status <?php echo $results['migration']['ok'] ? 'status-ok' : 'status-fail'; ?>">
            <?php echo htmlspecialchars($results['migration']['message']); ?>
        </div>
        <?php if ($results['migration']['ok']) { ?>
            <p class="kv"><span>Phiên bản hiện tại:</span> <?php echo htmlspecialchars($results['migration']['current_version']); ?></p>
            <p class="kv"><span>Phiên bản mới nhất:</span> <?php echo htmlspecialchars($results['migration']['latest_version']); ?></p>
            <p class="kv"><span>Số migration chờ:</span> <?php echo (int) $results['migration']['pending_count']; ?></p>
        <?php } ?>
    </div>

    <div class="section">
        <div class="section-title">Filesystem / Upload</div>
        <div class="status <?php echo $results['filesystem']['ok'] ? 'status-ok' : 'status-fail'; ?>">
            <?php echo htmlspecialchars($results['filesystem']['message']); ?>
        </div>
        <?php foreach ($results['filesystem']['paths'] as $item) { ?>
            <div class="path-item">
                <?php echo htmlspecialchars($item['path']); ?>:
                <?php echo $item['exists'] ? 'tồn tại' : 'không tồn tại'; ?>,
                <?php echo $item['writable'] ? 'ghi được' : 'không ghi được'; ?>
            </div>
        <?php } ?>
    </div>

    <div class="section">
        <div class="section-title">Disk Space</div>
        <div class="status <?php echo $results['disk']['ok'] ? 'status-ok' : 'status-fail'; ?>">
            <?php echo htmlspecialchars($results['disk']['message']); ?>
        </div>
        <p class="kv"><span>Tổng:</span> <?php echo $results['disk']['total_bytes'] !== null ? number_format($results['disk']['total_bytes'] / 1024 / 1024, 2) . ' MB' : 'n/a'; ?></p>
        <p class="kv"><span>Trống:</span> <?php echo $results['disk']['free_bytes'] !== null ? number_format($results['disk']['free_bytes'] / 1024 / 1024, 2) . ' MB' : 'n/a'; ?></p>
        <p class="kv"><span>% trống:</span> <?php echo $results['disk']['free_percent'] !== null ? number_format($results['disk']['free_percent'], 2) . '%' : 'n/a'; ?></p>
    </div>

    <div class="section">
        <div class="section-title">System</div>
        <div class="status <?php echo $results['system']['ok'] ? 'status-ok' : 'status-fail'; ?>">
            <?php echo htmlspecialchars($results['system']['message']); ?>
        </div>
        <p class="kv"><span>Memory dùng:</span> <?php echo number_format($results['system']['memory_usage'] / 1024 / 1024, 2); ?> MB</p>
        <p class="kv"><span>Memory đỉnh:</span> <?php echo number_format($results['system']['memory_peak'] / 1024 / 1024, 2); ?> MB</p>
        <p class="kv"><span>Memory limit:</span> <?php echo htmlspecialchars($results['system']['memory_limit']); ?></p>
        <p class="kv"><span>CPU load 1m:</span> <?php echo $results['system']['cpu_load_1m'] !== null ? number_format($results['system']['cpu_load_1m'], 2) : 'n/a'; ?></p>
    </div>
</div>
</body>
</html>
