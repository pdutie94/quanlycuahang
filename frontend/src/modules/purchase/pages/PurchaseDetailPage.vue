<script setup lang="ts">
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();
import { computed, onMounted, ref, watch } from 'vue';
import { Package, BanknoteArrowDown, ClipboardList, History, Pencil, Trash2, Users } from '@lucide/vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { usePurchaseDetail } from '../composables/usePurchaseDetail';
import { useToast } from '../../../shared/composables/useToast';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { purchase, items, manualItems, payments, logs, loading, error, load, refresh, submitPayment, paymentLoading, paymentError, remove, deleteLoading, deleteError } = usePurchaseDetail();

const paymentAmount = ref('');
const paymentMethod = ref('cash');
const paymentNote = ref('');
const showPayment = ref(false);

// Khi mở modal thanh toán, tự động điền số tiền còn nợ nếu chưa nhập
watch(showPayment, (val) => {
  if (val && totals.value.debt > 0) {
    // Chỉ set nếu chưa nhập gì trước đó
    if (!paymentAmount.value) {
      paymentAmount.value = String(totals.value.debt);
    }
  }
  // Khi đóng modal, có thể reset nếu muốn (giữ lại để user nhập lại nếu cần)
});
const showDeleteModal = ref(false);

// Đã thay thế bằng useFormat
const numberFormatter = new Intl.NumberFormat('vi-VN');
const formatNumber = (value: any) => {
  const nextValue = Number(value || 0);
  if (Number.isInteger(nextValue)) {
    return numberFormatter.format(nextValue);
  }

  return nextValue.toLocaleString('vi-VN', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
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

const historyDateFormatter = new Intl.DateTimeFormat('vi-VN', {
  hour: '2-digit',
  minute: '2-digit',
  day: '2-digit',
  month: '2-digit',
  year: 'numeric'
});

const formatHistoryDate = (value: any) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return String(value);
  return historyDateFormatter.format(date).replace(/^([^,]+),\s*/, '$1, ');
};

const loadPage = async () => {
  try {
    await load(Number(route.params.id || 0));
  } catch (_err: any) {
    toast.error(error.value || 'Không thể tải chi tiết phiếu nhập.');
  }
};

const refreshPage = async () => {
  try {
    await refresh(Number(route.params.id || 0));
  } catch (_err: any) {
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
  } catch (_err: any) {
    toast.error(paymentError.value || 'Không thể ghi nhận thanh toán.');
  }
};

const deleteCurrentPurchase = async () => {
  if (!purchase.value?.id) {
    return;
  }

  try {
    const payload = await remove(purchase.value.id);
    showDeleteModal.value = false;
    toast.success(payload?.message || 'Đã xóa phiếu nhập hàng.');
    router.push('/purchases');
  } catch (_err: any) {
    toast.error(deleteError.value || 'Không thể xóa phiếu nhập.');
  }
};

const parseLogText = (detailRaw: any) => {
  try {
    const detail = JSON.parse(detailRaw || '{}');
    if (detail.type === 'create') {
      return {
        text: `Tạo phiếu nhập với ${Number(detail.items_count || 0)} mặt hàng, tổng ${formatMoney(detail.total_amount || 0)}, đã trả ${formatMoney(detail.paid_amount || 0)}.`,
        tone: 'text-emerald-700'
      };
    }
    if (detail.type === 'update') {
      return {
        text: `Cập nhật ${Number(detail.items_count || 0)} mặt hàng, tổng từ ${formatMoney(detail.old_total || 0)} lên ${formatMoney(detail.new_total || 0)}, đã trả từ ${formatMoney(detail.old_paid || 0)} lên ${formatMoney(detail.new_paid || 0)}.`,
        tone: 'text-violet-700'
      };
    }
    if (detail.type === 'payment') {
      return {
        text: `Thanh toán ${formatMoney(detail.amount || 0)}${detail.method ? ` (${detail.method})` : ''}`,
        tone: 'text-brand-700'
      };
    }
  } catch (_err: any) {
    return { text: detailRaw || '-', tone: 'text-slate-700' };
  }
  return { text: detailRaw || '-', tone: 'text-slate-700' };
};

const getHistoryTone = (tone: string | null | undefined) => {
  const nextTone = String(tone || 'text-slate-700');

  if (nextTone.includes('emerald') || nextTone.includes('brand')) {
    return {
      dot: 'bg-emerald-400',
      row: 'bg-emerald-50/55',
      detail: 'text-slate-700'
    };
  }

  if (nextTone.includes('violet')) {
    return {
      dot: 'bg-violet-400',
      row: 'bg-violet-50/55',
      detail: 'text-slate-700'
    };
  }

  if (nextTone.includes('amber')) {
    return {
      dot: 'bg-amber-400',
      row: 'bg-amber-50/55',
      detail: 'text-slate-700'
    };
  }

  return {
    dot: 'bg-slate-400',
    row: 'bg-slate-50',
    detail: 'text-slate-700'
  };
};

interface LogEntry extends Record<string, any> {
  id: number | string;
  created_at: string;
  detail: string;
}

interface LogGroup {
  key: string;
  timeText: string;
  entries: (LogEntry & { parsed: any; tone: any })[];
}

const groupedLogs = computed(() => {
  const groups: LogGroup[] = [];
  const groupMap = new Map<string, LogGroup>();

  for (const log of (logs.value as LogEntry[])) {
    const timeText = formatHistoryDate(log?.created_at) || '-';
    if (!groupMap.has(timeText)) {
      const group: LogGroup = {
        key: `${timeText}-${log?.id || groups.length}`,
        timeText,
        entries: []
      };
      groupMap.set(timeText, group);
      groups.push(group);
    }

    const group = groupMap.get(timeText);
    if (group) {
      const parsed = parseLogText(log?.detail);
      group.entries.push({
        ...log,
        parsed,
        tone: getHistoryTone(parsed.tone)
      });
    }
  }

  return groups;
});

onMounted(async () => {
  await loadPage();
});

watch(
  () => route.params.id,
  async () => {
    await loadPage();
  }
);
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="purchase ? `Phiếu nhập #${purchase.purchase_code}` : `Phiếu nhập #${route.params.id}`" back-to="/purchases">
      <template #actions="{ closeMenu }">
        <template v-if="purchase">
          <RouterLink :to="{ name: 'purchases.edit', params: { id: purchase.id } }" class="detail-header-menu-item" @click="closeMenu"><Pencil class="h-4 w-4 shrink-0" /><span>Sửa phiếu nhập</span></RouterLink>
          <button v-if="totals.debt > 0" type="button" class="detail-header-menu-item" @click="closeMenu(); showPayment = !showPayment"><BanknoteArrowDown class="h-4 w-4 shrink-0" /><span>{{ showPayment ? 'Ẩn thanh toán' : 'Thanh toán' }}</span></button>
          <button type="button" class="detail-header-menu-item detail-header-menu-item-rose" @click="closeMenu(); showDeleteModal = true"><Trash2 class="h-4 w-4 shrink-0" /><span>Xóa phiếu nhập</span></button>
        </template>
      </template>
    </DetailHeaderBar>

    <ActionConfirmSheet
      :open="showDeleteModal"
      title="Xóa phiếu nhập"
      description="Bạn chắc chắn muốn xóa phiếu nhập này? Tồn kho, lịch sử thanh toán và lịch sử phiếu nhập liên quan sẽ bị xóa theo."
      confirm-label="Xóa phiếu nhập"
      :loading="deleteLoading"
      @cancel="showDeleteModal = false"
      @confirm="deleteCurrentPurchase"
    />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!purchase" class="app-empty-state">Không tìm thấy phiếu nhập.</div>
    <template v-else>
      <section class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
          <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><ClipboardList class="h-4 w-4" /></span>
            <div class="font-mono text-sm font-semibold text-slate-900">Phiếu nhập #{{ purchase.purchase_code }}</div>
          </div>
        </div>

        <div class="space-y-3 px-4 py-4">
          <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <span class="inline-flex items-center rounded-lg bg-sky-50 px-2.5 py-1 font-medium text-sky-700">{{ formatDateTime(purchase.purchase_date) }}</span>
            <span class="inline-flex items-center rounded-lg px-2.5 py-1 font-medium" :class="purchase.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">
              {{ purchase.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}
            </span>
            <span v-if="noteMeta.method" class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1 font-medium text-slate-700">{{ noteMeta.method }}</span>
          </div>

          <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.16em] text-slate-400">Tổng giá trị</div>
              <div class="mt-2 text-lg font-semibold text-slate-900">{{ formatMoney(totals.total) }}</div>
            </div>
            <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.16em] text-brand-600">Đã thanh toán</div>
              <div class="mt-2 text-lg font-semibold text-brand-700">{{ formatMoney(totals.paid) }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
              <div class="text-xs uppercase tracking-[0.16em] text-amber-700">Còn nợ</div>
              <div class="mt-2 text-lg font-semibold" :class="totals.debt > 0 ? 'text-amber-700' : 'text-slate-900'">{{ formatMoney(totals.debt) }}</div>
            </div>
          </div>

          <div v-if="noteMeta.note" class="text-sm text-slate-600">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700 whitespace-pre-line">
              {{ noteMeta.note }}
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-lg border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2">
          <div class="flex items-center gap-2 text-sm font-medium text-slate-800">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><Users class="h-4 w-4" /></span>
            <span>Nhà cung cấp</span>
          </div>
        </div>
        <div class="space-y-2 px-4 py-2 text-sm text-slate-700">
          <div class="flex flex-col gap-x-6 gap-y-1">
            <div class="flex gap-2">
              <span class="min-w-24 text-slate-500">Nhà cung cấp:</span>
              <RouterLink
                v-if="purchase.supplier_id"
                :to="{ name: 'suppliers.detail', params: { id: purchase.supplier_id } }"
                class="font-medium text-brand-700 hover:text-brand-800"
              >
                {{ purchase.supplier_name }}
              </RouterLink>
              <span v-else class="font-medium text-slate-900">{{ purchase.supplier_name }}</span>
            </div>
            <div v-if="purchase.supplier_phone" class="flex gap-2">
              <span class="min-w-24 text-slate-500">SĐT:</span>
              <div class="inline-flex items-center gap-2">
                <span class="font-medium text-slate-900">{{ purchase.supplier_phone }}</span>
                <a :href="`tel:${purchase.supplier_phone}`" class="text-sm font-medium text-brand-700 hover:text-brand-800">Gọi</a>
              </div>
            </div>
            <div v-if="purchase.supplier_address" class="flex gap-2">
              <span class="min-w-24 text-slate-500">Địa chỉ:</span>
              <span class="text-slate-900">{{ purchase.supplier_address }}</span>
            </div>
          </div>
        </div>
      </section>

      <Teleport to="body">
        <transition name="app-modal-fade-up">
          <div v-if="showPayment && totals.debt > 0" class="app-modal-overlay app-modal-open" @click.self="showPayment = false">
            <div class="app-modal-sheet-sm">
              <div class="app-modal-header">
                <h2 class="app-modal-title">Thanh toán phiếu nhập</h2>
                <button type="button" class="app-modal-close" @click="showPayment = false">
                  <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </button>
              </div>
              <form class="app-modal-body space-y-4" @submit.prevent="pay">
                <div class="grid grid-cols-3 gap-3 text-sm">
                  <div class="rounded-md bg-slate-50 px-3 py-2">
                    <div class="text-sm uppercase text-slate-500">Tổng tiền</div>
                    <div class="mt-1 font-medium text-slate-900">{{ formatMoney(totals.total) }}</div>
                  </div>
                  <div class="rounded-md bg-brand-50 px-3 py-2">
                    <div class="text-sm uppercase text-brand-600">Đã trả</div>
                    <div class="mt-1 font-medium text-brand-700">{{ formatMoney(totals.paid) }}</div>
                  </div>
                  <div class="rounded-md bg-slate-50 px-3 py-2">
                    <div class="text-sm uppercase text-slate-500">Còn nợ</div>
                    <div class="mt-1 font-medium text-rose-600">{{ formatMoney(totals.debt) }}</div>
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
                  <span class="app-label">Số tiền thanh toán</span>
                  <div class="relative">
                    <input v-model="paymentAmount" type="text"  v-money-input class="app-input pr-9 text-right" />
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">đ</span>
                  </div>
                </label>

                <label class="block space-y-1 text-sm text-slate-700">
                  <span class="app-label">Ghi chú</span>
                  <textarea v-model="paymentNote" rows="2" class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-500"></textarea>
                </label>

                <div class="app-modal-footer mt-2 border-t border-slate-100 px-0 py-0 pt-2">
                  <button type="button" class="app-btn-secondary" @click="showPayment = false">Hủy</button>
                  <button type="submit" class="app-btn-primary" :disabled="paymentLoading">Xác nhận thanh toán</button>
                </div>
              </form>
            </div>
          </div>
        </transition>
      </Teleport>

      <section v-if="items.length" class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><Package class="h-4 w-4" /></span>
            <span>Sản phẩm</span>
          </div>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-for="item in items" :key="item.id" class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="truncate font-medium text-slate-900">{{ item.product_name }}</div>
              <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm mt-1 text-slate-600">
                <div>SL: <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }} <span v-if="item.unit_name"> {{ item.unit_name }}</span></span></div>
                <div>Nhập: <span class="font-medium text-slate-900">{{ formatMoney(item.price_cost) }}</span></div>
                <div>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(item.amount) }}</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section v-if="manualItems.length" class="rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 px-4 py-3 text-sm font-medium text-slate-800">
          <div class="flex items-center gap-2">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"><Package class="h-4 w-4" /></span>
            <span>Sản phẩm khác</span>
          </div>
        </div>
        <div class="divide-y divide-slate-100">
          <div v-for="item in manualItems" :key="`manual-${item.id}`" class="flex flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="truncate font-medium text-slate-900">{{ item.item_name }}</div>
              <div class="flex flex-wrap gap-x-3 gap-y-1 text-sm mt-1 text-slate-600">
                <div>SL: <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }} <span v-if="item.unit_name"> {{ item.unit_name }}</span></span></div>
                <div>Nhập: <span class="font-medium text-slate-900">{{ formatMoney(item.price_cost) }}</span></div>
                <div>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(item.amount) }}</span></div>
              </div>
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

      <section v-if="groupedLogs.length" class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3">
        <h2 class="flex items-center gap-2 text-base font-medium text-slate-800">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-violet-100 text-violet-700">
            <History class="h-3.5 w-3.5" />
          </span>
          <span>Lịch sử phiếu nhập</span>
        </h2>
        <p class="text-sm text-slate-500">Nhật ký tạo phiếu, cập nhật mặt hàng và các lần thanh toán của phiếu nhập.</p>
        <div class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/70">
          <div
            v-for="group in groupedLogs"
            :key="group.key"
            class="border-b border-slate-200 px-3 py-2.5 last:border-b-0"
          >
            <div class="mb-2">
              <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium leading-none text-slate-600">{{ group.timeText }}</span>
            </div>
            <ul class="space-y-1 text-sm">
              <li
                v-for="entry in group.entries"
                :key="entry.id"
                class="flex gap-3 rounded-md p-1"
                :class="entry.tone.row"
              >
                <div class="pt-1">
                  <span class="mt-[2px] block h-2 w-2 rounded-full" :class="entry.tone.dot"></span>
                </div>
                <div class="min-w-0 flex-1 leading-5" :class="entry.tone.detail">{{ entry.parsed.text }}</div>
              </li>
            </ul>
          </div>
        </div>
      </section>
    </template>
  </section>
</template>