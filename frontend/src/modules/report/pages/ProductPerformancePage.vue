<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { BarChart3, DollarSign, Package, TrendingDown, TrendingUp } from '@lucide/vue';
import ReportNavButtons from '../components/ReportNavButtons.vue';
import ReportGroupTabs from '../components/ReportGroupTabs.vue';
import { useProductPerformance } from '../composables/useProductPerformance';
import { useFormat } from '../../../shared/composables/useFormat';

const { data, loading, error, load } = useProductPerformance();
const { formatMoney } = useFormat();
const period = ref('30d');
const sortBy = ref('revenue');
const page = ref(1);
const perPage = 50;

const periods = [
  { value: '7d', label: '7 ngày' },
  { value: '30d', label: '30 ngày' },
  { value: '90d', label: '90 ngày' },
  { value: '1y', label: '1 năm' },
];
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

async function loadData() {
  try {
    await load(period.value, sortBy.value, page.value, perPage);
  } catch {
    // useFetch exposes a user-facing error state; prevent an unhandled lifecycle rejection.
  }
}

function changePage(nextPage: number) {
  if (nextPage < 1 || nextPage > meta.value.total_pages || nextPage === page.value) return;
  page.value = nextPage;
}

watch([period, sortBy], () => {
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
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-xl font-semibold text-slate-900">Hiệu suất sản phẩm</h1><p class="text-sm text-slate-500">Tính tất cả đơn chưa hủy</p></div>
        <div class="flex gap-2">
        <div class="relative grid min-w-[6rem]"><select v-model="period" class="app-select col-start-1 row-start-1 h-9"><option v-for="item in periods" :key="item.value" :value="item.value">{{ item.label }}</option></select><span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span></div>
        <div class="relative grid min-w-[7rem]"><select v-model="sortBy" class="app-select col-start-1 row-start-1 h-9"><option v-for="item in sortOptions" :key="item.value" :value="item.value">{{ item.label }}</option></select><span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span></div>
        </div>
      </div>
      <ReportNavButtons />
      <ReportGroupTabs group="sales" />
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
      <div v-if="topProducts.length" class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 flex items-center gap-2 text-base font-medium"><TrendingUp class="h-5 w-5 text-green-600" />Sản phẩm bán chạy</h2><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Đã bán</th><th class="pb-2 text-right">Doanh thu</th><th class="pb-2 text-right">Lợi nhuận</th><th class="pb-2 text-right">Tỷ suất LN</th></tr></thead><tbody><tr v-for="product in topProducts" :key="product.id" class="border-b border-slate-100"><td class="py-3"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-3 text-right">{{ product.sold_qty?.toLocaleString() }}</td><td class="py-3 text-right">{{ formatMoney(product.revenue) }}</td><td class="py-3 text-right text-green-600">{{ formatMoney(product.profit) }}</td><td class="py-3 text-right">{{ product.profit_margin }}%</td></tr></tbody></table></div></div>
      <div v-if="slowProducts.length" class="rounded-xl border border-slate-200 bg-white p-4"><h2 class="mb-4 flex items-center gap-2 text-base font-medium"><TrendingDown class="h-5 w-5 text-rose-600" />Sản phẩm tồn kho không bán</h2><div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Tồn kho</th></tr></thead><tbody><tr v-for="product in slowProducts" :key="product.id" class="border-b border-slate-100"><td class="py-3"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-3 text-right">{{ product.current_stock?.toLocaleString() }}</td></tr></tbody></table></div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-4"><div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 class="text-base font-medium">Tất cả sản phẩm</h2><p class="text-sm text-slate-500">{{ meta.total_count.toLocaleString() }} sản phẩm · trang {{ meta.page }}/{{ meta.total_pages }}</p></div><p v-if="!items.length" class="py-6 text-center text-sm text-slate-500">Không có sản phẩm để hiển thị.</p><div v-else class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-600"><th class="pb-2">Sản phẩm</th><th class="pb-2 text-right">Tồn kho</th><th class="pb-2 text-right">Đã bán</th><th class="pb-2 text-right">Doanh thu</th><th class="pb-2 text-right">Lợi nhuận</th><th class="pb-2 text-right">Tỷ suất LN</th></tr></thead><tbody><tr v-for="product in items" :key="product.id" class="border-b border-slate-100" :class="{ 'bg-slate-50': product.sold_qty === 0 }"><td class="py-3"><div class="font-medium">{{ product.name }}</div><div class="text-xs text-slate-500">{{ product.code }}</div></td><td class="py-3 text-right">{{ product.current_stock?.toLocaleString() }}</td><td class="py-3 text-right">{{ product.sold_qty?.toLocaleString() || 0 }}</td><td class="py-3 text-right">{{ formatMoney(product.revenue) }}</td><td class="py-3 text-right" :class="product.profit > 0 ? 'text-green-600' : 'text-slate-400'">{{ formatMoney(product.profit) }}</td><td class="py-3 text-right">{{ product.profit_margin }}%</td></tr></tbody></table></div><div v-if="meta.total_pages > 1" class="mt-4 flex items-center justify-end gap-3"><button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50" :disabled="meta.page <= 1" @click="changePage(meta.page - 1)">Trước</button><button type="button" class="rounded-lg border px-3 py-1.5 text-sm disabled:opacity-50" :disabled="meta.page >= meta.total_pages" @click="changePage(meta.page + 1)">Sau</button></div></div>
    </template>
  </section>
</template>
