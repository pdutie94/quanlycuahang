<script setup>
import { onMounted } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useCustomerDetail } from '../composables/useCustomerDetail';
import { useToast } from '../../../shared/composables/useToast';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const route = useRoute();
const toast = useToast();
const { customer, orders, summary, loading, error, load } = useCustomerDetail();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết khách hàng.');
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
          <RouterLink to="/customers" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại danh sách</RouterLink>
          <h1 class="mt-1 text-lg font-semibold text-slate-900">Khách hàng {{ customer?.name || '' }}</h1>
        </div>
        <div v-if="customer" class="flex gap-2">
          <RouterLink :to="{ name: 'customers.edit', params: { id: customer.id } }" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">Chỉnh sửa</RouterLink>
        </div>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!customer" class="app-empty-state">Không tìm thấy khách hàng.</div>
    <template v-else>
      <section class="app-card">
        <div class="text-sm font-medium text-slate-800">{{ customer.name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="customer.phone" class="mr-4">SĐT: {{ customer.phone }}</span>
          <span v-if="customer.address">Địa chỉ: {{ customer.address }}</span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Tổng tiền</div><div class="mt-1 font-medium text-slate-900">{{ formatMoney(summary.total_amount) }}</div></div>
          <div class="rounded-md bg-brand-50 px-3 py-2"><div class="text-brand-600">Đã thu</div><div class="mt-1 font-medium text-brand-700">{{ formatMoney(summary.total_paid) }}</div></div>
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Còn nợ</div><div class="mt-1 font-medium" :class="Number(summary.total_debt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(summary.total_debt) }}</div></div>
        </div>
      </section>

      <section v-if="!orders.length" class="app-empty-state">Khách hàng chưa có đơn hàng nào.</section>

      <section v-else class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Đơn hàng</div>
        <OrderItemCard v-for="order in orders" :key="order.id" :order="order" :to="{ name: 'orders.detail', params: { id: order.id } }" :link-enabled="false" customer-fallback="Khách lẻ">
          <template #actions>
            <RouterLink v-if="order.order_status !== 'cancelled' && Number(order.total_amount || 0) - Number(order.paid_amount || 0) > 0" :to="{ name: 'customers.payment', params: { orderId: order.id } }" class="inline-flex h-8 items-center rounded-lg border border-brand-600 bg-brand-600 px-3 text-xs font-medium text-white">Thu tiền</RouterLink>
            <RouterLink :to="{ name: 'orders.detail', params: { id: order.id } }" class="inline-flex h-8 items-center rounded-lg border border-slate-300 px-3 text-xs font-medium text-slate-700">Xem đơn</RouterLink>
          </template>
        </OrderItemCard>
      </section>
    </template>
  </section>
</template>