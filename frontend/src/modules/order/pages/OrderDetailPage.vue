<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Archive, BanknoteArrowDown, ClipboardList, FileText, History, Package, Pencil, RotateCcw, Trash2, Undo2, Users } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useOrderDetail } from '../composables/useOrderDetail';
import { useToast } from '../../../shared/composables/useToast';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const {
  order,
  items,
  manualItems,
  payments,
  logs,
  loading,
  error,
  load,
  refresh,
  submitStatus,
  submitPayment,
  statusLoading,
  statusError,
  paymentLoading,
  paymentError,
  resetPayment,
  resetLoading,
  resetError,
  remove,
  deleteLoading,
  deleteError
} = useOrderDetail();

const paymentAmount = ref('');
const paymentNote = ref('');
const paymentMethod = ref('cash');
const selectedOrderStatus = ref('pending');
const showPaymentModal = ref(false);
const showResetPaymentModal = ref(false);
const showDeleteOrderModal = ref(false);

const orderId = computed(() => Number(route.params.id || 0));

const numberFormatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => `${numberFormatter.format(Number(amount || 0))} đ`;

const parseAmount = (value) => {
  if (value === null || value === undefined) {
    return 0;
  }

  const digits = String(value).replace(/[^0-9-]/g, '');
  if (!digits || digits === '-') {
    return 0;
  }

  return Number(digits);
};

const formatMoneyInput = (value, allowEmpty = true) => {
  const amount = parseAmount(value);
  if (amount <= 0) {
    return allowEmpty ? '' : '0';
  }

  return numberFormatter.format(amount);
};

const formatMoneyInputValue = (rawValue) => {
  const digits = String(rawValue ?? '').replace(/[^0-9]/g, '');
  if (!digits) {
    return '';
  }

  return numberFormatter.format(Number(digits));
};

const isDecimalShorthand = (value) => {
  const str = String(value ?? '').trim();
  const dotIdx = str.indexOf('.');
  if (dotIdx === -1) return false;
  if ((str.match(/\./g) || []).length !== 1) return false;
  const afterDot = str.slice(dotIdx + 1).replace(/[^0-9]/g, '');
  return afterDot.length < 3;
};

const sanitizeDecimalInput = (value) => {
  let result = '';
  let hasDot = false;

  for (const ch of String(value ?? '')) {
    if (ch >= '0' && ch <= '9') {
      result += ch;
    } else if (ch === '.' && !hasDot) {
      result += ch;
      hasDot = true;
    }
  }

  return result;
};

const onPaymentAmountInput = (event) => {
  const target = event?.target;
  const rawValue = target instanceof HTMLInputElement ? target.value : paymentAmount.value;

  if (isDecimalShorthand(rawValue)) {
    paymentAmount.value = sanitizeDecimalInput(rawValue);
    return;
  }

  paymentAmount.value = formatMoneyInputValue(rawValue);
};

const onPaymentAmountBlur = (event) => {
  const target = event?.target;
  const rawValue = String(target instanceof HTMLInputElement ? target.value : paymentAmount.value).trim();

  if (!rawValue) {
    return;
  }

  let finalNum;

  if (isDecimalShorthand(rawValue)) {
    const num = parseFloat(sanitizeDecimalInput(rawValue));
    if (!Number.isNaN(num) && num > 0) {
      finalNum = num < 1000 ? num * 1000 : num;
    }
  } else {
    const digits = rawValue.replace(/[^0-9]/g, '');
    if (digits) {
      const num = Number(digits);
      if (num > 0 && num < 1000) {
        finalNum = num * 1000;
      }
    }
  }

  if (finalNum !== undefined) {
    paymentAmount.value = numberFormatter.format(Math.round(finalNum));
  }
};

const formatNumber = (value) => {
  const nextValue = Number(value || 0);
  if (Number.isInteger(nextValue)) {
    return numberFormatter.format(nextValue);
  }

  return nextValue.toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
};

