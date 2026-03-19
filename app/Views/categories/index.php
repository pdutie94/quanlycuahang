<div class="mb-4 space-y-3">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-lg font-medium tracking-tight">Danh mục sản phẩm</h1>
        <button type="button" data-category-toggle class="inline-flex items-center justify-center rounded-full bg-brand-600 p-2 text-white  hover:bg-brand-700 active:bg-brand-800">
            <?php echo ui_icon("plus", "h-5 w-5"); ?>
        </button>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3  hidden" data-category-form>
        <form method="post" action="<?php echo $basePath; ?>/category/store" class="flex items-end gap-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="relative flex-1">
                <label class="absolute left-3 top-0 z-10 -translate-y-1/2 bg-white px-1 leading-none text-sm text-slate-700">Tên danh mục</label>
                <?php
                $categoryNameValue = '';
                if (isset($_POST['name']) && is_string($_POST['name'])) {
                    $categoryNameValue = $_POST['name'];
                }
                ui_input_text('name', $categoryNameValue, [
                    'placeholder' => 'Nhập tên danh mục',
                    'required' => 'required',
                    'class' => 'pt-3 pb-2.5',
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
                                <?php echo ui_icon("trash", "size-4"); ?>
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
