<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useProductForm } from '../composables/useProductForm';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const {
  product,
  productLogs,
  form,
  categories,
  units,
  baseUnitName,
  loadBootstrap,
  loadEdit,
  submitCreate,
  submitUpdate,
  bootstrapLoading,
  bootstrapError,
  editLoading,
  editError,
  createLoading,
  createError,
  updateLoading,
  updateError
} = useProductForm();

const isEdit = computed(() => Boolean(route.params.id));
const pageTitle = computed(() => (isEdit.value ? 'Sửa sản phẩm' : 'Thêm sản phẩm'));
const loading = computed(() => bootstrapLoading.value || (isEdit.value && editLoading.value));
const saving = computed(() => createLoading.value || updateLoading.value);

const submit = async (redirectMode) => {
  form.value.redirect = redirectMode;

  try {
    const payload = isEdit.value
      ? await submitUpdate(Number(route.params.id))
      : await submitCreate();

    toast.success(payload?.message || (isEdit.value ? 'Đã cập nhật sản phẩm.' : 'Đã thêm sản phẩm.'));

    if (redirectMode === 'exit') {
      await router.push('/products');
      return;
    }

    const nextId = Number(payload?.data?.id || route.params.id || 0);
    if (!isEdit.value && nextId > 0) {
      await router.push({ name: 'products.edit', params: { id: nextId } });
    }
  } catch (_err) {
    toast.error((isEdit.value ? updateError.value : createError.value) || 'Không thể lưu sản phẩm.');
  }
};

onMounted(async () => {
  try {
    await loadBootstrap();
    if (isEdit.value) {
      await loadEdit(Number(route.params.id));
    }
  } catch (_err) {
    toast.error(bootstrapError.value || editError.value || 'Không thể tải form sản phẩm.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div>
        <RouterLink :to="isEdit ? '/products' : '/products'" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại danh sách</RouterLink>
        <h1 class="mt-1 text-lg font-semibold text-slate-900">{{ pageTitle }}</h1>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải dữ liệu form...</div>

    <template v-else>
      <section class="space-y-4 app-card">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Tên sản phẩm</label>
            <input v-model="form.name" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" required />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Mã sản phẩm</label>
            <input v-model="form.code" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" placeholder="Để trống để tự sinh" />
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Đơn vị tồn kho</label>
            <div class="relative">
              <select v-model="form.base_unit_id" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                <option value="">Chọn đơn vị</option>
                <option v-for="unit in units" :key="unit.id" :value="String(unit.id)">{{ unit.name }}</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
              </div>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Danh mục</label>
            <div class="relative">
              <select v-model="form.category_id" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                <option value="">Chưa phân loại</option>
                <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
              </div>
            </div>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Giá bán</label>
            <div class="relative">
              <input v-model="form.price_sell_single" type="text" inputmode="numeric" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Giá nhập</label>
            <div class="relative">
              <input v-model="form.price_cost_single" type="text" inputmode="numeric" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
            </div>
          </div>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.allow_fraction" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600" />
          <span>Cho phép bán lẻ (số lượng thập phân)</span>
        </label>

        <div v-if="form.allow_fraction" class="max-w-xs">
          <label class="mb-1 block text-sm font-medium text-slate-700">Bước lẻ nhỏ nhất</label>
          <input v-model="form.min_step" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" placeholder="Ví dụ: 0.1" />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Số lượng hiện tại</label>
            <div class="relative">
              <input v-model="form.inventory_qty_base" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-16 text-sm outline-none focus:border-brand-500" />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">{{ baseUnitName }}</span>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Ngưỡng tồn thấp</label>
            <div class="relative">
              <input v-model="form.min_stock_qty" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-16 text-sm outline-none focus:border-brand-500" />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">{{ baseUnitName }}</span>
            </div>
          </div>
        </div>

        <div v-if="isEdit && productLogs.length" class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
          <h2 class="font-medium text-slate-800">Lịch sử giá và tồn kho</h2>
          <ul class="mt-2 space-y-1 text-slate-600">
            <li v-for="log in productLogs" :key="log.id">{{ log.created_at }} - {{ log.detail }}</li>
          </ul>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="saving" @click="submit('stay')">Lưu</button>
          <button type="button" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700 disabled:opacity-50" :disabled="saving" @click="submit('exit')">Lưu và thoát</button>
        </div>
      </section>

      <section v-if="isEdit && product" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
        Sản phẩm #{{ product.id }}
      </section>
    </template>
  </section>
</template>
