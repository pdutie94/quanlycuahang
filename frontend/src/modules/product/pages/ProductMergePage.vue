<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { ArrowRight, CheckCircle2, ChevronLeft, GitMerge, RefreshCw, ShieldAlert } from '@lucide/vue';
import { useToast } from '../../../shared/composables/useToast';
import { fetchProductMergeOptions, fetchProductMergePreview, mergeProducts } from '../services/product.api';
import type { ProductMergeOption, ProductMergePreview } from '../types';

const router = useRouter();
const toast = useToast();
const sourceId = ref('');
const targetId = ref('');
const sourceQuery = ref('');
const targetQuery = ref('');
const sourceOptions = ref<ProductMergeOption[]>([]);
const targetOptions = ref<ProductMergeOption[]>([]);
const preview = ref<ProductMergePreview | null>(null);
const finalInventory = ref<string | number>('');
const deleteSource = ref(false);
const loadingOptions = ref(false);
const loadingPreview = ref(false);
const submitting = ref(false);
const errorMessage = ref('');
let previewRequest = 0;

const readError = (error: any, fallback: string) => error?.response?.data?.message || error?.message || fallback;

const loadOptions = async (target: 'source' | 'target', query = '') => {
  loadingOptions.value = true;
  try {
    const result = await fetchProductMergeOptions({ q: query, page: 1, per_page: 50 });
    const rows = result?.data?.items || [];
    if (target === 'source') sourceOptions.value = rows;
    else targetOptions.value = rows;
  } catch (error: any) {
    errorMessage.value = readError(error, 'Không thể tải danh sách sản phẩm.');
  } finally {
    loadingOptions.value = false;
  }
};

const refreshPreview = async () => {
  preview.value = null;
  errorMessage.value = '';
  const source = Number(sourceId.value);
  const target = Number(targetId.value);
  if (!source || !target || source === target) return;

  const requestId = ++previewRequest;
  loadingPreview.value = true;
  try {
    const result = await fetchProductMergePreview(source, target);
    if (requestId === previewRequest) preview.value = result.data;
  } catch (error: any) {
    if (requestId === previewRequest) errorMessage.value = readError(error, 'Không thể kiểm tra dữ liệu gộp.');
  } finally {
    if (requestId === previewRequest) loadingPreview.value = false;
  }
};

watch([sourceId, targetId], () => {
  finalInventory.value = '';
  deleteSource.value = false;
  void refreshPreview();
});
watch(sourceQuery, (value) => { void loadOptions('source', value); });
watch(targetQuery, (value) => { void loadOptions('target', value); });

const inventoryInput = computed(() => String(finalInventory.value ?? '').trim());
const canSubmit = computed(() => Boolean(
  preview.value?.can_merge && inventoryInput.value !== '' && Number.isFinite(Number(inventoryInput.value)) && Number(inventoryInput.value) >= 0 && !submitting.value
));

const formatQty = (value: number | string | undefined) => {
  const number = Number(value || 0);
  return Number.isInteger(number) ? String(number) : number.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
};

const submit = async () => {
  if (!canSubmit.value || !preview.value) return;
  const confirmed = window.confirm(`Gộp “${preview.value.source.name}” vào “${preview.value.target.name}”? Tồn đích sau gộp sẽ là ${formatQty(Number(finalInventory.value))}. Các giá trị lịch sử trong đơn và phiếu nhập không thay đổi.`);
  if (!confirmed) return;

  submitting.value = true;
  errorMessage.value = '';
  try {
    const result = await mergeProducts({ source_id: Number(sourceId.value), target_id: Number(targetId.value), final_inventory_qty: Number(finalInventory.value), delete_source: deleteSource.value });
    toast.success(result?.message || 'Đã gộp sản phẩm.');
    await router.push({ name: 'products.list' });
  } catch (error: any) {
    errorMessage.value = readError(error, 'Không thể gộp sản phẩm.');
    toast.error(errorMessage.value);
  } finally {
    submitting.value = false;
  }
};

onMounted(() => {
  void loadOptions('source');
  void loadOptions('target');
});
</script>

