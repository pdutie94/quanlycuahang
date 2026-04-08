<script setup>
import { BarChart2, Package, User, Truck, Tag } from '@lucide/vue';
// ...existing code...
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useSalesReport } from '../composables/useSalesReport';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const toast = useToast();
const { rows, summary, meta, loading, error, load } = useSalesReport();
const hasLoadedOnce = ref(false);
const infiniteSentinel = ref(null);

const form = reactive({
  filter_mode: 'day',
  day: '',
  month: '',
  quarter: '',
  quarter_year: String(new Date().getFullYear()),
  year: String(new Date().getFullYear())
});

// Đã thay thế bằng useFormat

const buildParams = () => {
  const params = { filter_mode: form.filter_mode };
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
  itemsRef: rows,
  metaRef: meta,
  loadingRef: loading,
  fetchPage: (page) => load({ ...buildParams(), page }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách đơn hàng.');
  }
});


const applyFilter = async () => {
  await refresh();
};

const prevPage = async () => {
  if (form.page <= 1 || loading.value) return;
  form.page -= 1;
  await loadPage();
};

const nextPage = async () => {
  if (form.page >= totalPages.value || loading.value) return;
  form.page += 1;
  await loadPage();
};

onMounted(async () => {
  form.day = new Date().toISOString().slice(0, 10);
  form.month = form.day.slice(0, 7);
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo doanh thu chi tiết</h1>
      <p class="mt-1 text-sm text-slate-500">Xem danh sách đơn hàng, doanh thu và lợi nhuận theo khoảng thời gian.</p>
      <div class="mt-3 flex flex-wrap gap-2 overflow-x-auto">
        <RouterLink to="/reports/sales" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-brand-300 bg-brand-50 text-brand-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-brand-500 text-white"><BarChart2 class="h-4 w-4" /></span><span>Doanh thu chi tiết</span></RouterLink>
        <RouterLink to="/reports/inventory" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-sky-300 bg-sky-50 text-sky-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-sky-500 text-white"><Package class="h-4 w-4" /></span><span>Cập nhật tồn kho</span></RouterLink>
        <RouterLink to="/reports/customer-debt" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-rose-300 bg-rose-50 text-rose-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-rose-500 text-white"><User class="h-4 w-4" /></span><span>Công nợ khách hàng</span></RouterLink>
        <RouterLink to="/reports/supplier-debt" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-violet-300 bg-violet-50 text-violet-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-violet-500 text-white"><Truck class="h-4 w-4" /></span><span>Công nợ nhà cung cấp</span></RouterLink>
        <RouterLink to="/reports/missing-cost" class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium border-amber-300 bg-amber-50 text-amber-700"><span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-amber-500 text-white"><Tag class="h-4 w-4" /></span><span>Cập nhật giá vốn</span></RouterLink>
      </div>
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
              <select v-model="form.quarter" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm">
                <option value="">Quý</option>
                <option value="1">Quý 1</option>
                <option value="2">Quý 2</option>
                <option value="3">Quý 3</option>
                <option value="4">Quý 4</option>
              </select>
            </div>
            <div>
              <label class="app-label">Năm</label>
              <input v-model="form.quarter_year" type="number" min="2000" max="2100" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
            </div>
          </div>
          <div v-if="form.filter_mode === 'year'" class="relative">
            <label class="app-label">Chọn năm</label>
            <input v-model="form.year" type="number" min="2000" max="2100" class="h-10 min-w-[10rem] rounded-xl border border-slate-300 px-3 text-sm" />
          </div>
          <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Lọc</button>
        </div>
      </form>
    </header>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Số đơn</div><div class="mt-1 font-semibold text-slate-900">{{ Number(summary.order_count || 0) }}</div></div>
      <div class="rounded-xl border border-sky-100 bg-white p-3 text-sm"><div class="text-sky-700">Doanh thu</div><div class="mt-1 font-semibold text-slate-900">{{ formatMoney(summary.total_amount) }}</div></div>
      <div class="rounded-xl border border-brand-100 bg-white p-3 text-sm"><div class="text-brand-700">Lợi nhuận</div><div class="mt-1 font-semibold text-slate-900">{{ formatMoney(summary.profit) }}</div></div>
      <div class="rounded-xl border border-brand-100 bg-white p-3 text-sm"><div class="text-brand-700">Đã thu</div><div class="mt-1 font-semibold text-slate-900">{{ formatMoney(summary.paid_amount) }}</div></div>
      <div class="rounded-xl border border-rose-100 bg-white p-3 text-sm"><div class="text-rose-700">Còn nợ</div><div class="mt-1 font-semibold text-slate-900">{{ formatMoney(summary.debt_amount) }}</div></div>
    </section>

    <div v-if="isRefreshing" class="px-1 text-xs font-medium text-slate-500">Đang cập nhật báo cáo...</div>
    <div v-if="isInitialLoading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!rows.length" class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">Không có dữ liệu doanh thu.</div>

    <section v-else class="space-y-3">
      <OrderItemCard v-for="row in rows" :key="row.id" :order="row" :link-enabled="true" :show-view-icon="true" />
      <InfiniteListStatus :visible="rows.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
      <div v-if="rows.length && hasMore" ref="infiniteListSentinel" class="h-1 w-full"></div>
    </section>
  </section>
</template>
