<script setup>
import { onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useReportOverview } from '../composables/useReportOverview';

const toast = useToast();
const { overview, loading, error, load } = useReportOverview();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;

onMounted(async () => {
  try {
    await load();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo tổng quan.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo tổng quan</h1>
      <div class="mt-3 flex flex-wrap gap-2">
        <RouterLink to="/reports/customer-debt" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700">Công nợ khách hàng</RouterLink>
        <RouterLink to="/reports/supplier-debt" class="inline-flex h-9 items-center rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700">Công nợ nhà cung cấp</RouterLink>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <section v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <article class="rounded-2xl border border-slate-200 bg-white p-3">
        <div class="text-sm text-slate-500">Doanh thu tháng này</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_month?.total_amount) }}</div>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-3">
        <div class="text-sm text-slate-500">Lợi nhuận tháng này</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">{{ formatMoney(overview.orders_month?.profit) }}</div>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-3">
        <div class="text-sm text-slate-500">Nhập hàng tháng này</div>
        <div class="mt-1 text-lg font-semibold text-slate-900">{{ formatMoney(overview.purchases_month?.total_amount) }}</div>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-3">
        <div class="text-sm text-slate-500">Nợ khách hàng</div>
        <div class="mt-1 text-lg font-semibold text-rose-700">{{ formatMoney(overview.customer_debt) }}</div>
      </article>
      <article class="rounded-2xl border border-slate-200 bg-white p-3">
        <div class="text-sm text-slate-500">Nợ nhà cung cấp</div>
        <div class="mt-1 text-lg font-semibold text-violet-700">{{ formatMoney(overview.supplier_debt) }}</div>
      </article>
    </section>
  </section>
</template>
