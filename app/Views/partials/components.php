<?php

if (!function_exists('ui_icon')) {
	function ui_icon($name, $class = 'h-5 w-5', $attrs = [])
	{
		$icons = [
			'user' => [
				'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
				'M4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
			],
			'logout' => [
				'M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15',
			],
			'home' => [
				'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
			],
			'pos' => [
				'M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z',
			],
			'cube' => [
				'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
			],
			'chart-pie' => [
				'M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z',
				'M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z',
			],
			'bars-3' => [
				'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
			],
			'cart' => [
				'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
			],
			'plus' => [
				'M12 4.5v15m7.5-7.5h-15',
			],
			'purchase' => [
				'M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184',
			],
			'supplier' => [
				'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z',
			],
			'categories' => [
				'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
			],
			'unit' => [
				'M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z',
			],
			'chevron-left' => [
				'M15.75 19.5 8.25 12l7.5-7.5',
			],
			'chevron-right' => [
				'M8.25 4.5l7.5 7.5-7.5 7.5',
			],
			'chevron-down' => [
				'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.24 4.5a.75.75 0 0 1-1.08 0l-4.24-4.5a.75.75 0 0 1 .02-1.06Z',
			],
			'x-mark' => [
				'M6 18 18 6M6 6l12 12',
			],
			'ellipsis-vertical' => [
				'M12 6.75a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM12 13.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM12 20.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z',
			],
			'pencil-square' => [
				'M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM16.862 4.487L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H9',
			],
			'trash' => [
				'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
			],
			'archive-box' => [
				'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
			],
			'calendar' => [
				'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5',
			],
			'clock' => [
				'M12 6v6h4.5m3.75 0a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z',
			],
			'arrow-path' => [
				'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99',
			],
			'phone' => [
				'M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.386c0-.516-.351-.966-.852-1.091l-3.423-.856a1.125 1.125 0 0 0-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.977 3.102A1.125 1.125 0 0 0 5.886 2.25H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z',
			],
			'external-link' => [
				'M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25',
			],
			'banknotes' => [
				'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
			],
			'return' => [
				'M9 10.5V6.75A2.25 2.25 0 0 1 11.25 4.5h6A2.25 2.25 0 0 1 19.5 6.75V9M9 10.5 6.75 8.25M9 10.5 11.25 8.25M9 13.5v3.75A2.25 2.25 0 0 0 11.25 19.5h6A2.25 2.25 0 0 0 19.5 17.25V15M9 13.5 6.75 15.75M9 13.5 11.25 15.75',
			],
			'delete-box' => [
				'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M4.5 6.75h15m-1.5 0l-.621 12.42A1.125 1.125 0 0 1 16.257 20.25H7.743a1.125 1.125 0 0 1-1.122-1.08L6 6.75m3-3h6A1.125 1.125 0 0 1 16.125 4.875V6.75H7.875V4.875A1.125 1.125 0 0 1 9 3.75Z',
			],
			'minus' => [
				'M5 12h14',
			],
			'arrow-right' => [
				'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
			],
			'magnifying-glass' => [
				'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
			],
			'magnifying-glass-alt' => [
				'M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 5.25 5.25a7.5 7.5 0 0 0 11.4 11.4Z',
			],
			'check' => [
				'M4.5 12.75 9 17.25 19.5 6.75',
			],
			'document-text' => [
				'M6.75 3h10.5A2.25 2.25 0 0 1 19.5 5.25v13.5A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75V5.25A2.25 2.25 0 0 1 6.75 3zm3 4.5h4.5m-4.5 3h4.5m-4.5 3h2.25',
			],
			'undo-left' => [
				'M11.25 11.25l-3 3m0 0l3 3m-3-3h7.5a3.75 3.75 0 0 0 0-7.5h-1.5',
			],
			'user-group' => [
				'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
			],
			'arrow-left-on-rectangle' => [
				'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75',
			],
			'chart-bar' => [
				'M3 13.125C3 12.504 3.504 12 4.125 12h2.25C6.996 12 7.5 12.504 7.5 13.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 9.75 19.875V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 0 1 16.5 19.875V4.125Z',
			],
			'truck' => [
				'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
			],
			'tag' => [
				'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z',
				'M6 6h.008v.008H6V6Z',
			],
			'plus-minus' => [
				'M10.75 4.75a.75.75 0 1 0-1.5 0v10.5a.75.75 0 1 0 1.5 0V4.75Z',
				'M4.75 10a.75.75 0 0 1 .75-.75h9a.75.75 0 0 1 0 1.5h-9A.75.75 0 0 1 4.75 10Z',
			],
			'eye' => [
				'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z',
				'M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z',
			],
			'sliders-horizontal' => [
				'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 0 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
			],
			'clipboard-document' => [
				'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
			],
		];

		if (!isset($icons[$name])) {
			return '';
		}

		$attrParts = [
			'xmlns="http://www.w3.org/2000/svg"',
			'fill="none"',
			'viewBox="0 0 24 24"',
			'stroke-width="1.5"',
			'stroke="currentColor"',
			'class="' . htmlspecialchars((string) $class, ENT_QUOTES, 'UTF-8') . '"',
		];

		foreach ($attrs as $key => $value) {
			$attrParts[] = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
		}

		$paths = '';
		foreach ($icons[$name] as $d) {
			$paths .= '<path stroke-linecap="round" stroke-linejoin="round" d="' . htmlspecialchars($d, ENT_QUOTES, 'UTF-8') . '" />';
		}

		return '<svg ' . implode(' ', $attrParts) . '>' . $paths . '</svg>';
	}
}

