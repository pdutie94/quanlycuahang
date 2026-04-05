<script setup>
import { computed, onMounted, ref } from 'vue';
import { BanknoteArrowDown, Pencil } from '@lucide/vue';
import { RouterLink, useRoute } from 'vue-router';
import { usePurchaseDetail } from '../composables/usePurchaseDetail';
import { useToast } from '../../../shared/composables/useToast';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const toast = useToast();
const { purchase, items, payments, logs, loading, error, load, refresh, submitPayment, paymentLoading, paymentError } = usePurchaseDetail();

const paymentAmount = ref('');
const paymentMethod = ref('cash');
const paymentNote = ref('');
const showPayment = ref(false);

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;
const formatNumber = (value) => Number(value || 0).toLocaleString('vi-VN', { maximumFractionDigits: 3 });
const formatDateTime = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
};

const totals = computed(() => {
  const total = Number(purchase.value?.total_amount || 0);
  const paid = Number(purchase.value?.paid_amount || 0);
  return { total, paid, debt: Math.max(total - paid, 0) };
});

const noteMeta = computed(() => {
  const raw = String(purchase.value?.note || '').trim();
  if (raw.endsWith('[TT:cash]')) return { note: raw.slice(0, -9).trim(), method: 'Tiền mặt' };
  if (raw.endsWith('[TT:bank]')) return { note: raw.slice(0, -9).trim(), method: 'Chuyển khoản' };
  return { note: raw, method: '' };
});

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết phiếu nhập.');
  }
};

const refreshPage = async () => {
  try {
    await refresh(Number(route.params.id || 0));
  } catch (_err) {
    toast.error(error.value || 'Không thể tải chi tiết phiếu nhập.');
  }
};

const pay = async () => {
  try {
    const payload = await submitPayment(Number(route.params.id || 0), { amount: paymentAmount.value, note: paymentNote.value, payment_method: paymentMethod.value });
    toast.success(payload?.message || 'Đã ghi nhận thanh toán phiếu nhập.');
    paymentAmount.value = '';
    paymentNote.value = '';
    paymentMethod.value = 'cash';
    showPayment.value = false;
    await refreshPage();
  } catch (_err) {
    toast.error(paymentError.value || 'Không thể ghi nhận thanh toán.');
  }
};

