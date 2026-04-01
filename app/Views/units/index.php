<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-lg font-medium tracking-tight flex items-center gap-2">
        <span class="hidden">
        <?php echo ui_icon("unit", "h-6 w-6"); ?>
    </span>
    Đơn vị tính</h1>
</div>

<div class="mb-4 rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5  border border-slate-200">
    <form method="post" action="<?php echo $basePath; ?>/unit/store" class="flex flex-col gap-4 sm:flex-row sm:items-end">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="relative flex-1">
            <label class="app-label">Thêm đơn vị mới</label>
            <div class="grid grid-cols-1">
                <?php
                $unitNameValue = isset($_POST['name']) && is_string($_POST['name']) ? $_POST['name'] : '';
                ui_input_text('name', $unitNameValue, [
                    'required' => 'required',
                    'placeholder' => 'Tên (ví dụ: Cái, Kg, Mét)',
                    'class' => '',
                ]);
                ?>
            </div>
        </div>
        <div>
            <?php ui_button_primary('Lưu', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </div>
    </form>
</div>

<div class="space-y-3">
	<?php foreach ($units as $index => $unit) { ?>
		<?php $formId = 'unit-form-' . $unit['id']; ?>
		<div class="app-list-card flex items-center gap-3 px-3 py-2.5" data-infinite-item>
			<div class="w-8 text-center text-sm text-slate-500"><?php echo $index + 1; ?></div>
			<div class="flex-1 min-w-0">
				<form id="<?php echo $formId; ?>" method="post" action="<?php echo $basePath; ?>/unit/update" class="flex items-center gap-2">
					<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="id" value="<?php echo $unit['id']; ?>">
					<input type="text" name="name" value="<?php echo htmlspecialchars($unit['name']); ?>" class="form-field block flex-1" placeholder="Tên đơn vị" />
				</form>
			</div>
			<div class="inline-flex items-center justify-end gap-1">
				<button type="submit" form="<?php echo $formId; ?>" class="inline-flex h-8 w-8 items-center justify-center text-brand-600 hover:text-brand-700" title="Lưu">
					<?php echo ui_icon("check", "size-4"); ?>
				</button>
				<a href="<?php echo $basePath; ?>/unit/delete?id=<?php echo $unit['id']; ?>" onclick="return confirm('Xóa đơn vị này?');" class="inline-flex h-8 w-8 items-center justify-center text-rose-600 hover:text-rose-700" title="Xóa">
					<?php echo ui_icon("x-mark", "size-4"); ?>
				</a>
			</div>
		</div>
	<?php } ?>
</div>
