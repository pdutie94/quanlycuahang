<?php

global $config;
$basePath = rtrim($config['base_path'], '/');
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

require_once __DIR__ . '/../partials/components.php';

if (!function_exists('format_datetime')) {
	function format_datetime($value)
	{
		if (empty($value)) {
			return '';
		}
		$timestamp = strtotime($value);
		if ($timestamp === false) {
			return $value;
		}
		return date('H:i, d/m/Y', $timestamp);
	}
}

$assetVersion = '1';
$latestMtime = 0;

if (!empty($_SERVER['SCRIPT_FILENAME'])) {
	$publicRoot = dirname($_SERVER['SCRIPT_FILENAME']);
	$styleCssPath = $publicRoot . '/assets/css/style.css';
	$tailwindCssPath = $publicRoot . '/assets/css/tailwind.css';
    $iconsJsPath = $publicRoot . '/assets/icons.js';
	$jsPath = $publicRoot . '/assets/app.js';

	if (is_file($styleCssPath)) {
		$mtime = (int) @filemtime($styleCssPath);
		if ($mtime > $latestMtime) {
			$latestMtime = $mtime;
		}
	}

	if (is_file($tailwindCssPath)) {
		$mtime = (int) @filemtime($tailwindCssPath);
		if ($mtime > $latestMtime) {
			$latestMtime = $mtime;
		}
	}

    if (is_file($iconsJsPath)) {
        $mtime = (int) @filemtime($iconsJsPath);
        if ($mtime > $latestMtime) {
            $latestMtime = $mtime;
        }
    }

	if (is_file($jsPath)) {
		$mtime = (int) @filemtime($jsPath);
		if ($mtime > $latestMtime) {
			$latestMtime = $mtime;
		}
	}
}

if ($latestMtime > 0) {
	$assetVersion = (string) $latestMtime;
}

$currentRoute = '';
if (isset($_GET['r']) && is_string($_GET['r'])) {
    $currentRoute = trim($_GET['r'], '/');
}
if ($currentRoute === '') {
    $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $uriPath = (string) parse_url($requestUri, PHP_URL_PATH);
    if ($basePath !== '' && strpos($uriPath, $basePath) === 0) {
        $uriPath = substr($uriPath, strlen($basePath));
    }
    $currentRoute = trim($uriPath, '/');
}
$currentRoute = strtolower($currentRoute);

$isDashboardRoute = ($currentRoute === '' || $currentRoute === 'dashboard' || strpos($currentRoute, 'dashboard/') === 0);
$isPosRoute = (strpos($currentRoute, 'pos') === 0);
$isProductRoute = (strpos($currentRoute, 'product') === 0);
$isReportRoute = (strpos($currentRoute, 'report') === 0);
?><!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo isset($title) ? $title . ' - ' . htmlspecialchars($config['app_name']) : htmlspecialchars($config['app_name']); ?></title>
	<meta name="robots" content="noindex,nofollow,noarchive,noimageindex,nosnippet">
    <meta name="theme-color" content="#ffffff">
	<link rel="icon" type="image/png" href="<?php echo $basePath; ?>/favicon.png">
	<link rel="manifest" href="<?php echo $basePath; ?>/public/manifest.webmanifest">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo $basePath; ?>/assets/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?php echo $basePath; ?>/assets/icons/icon-512.png">
	<link href="<?php echo $basePath; ?>/assets/css/tailwind.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body class="app-shell min-h-screen" data-base-path="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>" data-csrf-token="<?php echo isset($csrfToken) ? htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') : ''; ?>" <?php if (!empty($flash) && !empty($flash['message'])) { ?>data-flash-message="<?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>" data-flash-type="<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
