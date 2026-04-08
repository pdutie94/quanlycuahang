<script setup>
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { CheckSquare, RotateCcw, Trash2 } from '@lucide/vue';
import { useDeletedOrders } from '../composables/useDeletedOrders';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import ActionConfirmSheet from '../../../shared/components/ActionConfirmSheet.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';

const keyword = ref('');
const showPurgeConfirm = ref(false);
const selectedIds = ref([]);
const restoringId = ref(0);

const { items, meta, loading, error, load, restore, restoreError, purgeSelected, purgeLoading, purgeError } = useDeletedOrders();
const toast = useToast();

const buildParams = () => ({
  q: keyword.value
});

const {
  hasMore,
  loadingMore,
  isInitialLoading,
  refresh
} = useInfiniteList({
  itemsRef: items,
  metaRef: meta,
  loadingRef: loading,
  fetchPage: (page) => load({ ...buildParams(), page }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách đơn đã xóa tạm.');
  }
});

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime, parseAmount } = useFormat();

const formatPurgeDeadline = (value) => {
  if (!value) {
    return '';
  }

  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  date.setDate(date.getDate() + 7);

  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date);
};

const selectedSet = computed(() => new Set(selectedIds.value.map((id) => Number(id)).filter((id) => id > 0)));
const loadedIds = computed(() => items.value.map((item) => Number(item.id || 0)).filter((id) => id > 0));
const hasSelected = computed(() => selectedIds.value.length > 0);
const isAllLoadedSelected = computed(() => loadedIds.value.length > 0 && loadedIds.value.every((id) => selectedSet.value.has(id)));

const applySearch = async () => {
  selectedIds.value = [];
  await refresh();
};

const toggleSelectAllLoaded = () => {
  if (isAllLoadedSelected.value) {
    selectedIds.value = selectedIds.value.filter((id) => !loadedIds.value.includes(Number(id)));
    return;
  }

  selectedIds.value = Array.from(new Set([...selectedIds.value, ...loadedIds.value]));
};

const toggleSelection = (id) => {
  const nextId = Number(id || 0);
  if (nextId <= 0) {
    return;
  }

  if (selectedSet.value.has(nextId)) {
    selectedIds.value = selectedIds.value.filter((value) => Number(value) !== nextId);
    return;
  }

  selectedIds.value = [...selectedIds.value, nextId];
};

const restoreItem = async (id) => {
  const nextId = Number(id || 0);
  if (nextId <= 0) {
    return;
  }

  restoringId.value = nextId;
  try {
    const payload = await restore(nextId);
    toast.success(payload?.message || 'Đã khôi phục đơn hàng.');
    selectedIds.value = selectedIds.value.filter((value) => Number(value) !== nextId);
    await refresh();
  } catch (_err) {
    toast.error(restoreError.value || 'Không thể khôi phục đơn hàng.');
  } finally {
    restoringId.value = 0;
  }
};

