<script setup lang="ts">
import { onMounted, ref, computed } from 'vue';
import { Pencil, ReceiptText, Trash2 } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useCustomerDetail } from '../composables/useCustomerDetail';
import { useToast } from '../../../shared/composables/useToast';
import { fetchCustomerPayments, submitCustomerDebtPayment } from '../services/customer.api';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';
import CustomerOrderItemList from '../components/CustomerOrderItemList.vue';
import PaymentModal from '../../../shared/components/PaymentModal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { customer, orders, summary, paymentHistory, loading, error, load, remove, deleteLoading, deleteError } = useCustomerDetail();
const showDeleteModal = ref(false);
const showPaymentModal = ref(false);
const paymentLoading = ref(false);
const paymentAmount = ref('');
const paymentMethod = ref('cash');
const paymentNote = ref('');
const paymentHistoryLoading = ref(false);

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();

// Tổng hợp số liệu cho 3 box đầu trang
const computedSummary = computed(() => {
  let total_amount = 0;
  let total_paid = 0;
  let total_debt = 0;
  for (const o of orders.value) {
    total_amount += Number(o.total_amount || 0);
    total_paid += Number(o.paid_amount || 0);
  }
  total_debt = total_amount - total_paid;
  return { total_amount, total_paid, total_debt };
});

// Get payment method text from note
function getPaymentMethod(note: string): string {
  if (!note) return 'Tiền mặt';
  if (note.includes('[TT:cash]')) return 'Tiền mặt';
  if (note.includes('[TT:bank]')) return 'Chuyển khoản';
  return 'Tiền mặt';
}

// Clean note by removing [TT:...] tags
function cleanNote(note: string): string {
  if (!note) return '';
  return note.replace(/\[TT:cash\]/g, '').replace(/\[TT:bank\]/g, '').trim();
}

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
    await loadPaymentHistory();
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải chi tiết khách hàng.');
  }
};

const loadPaymentHistory = async () => {
  const customerId = String(route.params.id || '');
  if (!customerId) return;
  paymentHistoryLoading.value = true;
  try {
    const response = await fetchCustomerPayments(customerId);
    paymentHistory.value = response?.data?.payments || [];
  } catch (_err: any) {
    console.error('Failed to load payment history', _err);
  } finally {
    paymentHistoryLoading.value = false;
  }
};

const submitPayment = async () => {
  if (!customer.value?.id) return;
  paymentLoading.value = true;
  try {
    await submitCustomerDebtPayment(customer.value.id, {
      amount: paymentAmount.value,
      note: paymentNote.value,
      payment_method: paymentMethod.value,
    });
    toast.success('Đã ghi nhận thanh toán.');
    showPaymentModal.value = false;
    paymentAmount.value = '';
    paymentNote.value = '';
    paymentMethod.value = 'cash';
    await loadPage();
  } catch (err: any) {
    toast.error(err?.response?.data?.message || 'Không thể ghi nhận thanh toán.');
  } finally {
    paymentLoading.value = false;
  }
};

const deleteCurrentCustomer = async () => {
  if (!customer.value?.id) {
    return;
  }

  try {
    const payload = await remove(customer.value.id);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa khách hàng.');
    router.push('/customers');
  } catch (_err: any) {
    toast.error(deleteError.value || 'Không thể xóa khách hàng.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="customer ? `Khách hàng ${customer.name}` : 'Chi tiết khách hàng'" back-to="/customers">
      <template #actions="{ closeMenu }">
        <template v-if="customer">
          <button v-if="Number(computedSummary.total_debt || 0) > 0" type="button" class="detail-header-menu-item detail-header-menu-item-amber" @click="closeMenu(); showPaymentModal = true"><ReceiptText class="h-4 w-4 shrink-0" /><span>Thu tiền</span></button>
          <RouterLink :to="{ name: 'customers.edit', params: { id: customer.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Chỉnh sửa</span></RouterLink>
          <button type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa khách hàng</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa khách hàng"
      description="Xóa khách hàng sẽ chuyển các đơn hàng về khách lẻ. Bạn chắc chắn muốn xóa?"
      confirm-label="Xóa khách hàng"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentCustomer"
    />

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
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Tổng tiền</div><div class="mt-1 font-medium text-slate-900">{{ formatMoney(computedSummary.total_amount) }}</div></div>
          <div class="rounded-md bg-brand-50 px-3 py-2"><div class="text-brand-600">Đã thu</div><div class="mt-1 font-medium text-brand-700">{{ formatMoney(computedSummary.total_paid) }}</div></div>
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Còn nợ</div><div class="mt-1 font-medium" :class="Number(computedSummary.total_debt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(computedSummary.total_debt) }}</div></div>
        </div>
      </section>

      <section class="space-y-3">
        <div class="text-sm font-medium text-slate-600">Lịch sử thanh toán</div>
        <div v-if="!paymentHistory.length" class="app-empty-state">Chưa có lịch sử thanh toán.</div>
        <div v-else class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-white">
          <div
            v-for="item in paymentHistory"
            :key="item.id"
            class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-2 last:border-b-0"
          >
            <div class="min-w-0 flex-1 text-sm">
              <div class="text-slate-700">{{ formatDateTime(item.paid_at) }}</div>
              <div class="mt-0.5 truncate text-slate-500">{{ getPaymentMethod(item.note) }}{{ cleanNote(item.note) ? ' - ' + cleanNote(item.note) : '' }}</div>
            </div>
            <div class="shrink-0 text-sm font-semibold" :class="Number(item.amount) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
              {{ Number(item.amount) >= 0 ? '+' : '' }}{{ formatMoney(item.amount) }}
            </div>
          </div>
        </div>
      </section>

      <CustomerOrderItemList :orders="orders" />

      <PaymentModal
        :open="showPaymentModal"
        title="Thu tiền khách hàng"
        :stat-total="computedSummary.total_amount"
        :stat-paid="computedSummary.total_paid"
        :stat-debt="computedSummary.total_debt"
        v-model:amount="paymentAmount"
        v-model:payment-method="paymentMethod"
        v-model:note="paymentNote"
        :loading="paymentLoading"
        @submit="submitPayment"
        @close="showPaymentModal = false"
      />
    </template>
  </section>
</template>