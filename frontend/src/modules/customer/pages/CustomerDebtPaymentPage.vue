<script setup>

import { onMounted, ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCustomerDebtPayment } from '../composables/useCustomerDebtPayment';
import { useToast } from '../../../shared/composables/useToast';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';
import { useFormat } from '../../../shared/composables/useFormat';
import { useDebtAllocation } from '../../../shared/composables/useDebtAllocation';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const { customer, orders, totalDebt, loading, error, load, submit, submitLoading, submitError } = useCustomerDebtPayment();
const amount = ref('');
const note = ref('');


const { formatMoney, formatDateTime, parseAmount } = useFormat();
const hasOutstandingDebt = computed(() => Number(totalDebt.value || 0) > 0);

// Sử dụng composable phân bổ công nợ
const {
  amountNumber,
  preview,
  previewTotal,
  unappliedAmount
} = useDebtAllocation({
  items: orders,
  amount,
  debtField: 'debt_amount',
  idField: 'id',
  codeField: 'order_code',
  dateField: 'order_date'
});


const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
    // Format số tiền ngay từ đầu
    const raw = Math.round(Number(totalDebt.value || 0));
    amount.value = raw > 0 ? formatMoney(raw).replace(' đ', '') : '';
  } catch (_err) {
    toast.error(error.value || 'Không thể tải thông tin thu tiền công nợ.');
  }
};

const save = async () => {
  try {
    const payload = await submit(Number(route.params.id || 0), { amount: amount.value, note: note.value });
    const allocatedOrders = Array.isArray(payload?.data?.allocations) ? payload.data.allocations.length : 0;
    toast.success(allocatedOrders > 0 ? `Đã ghi nhận thanh toán cho ${allocatedOrders} đơn.` : (payload?.message || 'Đã ghi nhận thanh toán công nợ.'));
    await router.push({ name: 'customers.detail', params: { id: route.params.id } });
  } catch (_err) {
    toast.error(submitError.value || 'Không thể ghi nhận thanh toán công nợ.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar
      title="Thu tiền công nợ"
      :back-to="{ name: 'customers.detail', params: { id: route.params.id } }"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <template v-else-if="customer">
      <section class="app-card space-y-3">
        <div>
          <div class="text-sm font-medium text-slate-900">{{ customer.name }}</div>
          <div class="mt-1 text-sm text-slate-600">
            <span v-if="customer.phone" class="mr-4">SĐT: {{ customer.phone }}</span>
            <span v-if="customer.address">Địa chỉ: {{ customer.address }}</span>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
          <div class="rounded-xl bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Tổng công nợ</div>
            <div class="mt-1 font-semibold" :class="hasOutstandingDebt ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(totalDebt) }}</div>
          </div>
          <div class="rounded-xl bg-brand-50 px-3 py-2">
            <div class="text-brand-700">Số tiền nhập</div>
            <div class="mt-1 font-semibold text-brand-700">{{ formatMoney(amountNumber) }}</div>
          </div>
          <div class="rounded-xl bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Sẽ phân bổ</div>
            <div class="mt-1 font-semibold text-slate-900">{{ formatMoney(previewTotal) }}</div>
          </div>
        </div>

        <div v-if="!hasOutstandingDebt" class="rounded-xl border border-dashed border-slate-300 px-3 py-3 text-sm text-slate-500">Khách hàng này hiện không còn đơn nào cần thu tiền.</div>
      </section>

      <section v-if="hasOutstandingDebt" class="app-card space-y-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Số tiền thu</label>
          <div class="relative">
            <input v-model="amount" type="text" v-money-input class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
          </div>
          <p class="mt-1 text-xs text-slate-500">Ví dụ nhập 5000000, hệ thống sẽ trừ lần lượt vào các đơn cũ nhất còn nợ.</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Ghi chú</label>
          <textarea v-model="note" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"></textarea>
        </div>

        <div class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
          <div class="flex items-center justify-between gap-3">
            <div>
              <div class="text-sm font-medium text-slate-800">Xem trước phân bổ</div>
              <div class="text-xs text-slate-500">Theo thứ tự từ đơn cũ đến mới.</div>
            </div>
            <div class="text-right text-xs text-slate-500">
              <div>Tổng phân bổ: <span class="font-medium text-slate-800">{{ formatMoney(previewTotal) }}</span></div>
              <div v-if="unappliedAmount > 0">Chưa dùng: <span class="font-medium text-amber-700">{{ formatMoney(unappliedAmount) }}</span></div>
            </div>
          </div>

          <div v-if="!preview.length" class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-500">
            Nhập số tiền cần thu để xem hệ thống sẽ phân bổ vào đơn nào.
          </div>

          <div v-else class="space-y-2">
            <article v-for="item in preview" :key="item.orderId" class="rounded-xl border border-slate-200 bg-white px-3 py-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900">{{ item.orderCode }}</div>
                  <div class="mt-1 text-xs text-slate-500">{{ formatDateTime(item.orderDate) }}</div>
                </div>
                <div class="rounded-lg bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700">Phân bổ {{ formatMoney(item.allocatedAmount) }}</div>
              </div>
              <div class="mt-2 grid grid-cols-1 gap-2 text-xs sm:grid-cols-3">
                <div>
                  <div class="text-slate-500">Nợ trước</div>
                  <div class="font-medium text-rose-600">{{ formatMoney(item.debtBefore) }}</div>
                </div>
                <div>
                  <div class="text-slate-500">Số tiền trừ</div>
                  <div class="font-medium text-brand-700">{{ formatMoney(item.allocatedAmount) }}</div>
                </div>
                <div>
                  <div class="text-slate-500">Nợ còn lại</div>
                  <div class="font-medium" :class="item.debtAfter > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(item.debtAfter) }}</div>
                </div>
              </div>
            </article>
          </div>
        </div>

        <button type="button" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="submitLoading || amountNumber <= 0 || !preview.length" @click="save">Ghi nhận thanh toán</button>
      </section>
    </template>

    <div v-else class="app-empty-state">{{ error || 'Không tìm thấy khách hàng.' }}</div>
  </section>
</template>