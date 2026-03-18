<div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-lg font-medium tracking-tight flex items-center gap-2">
        <span class="hidden">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.098 19.902a3.75 3.75 0 0 0 5.304 0l6.401-6.402M6.75 21A3.75 3.75 0 0 1 3 17.25V4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v4.072M6.75 21a3.75 3.75 0 0 0 3.75-3.75V8.197M6.75 21h13.125c.621 0 1.125-.504 1.125-1.125v-5.25c0-.621-.504-1.125-1.125-1.125h-4.072M10.5 8.197l2.88-2.88c.438-.439 1.15-.439 1.59 0l3.712 3.713c.44.44.44 1.152 0 1.59l-2.879 2.88M6.75 17.25h.008v.008H6.75v-.008Z" />
        </svg>
    </span>
    Đơn vị tính</h1>
</div>

<div class="mb-4 rounded-lg bg-white px-4 py-4 lg:px-5 lg:py-5  ring-1 ring-slate-100">
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
                        <input type="text" name="name" value="<?php echo htmlspecialchars($unit['name']); ?>" form="<?php echo $formId; ?>" class="form-field block flex-1 rounded-md border border-slate-300 bg-slate-50 px-2.5 text-sm outline-none focus:border-emerald-500 focus:bg-white" placeholder="Tên đơn vị" />
                    </div>
                </td>
				<td class="px-2 py-2 text-right text-sm align-middle w-px whitespace-nowrap">
                    <div class="inline-flex items-center justify-end gap-2">
                        <form id="<?php echo $formId; ?>" method="post" action="<?php echo $basePath; ?>/unit/update">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Lưu">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75 9 17.25 19.5 6.75" />
</svg>

                            </button>
                        </form>
                        <a href="<?php echo $basePath; ?>/unit/delete?id=<?php echo $unit['id']; ?>" onclick="return confirm('Xóa đơn vị này?');" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-red-50 text-red-600 hover:bg-red-100" title="Xóa">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
</svg>

                        </a>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
