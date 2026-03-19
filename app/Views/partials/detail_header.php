<?php
$detailTitle = isset($detailHeader['title']) ? (string) $detailHeader['title'] : '';
$detailBackUrl = isset($detailHeader['back_url']) ? (string) $detailHeader['back_url'] : '';
$detailBackLabel = isset($detailHeader['back_label']) ? (string) $detailHeader['back_label'] : '';
$detailActionsView = isset($detailHeader['actions_view']) ? (string) $detailHeader['actions_view'] : '';
?>
<?php if ($detailTitle !== '' || $detailBackUrl !== '') { ?>
	<div class="sticky top-0 z-20 border-b border-transparent bg-slate-50 transition-colors" data-list-sticky="1">
		<div class="mx-auto w-full max-w-6xl px-4 py-2 md:px-6">
			<div class="flex items-center justify-between gap-3">
				<div class="min-w-0">
					<h1 class="truncate font-display text-lg font-semibold text-slate-900 md:text-xl">
						<?php echo htmlspecialchars($detailTitle !== '' ? $detailTitle : $title, ENT_QUOTES, 'UTF-8'); ?>
					</h1>
				</div>
				<?php if ($detailBackUrl !== '' || $detailActionsView !== '') { ?>
					<div class="flex flex-wrap items-center gap-1.5" data-header-actions-root>
						<?php if ($detailBackUrl !== '') { ?>
							<a href="<?php echo $basePath . '/' . ltrim($detailBackUrl, '/'); ?>" class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
								<?php echo ui_icon('chevron-left', 'h-4 w-4'); ?>
								<span><?php echo htmlspecialchars($detailBackLabel !== '' ? $detailBackLabel : 'Quay lại', ENT_QUOTES, 'UTF-8'); ?></span>
							</a>
						<?php } ?>
						<?php if ($detailActionsView !== '') { ?>
							<?php require __DIR__ . '/../' . $detailActionsView . '.php'; ?>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
<?php } ?>

