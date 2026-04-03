<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useOrderDetail } from '../composables/useOrderDetail';
import { useToast } from '../../../shared/composables/useToast';

const route = useRoute();
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
  submitPayment,
  paymentLoading,
  paymentError,
  resetPayment,
  resetLoading,
  resetError
} = useOrderDetail();

const paymentAmount = ref('');
const paymentNote = ref('');
const paymentMethod = ref('cash');
const showPaymentForm = ref(false);

const orderId = computed(() => Number(route.params.id || 0));

const numberFormatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => `${numberFormatter.format(Number(amount || 0))} đ`;

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

const groupedLogs = computed(() => {
  const groups = [];
  const map = new Map();

  for (const log of logs.value) {
    const label = formatDateTime(log.created_at) || '-';
    if (!map.has(label)) {
      const group = { label, items: [] };
      map.set(label, group);
      groups.push(group);
    }
    map.get(label).items.push(log);
  }

  return groups;
});

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
    showPaymentForm.value = false;
    await loadOrder();
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
    toast.success(payload?.message || 'Đã đặt lại thanh toán.');
    await loadOrder();
  } catch (_err) {
    toast.error(resetError.value || 'Không thể đặt lại thanh toán.');
  }
};

watch(
  () => route.params.id,
  async () => {
    await loadOrder();
  }
);

