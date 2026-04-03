<script setup>
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { ClipboardList, Clock, Eye, X } from '@lucide/vue';
import { fetchOrderPreview } from '../../modules/order/services/order.api';

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  to: {
    type: [String, Object],
    default: null
  },
  linkEnabled: {
    type: Boolean,
    default: true
  },
  showViewIcon: {
    type: Boolean,
    default: true
  },
  dimCancelled: {
    type: Boolean,
    default: true
  },
  customerFallback: {
    type: String,
    default: 'Khách lẻ'
  }
});

const numberFormatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => `${numberFormatter.format(Number(amount || 0))} đ`;

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

const totalAmount = computed(() => Number(props.order?.total_amount || 0));
const paidAmount = computed(() => Number(props.order?.paid_amount || 0));
const totalCost = computed(() => Number(props.order?.total_cost || 0));
const debtAmount = computed(() => totalAmount.value - paidAmount.value);
const profitAmount = computed(() => totalAmount.value - totalCost.value);

const orderCode = computed(() => props.order?.order_code || props.order?.code || '');
const orderDateText = computed(() => formatDateTime(props.order?.order_date || props.order?.doc_date || ''));
const customerName = computed(() => {
  const value = String(props.order?.customer_name || '').trim();
  return value || props.customerFallback;
});

const isCancelled = computed(() => String(props.order?.order_status || '') === 'cancelled');
const isPaid = computed(() => String(props.order?.status || '') === 'paid' || debtAmount.value <= 0);

const badgeLabel = computed(() => {
  if (isCancelled.value) {
    return 'Đã hủy';
  }
  return isPaid.value ? 'Đã xong' : 'Còn nợ';
});

const badgeClass = computed(() => {
  if (isCancelled.value) {
    return 'bg-slate-100 text-slate-700';
  }
  return isPaid.value ? 'bg-brand-100 text-brand-800' : 'bg-rose-100 text-rose-800';
});

const rootTag = computed(() => (props.linkEnabled ? RouterLink : 'div'));
const rootTo = computed(() => {
  if (props.to) {
    return props.to;
  }
  return { name: 'orders.detail', params: { id: props.order?.id } };
});

const previewVisible = ref(false);
const previewLoading = ref(false);
const previewError = ref('');
const previewOrder = ref(null);
const previewItems = ref([]);
const previewManualItems = ref([]);
let activeRequestId = 0;

const previewPaymentStatusLabel = computed(() => {
  const order = previewOrder.value;
  if (!order) {
    return 'Chờ thanh toán';
  }

  const total = Number(order.total_amount || 0);
  const paid = Number(order.paid_amount || 0);
  const remaining = Math.max(total - paid, 0);
  const status = String(order.status || '');

  if (status === 'paid' || (total > 0 && paid >= total)) {
    return 'Đã thanh toán';
  }

  if (remaining > 0) {
    return 'Còn nợ';
  }

  return 'Chờ thanh toán';
});

const previewPaymentStatusClass = computed(() => {
  if (previewPaymentStatusLabel.value === 'Đã thanh toán') {
    return 'bg-brand-50 text-brand-700';
  }
  if (previewPaymentStatusLabel.value === 'Còn nợ') {
    return 'bg-rose-50 text-rose-700';
  }
  return 'bg-amber-50 text-amber-700';
});

const previewOrderStatusLabel = computed(() => {
  const status = String(previewOrder.value?.order_status || 'pending');
  if (status === 'cancelled') {
    return 'Đã hủy';
  }
  if (status === 'completed') {
    return 'Hoàn tất';
  }
  return 'Chờ xử lý';
});

const previewOrderStatusClass = computed(() => {
  const status = String(previewOrder.value?.order_status || 'pending');
  if (status === 'cancelled') {
    return 'bg-slate-100 text-slate-600';
  }
  if (status === 'completed') {
    return 'bg-sky-50 text-sky-700';
  }
  return 'bg-amber-50 text-amber-700';
});

