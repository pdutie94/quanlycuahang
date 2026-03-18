<div class="mb-4 space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg font-medium tracking-tight">Danh mục sản phẩm</h1>
        <button type="button" data-category-toggle class="inline-flex items-center justify-center rounded-full bg-emerald-600 p-2 text-white  hover:bg-emerald-700 active:bg-emerald-800">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
				</svg>
        </button>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3  hidden" data-category-form>
        <form method="post" action="<?php echo $basePath; ?>/category/store" class="flex items-center gap-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="flex-1">
                <?php
                $categoryNameValue = '';
                if (isset($_POST['name']) && is_string($_POST['name'])) {
                    $categoryNameValue = $_POST['name'];
                }
                ui_input_text('name', $categoryNameValue, [
                    'placeholder' => 'Tên danh mục',
                    'required' => 'required',
                ]);
                ?>
            </div>
            <?php ui_button_primary('Lưu', ['type' => 'submit', 'data-loading-button' => '1']); ?>
        </form>
    </div>
</div>



<?php if (empty($categories)) { ?>
    <div class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">
        Chưa có danh mục nào.
    </div>
<?php } else { ?>
	<div class="space-y-3" data-infinite-list data-infinite-url="<?php echo $basePath; ?>/category" data-current-page="<?php echo isset($page) ? (int) $page : 1; ?>" data-has-more="<?php echo isset($totalPages) && isset($page) && $page < $totalPages ? '1' : '0'; ?>">
		<?php foreach ($categories as $category) { ?>
			<div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 " data-infinite-item>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-slate-900 truncate"><?php echo htmlspecialchars($category['name']); ?></div>
                </div>
                        <?php if ((int) $category['id'] !== 1) { ?>
                            <a href="<?php echo $basePath; ?>/category/delete?id=<?php echo $category['id']; ?>" onclick="return confirm('Xóa danh mục này?');" class="inline-flex items-center justify-center text-red-600 " title="Xóa">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
</svg>
                            </a>
                        <?php } ?>
            </div>
        <?php } ?>
    </div>
<?php } ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.querySelector('[data-category-toggle]');
    var form = document.querySelector('[data-category-form]');
    if (!btn || !form) return;
    btn.addEventListener('click', function () {
        if (form.classList.contains('hidden')) {
            form.classList.remove('hidden');
        } else {
            form.classList.add('hidden');
        }
    });
});
</script>
