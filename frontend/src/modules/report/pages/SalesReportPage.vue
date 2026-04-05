<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useSalesReport } from '../composables/useSalesReport';

const toast = useToast();
const { rows, summary, meta, loading, error, load } = useSalesReport();
const hasLoadedOnce = ref(false);

const form = reactive({
  filter_mode: 'day',
  day: '',
  month: '',
  quarter: '',
  quarter_year: String(new Date().getFullYear()),
  year: String(new Date().getFullYear()),
  page: 1
});

const formatMoney = (amount) => `${new Intl.NumberFormat('vi-VN').format(Number(amount || 0))} đ`;
const totalPages = computed(() => Number(meta.value?.total_pages || 1));
const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);
const isRefreshing = computed(() => loading.value && hasLoadedOnce.value);

const buildParams = () => {
  const params = { filter_mode: form.filter_mode, page: form.page };
  if (form.filter_mode === 'day' && form.day) params.day = form.day;
  if (form.filter_mode === 'month' && form.month) params.month = form.month;
  if (form.filter_mode === 'quarter' && form.quarter && form.quarter_year) {
    params.quarter = form.quarter;
    params.quarter_year = form.quarter_year;
  }
  if (form.filter_mode === 'year' && form.year) params.year = form.year;
  return params;
};

const loadPage = async () => {
  try {
    await load(buildParams());
    hasLoadedOnce.value = true;
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo doanh thu.');
  }
};

const applyFilter = async () => {
  form.page = 1;
  await loadPage();
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
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo doanh thu</h1>
      <div class="mt-2 flex flex-wrap gap-2">
        <RouterLink to="/reports" class="rounded-lg border border-slate-300 px-3 py-1 text-sm">Tổng quan</RouterLink>
        <RouterLink to="/reports/sales-orders" class="rounded-lg border border-slate-300 px-3 py-1 text-sm">Danh sách đơn</RouterLink>
      </div>
      <form class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-5" @submit.prevent="applyFilter">
        <label class="space-y-1">
          <span class="app-label">Kiểu lọc</span>
          <div class="relative">
            <select v-model="form.filter_mode" class="block h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm">
          <option value="day">Ngày</option>
              <option value="month">Tháng</option>
              <option value="quarter">Quý</option>
              <option value="year">Năm</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
          </div>
        </label>
        <label v-if="form.filter_mode === 'day'" class="space-y-1">
          <span class="app-label">Ngày</span>
          <input v-model="form.day" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <label v-if="form.filter_mode === 'month'" class="space-y-1">
          <span class="app-label">Tháng</span>
          <input v-model="form.month" type="month" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <label v-if="form.filter_mode === 'quarter'" class="space-y-1">
          <span class="app-label">Quý</span>
          <div class="relative">
            <select v-model="form.quarter" class="block h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm">
              <option value="">Quý</option>
              <option value="1">Quý 1</option>
              <option value="2">Quý 2</option>
              <option value="3">Quý 3</option>
              <option value="4">Quý 4</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
          </div>
        </label>
        <label v-if="form.filter_mode === 'quarter'" class="space-y-1">
          <span class="app-label">Năm quý</span>
          <input v-model="form.quarter_year" type="number" min="2000" max="2100" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <label v-if="form.filter_mode === 'year'" class="space-y-1">
          <span class="app-label">Năm</span>
          <input v-model="form.year" type="number" min="2000" max="2100" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <button type="submit" class="h-10 self-end rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Lọc</button>
      </form>
    </header>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Số đơn</div><div class="mt-1 font-semibold">{{ Number(summary.order_count || 0) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Doanh thu</div><div class="mt-1 font-semibold">{{ formatMoney(summary.total_amount) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Lợi nhuận</div><div class="mt-1 font-semibold">{{ formatMoney(summary.profit) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Đã thu</div><div class="mt-1 font-semibold">{{ formatMoney(summary.paid_amount) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Còn nợ</div><div class="mt-1 font-semibold">{{ formatMoney(summary.debt_amount) }}</div></div>
    </section>

    <div v-if="isRefreshing" class="px-1 text-xs font-medium text-slate-500">Đang cập nhật báo cáo...</div>
    <div v-if="isInitialLoading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!rows.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Không có dữ liệu.</div>

    <section v-else class="space-y-2" :class="isRefreshing ? 'opacity-70 transition-opacity' : 'transition-opacity'">
      <article v-for="row in rows" :key="row.id" class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <div class="flex items-center justify-between gap-2">
          <div class="font-medium text-slate-900">#{{ row.code }}</div>
          <RouterLink :to="{ name: 'orders.detail', params: { id: row.id } }" class="text-brand-700">Xem đơn</RouterLink>
        </div>
        <div class="mt-1 text-slate-500">{{ row.doc_date }}</div>
        <div class="mt-2 flex flex-wrap gap-3">
          <span>Tổng: <b>{{ formatMoney(row.total_amount) }}</b></span>
          <span>Chi phí: <b>{{ formatMoney(row.total_cost) }}</b></span>
          <span>Đã thu: <b>{{ formatMoney(row.paid_amount) }}</b></span>
        </div>
      </article>
    </section>

    <footer class="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm" :class="isRefreshing ? 'opacity-70 transition-opacity' : 'transition-opacity'">
      <span>Trang {{ form.page }} / {{ totalPages }}</span>
      <div class="flex gap-2">
        <button class="rounded-lg border border-slate-300 px-3 py-1" :disabled="form.page <= 1 || loading" @click="prevPage">Trước</button>
        <button class="rounded-lg border border-slate-300 px-3 py-1" :disabled="form.page >= totalPages || loading" @click="nextPage">Sau</button>
      </div>
    </footer>
  </section>
</template>