const previewDiscountAmount = computed(() => Math.max(Number(previewOrder.value?.discount_amount || 0), 0));
const previewSurchargeAmount = computed(() => Math.max(Number(previewOrder.value?.surcharge_amount || 0), 0));
const previewTotalAmount = computed(() => Math.max(Number(previewOrder.value?.total_amount || 0), 0));
const previewPaidAmount = computed(() => Math.max(Number(previewOrder.value?.paid_amount || 0), 0));
const previewRemainingAmount = computed(() => Math.max(previewTotalAmount.value - previewPaidAmount.value, 0));
const previewBaseAmount = computed(() => {
  const amount = previewTotalAmount.value + previewDiscountAmount.value - previewSurchargeAmount.value;
  return amount > 0 ? amount : 0;
});

const previewNote = computed(() => {
  const raw = String(previewOrder.value?.note || previewOrder.value?.notes || '').trim();
  if (!raw) {
    return '';
  }

  const trimmed = raw.replace(/\s+$/, '');
  if (trimmed.endsWith('[TT:cash]') || trimmed.endsWith('[TT:bank]')) {
    return trimmed.slice(0, -9).trim();
  }
  return trimmed;
});

const toQtyText = (value) => {
  const n = Number(value || 0);
  const text = n.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  return text.replace(/,00$/, '');
};

const openPreview = async () => {
  const orderId = Number(props.order?.id || 0);
  if (orderId <= 0) {
    return;
  }

  previewVisible.value = true;
  previewLoading.value = true;
  previewError.value = '';
  previewOrder.value = null;
  previewItems.value = [];
  previewManualItems.value = [];

  activeRequestId += 1;
  const requestId = activeRequestId;
  const startedAt = Date.now();
  const minimumLoadingMs = 150;

  try {
    const payload = await fetchOrderPreview(orderId);
    const apply = () => {
      if (requestId !== activeRequestId) {
        return;
      }
      previewOrder.value = payload?.data?.order || null;
      previewItems.value = payload?.data?.items || [];
      previewManualItems.value = payload?.data?.manual_items || [];
      previewLoading.value = false;
    };

    const elapsed = Date.now() - startedAt;
    if (elapsed < minimumLoadingMs) {
      window.setTimeout(apply, minimumLoadingMs - elapsed);
    } else {
      apply();
    }
  } catch (error) {
    const applyError = () => {
      if (requestId !== activeRequestId) {
        return;
      }
      previewError.value = error?.response?.data?.message || 'Không thể hiển thị dữ liệu đơn hàng.';
      previewLoading.value = false;
    };

    const elapsed = Date.now() - startedAt;
    if (elapsed < minimumLoadingMs) {
      window.setTimeout(applyError, minimumLoadingMs - elapsed);
    } else {
      applyError();
    }
  }
};

const closePreview = () => {
  activeRequestId += 1;
  previewVisible.value = false;
};
</script>