<div class="app-shell flex min-h-screen flex-col">
	<header class="app-topbar">
                <div class="app-content-wrap flex items-center justify-between gap-3 py-2">
            <div class="flex items-center gap-2">
				<a href="<?php echo $basePath; ?>/dashboard" class="font-display text-lg font-semibold tracking-tight text-slate-900">Đại lý Đức Nam</a>
            </div>
            <?php if ($user) { ?>
                <div class="flex items-center gap-2 text-sm">
					<a href="<?php echo $basePath; ?>/user/changePasswordForm" class="app-icon-btn" title="Tài khoản">
                        <?php echo ui_icon('user', 'size-5'); ?>
                    </a>
					<a href="<?php echo $basePath; ?>/logout" class="app-icon-btn" title="Đăng xuất">
                        <?php echo ui_icon('logout', 'size-5'); ?>
                    </a>
                </div>
            <?php } ?>
        </div>
    </header>
    <main class="flex-1 pb-[4.2rem]">
        <?php if (isset($detailHeader) && is_array($detailHeader)) { ?>
            <?php require __DIR__ . '/../partials/detail_header.php'; ?>
        <?php } ?>
        <?php if (isset($listHeader) && is_array($listHeader)) { ?>
            <?php require __DIR__ . '/../partials/list_header.php'; ?>
        <?php } ?>
        <div class="app-content-wrap <?php echo isset($detailHeader) ? 'pt-4 pb-5 lg:pt-6 lg:pb-8' : 'py-5 lg:py-8'; ?>">
            <?php if (isset($viewFile)) { require $viewFile; } ?>
        </div>
    </main>
	<?php if ($user) { ?>
        <nav class="app-bottom-nav">
            <div class="app-bottom-nav-inner text-center text-slate-700">
                <a href="<?php echo $basePath; ?>/dashboard" class="app-bottom-nav-link <?php echo $isDashboardRoute ? 'app-bottom-nav-link-active' : ''; ?>">
                    <?php echo ui_icon('home', 'size-6'); ?>

                    <span>Home</span>
                </a>
                <a href="<?php echo $basePath; ?>/pos" class="app-bottom-nav-link <?php echo $isPosRoute ? 'app-bottom-nav-link-active' : ''; ?>">
                    <?php echo ui_icon('pos', 'size-6'); ?>
        
                    <span>Tạo đơn</span>
                </a>
                <a href="<?php echo $basePath; ?>/product" class="app-bottom-nav-link <?php echo $isProductRoute ? 'app-bottom-nav-link-active' : ''; ?>">
                    <?php echo ui_icon('cube', 'size-6'); ?>

                    <span>Sản phẩm</span>
                </a>
                <a href="<?php echo $basePath; ?>/report" class="app-bottom-nav-link <?php echo $isReportRoute ? 'app-bottom-nav-link-active' : ''; ?>">
                    <?php echo ui_icon('chart-pie', 'size-6'); ?>

                    <span>Báo cáo</span>
                </a>
                <button type="button" class="app-bottom-nav-link" data-footer-menu-toggle>
                    <?php echo ui_icon('bars-3', 'size-6'); ?>

                    <span>Menu</span>
                </button>
            </div>
		</nav>
        <div class="app-modal-overlay" data-app-menu-overlay>
            <div class="app-modal-sheet">
                <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2">
                    <div class="text-md transform font-medium text-slate-800">Menu quản lý</div>
                    <button type="button" class="app-modal-close" data-app-menu-close>
                        <?php echo ui_icon("x-mark", "h-4 w-4"); ?>
                    </button>
		        </div>
                <div class="flex-1 overflow-y-auto px-3 pb-4 pt-3 text-sm">
		        	<div class="grid grid-cols-2 gap-2">
                    <a href="<?php echo $basePath; ?>/dashboard" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('home', 'h-5 w-5'); ?>
                        </span>
                        <span>Tổng quan</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/pos" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
							<?php echo ui_icon('cart', 'h-5 w-5'); ?>
                        </span>
                        <span>Bán hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/order" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('clipboard-document', 'h-5 w-5'); ?>
                        </span>
                        <span>Đơn hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/product" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('cube', 'h-5 w-5'); ?>
                        </span>
                        <span>Sản phẩm</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/purchase" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('purchase', 'h-5 w-5'); ?>
                        </span>
                        <span>Phiếu nhập</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/customer" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('user', 'h-5 w-5'); ?>
                        </span>
                        <span>Khách hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/supplier" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
							<?php echo ui_icon('supplier', 'h-5 w-5'); ?>
                        </span>
                        <span>Nhà cung cấp</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/report/sales" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('chart-pie', 'h-5 w-5'); ?>
                        </span>
                        <span>Báo cáo doanh thu</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/report/inventory" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('chart-pie', 'h-5 w-5'); ?>
                        </span>
                        <span>Báo cáo tồn kho</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/category" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
                            <?php echo ui_icon('categories', 'h-5 w-5'); ?>
                        </span>
                        <span>Danh mục sản phẩm</span>
                    </a>
		                    <a href="<?php echo $basePath; ?>/unit" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-50 text-brand-700">
									<?php echo ui_icon('unit', 'h-5 w-5'); ?>
							</span>
							<span>Đơn vị tính</span>
						</a>
		                </div>
		            </div>
		        </div>
    <?php } ?>
</div>
<script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?php echo $basePath; ?>/assets/icons.js?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo $basePath; ?>/assets/app.js?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
