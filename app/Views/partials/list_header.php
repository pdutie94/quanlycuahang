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
	<div class="mx-auto w-full max-w-4xl px-3 py-3">
		<div class="flex items-center justify-between gap-3">
			<div>
				<h1 class="text-lg font-medium tracking-tight">
					<?php echo htmlspecialchars($headerTitle !== '' ? $headerTitle : (isset($title) ? (string) $title : ''), ENT_QUOTES, 'UTF-8'); ?>
				</h1>
				<?php if ($headerSubtitle !== '') { ?>
					<p class="mt-1 text-sm text-slate-600">
						<?php echo htmlspecialchars($headerSubtitle, ENT_QUOTES, 'UTF-8'); ?>
					</p>
				<?php } ?>
			</div>
			<?php if (!empty($primary) && isset($primary['url'])) { ?>
				<div class="flex items-center gap-1.5">
					<a href="<?php echo $basePath . '/' . ltrim((string) $primary['url'], '/'); ?>" class="inline-flex items-center justify-center rounded-full bg-emerald-600 p-2 text-white shadow-sm hover:bg-emerald-700 active:bg-emerald-800" title="<?php echo htmlspecialchars(isset($primary['tooltip']) ? (string) $primary['tooltip'] : '', ENT_QUOTES, 'UTF-8'); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
						</svg>
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
			<div class="mx-auto w-full max-w-4xl px-3<?php echo $sticky ? ' py-1.5 space-y-1.5' : ''; ?>">
				<div class="flex items-center gap-1.5">
					<div class="relative flex-1">
						<span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
							<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
								<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
							</svg>
						</span>
						<input type="text" name="<?php echo htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>" class="h-10 w-full rounded-full border border-slate-200 bg-slate-50 pl-9 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-0" placeholder="<?php echo htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8'); ?>" />
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
							<button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"<?php foreach ($btnAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
								<?php if ($iconType === 'grid') { ?>
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
										<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
									</svg>
								<?php } else { ?>
									<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
										<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 0 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
									</svg>
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
					$chipsClass = isset($chipsCfg['class']) ? (string) $chipsCfg['class'] : 'flex items-center gap-1.5 overflow-x-auto text-sm';
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
							$baseClass = isset($chip['base_class']) ? (string) $chip['base_class'] : 'border inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium';
							$activeClass = isset($chip['active_class']) ? (string) $chip['active_class'] : 'border-emerald-600 bg-emerald-600 text-white';
							$inactiveClass = isset($chip['inactive_class']) ? (string) $chip['inactive_class'] : 'bg-white text-slate-700 border-slate-200';
							$chipAttrs = isset($chip['attrs']) && is_array($chip['attrs']) ? $chip['attrs'] : [];
							$classes = $baseClass . ' ' . ($active ? $activeClass : $inactiveClass);
							?>
							<?php if ($kind === 'button') { ?>
								<?php
								$dataAttr = isset($chip['data_attr']) ? (string) $chip['data_attr'] : '';
								$dataValue = isset($chip['value']) ? (string) $chip['value'] : '';
								?>
								<button type="button" class="<?php echo htmlspecialchars($classes, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($ariaLabel !== '') { echo ' aria-label="' . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '"'; } ?><?php if ($dataAttr !== '') { echo ' ' . htmlspecialchars($dataAttr, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($dataValue, ENT_QUOTES, 'UTF-8') . '"'; } ?><?php foreach ($chipAttrs as $attrKey => $attrValue) { echo ' ' . htmlspecialchars((string) $attrKey, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"'; } ?>>
									<?php if ($icon === 'clear') { ?>
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
											<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
										</svg>
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
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
											<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
										</svg>
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