onMounted(async () => {
    await loadOrder();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <RouterLink to="/orders" class="text-sm font-medium text-slate-500 hover:text-slate-700">Quay lại danh sách</RouterLink>
          <h1 class="mt-1 text-lg font-semibold text-slate-900">Đơn hàng #{{ order?.order_code || orderId }}</h1>
        </div>
        <div v-if="order" class="flex flex-wrap gap-2">
          <RouterLink :to="{ name: 'orders.invoice', params: { id: order.id } }" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">In hóa đơn</RouterLink>
          <RouterLink v-if="orderStatus !== 'completed' && orderStatus !== 'cancelled'" :to="{ name: 'orders.edit', params: { id: order.id } }" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">Sửa đơn</RouterLink>
          <RouterLink v-if="orderStatus !== 'cancelled'" :to="{ name: 'orders.return', params: { id: order.id } }" class="inline-flex h-10 items-center rounded-xl border border-rose-300 px-4 text-sm font-medium text-rose-600">Trả hàng</RouterLink>
        </div>
      </div>
    </header>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <div v-else-if="!order" class="app-empty-state">
      Không tìm thấy đơn hàng.
    </div>

    <template v-else>
      <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr),minmax(0,1fr)]">
        <div class="space-y-4">
          <section class="app-card">
            <div class="flex flex-wrap items-center gap-2 text-sm">
              <span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-0.5 font-medium text-sky-700">{{ formatDateTime(order.order_date) }}</span>
              <span class="inline-flex items-center rounded-lg px-2.5 py-0.5 font-medium" :class="isPaid ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">
                {{ isPaid ? 'Đã thanh toán' : 'Còn nợ' }}
              </span>
              <span
                class="inline-flex items-center rounded-lg px-2.5 py-0.5 font-medium"
                :class="orderStatus === 'completed' ? 'bg-brand-50 text-brand-700' : orderStatus === 'cancelled' ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700'"
              >
                {{ orderStatus === 'completed' ? 'Đã hoàn thành' : orderStatus === 'cancelled' ? 'Đã hủy' : 'Chưa hoàn thành' }}
              </span>
            </div>

            <div class="mt-4 space-y-3 text-sm">
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
          </section>

          <section class="app-card">
            <h2 class="text-sm font-medium text-slate-800">Khách hàng</h2>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
              <div class="flex gap-2">
                <span class="min-w-20 text-slate-500">Khách hàng:</span>
                <div class="inline-flex items-center gap-2">
                  <span class="font-medium text-slate-900">{{ customerName }}</span>
                  <RouterLink v-if="order.customer_id" :to="{ name: 'customers.detail', params: { id: order.customer_id } }" class="text-sm font-medium text-brand-700 hover:text-brand-800">Xem khách hàng</RouterLink>
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
          </section>

          <section v-if="!items.length && !manualItems.length" class="app-empty-state">
            Đơn hàng không có mặt hàng nào.
          </section>

          <section v-if="items.length" class="rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Sản phẩm</div>
            <div class="divide-y divide-slate-100">
              <div v-for="item in items" :key="`item-${item.id}`" class="flex items-start gap-3 px-4 py-3 text-sm">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                      <div class="truncate font-medium text-slate-900">{{ item.product_name }}</div>
                      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                        <span>SL {{ formatNumber(item.qty) }} {{ item.unit_name }}</span>
                        <span>Đơn giá {{ formatMoney(item.price_sell) }}</span>
                        <span :class="Number(item.qty || 0) * (Number(item.price_sell || 0) - Number(item.price_cost || 0)) >= 0 ? 'text-brand-600' : 'text-rose-600'">
                          LN {{ formatMoney(Number(item.qty || 0) * (Number(item.price_sell || 0) - Number(item.price_cost || 0))) }}
                        </span>
                      </div>
                    </div>
                    <div class="font-medium text-slate-900">{{ formatMoney(item.amount) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section v-if="manualItems.length" class="rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Sản phẩm khác</div>
            <div class="divide-y divide-slate-100">
              <div v-for="item in manualItems" :key="`manual-${item.id}`" class="flex items-start gap-3 px-4 py-3 text-sm">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                      <div class="truncate font-medium text-slate-900">{{ item.item_name }}</div>
                      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                        <span>SL {{ formatNumber(item.qty) }} {{ item.unit_name }}</span>
                        <span>Giá bán {{ formatMoney(item.price_sell) }}</span>
                        <span v-if="Number(item.price_buy || 0) > 0">Giá vốn {{ formatMoney(item.price_buy) }}</span>
                        <span :class="Number(item.amount_sell || 0) - Number(item.amount_buy || 0) >= 0 ? 'text-brand-600' : 'text-rose-600'">
                          LN {{ formatMoney(Number(item.amount_sell || 0) - Number(item.amount_buy || 0)) }}
                        </span>
                      </div>
                    </div>
                    <div class="font-medium text-slate-900">{{ formatMoney(item.amount_sell) }}</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section v-if="payments.length" class="rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Lịch sử thanh toán</div>
            <div class="divide-y divide-slate-100">
              <div v-for="payment in payments" :key="`payment-${payment.id}`" class="flex flex-col gap-1 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <div class="font-medium text-slate-900">{{ formatMoney(payment.amount) }}</div>
                  <div v-if="payment.note" class="mt-1 text-slate-600 whitespace-pre-line">{{ payment.note }}</div>
                </div>
                <div class="text-slate-500">{{ formatDateTime(payment.paid_at) }}</div>
              </div>
            </div>
          </section>

          <section v-if="groupedLogs.length" class="rounded-2xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Lịch sử thay đổi</div>
            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto text-sm">
              <div v-for="group in groupedLogs" :key="group.label" class="px-4 py-3">
                <div class="mb-2 inline-flex rounded-lg bg-slate-50 px-2 py-0.5 text-sm font-medium text-slate-600">{{ group.label }}</div>
                <div class="space-y-1.5">
                  <div v-for="log in group.items" :key="log.id" :class="parseLogLine(log.detail).tone">
                    {{ parseLogLine(log.detail).text }}
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>

        <aside class="space-y-4">
          <section class="app-card">
            <div class="flex items-center justify-between gap-2">
              <h2 class="text-sm font-medium text-slate-800">Thanh toán</h2>
              <button
                v-if="remainingAmount > 0 && orderStatus !== 'cancelled'"
                type="button"
                class="text-sm font-medium text-brand-700 hover:text-brand-800"
                @click="showPaymentForm = !showPaymentForm"
              >
                {{ showPaymentForm ? 'Ẩn form' : 'Thu tiền' }}
              </button>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
              <div class="rounded-md bg-slate-50 px-3 py-2">
                <div class="text-slate-500">Tổng tiền</div>
                <div class="mt-1 font-medium text-slate-900">{{ formatMoney(order.total_amount) }}</div>
              </div>
              <div class="rounded-md bg-brand-50 px-3 py-2">
                <div class="text-brand-700">Đã thu</div>
                <div class="mt-1 font-medium text-brand-700">{{ formatMoney(order.paid_amount) }}</div>
              </div>
              <div class="rounded-md bg-amber-50 px-3 py-2">
                <div class="text-amber-700">Còn nợ</div>
                <div class="mt-1 font-medium text-amber-700">{{ formatMoney(remainingAmount) }}</div>
              </div>
            </div>

            <form v-if="showPaymentForm && remainingAmount > 0 && orderStatus !== 'cancelled'" class="mt-4 space-y-3" @submit.prevent="submitPaymentForm">
              <label class="block space-y-1 text-sm text-slate-700">
                <span>Số tiền thu</span>
                <div class="relative">
                  <input
                    v-model="paymentAmount"
                    type="text"
                    inputmode="numeric"
                    class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500"
                    placeholder="Nhập số tiền"
                  />
                  <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                </div>
              </label>
              <label class="block space-y-1 text-sm text-slate-700">
                <span>Phương thức</span>
                <div class="relative">
                  <select v-model="paymentMethod" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                    <option value="cash">Tiền mặt</option>
                    <option value="bank">Chuyển khoản</option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                  </div>
                </div>
              </label>
              <label class="block space-y-1 text-sm text-slate-700">
                <span>Ghi chú</span>
                <textarea v-model="paymentNote" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500" placeholder="Ghi chú thanh toán"></textarea>
              </label>
              <button type="submit" class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="paymentLoading">
                Ghi nhận thanh toán
              </button>
            </form>

            <button
              v-if="order.status === 'paid' && orderStatus !== 'cancelled'"
              type="button"
              class="mt-4 inline-flex h-10 items-center rounded-xl border border-amber-300 px-4 text-sm font-medium text-amber-700 disabled:opacity-50"
              :disabled="resetLoading"
              @click="resetPaymentState"
            >
              Đặt lại thanh toán
            </button>
          </section>
        </aside>
      </div>
    </template>
  </section>
</template>