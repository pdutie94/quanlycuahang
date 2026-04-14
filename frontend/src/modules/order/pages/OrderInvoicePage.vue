<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useOrderInvoice } from '../composables/useOrderInvoice';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
const toast = useToast();

const { order, items, loading, error, load } = useOrderInvoice();

const orderId = computed(() => Number(route.params.id || 0));

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime, parseAmount, formatNumber } = useFormat();
// formatQty giữ nguyên nếu không liên quan

const remaining = computed(() => {
  const total = Number(order.value?.total_amount || 0);
  const paid = Number(order.value?.paid_amount || 0);
  return Math.max(total - paid, 0);
});

const printInvoice = () => {
  window.print();
};

onMounted(async () => {
  if (orderId.value <= 0) {
    toast.error('Mã đơn hàng không hợp lệ.');
    return;
  }

  try {
    await load(orderId.value);
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải dữ liệu hóa đơn.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card print:hidden">
      <div class="flex items-center justify-between gap-3">
        <div>
          <RouterLink :to="{ name: 'orders.detail', params: { id: route.params.id } }" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại chi tiết</RouterLink>
          <h1 class="mt-1 text-lg font-semibold text-slate-900">Hóa đơn đơn hàng</h1>
        </div>
        <button type="button" class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white" @click="printInvoice">In hóa đơn</button>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải hóa đơn...</div>

    <article v-else-if="order" class="mx-auto w-full max-w-4xl rounded-2xl border border-slate-200 bg-white p-6">
      <div class="flex items-start justify-between gap-4 border-b border-dashed border-slate-200 pb-4">
        <div>
          <p class="text-sm uppercase text-slate-500">Hóa đơn bán hàng</p>
          <h2 class="mt-1 text-lg font-semibold text-slate-900">Đơn hàng #{{ order.order_code || order.id }}</h2>
          <p class="mt-1 text-sm text-slate-500">{{ order.order_date }}</p>
        </div>
        <div class="text-right text-sm text-slate-600">
          <div v-if="order.customer_name">{{ order.customer_name }}</div>
          <div v-if="order.customer_phone">{{ order.customer_phone }}</div>
          <div v-if="order.customer_address">{{ order.customer_address }}</div>
        </div>
      </div>

      <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full border-collapse text-sm">
          <thead class="bg-slate-50 text-slate-600">
            <tr>
              <th class="border-b border-slate-200 px-3 py-2 text-left">STT</th>
              <th class="border-b border-slate-200 px-3 py-2 text-left">Sản phẩm</th>
              <th class="border-b border-slate-200 px-3 py-2 text-left">ĐVT</th>
              <th class="border-b border-slate-200 px-3 py-2 text-right">SL</th>
              <th class="border-b border-slate-200 px-3 py-2 text-right">Đơn giá</th>
              <th class="border-b border-slate-200 px-3 py-2 text-right">Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in items" :key="row.id || index">
              <td class="border-b border-slate-100 px-3 py-2">{{ index + 1 }}</td>
              <td class="border-b border-slate-100 px-3 py-2">{{ row.product_name }}</td>
              <td class="border-b border-slate-100 px-3 py-2">{{ row.unit_name || '-' }}</td>
              <td class="border-b border-slate-100 px-3 py-2 text-right">{{ formatNumber(row.qty) }}</td>
              <td class="border-b border-slate-100 px-3 py-2 text-right">{{ formatMoney(row.price_sell) }}</td>
              <td class="border-b border-slate-100 px-3 py-2 text-right">{{ formatMoney(row.amount) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 space-y-1 text-right text-sm">
        <div class="flex items-center justify-end gap-3">
          <span class="text-slate-500">Tổng đơn:</span>
          <span class="min-w-28 font-medium text-slate-900">{{ formatMoney(order.total_amount) }}</span>
        </div>
        <div class="flex items-center justify-end gap-3">
          <span class="text-slate-500">Đã thu:</span>
          <span class="min-w-28 font-medium text-brand-700">{{ formatMoney(order.paid_amount) }}</span>
        </div>
        <div class="flex items-center justify-end gap-3">
          <span class="text-slate-500">Còn lại:</span>
          <span class="min-w-28 font-medium" :class="remaining > 0 ? 'text-rose-700' : 'text-slate-900'">{{ formatMoney(remaining) }}</span>
        </div>
      </div>
    </article>

    <div v-else class="app-empty-state">Không thể tải hóa đơn.</div>
  </section>
</template>
