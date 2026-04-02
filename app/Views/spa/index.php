<?php

global $config;
$basePath = isset($config['base_path']) ? rtrim((string) $config['base_path'], '/') : '';
?>
<div id="spa-root" class="space-y-4"></div>
<script type="module" src="<?php echo $basePath; ?>/assets/spa/main.js"></script>
<script>
document.addEventListener('click', function (event) {
	if (event.defaultPrevented) {
		return;
	}

	if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
		return;
	}

	var link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
	if (!link) {
		return;
	}

	var href = link.getAttribute('href');
	if (!href || href.indexOf('javascript:') === 0 || href.indexOf('#') === 0) {
		return;
	}

	if (link.hasAttribute('download') || link.getAttribute('rel') === 'external' || link.hasAttribute('data-no-spa')) {
		return;
	}

	var targetAttr = link.getAttribute('target');
	if (targetAttr && targetAttr !== '_self') {
		return;
	}

	var url;
	try {
		url = new URL(href, window.location.origin);
	} catch (_err) {
		return;
	}

	if (url.origin !== window.location.origin) {
		return;
	}

	var path = url.pathname;
	var spaPattern = /^\/(dashboard|products|customers|orders|pos|purchases|suppliers|categories|units|reports)(\/|$)/;
	if (!spaPattern.test(path)) {
		return;
	}

	var router = window.__SPA_ROUTER__;
	if (!router || typeof router.push !== 'function') {
		return;
	}

	event.preventDefault();
	var fullPath = path + (url.search || '') + (url.hash || '');
	if (window.location.pathname === path && window.location.search === (url.search || '') && window.location.hash === (url.hash || '')) {
		return;
	}
	router.push(fullPath);
}, true);
</script>