const formatDateTime = (value) => {
  if (!value) {
    return '';
  }

  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

const totalAmount = computed(() => Number(order.value?.total_amount || 0));
const totalCost = computed(() => Number(order.value?.total_cost || 0));
const discountAmount = computed(() => Math.max(Number(order.value?.discount_amount || 0), 0));
const surchargeAmount = computed(() => Math.max(Number(order.value?.surcharge_amount || 0), 0));
const grossAmount = computed(() => Math.max(totalAmount.value + discountAmount.value - surchargeAmount.value, 0));
const profitAmount = computed(() => totalAmount.value - totalCost.value);
const remainingAmount = computed(() => Math.max(totalAmount.value - Number(order.value?.paid_amount || 0), 0));
const isPaid = computed(() => order.value?.status === 'paid' || remainingAmount.value <= 0);
const orderStatus = computed(() => order.value?.order_status || 'pending');
const canCollectPayment = computed(() => remainingAmount.value > 0 && orderStatus.value !== 'cancelled');
const canResetPayment = computed(() => order.value?.status === 'paid' && orderStatus.value !== 'cancelled');
const canEditOrder = computed(() => orderStatus.value !== 'completed' && orderStatus.value !== 'cancelled');
const canReturnOrder = computed(() => orderStatus.value !== 'cancelled');
const canDeleteOrder = computed(() => orderStatus.value !== 'completed');
const customerName = computed(() => {
  const name = String(order.value?.customer_name || '').trim();
  return name || 'Khách lẻ';
});

const noteMeta = computed(() => {
  const rawNote = String(order.value?.note || '').trim();
  let note = rawNote;
  let paymentMethodText = '';

  if (note.endsWith('[TT:cash]')) {
    paymentMethodText = 'Tiền mặt';
    note = note.slice(0, -9).trim();
  } else if (note.endsWith('[TT:bank]')) {
    paymentMethodText = 'Chuyển khoản';
    note = note.slice(0, -9).trim();
  }

  return {
    note,
    paymentMethodText
  };
});

const historyDateFormatter = new Intl.DateTimeFormat('vi-VN', {
  hour: '2-digit',
  minute: '2-digit',
  day: '2-digit',
  month: '2-digit',
  year: 'numeric'
});

const formatHistoryDate = (value) => {
  if (!value) {
    return '';
  }

  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) {
    return String(value);
  }

  return historyDateFormatter.format(date).replace(/^([^,]+),\s*/, '$1, ');
};

const loadOrder = async () => {
  if (orderId.value <= 0) {
    toast.error('Mã đơn hàng không hợp lệ.');
    return;
  }

  try {
    await load(orderId.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết đơn hàng.');
  }
};

const refreshOrder = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    await refresh(orderId.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết đơn hàng.');
  }
};

const syncOrderUiState = () => {
  selectedOrderStatus.value = order.value?.order_status || 'pending';

  if (canCollectPayment.value) {
    paymentAmount.value = formatMoneyInput(
      Math.max(Number(order.value?.total_amount || 0) - Number(order.value?.paid_amount || 0), 0)
    );
  } else {
    paymentAmount.value = '';
  }
};

const openPaymentModal = () => {
  if (!canCollectPayment.value) {
    return;
  }

  paymentAmount.value = formatMoneyInput(remainingAmount.value);
  showPaymentModal.value = true;
};

const closePaymentModal = () => {
  showPaymentModal.value = false;
};

const submitStatusForm = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    const payload = await submitStatus(orderId.value, {
      order_status: selectedOrderStatus.value
    });
    toast.success(payload?.message || 'Đã cập nhật trạng thái đơn hàng.');
    await refreshOrder();
  } catch (_err) {
    toast.error(statusError.value || 'Không thể cập nhật trạng thái đơn hàng.');
  }
};