<template>
  <component
    :is="rootTag"
    class="app-card relative block bg-white p-3 transition-colors hover:border-brand-200 hover:bg-brand-50"
    :class="dimCancelled && isCancelled ? 'opacity-60' : ''"
    v-bind="linkEnabled ? { to: rootTo } : {}"
  >
    <div class="absolute right-1 top-1 flex items-center gap-1">
      <slot name="actions" />
      <button v-if="showViewIcon" type="button" class="inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-brand-50 hover:text-brand-600" @click.stop.prevent="openPreview">
        <Eye class="h-5 w-5" />
      </button>
    </div>

    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-2">
        <div class="min-w-0 truncate text-sm font-semibold text-slate-900">{{ customerName }}</div>
        <span class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="badgeClass">{{ badgeLabel }}</span>
      </div>

      <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm leading-none text-slate-500">
        <span class="inline-flex items-center gap-1">
          <ClipboardList class="h-4 w-4 text-slate-400" />
          <span>{{ orderCode }}</span>
        </span>
        <span v-if="orderDateText" class="inline-flex items-center gap-1">
          <Clock class="h-4 w-4 text-slate-400" />
          <span>{{ orderDateText }}</span>
        </span>
      </div>

      <div class="mt-0.5 flex flex-wrap items-center gap-x-3 text-sm">
        <span class="text-slate-600">Tổng: <span class="font-semibold text-slate-900">{{ formatMoney(totalAmount) }}</span></span>
        <span v-if="isPaid" class="text-slate-600">
          Lãi:
          <span class="font-semibold" :class="profitAmount >= 0 ? 'text-brand-700' : 'text-rose-700'">{{ formatMoney(profitAmount) }}</span>
        </span>
        <span v-else class="text-slate-600">
          Nợ: <span class="font-semibold text-rose-700">{{ formatMoney(debtAmount) }}</span>
        </span>
      </div>
    </div>
  </component>

  <Teleport to="body">
    <transition name="app-modal-fade-up" appear>
      <div v-if="previewVisible" class="app-modal-overlay app-modal-open z-[99999]" @click.self="closePreview">
        <div class="app-modal-sheet">
      <div class="app-modal-header">
        <div>
          <div class="text-sm font-semibold text-slate-800">{{ orderCode }}<span v-if="customerName !== customerFallback"> - {{ customerName }}</span></div>
          <div class="text-xs text-slate-500">{{ orderDateText || '--' }}</div>
        </div>
        <button type="button" class="app-modal-close" aria-label="Đóng" @click="closePreview">
          <X class="h-4 w-4" />
        </button>
      </div>

        <div class="app-modal-body text-sm">
          <div v-if="previewLoading" class="h-full min-h-full animate-pulse" aria-hidden="true">
            <div class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <div class="h-6 w-24 rounded-lg bg-brand-100"></div>
                <div class="h-6 w-20 rounded-lg bg-amber-100"></div>
              </div>

              <div class="rounded-lg bg-white">
                <div class="space-y-2">
                  <div class="flex items-center justify-between gap-3">
                    <div class="h-3 w-16 rounded bg-slate-200"></div>
                    <div class="h-4 w-20 rounded bg-slate-300"></div>
                  </div>
                  <div class="flex items-center justify-between gap-3">
                    <div class="h-3 w-14 rounded bg-slate-200"></div>
                    <div class="h-4 w-16 rounded bg-brand-100"></div>
                  </div>
                  <div class="flex items-center justify-between gap-3">
                    <div class="h-3 w-12 rounded bg-slate-200"></div>
                    <div class="h-4 w-16 rounded bg-amber-100"></div>
                  </div>
                </div>
                <div class="my-2 border-t border-dashed border-slate-200"></div>
                <div class="flex items-center justify-between gap-3">
                  <div class="h-4 w-20 rounded bg-slate-300"></div>
                  <div class="h-5 w-24 rounded bg-slate-300"></div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
                  <div class="h-3 w-16 rounded bg-slate-200"></div>
                  <div class="mt-2 h-5 w-20 rounded bg-brand-100"></div>
                </div>
                <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
                  <div class="h-3 w-12 rounded bg-slate-200"></div>
                  <div class="mt-2 h-5 w-16 rounded bg-slate-300"></div>
                </div>
              </div>

              <div class="rounded-lg border border-slate-200 bg-white">
                <div class="border-b border-slate-100 px-3 py-2">
                  <div class="h-4 w-20 rounded bg-slate-300"></div>
                </div>
                <div class="divide-y divide-slate-100">
                  <div class="flex items-center justify-between px-3 py-2">
                    <div class="space-y-2">
                      <div class="h-4 w-36 rounded bg-slate-300"></div>
                      <div class="h-3 w-44 rounded bg-slate-200"></div>
                    </div>
                    <div class="h-4 w-16 rounded bg-slate-300"></div>
                  </div>
                  <div class="flex items-center justify-between px-3 py-2">
                    <div class="space-y-2">
                      <div class="h-4 w-32 rounded bg-slate-300"></div>
                      <div class="h-3 w-40 rounded bg-slate-200"></div>
                    </div>
                    <div class="h-4 w-14 rounded bg-slate-300"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        <div v-else-if="previewError" class="py-6 text-center text-rose-600">{{ previewError }}</div>

        <div v-else-if="previewOrder" class="space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="previewPaymentStatusClass">{{ previewPaymentStatusLabel }}</span>
            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="previewOrderStatusClass">{{ previewOrderStatusLabel }}</span>
          </div>

          <div class="rounded-lg bg-white">
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Tạm tính</span>
                <span class="font-medium text-slate-900">{{ formatMoney(previewBaseAmount) }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Giảm giá</span>
                <span class="font-medium" :class="previewDiscountAmount > 0 ? 'text-brand-700' : 'text-slate-400'">{{ previewDiscountAmount > 0 ? `-${formatMoney(previewDiscountAmount)}` : formatMoney(0) }}</span>
              </div>
              <div class="flex items-center justify-between gap-3">
                <span class="text-slate-500">Phụ thu</span>
                <span class="font-medium" :class="previewSurchargeAmount > 0 ? 'text-amber-700' : 'text-slate-400'">{{ previewSurchargeAmount > 0 ? `+${formatMoney(previewSurchargeAmount)}` : formatMoney(0) }}</span>
              </div>
            </div>
            <div class="my-2 border-t border-dashed border-slate-200"></div>
            <div class="flex items-center justify-between gap-3">
              <span class="font-medium text-slate-700">Tổng cộng</span>
              <span class="text-sm font-semibold text-slate-900 md:text-base">{{ formatMoney(previewTotalAmount) }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
              <div class="text-sm text-slate-500">Đã thanh toán</div>
              <div class="mt-0.5 font-semibold text-brand-700">{{ formatMoney(previewPaidAmount) }}</div>
            </div>
            <div class="rounded-lg bg-white px-2.5 py-2 ring-1 ring-slate-200">
              <div class="text-sm text-slate-500">Còn nợ</div>
              <div class="mt-0.5 font-semibold" :class="previewRemainingAmount > 0 ? 'text-rose-700' : 'text-slate-700'">{{ formatMoney(previewRemainingAmount) }}</div>
            </div>
          </div>

          <div v-if="previewNote" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <div class="text-slate-500">Ghi chú</div>
            <div class="mt-0.5 whitespace-pre-line text-slate-800">{{ previewNote }}</div>
          </div>

          <div v-if="!previewItems.length && !previewManualItems.length" class="rounded-lg border border-dashed border-slate-300 bg-white px-4 py-4 text-center text-sm text-slate-500">Đơn hàng không có mặt hàng.</div>

          <div v-if="previewItems.length" class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-medium text-slate-800">Sản phẩm</div>
            <div class="divide-y divide-slate-100">
              <div v-for="item in previewItems" :key="`p-${item.id || item.product_id}-${item.product_unit_id || item.unit_name}`" class="flex items-center justify-between px-3 py-1 text-sm">
                <div>
                  <div class="font-medium text-slate-900">{{ item.product_name }}</div>
                  <div class="text-slate-500">SL: {{ toQtyText(item.qty) }} {{ item.unit_name }} - Giá: {{ formatMoney(item.price_sell) }}</div>
                </div>
                <div class="text-slate-900">{{ formatMoney(item.amount) }}</div>
              </div>
            </div>
          </div>

          <div v-if="previewManualItems.length" class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-3 py-2 text-sm font-medium text-slate-800">Sản phẩm khác</div>
            <div class="divide-y divide-slate-100">
              <div v-for="(item, index) in previewManualItems" :key="`m-${index}`" class="flex items-center justify-between px-3 py-1 text-sm">
                <div>
                  <div class="font-medium text-slate-900">{{ item.item_name }}</div>
                  <div class="text-slate-500">SL: {{ toQtyText(item.qty) }} {{ item.unit_name }} - Giá: {{ formatMoney(item.price_sell) }}</div>
                </div>
                <div class="text-slate-900">{{ formatMoney(item.amount_sell || (Number(item.qty || 0) * Number(item.price_sell || 0))) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

        <div class="app-modal-footer bg-slate-50">
          <RouterLink class="app-btn-primary" :to="rootTo" @click="closePreview">Xem chi tiết</RouterLink>
          <button type="button" class="app-btn-secondary" @click="closePreview">Đóng</button>
        </div>
      </div>
      </div>
    </transition>
  </Teleport>
</template>
