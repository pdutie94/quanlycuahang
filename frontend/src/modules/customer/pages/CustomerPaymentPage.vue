<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCustomerPayment } from '../composables/useCustomerPayment';
import { useToast } from '../../../shared/composables/useToast';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const { order, remaining, loading, error, load, submit, submitLoading, submitError } = useCustomerPayment();
const amount = ref('');
const note = ref('');

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();

const loadPage = async () => {
  try {
    await load(Number(route.params.orderId || 0));
    amount.value = String(Math.round(Number(remaining.value || 0)));
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải thông tin thu tiền.');
  }
};

const save = async () => {
  try {
    const payload = await submit(Number(route.params.orderId || 0), { amount: amount.value, note: note.value });
    toast.success(payload?.message || 'Đã ghi nhận thanh toán.');
    if (order.value?.customer_id) {
      await router.push({ name: 'customers.detail', params: { id: order.value.customer_id } });
      return;
    }
    await router.push('/customers');
  } catch (_err: any) {
    toast.error(submitError.value || 'Không thể ghi nhận thanh toán.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar title="Thu tiền khách hàng" :back-to="order?.customer_id ? { name: 'customers.detail', params: { id: order.customer_id } } : '/customers'" />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <template v-else-if="order">
      <section class="app-card">
        <div class="text-sm font-medium text-slate-800">{{ order.customer_name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="order.customer_phone" class="mr-4">SĐT: {{ order.customer_phone }}</span>
          <span v-if="order.customer_address">Địa chỉ: {{ order.customer_address }}</span>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
          <div><div class="text-slate-500">Mã đơn</div><div class="font-mono text-slate-800">{{ order.order_code }}</div></div>
          <div><div class="text-slate-500">Tổng tiền</div><div class="font-medium text-slate-800">{{ formatMoney(order.total_amount) }}</div></div>
          <div><div class="text-slate-500">Còn nợ</div><div class="font-medium text-rose-600">{{ formatMoney(remaining) }}</div></div>
        </div>
      </section>

      <section class="app-card space-y-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Số tiền thu</label>
          <div class="relative">
            <input v-model="amount" type="text"  v-money-input class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Ghi chú</label>
          <textarea v-model="note" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
        </div>
        <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="submitLoading" @click="save">Ghi nhận thanh toán</button>
      </section>
    </template>
  </section>
</template>