const parseLogLine = (detailRaw) => {
  if (!detailRaw) {
    return { text: '-', tone: 'text-slate-700' };
  }

  try {
    const detail = JSON.parse(detailRaw);
    if (!detail || typeof detail !== 'object' || !detail.type) {
      return { text: detailRaw, tone: 'text-slate-700' };
    }

    if (detail.type === 'add_items') {
      const count = Number(detail.items_count || 0);
      const amount = formatMoney(detail.total_amount || 0);
      return {
        text: `${detail.context === 'update' ? 'Tăng số lượng cho' : 'Thêm'} ${count} sản phẩm, + ${amount}`,
        tone: 'text-brand-700'
      };
    }

    if (detail.type === 'remove_items') {
      const count = Number(detail.items_count || 0);
      const amount = formatMoney(detail.total_amount || 0);
      return {
        text: `${detail.context === 'update' ? 'Giảm số lượng của' : 'Giảm'} ${count} sản phẩm, - ${amount}`,
        tone: 'text-rose-700'
      };
    }

    if (detail.type === 'return_items') {
      const count = Number(detail.items_count || 0);
      const totalReduce = formatMoney(detail.total_reduce_amount || 0);
      const refundAmount = Number(detail.refund_amount || 0);
      return {
        text: `Trả ${count} sản phẩm, - ${totalReduce}${refundAmount > 0 ? `, hoàn ${formatMoney(refundAmount)}` : ''}`,
        tone: 'text-rose-700'
      };
    }

    if (detail.type === 'payment_reset') {
      return {
        text: `Đặt lại thanh toán: đã thu ${formatMoney(detail.paid_before || 0)} -> ${formatMoney(detail.paid_after || 0)}${Number(detail.payments_count || 0) > 0 ? `, xóa ${Number(detail.payments_count || 0)} lần thanh toán` : ''}`,
        tone: 'text-amber-700'
      };
    }

    if (detail.type === 'update_status') {
      return { text: String(detail.text || '-'), tone: 'text-slate-700' };
    }

    if (detail.type === 'payment') {
      const methodText = detail.method_text ? ` (${detail.method_text})` : '';
      const remainingAfter = detail.remaining_after !== undefined && detail.remaining_after !== null
        ? `, còn nợ ${formatMoney(detail.remaining_after)}`
        : '';
      return {
        text: `Thu ${formatMoney(detail.amount || 0)}${methodText}${remainingAfter}`,
        tone: 'text-brand-700'
      };
    }

    return { text: detailRaw, tone: 'text-slate-700' };
  } catch (_err) {
    return { text: String(detailRaw), tone: 'text-slate-700' };
  }
};

const getHistoryTone = (log) => {
  const parsed = parseLogLine(log?.detail);
  const tone = String(parsed?.tone || 'text-slate-700');

  if (tone.includes('rose')) {
    return {
      dot: 'bg-rose-400',
      meta: 'text-rose-700',
      detail: 'text-slate-700',
      row: 'bg-rose-50/55'
    };
  }

  if (tone.includes('brand') || tone.includes('emerald')) {
    return {
      dot: 'bg-emerald-400',
      meta: 'text-emerald-700',
      detail: 'text-slate-700',
      row: 'bg-emerald-50/55'
    };
  }

  if (tone.includes('amber')) {
    return {
      dot: 'bg-amber-400',
      meta: 'text-amber-700',
      detail: 'text-slate-700',
      row: 'bg-amber-50/55'
    };
  }

  return {
    dot: 'bg-violet-400',
    meta: 'text-violet-700',
    detail: 'text-slate-700',
    row: 'bg-slate-50'
  };
};

const submitPaymentForm = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    const payload = await submitPayment(orderId.value, {
      amount: paymentAmount.value,
      note: paymentNote.value,
      payment_method: paymentMethod.value
    });
    toast.success(payload?.message || 'Đã ghi nhận thanh toán.');
    paymentAmount.value = '';
    paymentNote.value = '';
    paymentMethod.value = 'cash';
    showPaymentModal.value = false;
    await refreshOrder();
  } catch (_err) {
    toast.error(paymentError.value || 'Không thể ghi nhận thanh toán.');
  }
};

const resetPaymentState = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    const payload = await resetPayment(orderId.value);
    showResetPaymentModal.value = false;
    showPaymentModal.value = false;
    toast.success(payload?.message || 'Đã đặt lại thanh toán.');
    await refreshOrder();
  } catch (_err) {
    toast.error(resetError.value || 'Không thể đặt lại thanh toán.');
  }
};

