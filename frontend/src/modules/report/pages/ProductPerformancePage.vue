<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { BarChart3, DollarSign, Package, TrendingDown, TrendingUp } from '@lucide/vue';
import ReportNavButtons from '../components/ReportNavButtons.vue';
import ReportGroupTabs from '../components/ReportGroupTabs.vue';
import ReportDateFilter, { type ReportDateFilterValue } from '../components/ReportDateFilter.vue';
import { useProductPerformance } from '../composables/useProductPerformance';
import { useFormat } from '../../../shared/composables/useFormat';

const { data, loading, error, load } = useProductPerformance();
const { formatMoney, formatNumber } = useFormat();
const sortBy = ref('revenue');
const page = ref(1);
const insightTab = ref<'top' | 'slow'>('top');
const perPage = 50;

const today = new Date().toISOString().slice(0, 10);
const filter = reactive<ReportDateFilterValue>({
  filter_mode: 'month', day: today, month: today.slice(0, 7),
  quarter: String(Math.floor(new Date().getMonth() / 3) + 1), quarter_year: String(new Date().getFullYear()), year: String(new Date().getFullYear()),
});
const sortOptions = [
  { value: 'revenue', label: 'Doanh thu' },
  { value: 'quantity', label: 'Số lượng' },
  { value: 'profit', label: 'Lợi nhuận' },
  { value: 'margin', label: 'Tỷ suất LN' },
];

const summary = computed(() => data.value?.summary || {});
const items = computed(() => data.value?.items || []);
const topProducts = computed(() => data.value?.top_products || []);
const slowProducts = computed(() => data.value?.slow_products || []);
const meta = computed(() => data.value?.meta || { page: 1, per_page: perPage, total_count: 0, total_pages: 1 });
const isSortDisabled = computed(() => data.value !== null && Number(summary.value.active_products || 0) === 0);

function buildParams() {
  const params: Record<string, string> = { filter_mode: filter.filter_mode };
  if (filter.filter_mode === 'day') params.day = filter.day;
  if (filter.filter_mode === 'month') params.month = filter.month;
  if (filter.filter_mode === 'quarter') { params.quarter = filter.quarter; params.quarter_year = filter.quarter_year; }
  if (filter.filter_mode === 'year') params.year = filter.year;
  return params;
}

async function loadData() {
  try {
    await load(buildParams(), sortBy.value, page.value, perPage);
  } catch {
    // useFetch exposes a user-facing error state; prevent an unhandled lifecycle rejection.
  }
}

function changePage(nextPage: number) {
  if (nextPage < 1 || nextPage > meta.value.total_pages || nextPage === page.value) return;
  page.value = nextPage;
}

function applyFilter() {
  if (page.value !== 1) page.value = 1;
  else void loadData();
}

watch(sortBy, () => {
  if (page.value !== 1) {
    page.value = 1;
    return;
  }
  void loadData();
});
watch(page, () => { void loadData(); });
onMounted(() => { void loadData(); });
</script>

