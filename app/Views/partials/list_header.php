<?php
$cfg = isset($listHeader) && is_array($listHeader) ? $listHeader : [];
$headerTitle = isset($cfg['title']) ? (string) $cfg['title'] : (isset($title) ? (string) $title : '');
$headerSubtitle = isset($cfg['subtitle']) ? (string) $cfg['subtitle'] : '';
$primary = isset($cfg['primary']) && is_array($cfg['primary']) ? $cfg['primary'] : [];
$sticky = !empty($cfg['sticky']);
$formCfg = isset($cfg['form']) && is_array($cfg['form']) ? $cfg['form'] : [];
$searchCfg = isset($cfg['search']) && is_array($cfg['search']) ? $cfg['search'] : [];
$hiddenFields = isset($cfg['hidden']) && is_array($cfg['hidden']) ? $cfg['hidden'] : [];
$extraButtons = isset($cfg['extra_buttons']) && is_array($cfg['extra_buttons']) ? $cfg['extra_buttons'] : [];
$chipsCfg = isset($cfg['chips']) && is_array($cfg['chips']) ? $cfg['chips'] : [];
?>
<?php if ($headerTitle !== '' || !empty($searchCfg)) { ?>
	<div class="mx-auto w-full max-w-5xl px-4 py-4">
		<div class="flex items-center justify-between gap-3">
			<div>
				<h1 class="font-display text-xl font-bold tracking-tight text-slate-900">
					<?php echo htmlspecialchars($headerTitle !== '' ? $headerTitle : (isset($title) ? (string) $title : ''), ENT_QUOTES, 'UTF-8'); ?>
				</h1>
				<?php if ($headerSubtitle !== '') { ?>
					<p class="mt-1 text-sm text-slate-500">
						<?php echo htmlspecialchars($headerSubtitle, ENT_QUOTES, 'UTF-8'); ?>
					</p>
				<?php } ?>
			</div>
			<?php if (!empty($primary) && isset($primary['url'])) { ?>
				<div class="flex items-center gap-2">
					<a href="<?php echo $basePath . '/' . ltrim((string) $primary['url'], '/'); ?>" class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-600 text-white transition hover:bg-brand-700" title="<?php echo htmlspecialchars(isset($primary['tooltip']) ? (string) $primary['tooltip'] : '', ENT_QUOTES, 'UTF-8'); ?>">
						<?php echo ui_icon("plus", "h-5 w-5"); ?>
					</a>
				</div>
			<?php } ?>
		</div>
	</div>
	<?php if (!empty($searchCfg)) { ?>
		<?php
		$formMethod = isset($formCfg['method']) ? (string) $formCfg['method'] : 'get';
		$formAction = isset($formCfg['action']) ? (string) $formCfg['action'] : '';
		$formAttrs = isset($formCfg['attrs']) && is_array($formCfg['attrs']) ? $formCfg['attrs'] : [];
		if ($sticky) {
			$formAttrs['data-list-sticky'] = '1';
		}
			$formClass = $sticky ? 'space-y-0 sticky z-20 top-0 border-b border-transparent' : 'space-y-3';
		$searchParam = isset($searchCfg['param']) ? (string) $searchCfg['param'] : 'q';
		$searchPlaceholder = isset($searchCfg['placeholder']) ? (string) $searchCfg['placeholder'] : '';
		$searchValue = isset($searchCfg['value']) ? (string) $searchCfg['value'] : '';
		$searchClearUrl = isset($searchCfg['clear_url']) ? (string) $searchCfg['clear_url'] : '';
		$searchShowClear = !empty($searchCfg['show_clear']);
		?>
		<form method="<?php echo htmlspecialchars($formMethod, ENT_QUOTES, 'UTF-8'); ?>" action="<?php echo $formAction !== '' ? htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') : ''; ?>" class="<?php echo $formClass; ?>"<?php foreach ($formAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
			<div class="mx-auto w-full max-w-5xl px-4<?php echo $sticky ? ' py-2 space-y-2' : ' space-y-2'; ?>">
				<div class="flex items-center gap-2">
					<div class="relative flex-1">
						<span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
							<?php echo ui_icon("magnifying-glass", "size-4"); ?>
						</span>
						<input type="text" name="<?php echo htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>" class="h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0" placeholder="<?php echo htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8'); ?>" />
						<?php if ($searchClearUrl !== '') { ?>
							<a href="<?php echo $basePath . '/' . ltrim($searchClearUrl, '/'); ?>" class="absolute inset-y-0 right-2 flex items-center rounded-full px-2 text-sm text-slate-400 hover:text-slate-600<?php echo $searchShowClear ? '' : ' hidden'; ?>" data-list-search-clear="1">Xóa</a>
						<?php } ?>
					</div>
					<?php if (!empty($extraButtons)) { ?>
						<?php foreach ($extraButtons as $btn) { ?>
							<?php
							$btnAttrs = isset($btn['attrs']) && is_array($btn['attrs']) ? $btn['attrs'] : [];
							$iconType = isset($btn['icon']) ? (string) $btn['icon'] : 'filter';
							?>
							<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"<?php foreach ($btnAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
								<?php if ($iconType === 'grid') { ?>
									<?php echo ui_icon("categories", "h-5 w-5"); ?>
								<?php } else { ?>
									<?php echo ui_icon("sliders-horizontal", "h-4 w-4"); ?>
								<?php } ?>
							</button>
						<?php } ?>
					<?php } ?>
				</div>
				<?php if (!empty($hiddenFields)) { ?>
					<?php foreach ($hiddenFields as $field) { ?>
						<?php
						$name = isset($field['name']) ? (string) $field['name'] : '';
						if ($name === '') {
							continue;
						}
						$value = isset($field['value']) ? (string) $field['value'] : '';
						?>
						<input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
					<?php } ?>
				<?php } ?>
				<?php if (!empty($chipsCfg) && isset($chipsCfg['items']) && is_array($chipsCfg['items']) && !empty($chipsCfg['items'])) { ?>
					<?php
					$chipsClass = isset($chipsCfg['class']) ? (string) $chipsCfg['class'] : 'flex items-center gap-2 overflow-x-auto pb-0.5 text-sm';
					?>
					<div class="<?php echo htmlspecialchars($chipsClass, ENT_QUOTES, 'UTF-8'); ?>">
						<?php foreach ($chipsCfg['items'] as $chip) { ?>
							<?php
							$kind = isset($chip['kind']) ? (string) $chip['kind'] : 'submit';
							$label = isset($chip['label']) ? (string) $chip['label'] : '';
							$icon = isset($chip['icon']) ? (string) $chip['icon'] : '';
							$iconOnly = !empty($chip['icon_only']);
							$ariaLabel = isset($chip['aria_label']) ? (string) $chip['aria_label'] : $label;
							if ($label === '' && $icon === '') {
								continue;
							}
							$active = !empty($chip['active']);
							$baseClass = isset($chip['base_class']) ? (string) $chip['base_class'] : 'border inline-flex min-h-10 items-center rounded-chip px-3.5 py-2 text-sm font-medium';
							$activeClass = isset($chip['active_class']) ? (string) $chip['active_class'] : 'border-brand-600 bg-brand-600 text-white';
							$inactiveClass = isset($chip['inactive_class']) ? (string) $chip['inactive_class'] : 'bg-white text-slate-700 border-slate-300';
							$chipAttrs = isset($chip['attrs']) && is_array($chip['attrs']) ? $chip['attrs'] : [];
							$classes = $baseClass . ' shrink-0 ' . ($active ? $activeClass : $inactiveClass);
							?>
							<?php if ($kind === 'button') { ?>
								<?php
								$dataAttr = isset($chip['data_attr']) ? (string) $chip['data_attr'] : '';
								$dataValue = isset($chip['value']) ? (string) $chip['value'] : '';
								?>
								<button type="button" class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($ariaLabel !== '') { echo ' aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '"'; } ?><?php if ($dataAttr !== '') { echo ' ' . htmlspecialchars($dataAttr, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($dataValue, ENT_QUOTES, 'UTF-8') . '"'; } ?><?php foreach ($chipAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
									<?php if ($icon === 'clear') { ?>
										<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
									<?php } ?>
									<?php if (!$iconOnly && $label !== '') { ?>
										<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
									<?php } elseif ($iconOnly && $label !== '') { ?>
										<span class="sr-only"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
									<?php } ?>
								</button>
							<?php } else { ?>
								<?php
								$name = isset($chip['name']) ? (string) $chip['name'] : '';
								$value = isset($chip['value']) ? (string) $chip['value'] : '';
								?>
								<button type="submit"<?php if ($name !== '') { echo ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'; } ?> value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($ariaLabel !== '') { echo ' aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '"'; } ?><?php foreach ($chipAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
									<?php if ($icon === 'clear') { ?>
										<?php echo ui_icon("x-mark", "h-4 w-4"); ?>
									<?php } ?>
									<?php if (!$iconOnly && $label !== '') { ?>
										<span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
									<?php } elseif ($iconOnly && $label !== '') { ?>
										<span class="sr-only"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
									<?php } ?>
								</button>
							<?php } ?>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</form>
	<?php } ?>
<?php } ?>