if (!function_exists('ui_button_primary')) {
	function ui_button_primary($label, $attrs = [])
	{
		$baseClass = 'inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50';
		$attrParts = [];
		foreach ($attrs as $key => $value) {
			if ($key === 'class') {
				$value = $baseClass . ' ' . trim((string) $value);
			}
			$attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
		}
		if (!isset($attrs['class'])) {
			$attrParts[] = 'class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '"';
		}
		echo '<button ' . implode(' ', $attrParts) . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
	}
}

if (!function_exists('ui_button_secondary')) {
	function ui_button_secondary($label, $attrs = [])
	{
		$baseClass = 'inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50';
		$attrParts = [];
		foreach ($attrs as $key => $value) {
			if ($key === 'class') {
				$value = $baseClass . ' ' . trim((string) $value);
			}
			$attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
		}
		if (!isset($attrs['class'])) {
			$attrParts[] = 'class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '"';
		}
		echo '<button ' . implode(' ', $attrParts) . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</button>';
	}
}

if (!function_exists('ui_input_text')) {
	function ui_input_text($name, $value = '', $attrs = [])
	{
		$baseClass = 'form-field block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500';
		$attrParts = [];
		$attrs['name'] = $name;
		$attrs['value'] = $value;
		foreach ($attrs as $key => $val) {
			if ($key === 'class') {
				$val = $baseClass . ' ' . trim((string) $val);
			}
			$attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '"';
		}
		if (!isset($attrs['class'])) {
			$attrParts[] = 'class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '"';
		}
		echo '<input ' . implode(' ', $attrParts) . ' />';
	}
}

if (!function_exists('ui_select')) {
	function ui_select($name, $options, $selected = null, $attrs = [])
	{
		$baseClass = 'form-field block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-brand-500';
		$attrParts = [];
		$attrs['name'] = $name;
		foreach ($attrs as $key => $val) {
			if ($key === 'class') {
				$val = $baseClass . ' ' . trim((string) $val);
			}
			$attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') . '"';
		}
		if (!isset($attrs['class'])) {
			$attrParts[] = 'class="' . htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8') . '"';
		}
		echo '<select ' . implode(' ', $attrParts) . '>';
		foreach ($options as $value => $label) {
			$isSelected = (string) $value === (string) $selected ? ' selected' : '';
			echo '<option value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</option>';
		}
		echo '</select>';
	}
}

if (!function_exists('ui_alert')) {
	function ui_alert($type, $message)
	{
		$baseClass = 'flex items-start gap-2 rounded-lg border px-3 py-2 text-sm';
		$map = [
			'success' => 'border-brand-200 bg-brand-50 text-brand-700',
			'error' => 'border-red-200 bg-red-50 text-red-800',
			'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
			'info' => 'border-slate-200 bg-slate-50 text-slate-700',
		];
		$colorClass = isset($map[$type]) ? $map[$type] : $map['info'];
		echo '<div class="' . htmlspecialchars($baseClass . ' ' . $colorClass, ENT_QUOTES, 'UTF-8') . '">';
		echo '<div class="text-sm">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
		echo '</div>';
	}
}

