<script setup>
import { ClipboardList, History, Package, Tags, Trash2 } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useProductForm } from '../composables/useProductForm';
import { useToast } from '../../../shared/composables/useToast';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

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
  updateError,
  remove,
  deleteLoading,
  deleteError
} = useProductForm();

const isEdit = computed(() => Boolean(route.params.id));
const pageTitle = computed(() => (isEdit.value ? 'Sửa sản phẩm' : 'Thêm sản phẩm'));
const loading = computed(() => bootstrapLoading.value || (isEdit.value && editLoading.value));
const saving = computed(() => createLoading.value || updateLoading.value);
const showDeleteModal = ref(false);

const historyDateFormatter = new Intl.DateTimeFormat('vi-VN', {
  hour: '2-digit',
  minute: '2-digit',
  day: '2-digit',
  month: '2-digit',
  year: 'numeric'
});

const formatHistoryDate = (value) => {
  if (!value) {
    return '';
  }

  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return historyDateFormatter.format(date).replace(/^([^,]+),\s*/, '$1, ');
};

const getHistoryTone = (log) => {
  const detail = String(log?.detail || '').toLowerCase();
  const action = String(log?.action || '').toLowerCase();

  if (/(giảm|bớt|hạ|xuống|xóa|huy)/.test(detail)) {
    return {
      dot: 'bg-rose-400',
      meta: 'text-rose-700',
      detail: 'text-slate-700',
      row: 'bg-rose-50/55'
    };
  }

  if (/(tăng|thêm|lên|thiết lập|khởi tạo)/.test(detail) || /(init_|adjust_inventory)/.test(action)) {
    return {
      dot: 'bg-emerald-400',
      meta: 'text-emerald-700',
      detail: 'text-slate-700',
      row: 'bg-emerald-50/55'
    };
  }

  return {
    dot: 'bg-violet-400',
    meta: 'text-violet-700',
    detail: 'text-slate-700',
    row: 'bg-slate-50'
  };
};

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

const deleteCurrentProduct = async () => {
  const productId = Number(route.params.id || 0);
  if (productId <= 0) {
    return;
  }

  try {
    const payload = await remove(productId);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa sản phẩm.');
    await router.push('/products');
  } catch (_err) {
    toast.error(deleteError.value || 'Không thể xóa sản phẩm vì đã có đơn hàng sử dụng.');
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
    <DetailHeaderBar :title="pageTitle" back-to="/products">
      <template #actions="{ closeMenu }">
        <button v-if="isEdit && product" type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa sản phẩm</span></button>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa sản phẩm"
      description="Bạn chắc chắn muốn xóa sản phẩm này?"
      confirm-label="Xóa sản phẩm"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentProduct"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải dữ liệu form...</div>

    <template v-else>
      <form class="space-y-3" @submit.prevent="submit('stay')">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 space-y-2">
          <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-700">
              <ClipboardList class="h-3.5 w-3.5" />
            </span>
            <span>Thông tin sản phẩm</span>
          </h2>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-slate-700">Tên sản phẩm</label>
            <input v-model="form.name" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" required />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-slate-700">Mã sản phẩm <span class="font-normal text-slate-400">(tùy chọn)</span></label>
            <input v-model="form.code" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" placeholder="Để trống để tự sinh" />
          </div>

          <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Đơn vị tồn kho</label>
              <div class="relative grid">
                <select v-model="form.base_unit_id" class="col-start-1 row-start-1 h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                  <option value="">Chọn đơn vị</option>
                  <option v-for="unit in units" :key="unit.id" :value="String(unit.id)">{{ unit.name }}</option>
                </select>
                <span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
              </div>
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Danh mục</label>
              <div class="relative grid">
                <select v-model="form.category_id" class="col-start-1 row-start-1 h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                  <option value="">Chưa phân loại</option>
                  <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                </select>
                <span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </span>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 space-y-2">
          <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
              <Tags class="h-3.5 w-3.5" />
            </span>
            <span>Giá sản phẩm</span>
          </h2>

          <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Giá bán</label>
              <div class="relative">
                <input v-model="form.price_sell_single" type="text" inputmode="numeric" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
              </div>
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Giá nhập</label>
              <div class="relative">
                <input v-model="form.price_cost_single" type="text" inputmode="numeric" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">đ</span>
              </div>
            </div>
          </div>

          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.allow_fraction" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600" />
            <span>Cho phép bán lẻ (số lượng thập phân)</span>
          </label>

          <div v-if="form.allow_fraction" class="flex flex-col gap-1 max-w-xs">
            <label class="text-sm font-medium text-slate-700">Bước lẻ nhỏ nhất</label>
            <input v-model="form.min_step" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" placeholder="Ví dụ: 0.1" />
          </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 space-y-2">
          <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
              <Package class="h-3.5 w-3.5" />
            </span>
            <span>Tồn kho</span>
          </h2>

          <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Số lượng hiện tại</label>
              <div class="relative">
                <input v-model="form.inventory_qty_base" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-16 text-sm outline-none focus:border-brand-500" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex max-w-[50%] items-center truncate text-sm text-slate-400">{{ baseUnitName }}</span>
              </div>
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-sm font-medium text-slate-700">Ngưỡng tồn thấp</label>
              <div class="relative">
                <input v-model="form.min_stock_qty" type="text" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-16 text-sm outline-none focus:border-brand-500" />
                <span class="pointer-events-none absolute inset-y-0 right-3 flex max-w-[50%] items-center truncate text-sm text-slate-400">{{ baseUnitName }}</span>
              </div>
              <p class="text-xs text-slate-400">Để trống nếu không dùng cảnh báo.</p>
            </div>
          </div>
        </section>

        <section v-if="isEdit && productLogs.length" class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
          <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-violet-700">
              <History class="h-3.5 w-3.5" />
            </span>
            <span>Lịch sử giá &amp; tồn</span>
          </h2>
          <p class="text-sm text-slate-500">Nhật ký các lần thay đổi giá bán, giá nhập và tồn kho.</p>
          <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/70">
            <div
              v-for="log in productLogs"
              :key="log.id"
              class="flex gap-3 border-b border-slate-200 px-3 py-2.5 text-sm last:border-b-0"
              :class="getHistoryTone(log).row"
            >
              <div class="pt-1">
                <span class="block h-2 w-2 rounded-full" :class="getHistoryTone(log).dot"></span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-xs font-medium tracking-wide" :class="getHistoryTone(log).meta">{{ formatHistoryDate(log.created_at) }}</div>
                <div class="mt-0.5 leading-5" :class="getHistoryTone(log).detail">{{ log.detail }}</div>
              </div>
            </div>
          </div>
        </section>

        <div class="mt-3 flex items-center justify-end" data-floating-actions>
          <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="saving">Lưu</button>
            <button type="button" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700 disabled:opacity-50" :disabled="saving" @click="submit('exit')">Lưu & thoát</button>
          </div>
        </div>
      </form>
    </template>
  </section>
</template>