<template>
  <section class="mx-auto w-full max-w-5xl space-y-5 pb-2">
    <header class="flex items-start gap-3 px-1">
      <RouterLink to="/products" class="mt-1 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-brand-300 hover:text-brand-700" aria-label="Quay lại danh sách sản phẩm">
        <ChevronLeft class="h-5 w-5" />
      </RouterLink>
      <div class="min-w-0">
        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-brand-700"><GitMerge class="h-4 w-4" /> Công cụ sản phẩm</div>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Gộp sản phẩm trùng</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">Chuyển lịch sử bán hàng, nhập hàng và tồn kho về một sản phẩm chính mà không làm thay đổi giá trị giao dịch cũ.</p>
      </div>
    </header>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5"><div class="flex items-center gap-3"><span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span><div><h2 class="text-sm font-semibold text-slate-900">Chọn hai sản phẩm</h2><p class="text-xs text-slate-500">Sản phẩm nguồn sẽ được chuyển vào sản phẩm đích.</p></div></div></div>
      <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:items-center">
        <div class="min-w-0 rounded-2xl border border-slate-200 p-4 transition focus-within:border-brand-300 focus-within:ring-2 focus-within:ring-brand-100">
          <div class="flex items-center gap-2"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-100 text-xs font-bold text-violet-700">A</span><label class="text-sm font-semibold text-slate-800">Sản phẩm nguồn</label></div>
          <p class="mt-1 text-xs text-slate-500">Sản phẩm trùng cần chuyển tham chiếu.</p>
          <input v-model="sourceQuery" type="search" placeholder="Tìm theo tên hoặc mã..." class="mt-3 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-500" />
          <select v-model="sourceId" class="mt-2 h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500"><option value="">Chọn sản phẩm nguồn</option><option v-for="item in sourceOptions" :key="`source-${item.id}`" :value="String(item.id)">{{ item.name }} · {{ item.code || 'Chưa có mã' }}</option></select>
        </div>

        <div class="flex justify-center lg:pt-7"><span class="inline-flex h-9 w-9 rotate-90 items-center justify-center rounded-full bg-slate-100 text-slate-500 lg:rotate-0"><ArrowRight class="h-5 w-5" /></span></div>

        <div class="min-w-0 rounded-2xl border border-brand-200 bg-brand-50/40 p-4 transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-100">
          <div class="flex items-center gap-2"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">B</span><label class="text-sm font-semibold text-slate-800">Sản phẩm đích</label></div>
          <p class="mt-1 text-xs text-slate-500">Sản phẩm chính sẽ giữ lại.</p>
          <input v-model="targetQuery" type="search" placeholder="Tìm theo tên hoặc mã..." class="mt-3 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-500" />
          <select v-model="targetId" class="mt-2 h-11 w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none transition focus:border-brand-500"><option value="">Chọn sản phẩm đích</option><option v-for="item in targetOptions" :key="`target-${item.id}`" :value="String(item.id)">{{ item.name }} · {{ item.code || 'Chưa có mã' }}</option></select>
        </div>
      </div>
    </section>

    <div v-if="loadingOptions || loadingPreview" class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-500 shadow-sm"><RefreshCw class="h-4 w-4 animate-spin text-brand-600" />{{ loadingPreview ? 'Đang kiểm tra dữ liệu gộp...' : 'Đang tải danh sách sản phẩm...' }}</div>
    <div v-if="errorMessage" class="flex items-start gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" /><span>{{ errorMessage }}</span></div>

    <template v-if="preview">
      <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5"><div class="flex items-center gap-3"><span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-brand-600 text-xs font-bold text-white">2</span><div><h2 class="text-sm font-semibold text-slate-900">Kiểm tra trước khi gộp</h2><p class="text-xs text-slate-500">Xác nhận dữ liệu tham chiếu và tồn thực tế.</p></div></div></div>

        <div class="grid gap-3 bg-slate-50/70 p-4 sm:grid-cols-2 sm:p-5"><div class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4"><div class="text-[11px] font-bold uppercase tracking-[0.14em] text-violet-600">Nguồn</div><div class="mt-2 truncate text-base font-semibold text-slate-900">{{ preview.source.name }}</div><div class="mt-1 text-sm text-slate-500">Tồn hiện tại <strong class="font-semibold text-slate-700">{{ formatQty(preview.source.inventory_qty_base) }} {{ preview.source.base_unit_name }}</strong></div></div><div class="min-w-0 rounded-2xl border border-brand-200 bg-white p-4"><div class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-700">Đích</div><div class="mt-2 truncate text-base font-semibold text-slate-900">{{ preview.target.name }}</div><div class="mt-1 text-sm text-slate-500">Tồn hiện tại <strong class="font-semibold text-slate-700">{{ formatQty(preview.target.inventory_qty_base) }} {{ preview.target.base_unit_name }}</strong></div></div></div>

        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3 sm:p-5 lg:grid-cols-6"><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Dòng đơn</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.order_items }}</div></div><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Đơn bán</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.order_count }}</div></div><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Dòng nhập</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.purchase_items }}</div></div><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Phiếu nhập</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.purchase_count }}</div></div><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Dòng xuất kho</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.project_issue_items }}</div></div><div class="rounded-xl bg-slate-50 px-3 py-2.5"><div class="text-xs text-slate-500">Lịch sử</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ preview.references.product_logs }}</div></div></div>

        <div class="mx-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 sm:mx-5"><div class="flex items-start gap-2"><ShieldAlert class="mt-0.5 h-4 w-4 shrink-0" /><div><div class="font-semibold">Tồn kho cần xác nhận</div><p class="mt-0.5 leading-5">{{ preview.warnings.inventory }}</p></div></div></div>

        <div class="grid gap-5 p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.72fr)]"><div><div class="flex items-center justify-between gap-3"><h3 class="text-sm font-semibold text-slate-900">Đối chiếu đơn vị</h3><span v-if="preview.can_merge" class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><CheckCircle2 class="h-4 w-4" /> Hợp lệ</span></div><div v-if="preview.unit_mapping.length" class="mt-3 space-y-2"><div v-for="mapping in preview.unit_mapping" :key="mapping.source_unit_id" class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm"><span class="text-slate-700">{{ mapping.unit_name }} · hệ số {{ formatQty(mapping.factor) }}</span><span class="font-medium text-emerald-700">Có thể chuyển</span></div></div><div v-if="preview.conflicts.length" class="mt-3 space-y-2"><div v-for="conflict in preview.conflicts" :key="conflict.source_unit_id" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-sm text-rose-700">{{ conflict.unit_name || `Đơn vị #${conflict.source_unit_id}` }}: {{ conflict.reason }}</div></div><div v-if="!preview.unit_mapping.length && !preview.conflicts.length" class="mt-3 rounded-xl bg-slate-50 px-3 py-3 text-sm text-slate-500">Không có dòng giao dịch cần mapping đơn vị.</div></div><div class="rounded-2xl border border-brand-200 bg-brand-50/50 p-4"><label class="text-sm font-semibold text-slate-900">Tồn thực tế sau khi gộp</label><p class="mt-1 text-xs leading-5 text-slate-600">Nhập số lượng thực tế, không cộng máy móc hai con số đang hiển thị.</p><input v-model="finalInventory" type="number" min="0" step="any" placeholder="Ví dụ: 7" class="mt-3 h-11 w-full rounded-xl border border-brand-200 bg-white px-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100" /><p class="mt-2 text-xs text-slate-500">Tồn nguồn sẽ về 0 sau khi hoàn tất.</p></div></div>

        <div class="mx-4 mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:mx-5 sm:mb-5 sm:flex-row sm:items-center sm:justify-between"><label class="flex min-w-0 items-start gap-3 text-sm text-slate-700"><input v-model="deleteSource" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500" /><span><strong class="font-semibold text-slate-900">Ẩn sản phẩm nguồn sau khi gộp</strong><span class="mt-0.5 block text-xs leading-5 text-slate-500">Bỏ chọn nếu muốn giữ lại để kiểm tra hoặc xóa thủ công sau.</span></span></label><button type="button" class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 sm:w-auto" :disabled="!canSubmit" @click="submit"><RefreshCw v-if="submitting" class="h-4 w-4 animate-spin" /><span>{{ submitting ? 'Đang gộp...' : 'Xác nhận gộp' }}</span></button></div>
      </section>
    </template>
  </section>
</template>