const deleteCurrentOrder = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    const payload = await remove(orderId.value);
    showDeleteOrderModal.value = false;
    toast.success(payload?.message || 'Đã xóa tạm đơn hàng.');
    router.push('/orders');
  } catch (_err) {
    toast.error(deleteError.value || 'Không thể xóa đơn hàng.');
  }
};

watch(
  () => route.params.id,
  async () => {
    await loadOrder();
  }
);

watch(order, () => {
  syncOrderUiState();
});

onMounted(async () => {
  await loadOrder();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="order ? `Đơn hàng #${order.order_code}` : `Đơn hàng #${orderId}`" back-to="/orders">
      <template #actions="{ closeMenu }">
        <template v-if="order">
          <RouterLink :to="{ name: 'orders.invoice', params: { id: order.id } }" class="detail-header-menu-item" @click="closeMenu"><FileText class="h-4 w-4 shrink-0" /><span>In hóa đơn</span></RouterLink>
          <button v-if="canCollectPayment" type="button" class="detail-header-menu-item" @click="closeMenu(); openPaymentModal()"><BanknoteArrowDown class="h-4 w-4 shrink-0" /><span>Thu tiền</span></button>
          <button v-if="canResetPayment" type="button" class="detail-header-menu-item detail-header-menu-item-amber" @click="closeMenu(); showResetPaymentModal = true"><RotateCcw class="h-4 w-4 shrink-0" /><span>Đặt lại thanh toán</span></button>
          <RouterLink v-if="canEditOrder" :to="{ name: 'orders.edit', params: { id: order.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Sửa đơn</span></RouterLink>
          <RouterLink v-if="canReturnOrder" :to="{ name: 'orders.return', params: { id: order.id } }" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu"><Undo2 class="h-4 w-4 shrink-0" /><span>Trả hàng</span></RouterLink>
          <button v-if="canDeleteOrder" type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteOrderModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa đơn hàng</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showResetPaymentModal"
      title="Đặt lại thanh toán"
      description="Đặt lại về chưa thanh toán và xóa toàn bộ lịch sử thu tiền của đơn này?"
      confirm-label="Đặt lại thanh toán"
      tone="warning"
      :loading="resetLoading"
      @cancel="showResetPaymentModal = false"
      @confirm="resetPaymentState"
    />

    <ActionConfirmSheet
      :open="showDeleteOrderModal"
      title="Xóa đơn hàng"
      description="Bạn có chắc chắn muốn xóa tạm đơn hàng này? Đơn sẽ được lưu 30 ngày trước khi xóa hẳn."
      confirm-label="Xóa đơn hàng"
      :loading="deleteLoading"
      @cancel="showDeleteOrderModal = false"
      @confirm="deleteCurrentOrder"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <div v-else-if="!order" class="app-empty-state">
      Không tìm thấy đơn hàng.
    </div>

    <template v-else>
      <div class="space-y-4">
        <section class="rounded-lg border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><ClipboardList class="h-4 w-4" /></span>
              <span class="text-sm font-medium text-slate-900 sm:text-sm">#{{ order.order_code }}</span>
            </div>
          </div>
          <div class="px-4 py-2">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600 sm:text-sm">
              <span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-0.5 text-sm font-medium text-sky-700">{{ formatDateTime(order.order_date) }}</span>
              <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-sm font-medium" :class="isPaid ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">
                {{ isPaid ? 'Đã thanh toán' : 'Còn nợ' }}
              </span>
              <span
                class="inline-flex items-center rounded-lg px-2.5 py-0.5 text-sm font-medium"
                :class="orderStatus === 'completed' ? 'bg-brand-50 text-brand-700' : orderStatus === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700'"
              >
                {{ orderStatus === 'completed' ? 'Đã hoàn thành' : orderStatus === 'cancelled' ? 'Đã hủy' : 'Chưa hoàn thành' }}
              </span>
            </div>

            <div class="mt-3 space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="font-medium uppercase text-slate-500">Tổng vốn</span>
                <span class="font-medium text-slate-900">{{ formatMoney(totalCost) }}</span>
              </div>

              <div class="space-y-1">
                <div class="flex items-center justify-between">
                  <span class="font-medium uppercase text-slate-500">Tổng bán (gốc)</span>
                  <span class="font-medium text-slate-900">{{ formatMoney(grossAmount) }}</span>
                </div>
                <div v-if="discountAmount > 0" class="flex items-center justify-between pl-2">
                  <span class="text-rose-600">Giảm giá</span>
                  <span class="font-medium text-rose-600">-{{ formatMoney(discountAmount) }}</span>
                </div>
                <div v-if="surchargeAmount > 0" class="flex items-center justify-between pl-2">
                  <span class="text-sky-600">Phụ thu</span>
                  <span class="font-medium text-sky-600">+{{ formatMoney(surchargeAmount) }}</span>
                </div>
              </div>

              <div class="flex items-center justify-between">
                <span class="font-medium uppercase text-slate-500">Tổng bán thực tế</span>
                <span class="font-medium text-slate-900">{{ formatMoney(totalAmount) }}</span>
              </div>

              <div class="flex items-center justify-between rounded-md px-3 py-2" :class="profitAmount >= 0 ? 'bg-brand-50' : 'bg-rose-50'">
                <span class="font-medium uppercase" :class="profitAmount >= 0 ? 'text-brand-700' : 'text-rose-700'">Lợi nhuận</span>
                <span class="font-medium" :class="profitAmount >= 0 ? 'text-brand-700' : 'text-rose-700'">
                  {{ `${profitAmount >= 0 ? '+' : ''}${formatMoney(profitAmount)}` }}
                </span>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div class="rounded-md bg-slate-50 px-3 py-2">
                  <div class="text-sm font-medium uppercase text-slate-500">Đã thu</div>
                  <div class="mt-1 font-medium text-slate-900">{{ formatMoney(order.paid_amount) }}</div>
                </div>
                <div class="rounded-md bg-amber-50 px-3 py-2">
                  <div class="text-sm font-medium uppercase text-amber-700">Còn nợ</div>
                  <div class="mt-1 font-medium text-amber-700">{{ formatMoney(remainingAmount) }}</div>
                </div>
              </div>
            </div>

            <div class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-700">
              <form class="flex flex-wrap items-center gap-2" @submit.prevent="submitStatusForm">
                <span class="hidden text-slate-500 sm:block">Trạng thái:</span>
                <div class="relative">
                  <select v-model="selectedOrderStatus" class="form-field block w-full appearance-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-8 text-sm outline-none transition focus:border-brand-500">
                    <option value="pending">Chưa hoàn thành</option>
                    <option value="completed">Đã hoàn thành</option>
                    <option value="cancelled">Đã hủy</option>
                  </select>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                  </span>
                </div>
                <button type="submit" class="app-btn-primary gap-1.5" :disabled="statusLoading">
                  <span>Cập nhật</span>
                </button>
              </form>
            </div>
          </div>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><Users class="h-4 w-4" /></span>
              <span>Khách hàng</span>
            </div>
          </div>
          <div class="space-y-2 px-4 py-2 text-sm text-slate-700">
            <div class="flex flex-col gap-x-6 gap-y-1">
              <div class="flex gap-2">
                <span class="min-w-20 text-slate-500">Khách hàng:</span>
                <div class="inline-flex items-center gap-2">
                  <RouterLink v-if="order.customer_id" :to="{ name: 'customers.detail', params: { id: order.customer_id } }" class="font-medium text-brand-700 hover:text-brand-800">
                    {{ customerName }}
                  </RouterLink>
                  <span v-else class="font-medium text-slate-900">{{ customerName }}</span>
                </div>
              </div>
              <div v-if="order.customer_phone" class="flex gap-2">
                <span class="min-w-20 text-slate-500">SĐT:</span>
                <div class="inline-flex items-center gap-2">
                  <span class="font-medium text-slate-900">{{ order.customer_phone }}</span>
                  <a :href="`tel:${order.customer_phone}`" class="text-sm font-medium text-brand-700 hover:text-brand-800">Gọi</a>
                </div>
              </div>
              <div v-if="order.customer_address" class="flex gap-2">
                <span class="min-w-20 text-slate-500">Địa chỉ:</span>
                <span class="text-slate-900">{{ order.customer_address }}</span>
              </div>
              <div v-if="noteMeta.paymentMethodText" class="flex gap-2">
                <span class="min-w-20 text-slate-500">Thanh toán:</span>
                <span class="font-medium text-slate-900">{{ noteMeta.paymentMethodText }}</span>
              </div>
              <div v-if="noteMeta.note" class="rounded-md bg-brand-50 px-3 py-2 text-slate-700 whitespace-pre-line">{{ noteMeta.note }}</div>
            </div>
          </div>
        </section>

        <section v-if="!items.length && !manualItems.length" class="app-empty-state">
          Đơn hàng không có mặt hàng nào.
        </section>

        <section v-if="items.length" class="rounded-lg border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-50 text-slate-700"><Package class="h-4 w-4" /></span>
              <span>Sản phẩm</span>
            </div>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="item in items" :key="`item-${item.id}`" class="flex items-start gap-3 px-4 py-3 text-sm">
              <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">{{ item.product_name }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">SL</span>
                        <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }}</span>
                        <span class="text-slate-500">{{ item.unit_name }}</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">Giá</span>
                        <span class="font-medium text-slate-900">{{ formatMoney(item.price_sell) }}</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">Lãi</span>
                        <span :class="Number(item.qty || 0) * (Number(item.price_sell || 0) - Number(item.price_cost || 0)) >= 0 ? 'font-medium text-brand-600' : 'font-medium text-rose-600'">
                          {{ formatMoney(Number(item.qty || 0) * (Number(item.price_sell || 0) - Number(item.price_cost || 0))) }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="font-medium text-slate-900">{{ formatMoney(item.amount) }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section v-if="manualItems.length" class="rounded-lg border border-slate-200 bg-white">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><Archive class="h-4 w-4" /></span>
              <span>Sản phẩm khác</span>
            </div>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="item in manualItems" :key="`manual-${item.id}`" class="flex items-start gap-3 px-4 py-3 text-sm">
              <div class="min-w-0 flex-1">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">{{ item.item_name }}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">SL</span>
                        <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }}</span>
                        <span v-if="item.unit_name" class="text-slate-500">{{ item.unit_name }}</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">Giá</span>
                        <span class="font-medium text-slate-900">{{ formatMoney(item.price_sell) }}</span>
                      </div>
                      <div v-if="Number(item.price_buy || 0) > 0" class="flex items-center gap-1">
                        <span class="text-slate-500">Giá vốn</span>
                        <span class="font-medium text-slate-900">{{ formatMoney(item.price_buy) }}</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="text-slate-500">Lãi</span>
                        <span :class="Number(item.amount_sell || 0) - Number(item.amount_buy || 0) >= 0 ? 'font-medium text-brand-600' : 'font-medium text-rose-600'">
                          {{ formatMoney(Number(item.amount_sell || 0) - Number(item.amount_buy || 0)) }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="font-medium text-slate-900">{{ formatMoney(item.amount_sell) }}</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section v-if="payments.length" class="rounded-lg border border-slate-200 bg-white">
          <div class="border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800">Lịch sử thanh toán</div>
          <div class="divide-y divide-slate-100">
            <div v-for="payment in payments" :key="`payment-${payment.id}`" class="flex flex-col gap-x-2 gap-y-1 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-0.5 text-sm font-medium text-brand-700">
                    <span>Thanh toán</span>
                  </span>
                  <span class="font-medium text-slate-900">{{ formatMoney(payment.amount) }}</span>
                </div>
                <div v-if="payment.note" class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ payment.note }}</div>
              </div>
              <div class="flex flex-col gap-1 text-sm text-slate-500">
                <div>{{ formatDateTime(payment.paid_at) }}</div>
              </div>
            </div>
          </div>
        </section>

        <section v-if="logs.length" class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
          <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-violet-700">
              <History class="h-3.5 w-3.5" />
            </span>
            <span>Lịch sử thay đổi</span>
          </h2>
          <p class="text-sm text-slate-500">Nhật ký các lần thay đổi trạng thái, thanh toán và cập nhật mặt hàng của đơn.</p>
          <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/70">
            <div
              v-for="log in logs"
              :key="log.id"
              class="flex gap-3 border-b border-slate-200 px-3 py-2.5 text-sm last:border-b-0"
              :class="getHistoryTone(log).row"
            >
              <div class="pt-1">
                <span class="block h-2 w-2 rounded-full" :class="getHistoryTone(log).dot"></span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="text-xs font-medium tracking-wide" :class="getHistoryTone(log).meta">{{ formatHistoryDate(log.created_at) }}</div>
                <div class="mt-0.5 leading-5" :class="getHistoryTone(log).detail">{{ parseLogLine(log.detail).text }}</div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <Teleport to="body">
        <transition name="app-modal-fade-up">
          <div v-if="showPaymentModal && canCollectPayment" class="app-modal-overlay app-modal-open" @click.self="closePaymentModal">
            <div class="app-modal-sheet-sm">
              <div class="app-modal-header">
                <h2 class="app-modal-title">Thu tiền đơn hàng</h2>
                <button type="button" class="app-modal-close" @click="closePaymentModal">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
              </div>
              <form class="app-modal-body space-y-4" @submit.prevent="submitPaymentForm">
                <div class="grid grid-cols-3 gap-3 text-sm">
                  <div class="rounded-md bg-slate-50 px-3 py-2">
                    <div class="text-sm uppercase text-slate-500">Tổng tiền</div>
                    <div class="mt-1 font-medium text-slate-900">{{ formatMoney(order.total_amount) }}</div>
                  </div>
                  <div class="rounded-md bg-brand-50 px-3 py-2">
                    <div class="text-sm uppercase text-brand-600">Đã thu</div>
                    <div class="mt-1 font-medium text-brand-700">{{ formatMoney(order.paid_amount) }}</div>
                  </div>
                  <div class="rounded-md bg-slate-50 px-3 py-2">
                    <div class="text-sm uppercase text-slate-500">Còn nợ</div>
                    <div class="mt-1 font-medium text-red-600">{{ formatMoney(remainingAmount) }}</div>
                  </div>
                </div>

                <div class="space-y-1">
                  <label class="block text-sm text-slate-700">Hình thức thanh toán</label>
                  <div class="app-segment">
                    <button type="button" class="app-segment-item" :class="paymentMethod === 'cash' ? 'app-segment-item-active' : ''" @click="paymentMethod = 'cash'">
                      Tiền mặt
                    </button>
                    <button type="button" class="app-segment-item" :class="paymentMethod === 'bank' ? 'app-segment-item-active' : ''" @click="paymentMethod = 'bank'">
                      Chuyển khoản
                    </button>
                  </div>
                </div>

                <label class="block space-y-1 text-sm text-slate-700">
                  <span class="app-label">Số tiền thu</span>
                  <div class="relative">
                    <input v-model="paymentAmount" type="text" inputmode="numeric" class="app-input pr-9 text-right" placeholder="Nhập số tiền" @input="onPaymentAmountInput" @blur="onPaymentAmountBlur" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>

                <label class="block space-y-1 text-sm text-slate-700">
                  <span class="app-label">Ghi chú</span>
                  <textarea v-model="paymentNote" rows="2" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-500"></textarea>
                </label>

                <div class="app-modal-footer mt-2 border-t border-slate-100 px-0 py-0 pt-2">
                  <button type="button" class="app-btn-secondary" @click="closePaymentModal">Hủy</button>
                  <button type="submit" class="app-btn-primary" :disabled="paymentLoading">Xác nhận thu</button>
                </div>
              </form>
            </div>
          </div>
        </transition>
      </Teleport>
    </template>
  </section>
</template>