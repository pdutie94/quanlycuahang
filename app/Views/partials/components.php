<?php

if (!function_exists('ui_button_primary')) {
	function ui_button_primary($label, $attrs = [])
	{
		$baseClass = 'rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-50 disabled:cursor-not-allowed';
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
		$baseClass = 'inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed';
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
		$baseClass = 'form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white';
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
		$baseClass = 'form-field block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-emerald-500 focus:bg-white';
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
			'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
			'error' => 'border-red-200 bg-red-50 text-red-800',
			'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
			'info' => 'border-sky-200 bg-sky-50 text-sky-800',
		];
		$colorClass = isset($map[$type]) ? $map[$type] : $map['info'];
		echo '<div class="' . htmlspecialchars($baseClass . ' ' . $colorClass, ENT_QUOTES, 'UTF-8') . '">';
		echo '<div class="text-sm">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
		echo '</div>';
	}
}