const purgeSelectedItems = async () => {
  if (!selectedIds.value.length) {
    showPurgeConfirm.value = false;
    return;
  }

  try {
    const payload = await purgeSelected([...selectedIds.value]);
    toast.success(payload?.message || 'Đã xóa vĩnh viễn các đơn hàng đã chọn.');
    selectedIds.value = [];
    showPurgeConfirm.value = false;
    await refresh();
  } catch (_err) {
    toast.error(purgeError.value || 'Không thể xóa vĩnh viễn các đơn hàng đã chọn.');
  }
};
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
      title="Đơn đã xóa tạm"
      subtitle="Quản lý đơn hàng đã xóa tạm, có thể khôi phục trong 7 ngày trước khi bị xóa hẳn."
      search-placeholder="Tìm theo mã đơn, tên khách, SĐT..."
      @search="applySearch"
    >
      <template #chips>
        <RouterLink to="/orders" class="border inline-flex items-center rounded-lg bg-white px-3 py-1 text-sm font-medium text-slate-700">
          Quay lại đơn hàng
        </RouterLink>
        <button
          type="button"
          class="border inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1 text-sm font-medium text-slate-700 disabled:opacity-50"
          :disabled="!loadedIds.length"
          @click="toggleSelectAllLoaded"
        >
          <CheckSquare class="h-4 w-4" />
          <span>{{ isAllLoadedSelected ? 'Bỏ chọn trang hiện tại' : 'Chọn trang hiện tại' }}</span>
        </button>
        <button
          type="button"
          class="border inline-flex items-center gap-1 rounded-lg border-rose-300 bg-rose-50 px-3 py-1 text-sm font-medium text-rose-700 disabled:opacity-50"
          :disabled="!hasSelected || purgeLoading"
          @click="showPurgeConfirm = true"
        >
          <Trash2 class="h-4 w-4" />
          <span>Xóa vĩnh viễn {{ hasSelected ? `(${selectedIds.length})` : '' }}</span>
        </button>
      </template>
    </ListHeaderBar>

    <ActionConfirmSheet
      :open="showPurgeConfirm"
      title="Xóa vĩnh viễn đơn hàng"
      :description="`Bạn có chắc muốn xóa vĩnh viễn ${selectedIds.length} đơn đã chọn? Thao tác này không thể khôi phục.`"
      confirm-label="Xóa vĩnh viễn"
      :loading="purgeLoading"
      @cancel="showPurgeConfirm = false"
      @confirm="purgeSelectedItems"
    />

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Các đơn ở đây đã bị xóa tạm. Hệ thống sẽ tự xóa hẳn sau 7 ngày kể từ thời điểm xóa nếu không được khôi phục trước.
    </div>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">
        Đang tải...
      </div>

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có đơn hàng nào trong thùng rác.
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="item in items"
          :key="item.id"
          class="app-card p-3"
          :class="selectedSet.has(Number(item.id)) ? 'border-rose-300 bg-rose-50/40' : ''"
        >
          <div class="flex items-start gap-3">
            <label class="pt-1">
              <input
                :checked="selectedSet.has(Number(item.id))"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-rose-600"
                @change="toggleSelection(item.id)"
              />
            </label>

            <div class="min-w-0 flex-1">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <div class="font-medium text-slate-900">#{{ item.order_code || item.id }}</div>
                    <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                      {{ item.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}
                    </span>
                    <span class="inline-flex items-center rounded-lg bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">
                      Đã xóa tạm
                    </span>
                  </div>

                  <div class="mt-1 text-sm text-slate-600">
                    {{ (item.customer_name || 'Khách lẻ') + (item.customer_phone ? ` - ${item.customer_phone}` : '') }}
                  </div>

                  <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                    <span>Ngày đơn: {{ formatDateTime(item.order_date) }}</span>
                    <span>Xóa lúc: {{ formatDateTime(item.deleted_at) }}</span>
                    <span>Tự xóa hẳn: {{ formatPurgeDeadline(item.deleted_at) }}</span>
                  </div>

                  <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                    <span class="text-slate-600">Tổng tiền: <span class="font-medium text-slate-900">{{ formatMoney(item.total_amount) }}</span></span>
                    <span class="text-slate-600">Đã thu: <span class="font-medium text-brand-700">{{ formatMoney(item.paid_amount) }}</span></span>
                    <span class="text-slate-600">Mặt hàng: <span class="font-medium text-slate-900">{{ Number(item.items_count || 0) }}</span></span>
                  </div>
                </div>

                <div class="flex shrink-0 gap-2">
                  <button
                    type="button"
                    class="app-btn-secondary !min-h-0 gap-1 px-3 py-2 text-sm"
                    :disabled="restoringId === Number(item.id) || purgeLoading"
                    @click="restoreItem(item.id)"
                  >
                    <RotateCcw class="h-4 w-4" />
                    <span>Khôi phục</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>