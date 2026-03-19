<?php

if (!function_exists('ui_icon')) {
	function ui_icon($name, $class = 'h-5 w-5', $attrs = [])
	{
		$icons = [
			'archive-box' => '<rect width="20" height="5" x="2" y="3" rx="1" /><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8" /><path d="M10 12h4" />',
			'arrow-left-on-rectangle' => '<path d="m16 17 5-5-5-5" /><path d="M21 12H9" /><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />',
			'arrow-path' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" /><path d="M21 3v5h-5" /><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" /><path d="M8 16H3v5" />',
			'arrow-right' => '<path d="M5 12h14" /><path d="m12 5 7 7-7 7" />',
			'banknotes' => '<path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17" /><path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.75-2.91l-4.2 3.9" /><path d="m2 16 6 6" /><circle cx="16" cy="9" r="2.9" /><circle cx="6" cy="5" r="3" />',
			'bars-3' => '<path d="M4 5h16" /><path d="M4 12h16" /><path d="M4 19h16" />',
			'calendar' => '<path d="M8 2v4" /><path d="M16 2v4" /><rect width="18" height="18" x="3" y="4" rx="2" /><path d="M3 10h18" />',
			'cart' => '<circle cx="8" cy="21" r="1" /><circle cx="19" cy="21" r="1" /><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12" />',
			'categories' => '<rect width="7" height="7" x="3" y="3" rx="1" /><rect width="7" height="7" x="14" y="3" rx="1" /><rect width="7" height="7" x="14" y="14" rx="1" /><rect width="7" height="7" x="3" y="14" rx="1" />',
			'chart-bar' => '<path d="M3 3v16a2 2 0 0 0 2 2h16" /><path d="M18 17V9" /><path d="M13 17V5" /><path d="M8 17v-3" />',
			'chart-pie' => '<path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 0 0-8.953-8.951c-.55-.055-.998.398-.998.95v8a1 1 0 0 0 1 1z" /><path d="M21.21 15.89A10 10 0 1 1 8 2.83" />',
			'check' => '<path d="M20 6 9 17l-5-5" />',
			'chevron-down' => '<path d="m6 9 6 6 6-6" />',
			'chevron-left' => '<path d="m15 18-6-6 6-6" />',
			'chevron-right' => '<path d="m9 18 6-6-6-6" />',
			'clipboard-document' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1" /><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" /><path d="M12 11h4" /><path d="M12 16h4" /><path d="M8 11h.01" /><path d="M8 16h.01" />',
			'clock' => '<circle cx="12" cy="12" r="10" /><path d="M12 6v6h4" />',
			'cube' => '<path d="M12 3v6" /><path d="M16.76 3a2 2 0 0 1 1.8 1.1l2.23 4.479a2 2 0 0 1 .21.891V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9.472a2 2 0 0 1 .211-.894L5.45 4.1A2 2 0 0 1 7.24 3z" /><path d="M3.054 9.013h17.893" />',
			'delete-box' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" /><path d="M14 2v5a1 1 0 0 0 1 1h5" /><path d="m14.5 12.5-5 5" /><path d="m9.5 12.5 5 5" />',
			'document-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z" /><path d="M14 2v5a1 1 0 0 0 1 1h5" /><path d="M10 9H8" /><path d="M16 13H8" /><path d="M16 17H8" />',
			'ellipsis-vertical' => '<circle cx="12" cy="12" r="1" /><circle cx="12" cy="5" r="1" /><circle cx="12" cy="19" r="1" />',
			'external-link' => '<path d="M15 3h6v6" /><path d="M10 14 21 3" /><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />',
			'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" /><circle cx="12" cy="12" r="3" />',
			'home' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8" /><path d="M3 10a2 2 0 0 1 .709-1.528l7-6a2 2 0 0 1 2.582 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />',
			'logout' => '<path d="m16 17 5-5-5-5" /><path d="M21 12H9" /><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />',
			'magnifying-glass' => '<path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />',
			'magnifying-glass-alt' => '<path d="m21 21-4.34-4.34" /><circle cx="11" cy="11" r="8" />',
			'minus' => '<path d="M5 12h14" />',
			'pencil-square' => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" /><path d="m15 5 4 4" />',
			'phone' => '<path d="M13.832 16.568a1 1 0 0 0 1.213-.303l.355-.465A2 2 0 0 1 17 15h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2A18 18 0 0 1 2 4a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-.8 1.6l-.468.351a1 1 0 0 0-.292 1.233 14 14 0 0 0 6.392 6.384" />',
			'plus' => '<path d="M5 12h14" /><path d="M12 5v14" />',
			'plus-minus' => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><line x1="12" x2="12" y1="8" y2="16" /><line x1="8" x2="16" y1="12" y2="12" />',
			'pos' => '<path d="M3 7V5a2 2 0 0 1 2-2h2" /><path d="M17 3h2a2 2 0 0 1 2 2v2" /><path d="M21 17v2a2 2 0 0 1-2 2h-2" /><path d="M7 21H5a2 2 0 0 1-2-2v-2" /><path d="M7 12h10" />',
			'purchase' => '<path d="M13 16H8" /><path d="M14 8H8" /><path d="M16 12H8" /><path d="M4 3a1 1 0 0 1 1-1 1.3 1.3 0 0 1 .7.2l.933.6a1.3 1.3 0 0 0 1.4 0l.934-.6a1.3 1.3 0 0 1 1.4 0l.933.6a1.3 1.3 0 0 0 1.4 0l.933-.6a1.3 1.3 0 0 1 1.4 0l.934.6a1.3 1.3 0 0 0 1.4 0l.933-.6A1.3 1.3 0 0 1 19 2a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1 1.3 1.3 0 0 1-.7-.2l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.934.6a1.3 1.3 0 0 1-1.4 0l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-1.4 0l-.934-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-.7.2 1 1 0 0 1-1-1z" />',
			'return' => '<path d="M9 14 4 9l5-5" /><path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5 5.5 5.5 0 0 1-5.5 5.5H11" />',
			'sliders-horizontal' => '<path d="M10 5H3" /><path d="M12 19H3" /><path d="M14 3v4" /><path d="M16 17v4" /><path d="M21 12h-9" /><path d="M21 19h-5" /><path d="M21 5h-7" /><path d="M8 10v4" /><path d="M8 12H3" />',
			'supplier' => '<path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5" /><path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244" /><path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05" />',
			'tag' => '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" /><circle cx="7.5" cy="7.5" r=".5" fill="currentColor" />',
			'trash' => '<path d="M10 11v6" /><path d="M14 11v6" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />',
			'truck' => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />',
			'undo-left' => '<path d="M3 7v6h6" /><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" />',
			'unit' => '<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z" /><path d="m14.5 12.5 2-2" /><path d="m11.5 9.5 2-2" /><path d="m8.5 6.5 2-2" /><path d="m17.5 15.5 2-2" />',
			'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" />',
			'user-group' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><path d="M16 3.128a4 4 0 0 1 0 7.744" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><circle cx="9" cy="7" r="4" />',
			'x-mark' => '<path d="M18 6 6 18" /><path d="m6 6 12 12" />',
		];

		if (!isset($icons[$name])) {
			return '';
		}

		$attrParts = [
			'xmlns="http://www.w3.org/2000/svg"',
			'fill="none"',
			'viewBox="0 0 24 24"',
			'stroke-width="2"',
			'stroke="currentColor"',
			'stroke-linecap="round"',
			'stroke-linejoin="round"',
			'class="' . htmlspecialchars((string) $class, ENT_QUOTES, 'UTF-8') . '"',
			'aria-hidden="true"',
		];

		foreach ($attrs as $key => $value) {
			$attrParts[] = htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
		}

		return '<svg ' . implode(' ', $attrParts) . '>' . $icons[$name] . '</svg>';
	}
}

if (!function_exists('ui_button_primary')) {
	function ui_button_primary($label, $attrs = [])
	{
		$baseClass = 'inline-flex min-h-9 items-center justify-center rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white transition hover:bg-brand-700 active:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50';
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
		$baseClass = 'inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50';
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
		$baseClass = 'form-field block min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition';
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
		$baseClass = 'form-field block min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition';
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
			'success' => 'border-brand-200 bg-brand-50/90 text-brand-800',
			'error' => 'border-red-200 bg-red-50 text-red-800',
			'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
			'info' => 'border-slate-200 bg-white text-slate-700',
		];
		$colorClass = isset($map[$type]) ? $map[$type] : $map['info'];
		echo '<div class="' . htmlspecialchars($baseClass . ' ' . $colorClass, ENT_QUOTES, 'UTF-8') . '">';
		echo '<div class="text-sm">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
		echo '</div>';
	}
}

