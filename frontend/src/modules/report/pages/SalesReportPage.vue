<script setup lang="ts">
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatCompactMoney } = useFormat();
import { reactive } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useSalesReport } from '../composables/useSalesReport';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';
import ReportNavButtons from '../components/ReportNavButtons.vue';
import ReportGroupTabs from '../components/ReportGroupTabs.vue';

const toast = useToast();
const { items, summary, meta, loading, error, load } = useSalesReport();

const today = new Date().toISOString().slice(0, 10);
const thisMonth = today.slice(0, 7);
const currentQuarter = String(Math.floor(new Date().getMonth() / 3) + 1);
const form = reactive({
  filter_mode: 'day' as 'day' | 'month' | 'quarter' | 'year',
  day: today,
  month: thisMonth,
  quarter: currentQuarter,
  quarter_year: String(new Date().getFullYear()),
  year: String(new Date().getFullYear())
});

// Đã thay thế bằng useFormat

const buildParams = () => {
  const params: Record<string, any> = { filter_mode: form.filter_mode };
  if (form.filter_mode === 'day' && form.day) params.day = form.day;
  if (form.filter_mode === 'month' && form.month) params.month = form.month;
  if (form.filter_mode === 'quarter' && form.quarter && form.quarter_year) {
    params.quarter = form.quarter;
    params.quarter_year = form.quarter_year;
  }
  if (form.filter_mode === 'year' && form.year) params.year = form.year;
  return params;
};

const {
  hasMore,
  loadingMore,
  isInitialLoading,
  infiniteSentinel: infiniteListSentinel,
  refresh
} = useInfiniteList({
  itemsRef: items,
  metaRef: meta,
  loadingRef: loading,
  fetchPage: (page: number) => load({ ...buildParams(), page }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách đơn hàng.');
  }
});

console.log(items);

const applyFilter = async () => {
  await refresh();
};


</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo doanh thu chi tiết</h1>
      <p class="mt-1 text-sm text-slate-500">Xem danh sách đơn hàng, doanh thu và lợi nhuận theo khoảng thời gian.</p>
      <ReportNavButtons />
      <ReportGroupTabs group="sales" />
      <form class="app-card mt-3 flex flex-col gap-2" @submit.prevent="applyFilter">
        <div class="flex gap-2 flex-wrap">
          <button type="button" class="inline-flex h-9 items-center rounded-xl border px-4 text-sm font-medium border-slate-300 text-slate-700 hover:bg-slate-100" :class="{ 'border-brand-600 bg-brand-50 text-brand-700': form.filter_mode === 'day' }" @click="form.filter_mode = 'day'">Ngày</button>
          <button type="button" class="inline-flex h-9 items-center rounded-xl border px-4 text-sm font-medium border-slate-300 text-slate-700 hover:bg-slate-100" :class="{ 'border-brand-600 bg-brand-50 text-brand-700': form.filter_mode === 'month' }" @click="form.filter_mode = 'month'">Tháng</button>
          <button type="button" class="inline-flex h-9 items-center rounded-xl border px-4 text-sm font-medium border-slate-300 text-slate-700 hover:bg-slate-100" :class="{ 'border-brand-600 bg-brand-50 text-brand-700': form.filter_mode === 'quarter' }" @click="form.filter_mode = 'quarter'">Quý</button>
          <button type="button" class="inline-flex h-9 items-center rounded-xl border px-4 text-sm font-medium border-slate-300 text-slate-700 hover:bg-slate-100" :class="{ 'border-brand-600 bg-brand-50 text-brand-700': form.filter_mode === 'year' }" @click="form.filter_mode = 'year'">Năm</button>
        </div>
        <div class="flex items-end gap-2 flex-wrap">
          <div v-if="form.filter_mode === 'day'" class="relative">
            <label class="app-label">Chọn ngày</label>
            <input v-model="form.day" type="date" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
          </div>
          <div v-if="form.filter_mode === 'month'" class="relative">
            <label class="app-label">Chọn tháng</label>
            <input v-model="form.month" type="month" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
          </div>
          <div v-if="form.filter_mode === 'quarter'" class="flex gap-2">
            <div>
              <label class="app-label">Quý</label>
              <div class="relative grid min-w-[9rem] md:min-w-[10rem]"><select v-model="form.quarter" class="app-select col-start-1 row-start-1"><option value="1">Quý 1</option><option value="2">Quý 2</option><option value="3">Quý 3</option><option value="4">Quý 4</option></select><span class="pointer-events-none col-start-1 row-start-1 mr-3 flex items-center justify-end text-slate-400"><svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg></span></div>
            </div>
            <div>
              <label class="app-label">Năm</label>
              <input v-model="form.quarter_year" type="number" min="2000" max="2100" class="h-10 min-w-[9rem] md:min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
            </div>
          </div>
          <div v-if="form.filter_mode === 'year'" class="relative">
            <label class="app-label">Chọn năm</label>
            <input v-model="form.year" type="number" min="2000" max="2100" class="h-10 min-w-[9rem] md:min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
          </div>
          <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Lọc</button>
        </div>
      </form>
    </header>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Số đơn</div><div class="mt-1 font-semibold text-slate-900">{{ Number(summary.order_count || 0) }}</div></div>
      <div class="rounded-xl border border-sky-100 bg-white p-3 text-sm"><div class="text-sky-700">Doanh thu</div><div class="mt-1 font-semibold text-slate-900" :title="formatMoney(summary.total_amount)">{{ formatCompactMoney(summary.total_amount) }}</div></div>
      <div class="rounded-xl border border-brand-100 bg-white p-3 text-sm"><div class="text-brand-700">Lợi nhuận</div><div class="mt-1 font-semibold text-slate-900" :title="formatMoney(summary.profit)">{{ formatCompactMoney(summary.profit) }}</div></div>
      <div class="rounded-xl border border-brand-100 bg-white p-3 text-sm"><div class="text-brand-700">Đã thu</div><div class="mt-1 font-semibold text-slate-900" :title="formatMoney(summary.paid_amount)">{{ formatCompactMoney(summary.paid_amount) }}</div></div>
      <div class="rounded-xl border border-rose-100 bg-white p-3 text-sm"><div class="text-rose-700">Còn nợ</div><div class="mt-1 font-semibold text-slate-900" :title="formatMoney(summary.debt_amount)">{{ formatCompactMoney(summary.debt_amount) }}</div></div>
    </section>


    <div v-if="isInitialLoading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!items.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Không có dữ liệu doanh thu.</div>

    <section v-else class="space-y-3">
      <OrderItemCard v-for="item in items" :key="item.id" :order="item" :link-enabled="true" :show-view-icon="true" :compact-money="true" />
      <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
      <div v-if="items.length && hasMore" ref="infiniteListSentinel" class="h-1 w-full"></div>
    </section>
  </section>
</template>