const parseLogText = (detailRaw) => {
  try {
    const detail = JSON.parse(detailRaw || '{}');
    if (detail.type === 'create') {
      return `Tạo phiếu nhập với ${Number(detail.items_count || 0)} mặt hàng, tổng ${formatMoney(detail.total_amount || 0)}, đã trả ${formatMoney(detail.paid_amount || 0)}.`;
    }
    if (detail.type === 'update') {
      return `Cập nhật ${Number(detail.items_count || 0)} mặt hàng, tổng từ ${formatMoney(detail.old_total || 0)} lên ${formatMoney(detail.new_total || 0)}, đã trả từ ${formatMoney(detail.old_paid || 0)} lên ${formatMoney(detail.new_paid || 0)}.`;
    }
    if (detail.type === 'payment') {
      return `Thanh toán ${formatMoney(detail.amount || 0)}${detail.method ? ` (${detail.method})` : ''}`;
    }
  } catch (_err) {
    return detailRaw || '-';
  }
  return detailRaw || '-';
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="purchase ? `Phiếu nhập #${purchase.purchase_code}` : `Phiếu nhập #${route.params.id}`" back-to="/purchases">
      <template #actions="{ closeMenu }">
        <template v-if="purchase">
          <RouterLink :to="{ name: 'purchases.edit', params: { id: purchase.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Sửa phiếu nhập</span></RouterLink>
          <button v-if="totals.debt > 0" type="button" class="detail-header-menu-item" @click="closeMenu(); showPayment = !showPayment"><BanknoteArrowDown class="h-4 w-4 shrink-0" /><span>{{ showPayment ? 'Ẩn thanh toán' : 'Thanh toán' }}</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!purchase" class="app-empty-state">Không tìm thấy phiếu nhập.</div>
    <template v-else>
      <section class="app-card">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-sm text-slate-500">Mã phiếu</div>
            <div class="text-sm font-mono font-medium text-slate-900">{{ purchase.purchase_code }}</div>
            <div class="mt-1 text-sm text-slate-600">{{ formatDateTime(purchase.purchase_date) }}</div>
          </div>
          <span class="inline-flex items-center rounded-lg px-3 py-0.5 text-sm font-medium" :class="purchase.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">{{ purchase.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}</span>
        </div>
        <div class="mt-3 space-y-1 text-sm text-slate-600">
          <div>Nhà cung cấp: <span class="font-medium text-slate-800">{{ purchase.supplier_name }}</span></div>
          <div v-if="purchase.supplier_phone || purchase.supplier_address">
            <span v-if="purchase.supplier_phone" class="mr-3">SĐT: {{ purchase.supplier_phone }}</span>
            <span v-if="purchase.supplier_address">Địa chỉ: {{ purchase.supplier_address }}</span>
          </div>
          <div>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(totals.total) }}</span></div>
          <div>Đã trả: <span class="font-medium text-brand-600">{{ formatMoney(totals.paid) }}</span></div>
          <div>Còn nợ: <span class="font-medium" :class="totals.debt > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(totals.debt) }}</span></div>
          <div v-if="noteMeta.method">Thanh toán: <span class="font-medium text-slate-900">{{ noteMeta.method }}</span></div>
          <div v-if="noteMeta.note">Ghi chú: <span class="whitespace-pre-line">{{ noteMeta.note }}</span></div>
        </div>
      </section>

      <section v-if="showPayment && totals.debt > 0" class="app-card">
        <h2 class="text-sm font-medium text-slate-800">Thanh toán phiếu nhập</h2>
        <div class="mt-3 grid gap-3 md:grid-cols-3 text-sm">
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Tổng tiền</div><div class="mt-1 font-medium text-slate-900">{{ formatMoney(totals.total) }}</div></div>
          <div class="rounded-md bg-brand-50 px-3 py-2"><div class="text-brand-600">Đã trả</div><div class="mt-1 font-medium text-brand-700">{{ formatMoney(totals.paid) }}</div></div>
          <div class="rounded-md bg-slate-50 px-3 py-2"><div class="text-slate-500">Còn nợ</div><div class="mt-1 font-medium text-rose-600">{{ formatMoney(totals.debt) }}</div></div>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
          <label class="space-y-1">
            <span class="app-label">Hình thức thanh toán</span>
            <div class="relative">
              <select v-model="paymentMethod" class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500"><option value="cash">Tiền mặt</option><option value="bank">Chuyển khoản</option></select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
              </div>
            </div>
          </label>
          <label class="space-y-1">
            <span class="app-label">Số tiền thanh toán</span>
            <div class="relative">
              <input v-model="paymentAmount" type="text" inputmode="numeric" :placeholder="String(totals.debt)" class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500" />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
            </div>
          </label>
          <label class="space-y-1">
            <span class="app-label">Ghi chú</span>
            <input v-model="paymentNote" type="text" placeholder="Ghi chú" class="h-10 rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
          </label>
        </div>
        <div class="mt-4 flex gap-2">
          <button type="button" class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700" @click="showPayment = false">Hủy</button>
          <button type="button" class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="paymentLoading" @click="pay">Xác nhận thanh toán</button>
        </div>
      </section>

      <section v-if="items.length" class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Danh sách sản phẩm</div>
        <div class="divide-y divide-slate-100">
          <div v-for="item in items" :key="item.id" class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="truncate font-medium text-slate-900">{{ item.product_name }}</div>
              <div class="mt-1 text-slate-600">Số lượng: <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }}</span> <span class="text-slate-500">{{ item.unit_name }}</span></div>
            </div>
            <div class="flex flex-col items-end gap-1 text-sm">
              <div><span class="text-slate-500">Giá vốn:</span> <span class="font-medium text-slate-900">{{ formatMoney(item.price_cost) }}</span></div>
              <div><span class="text-slate-500">Thành tiền:</span> <span class="font-medium text-slate-900">{{ formatMoney(item.amount) }}</span></div>
            </div>
          </div>
        </div>
      </section>

      <section v-if="payments.length" class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Lịch sử thanh toán</div>
        <div class="divide-y divide-slate-100">
          <div v-for="payment in payments" :key="payment.id" class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="font-medium text-slate-900">{{ formatMoney(payment.amount) }}</div>
              <div v-if="payment.note" class="mt-1 text-slate-600 whitespace-pre-line">{{ payment.note }}</div>
            </div>
            <div class="text-slate-500">{{ formatDateTime(payment.paid_at) }}</div>
          </div>
        </div>
      </section>

      <section v-if="logs.length" class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">Lịch sử phiếu nhập</div>
        <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto text-sm">
          <div v-for="log in logs" :key="log.id" class="px-4 py-3">
            <div class="mb-1 inline-flex rounded-lg bg-slate-50 px-2 py-0.5 text-sm font-medium text-slate-600">{{ formatDateTime(log.created_at) }}</div>
            <div class="text-slate-700">{{ parseLogText(log.detail) }}</div>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>