<template>
  <section class="space-y-6">
    <header>
      <div><h1 class="text-xl font-semibold text-slate-900">Hiệu suất sản phẩm</h1><p class="text-sm text-slate-500">Tính tất cả đơn chưa hủy</p></div>
      <ReportNavButtons />
      <ReportGroupTabs group="sales" />
      <ReportDateFilter :model-value="filter" :loading="loading" @update:model-value="Object.assign(filter, $event)" @apply="applyFilter"><template #actions><div class="flex flex-col items-end gap-1"><div class="relative grid min-w-[7rem]"><select v-model="sortBy" :disabled="isSortDisabled" class="app-select col-start-1 row-start-1 h-10 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"><option v-for="item in sortOptions" :key="item.value" :value="item.value">{{ item.label }}</option></select><span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span></div><p v-if="isSortDisabled" class="text-xs text-slate-500">Chưa có bán hàng để sắp xếp.</p></div></template></ReportDateFilter>
    </header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><Package class="h-5 w-5 text-blue-600" /><div><p class="text-sm text-slate-500">Tổng sản phẩm</p><p class="text-lg font-semibold">{{ summary.total_products?.toLocaleString() || 0 }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><TrendingUp class="h-5 w-5 text-green-600" /><div><p class="text-sm text-slate-500">SP đang bán</p><p class="text-lg font-semibold">{{ summary.active_products?.toLocaleString() || 0 }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><DollarSign class="h-5 w-5 text-amber-600" /><div><p class="text-sm text-slate-500">Tổng doanh thu</p><p class="text-lg font-semibold">{{ formatMoney(summary.total_revenue) }}</p></div></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex items-center gap-3"><BarChart3 class="h-5 w-5 text-purple-600" /><div><p class="text-sm text-slate-500">Tỷ suất LN TB</p><p class="text-lg font-semibold">{{ summary.avg_profit_margin || 0 }}%</p></div></div></div>
    </div>

    <div v-if="loading" class="flex justify-center py-12"><div class="h-8 w-8 animate-spin rounded-full border-2 border-brand-500 border-t-transparent" /></div>
    <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-center text-red-600">{{ error }}</div>
    <template v-else>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-4 flex flex-wrap items-center gap-2"><h2 class="mr-2 text-base font-medium">Điểm nổi bật sản phẩm</h2><button type="button" class="inline-flex h-8 items-center gap-1 rounded-lg border px-3 text-sm" :class="insightTab === 'top' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-300 text-slate-600'" @click="insightTab = 'top'"><TrendingUp class="h-4 w-4" />Bán chạy</button><button type="button" class="inline-flex h-8 items-center gap-1 rounded-lg border px-3 text-sm" :class="insightTab === 'slow' ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-slate-300 text-slate-600'" @click="insightTab = 'slow'"><TrendingDown class="h-4 w-4" />Chưa bán được</button></div>
        <template v-if="insightTab === 'top'"><p v-if="!topProducts.length" class="py-6 text-center text-sm text-slate-500">Chưa có sản phẩm phát sinh doanh thu trong kỳ đã chọn.</p><div v-else class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Đã bán</th><th class="pb-2 text-right">Doanh thu</th><th class="pb-2 text-right">Lợi nhuận</th><th class="pb-2 text-right">Tỷ suất LN</th></tr></thead><tbody><tr v-for="product in topProducts" :key="product.id" class="border-b border-slate-100"><td class="py-2"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-2 text-right">{{ product.sold_qty?.toLocaleString() }}</td><td class="py-2 text-right">{{ formatMoney(product.revenue) }}</td><td class="py-2 text-right text-green-600">{{ formatMoney(product.profit) }}</td><td class="py-2 text-right">{{ product.profit_margin }}%</td></tr></tbody></table></div></template>
        <template v-else><p v-if="!slowProducts.length" class="py-6 text-center text-sm text-slate-500">Không có sản phẩm chưa bán được trong kỳ này.</p><div v-else class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Tồn kho</th></tr></thead><tbody><tr v-for="product in slowProducts" :key="product.id" class="border-b border-slate-100"><td class="py-2"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-2 text-right">{{ product.current_stock?.toLocaleString() }}</td></tr></tbody></table></div></template>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-3"><div class="mb-3 flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-medium">Tất cả sản phẩm</h2><p class="text-sm text-slate-500">{{ meta.total_count.toLocaleString() }} sản phẩm · trang {{ meta.page }}/{{ meta.total_pages }}</p></div><p v-if="!items.length" class="py-6 text-center text-sm text-slate-500">Không có sản phẩm để hiển thị.</p><div v-else class="overflow-x-auto"><table class="min-w-[44rem] w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2 pr-4">Sản phẩm</th><th class="px-2 pb-2 text-right">Tồn kho</th><th class="px-2 pb-2 text-right">Đã bán</th><th class="px-2 pb-2 text-right">Doanh thu</th><th class="px-2 pb-2 text-right">Lợi nhuận</th><th class="pl-2 pb-2 text-right">Tỷ suất LN</th></tr></thead><tbody><tr v-for="product in items" :key="product.id" class="border-b border-slate-100" :class="{ 'bg-slate-50': product.sold_qty === 0 }"><td class="py-2 pr-4"><div class="flex items-baseline gap-2 whitespace-nowrap"><span class="font-medium">{{ product.name }}</span><span class="text-xs text-slate-500">{{ product.code }}</span></div></td><td class="px-2 py-2 text-right whitespace-nowrap">{{ formatNumber(product.current_stock) }}</td><td class="px-2 py-2 text-right whitespace-nowrap">{{ formatNumber(product.sold_qty) }}</td><td class="px-2 py-2 text-right whitespace-nowrap">{{ formatMoney(product.revenue) }}</td><td class="px-2 py-2 text-right whitespace-nowrap" :class="product.profit > 0 ? 'text-green-600' : 'text-slate-400'">{{ formatMoney(product.profit) }}</td><td class="pl-2 py-2 text-right whitespace-nowrap">{{ product.profit_margin }}%</td></tr></tbody></table></div><div v-if="meta.total_pages > 1" class="mt-3 flex items-center justify-end gap-3"><button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50" :disabled="meta.page <= 1" @click="changePage(meta.page - 1)">Trước</button><button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50" :disabled="meta.page >= meta.total_pages" @click="changePage(meta.page + 1)">Sau</button></div></div>
    </template>
  </section>
</template>
