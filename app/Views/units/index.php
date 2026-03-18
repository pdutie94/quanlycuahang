<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-lg font-medium tracking-tight flex items-center gap-2">
        <span class="hidden">
        <?php echo ui_icon("unit", "h-6 w-6"); ?>
    </span>
    Đơn vị tính</h1>
</div>

<div class="mb-4 rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5  border border-slate-200">
    <form method="post" action="<?php echo $basePath; ?>/unit/store" class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-slate-700">Thêm đơn vị mới</label>
            <div class="grid grid-cols-1">
                <?php
                $unitNameValue = isset($_POST['name']) && is_string($_POST['name']) ? $_POST['name'] : '';
                ui_input_text('name', $unitNameValue, [
                    'required' => 'required',
                    'placeholder' => 'Tên (ví dụ: Cái, Kg, Mét)',
                ]);
                ?>
            </div>
        </div>
        <div class="pt-2 sm:pt-6">
            <?php ui_button_primary('Lưu', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </div>
    </form>
</div>

<div class="overflow-x-auto rounded-lg border border-slate-200 bg-white ">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-sm uppercase  text-slate-500">
        <tr>
            <th class="px-4 py-2 font-medium">#</th>
            <th class="px-4 py-2 font-medium">Đơn vị tính</th>
            <th class="px-2 py-2 text-right font-medium w-px whitespace-nowrap"></th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
		<?php foreach ($units as $index => $unit) { ?>
            <?php $formId = 'unit-form-' . $unit['id']; ?>
			<tr class="hover:bg-slate-50/70">
				<td class="px-4 py-2 text-sm text-slate-500 w-12"><?php echo $index + 1; ?></td>
                <td class="px-4 py-2 text-sm text-slate-800">
                    <input type="hidden" name="id" value="<?php echo $unit['id']; ?>" form="<?php echo $formId; ?>">
                    <div class="flex items-center gap-2">
                        <input type="text" name="name" value="<?php echo htmlspecialchars($unit['name']); ?>" form="<?php echo $formId; ?>" class="form-field block flex-1 rounded-md border border-slate-300 bg-slate-50 px-2.5 text-sm outline-none focus:border-brand-500 focus:bg-white" placeholder="Tên đơn vị" />
                    </div>
                </td>
				<td class="px-2 py-2 text-right text-sm align-middle w-px whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-2">
                        <form id="<?php echo $formId; ?>" method="post" action="<?php echo $basePath; ?>/unit/update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100" title="Lưu">
                                                                <?php echo ui_icon("check", "size-4"); ?>

                            </button>
                        </form>
                        <a href="<?php echo $basePath; ?>/unit/delete?id=<?php echo $unit['id']; ?>" onclick="return confirm('Xóa đơn vị này?');" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 hover:bg-red-100" title="Xóa">
                            <?php echo ui_icon("x-mark", "size-4"); ?>

                        </a>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
