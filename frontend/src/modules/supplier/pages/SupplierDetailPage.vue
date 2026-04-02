<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useSupplierDetail } from '../composables/useSupplierDetail';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
const toast = useToast();
const { supplier, purchases, totalDebt, loading, error, load } = useSupplierDetail();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;
const formatDateTime = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
};

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết nhà cung cấp.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <RouterLink to="/suppliers" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại danh sách</RouterLink>
          <h1 class="mt-1 text-lg font-semibold text-slate-900">Nhà cung cấp {{ supplier?.name || '' }}</h1>
        </div>
        <div v-if="supplier" class="flex gap-2">
          <RouterLink :to="{ name: 'suppliers.edit', params: { id: supplier.id } }" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">Chỉnh sửa</RouterLink>
        </div>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!supplier" class="app-empty-state">Không tìm thấy nhà cung cấp.</div>
    <template v-else>
      <section class="app-card">
        <div class="text-sm font-medium text-slate-800">{{ supplier.name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="supplier.phone" class="mr-4">SĐT: {{ supplier.phone }}</span>
          <span v-if="supplier.address">Địa chỉ: {{ supplier.address }}</span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Tổng công nợ</div><div class="mt-1 font-medium" :class="Number(totalDebt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(totalDebt) }}</div></div>
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Số phiếu nhập</div><div class="mt-1 font-medium text-slate-900">{{ purchases.length }}</div></div>
        </div>
      </section>

      <section v-if="!purchases.length" class="app-empty-state">Nhà cung cấp chưa có phiếu nhập nào.</section>

      <section v-else class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Phiếu nhập</div>
        <div v-for="purchase in purchases" :key="purchase.id" class="app-card">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="text-sm font-mono font-medium text-brand-700">#{{ purchase.purchase_code }}</div>
            <div class="inline-flex items-center rounded-lg px-3 py-0.5 text-xs font-medium" :class="Number(purchase.debt_amount || 0) > 0 ? 'bg-rose-50 text-rose-700' : 'bg-brand-50 text-brand-700'">
              {{ Number(purchase.debt_amount || 0) > 0 ? 'Còn nợ' : 'Đã thanh toán' }}
            </div>
          </div>
          <div class="mt-2 text-sm text-slate-500">{{ formatDateTime(purchase.purchase_date) }}</div>
          <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-600">
            <span>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(purchase.total_amount) }}</span></span>
            <span>Đã trả: <span class="font-medium text-brand-600">{{ formatMoney(purchase.paid_amount) }}</span></span>
            <span>Còn nợ: <span class="font-medium" :class="Number(purchase.debt_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(purchase.debt_amount) }}</span></span>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>
