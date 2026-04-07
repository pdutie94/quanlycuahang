<script setup>
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useSupplierDebtReport } from '../composables/useSupplierDebtReport';

const toast = useToast();
const { rows, summary, loading, error, load } = useSupplierDebtReport();
const form = reactive({ start_date: '', end_date: '', q: '', show_all: false });
const hasLoadedOnce = ref(false);
const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);
const isRefreshing = computed(() => loading.value && hasLoadedOnce.value);

// Đã thay thế bằng useFormat

const loadPage = async () => {
  try {
    await load({
      start_date: form.start_date,
      end_date: form.end_date,
      q: form.q,
      show_all: form.show_all ? '1' : '0'
    });
    hasLoadedOnce.value = true;
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo công nợ nhà cung cấp.');
  }
};

const resetFilter = async () => {
  form.start_date = '';
  form.end_date = '';
  form.q = '';
  form.show_all = false;
  await loadPage();
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Công nợ nhà cung cấp</h1>
      <div class="mt-3 flex flex-wrap gap-2">
        <RouterLink to="/reports" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700">Báo cáo tổng quan</RouterLink>
        <RouterLink to="/reports/customer-debt" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700">Công nợ khách hàng</RouterLink>
      </div>

      <form class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-4" @submit.prevent="loadPage">
        <label class="space-y-1">
          <span class="app-label">Từ ngày</span>
          <input v-model="form.start_date" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
        </label>
        <label class="space-y-1">
          <span class="app-label">Đến ngày</span>
          <input v-model="form.end_date" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
        </label>
        <label class="space-y-1">
          <span class="app-label">Từ khóa</span>
          <input v-model="form.q" type="text" placeholder="Tên, SĐT, địa chỉ" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
        </label>
        <label class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm text-slate-700">
          <input v-model="form.show_all" type="checkbox" class="h-4 w-4" />
          Hiển thị tất cả
        </label>
        <div class="sm:col-span-4 flex gap-2">
          <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Lọc dữ liệu</button>
          <button type="button" class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700" :disabled="loading" @click="resetFilter">Đặt lại</button>
        </div>
      </form>
    </header>

    <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
      <article class="rounded-2xl border border-slate-200 bg-white p-3"><div class="text-sm text-slate-500">Tổng nhập</div><div class="mt-1 text-lg font-semibold text-slate-900">{{ formatMoney(summary.total_amount) }}</div></article>
      <article class="rounded-2xl border border-brand-100 bg-brand-50 p-3"><div class="text-sm text-brand-700">Đã trả</div><div class="mt-1 text-lg font-semibold text-brand-800">{{ formatMoney(summary.paid_amount) }}</div></article>
      <article class="rounded-2xl border border-violet-100 bg-violet-50 p-3"><div class="text-sm text-violet-700">Còn nợ</div><div class="mt-1 text-lg font-semibold text-violet-800">{{ formatMoney(summary.debt_amount) }}</div></article>
    </section>

    <div v-if="isRefreshing" class="px-1 text-xs font-medium text-slate-500">Đang cập nhật công nợ nhà cung cấp...</div>
    <div v-if="isInitialLoading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!rows.length" class="app-empty-state">Không có dữ liệu công nợ phù hợp.</div>

    <section v-else class="rounded-2xl border border-slate-200 bg-white p-2 sm:p-4" :class="isRefreshing ? 'opacity-70 transition-opacity' : 'transition-opacity'">
      <div v-for="row in rows" :key="row.id" class="rounded-xl border border-slate-200 p-3 mb-2">
        <div class="text-sm font-medium text-slate-900">{{ row.name || 'Nhà cung cấp' }}</div>
        <div class="mt-1 text-sm text-slate-600">{{ row.phone || 'Không có SĐT' }}</div>
        <div class="mt-1 text-sm text-slate-600">{{ row.address || 'Không có địa chỉ' }}</div>
        <div class="mt-2 grid grid-cols-1 gap-1 text-sm sm:grid-cols-3">
          <div>Tổng nhập: <span class="font-medium text-slate-900">{{ formatMoney(row.total_amount) }}</span></div>
          <div>Đã trả: <span class="font-medium text-brand-700">{{ formatMoney(row.paid_amount) }}</span></div>
          <div>Còn nợ: <span class="font-medium" :class="Number(row.debt_amount || 0) > 0 ? 'text-violet-700' : 'text-slate-700'">{{ formatMoney(row.debt_amount) }}</span></div>
        </div>
      </div>
    </section>
  </section>
</template>
