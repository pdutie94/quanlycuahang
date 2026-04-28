<script setup lang="ts">
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();
import { onMounted, ref, computed } from 'vue';
// Tổng hợp số liệu cho 3 box đầu trang
const summary = computed(() => {
  let total_amount = 0;
  let total_paid = 0;
  let total_debt = 0;
  for (const p of purchases.value) {
    total_amount += Number(p.total_amount || 0);
    total_paid += Number(p.paid_amount || 0);
  }
  total_debt = total_amount - total_paid;
  return { total_amount, total_paid, total_debt };
});
import SupplierPurchaseItemList from '../components/SupplierPurchaseItemList.vue';
import { Pencil, Trash2, ReceiptText } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useSupplierDetail } from '../composables/useSupplierDetail';
import { useToast } from '../../../shared/composables/useToast';
import { fetchSupplierPayments, paySupplierDebt } from '../services/supplier.api';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';
import PaymentModal from '../../../shared/components/PaymentModal.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { supplier, purchases, totalDebt, loading, error, load, remove, deleteLoading, deleteError } = useSupplierDetail();
const showDeleteModal = ref(false);
const showPaymentModal = ref(false);
const paymentLoading = ref(false);
const paymentAmount = ref('');
const paymentMethod = ref('cash');
const paymentNote = ref('');
const paymentHistory = ref<Record<string, any>[]>([]);
const paymentHistoryLoading = ref(false);

// Đã thay thế bằng useFormat

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
    await loadPaymentHistory();
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải chi tiết nhà cung cấp.');
  }
};

const loadPaymentHistory = async () => {
  const supplierId = String(route.params.id || '');
  if (!supplierId) return;
  paymentHistoryLoading.value = true;
  try {
    const response = await fetchSupplierPayments(supplierId);
    paymentHistory.value = response?.data?.payments || [];
  } catch (_err: any) {
    console.error('Failed to load payment history', _err);
  } finally {
    paymentHistoryLoading.value = false;
  }
};

const submitPayment = async () => {
  if (!supplier.value?.id) return;
  paymentLoading.value = true;
  try {
    await paySupplierDebt(supplier.value.id, {
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

const deleteCurrentSupplier = async () => {
  if (!supplier.value?.id) {
    return;
  }

  try {
    const payload = await remove(supplier.value.id);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa nhà cung cấp.');
    router.push('/suppliers');
  } catch (_err: any) {
    toast.error(deleteError.value || 'Không thể xóa nhà cung cấp.');
  }
};

onMounted(async () => {
  await loadPage();
});

</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="supplier ? `Nhà cung cấp ${supplier.name}` : 'Chi tiết nhà cung cấp'" back-to="/suppliers">
      <template #actions="{ closeMenu }">
        <template v-if="supplier">
          <button
            v-if="Number(summary.total_debt || 0) > 0"
            type="button"
            class="detail-header-menu-item detail-header-menu-item-amber"
            @click="closeMenu(); showPaymentModal = true"
          >
            <ReceiptText class="h-4 w-4 shrink-0" />
            <span>Thanh toán</span>
          </button>
          <RouterLink :to="{ name: 'suppliers.edit', params: { id: supplier.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Chỉnh sửa</span></RouterLink>
          <button type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa nhà cung cấp</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <PaymentModal
      v-model:open="showPaymentModal"
      title="Thanh toán công nợ nhà cung cấp"
      :stat-total="summary.total_amount"
      :stat-paid="summary.total_paid"
      :stat-debt="summary.total_debt"
      stat-paid-label="Đã thanh toán"
      submit-label="Ghi nhận thanh toán"
      :loading="paymentLoading"
      v-model:amount="paymentAmount"
      v-model:payment-method="paymentMethod"
      v-model:note="paymentNote"
      @submit="submitPayment"
      @close="showPaymentModal = false"
    />

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa nhà cung cấp"
      description="Bạn chắc chắn muốn xóa nhà cung cấp này?"
      confirm-label="Xóa nhà cung cấp"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentSupplier"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!supplier" class="app-empty-state">Không tìm thấy nhà cung cấp.</div>
    <template v-else>
      <section class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3">
        <div class="text-base font-semibold text-slate-800">{{ supplier.name }}</div>
        <div class="mt-1 text-sm text-slate-600">
          <span v-if="supplier.phone" class="mr-4">SĐT: {{ supplier.phone }}</span>
          <span v-if="supplier.address">Địa chỉ: {{ supplier.address }}</span>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Tổng nợ</div>
            <div class="mt-1 font-medium text-slate-900">{{ formatMoney(summary.total_amount) }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Thanh toán</div>
            <div class="mt-1 font-medium text-brand-700">{{ formatMoney(summary.total_paid) }}</div>
          </div>
          <div class="rounded-md border border-slate-200 bg-slate-50 px-3 py-2">
            <div class="text-slate-500">Còn nợ</div>
            <div class="mt-1 font-medium" :class="Number(summary.total_debt || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(summary.total_debt) }}</div>
          </div>
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
              <div class="text-slate-400">{{ formatDateTime(item.paid_at) }}</div>
              <div v-if="item.note" class="mt-0.5 truncate text-slate-500">{{ item.note }}</div>
            </div>
            <div class="shrink-0 text-sm font-semibold text-emerald-600">+{{ formatMoney(item.amount) }}</div>
          </div>
        </div>
      </section>

      <SupplierPurchaseItemList :purchases="purchases" />
    </template>
  </section>
</template>
