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
	<meta name="theme-color" content="#0f172a">
	<link rel="icon" type="image/png" href="<?php echo $basePath; ?>/favicon.png">
	<link rel="manifest" href="<?php echo $basePath; ?>/public/manifest.webmanifest">
	<link rel="apple-touch-icon" sizes="192x192" href="<?php echo $basePath; ?>/assets/icons/icon-192.png">
	<link rel="apple-touch-icon" sizes="512x512" href="<?php echo $basePath; ?>/assets/icons/icon-512.png">
	<link href="<?php echo $basePath; ?>/assets/css/tailwind.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
	<link href="<?php echo $basePath; ?>/assets/css/style.css?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen" data-base-path="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>" data-csrf-token="<?php echo isset($csrfToken) ? htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') : ''; ?>" <?php if (!empty($flash) && !empty($flash['message'])) { ?>data-flash-message="<?php echo htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8'); ?>" data-flash-type="<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>"<?php } ?>>
<div class="min-h-screen flex flex-col">
	<header class="bg-slate-900/95 text-slate-50 backdrop-blur border-b border-slate-800/80">
        <div class="mx-auto w-full max-w-4xl px-3 py-2 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="<?php echo $basePath; ?>/dashboard" class="font-medium tracking-tight text-lg">Đại lý Đức Nam</a>
            </div>
            <?php if ($user) { ?>
                <div class="flex items-center gap-2 text-sm">
                    <a href="<?php echo $basePath; ?>/user/changePasswordForm" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-500 text-slate-100 hover:bg-slate-800 transition" title="Tài khoản">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </a>
                    <a href="<?php echo $basePath; ?>/logout" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-500 text-slate-100 hover:bg-slate-800 transition" title="Đăng xuất">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" />
                        </svg>
                    </a>
                </div>
            <?php } ?>
        </div>
    </header>
	<main class="flex-1 pb-[3.5rem]">
        <?php if (isset($detailHeader) && is_array($detailHeader)) { ?>
            <?php require __DIR__ . '/../partials/detail_header.php'; ?>
        <?php } ?>
        <?php if (isset($listHeader) && is_array($listHeader)) { ?>
            <?php require __DIR__ . '/../partials/list_header.php'; ?>
        <?php } ?>
        <div class="mx-auto w-full max-w-4xl px-3 <?php echo isset($detailHeader) ? 'pt-3 pb-4 lg:pt-4 lg:pb-6' : 'py-4 lg:py-6'; ?>">
            <?php if (isset($viewFile)) { require $viewFile; } ?>
        </div>
    </main>
	<?php if ($user) { ?>
		<nav class="fixed inset-x-0 bottom-0 border-t border-slate-200 bg-white">
			<div class="mx-auto flex max-w-4xl text-center items-center justify-between text-xs font-medium text-slate-700">
                <a href="<?php echo $basePath; ?>/dashboard" class="flex flex-1 flex-col items-center p-2 <?php echo $isDashboardRoute ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
</svg>

                    <span>Home</span>
                </a>
                <a href="<?php echo $basePath; ?>/pos" class="flex flex-1 flex-col items-center p-2 <?php echo $isPosRoute ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z" />
</svg>
        
                    <span>Tạo đơn</span>
                </a>
                <a href="<?php echo $basePath; ?>/product" class="flex flex-1 flex-col items-center p-2 <?php echo $isProductRoute ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
</svg>

                    <span>Sản phẩm</span>
                </a>
                <a href="<?php echo $basePath; ?>/report" class="flex flex-1 flex-col items-center p-2 <?php echo $isReportRoute ? 'text-emerald-700 bg-emerald-50' : 'text-slate-600'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
</svg>

                    <span>Báo cáo</span>
                </a>
                <button type="button" class="flex flex-1 flex-col items-center p-2" data-footer-menu-toggle>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
</svg>

                    <span>Menu</span>
                </button>
            </div>
		</nav>
		<div class="fixed inset-0 z-40 hidden items-center justify-center bg-black/40" data-app-menu-overlay>
			<div class="flex h-full w-full flex-col bg-white  overflow-hidden">
		        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <div class="text-sm font-medium text-slate-800">Menu quản lý</div>
                    <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200" data-app-menu-close>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                        </svg>
                    </button>
		        </div>
		        <div class="flex-1 overflow-y-auto px-4 pt-3 pb-4 text-sm">
		        	<div class="grid grid-cols-2 gap-2">
                    <a href="<?php echo $basePath; ?>/dashboard" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
</svg>
                        </span>
                        <span>Tổng quan</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/pos" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
</svg>
                        </span>
                        <span>Bán hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/order" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
</svg>
                        </span>
                        <span>Đơn hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/product" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
</svg>
                        </span>
                        <span>Sản phẩm</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/purchase" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
</svg>
                        </span>
                        <span>Phiếu nhập</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/customer" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
</svg>
                        </span>
                        <span>Khách hàng</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/supplier" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
</svg>
                        </span>
                        <span>Nhà cung cấp</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/report/sales" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
</svg>
                        </span>
                        <span>Báo cáo doanh thu</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/report/inventory" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
</svg>
                        </span>
                        <span>Báo cáo tồn kho</span>
                    </a>
                    <a href="<?php echo $basePath; ?>/category" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
</svg>
                        </span>
                        <span>Danh mục sản phẩm</span>
                    </a>
		                    <a href="<?php echo $basePath; ?>/unit" class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
					<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					  <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z" />
					</svg>
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
<script src="<?php echo $basePath; ?>/assets/app.js?v=<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
