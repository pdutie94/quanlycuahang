<script setup>
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import PurchaseListItemCard from '../components/PurchaseListItemCard.vue';
import { X } from '@lucide/vue';
import { usePurchases } from '../composables/usePurchases';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';

const keyword = ref('');
const supplierId = ref('');
const fromDate = ref('');
const toDate = ref('');
const showFilters = ref(false);

const { items, suppliers, meta, loading, error, load } = usePurchases();
const toast = useToast();

// Đã thay thế bằng useFormat

const {
  hasMore,
  loadingMore,
  isInitialLoading,
  refresh
} = useInfiniteList({
  itemsRef: items,
  metaRef: meta,
  loadingRef: loading,
  fetchPage: (page) => load({ q: keyword.value, supplier_id: supplierId.value, from_date: fromDate.value, to_date: toDate.value, page }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách phiếu nhập.');
  }
});

const applyFilters = async () => {
  showFilters.value = false;
  await refresh();
};
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
      title="Phiếu nhập hàng"
      subtitle="Quản lý danh sách phiếu nhập hàng và công nợ nhập."
      :create-to="{ name: 'purchases.create' }"
      create-label="Tạo phiếu"
      search-placeholder="Tìm theo mã phiếu, nhà cung cấp, SĐT..."
      filter-type="filter"
      @search="applyFilters"
      @filter-click="showFilters = true"
    />

    <Teleport to="body">
      <transition name="app-modal-fade-up">
        <div v-if="showFilters" class="app-modal-overlay app-modal-open" @click.self="showFilters = false">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Lọc phiếu nhập</h2>
              <button type="button" class="app-modal-close" @click="showFilters = false">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <div class="grid gap-3">
          <label class="space-y-1">
            <span class="app-label">Nhà cung cấp</span>
            <div class="relative">
              <select v-model="supplierId" class="block h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500">
                <option value="">Tất cả nhà cung cấp</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">{{ supplier.name }}</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                  <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
            </div>
          </label>
          <label class="space-y-1">
            <span class="app-label">Từ ngày</span>
            <input v-model="fromDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
          </label>
          <label class="space-y-1">
            <span class="app-label">Đến ngày</span>
            <input v-model="toDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
          </label>
        </div>

              <div class="flex items-center justify-between gap-2 pt-1">
                <button type="button" class="text-sm font-medium text-slate-500" @click="supplierId = ''; fromDate = ''; toDate = ''; applyFilters()">Xóa lọc</button>
                <div class="flex gap-2">
                  <button type="button" class="app-btn-secondary" @click="showFilters = false">Đóng</button>
                  <button type="button" class="app-btn-primary" @click="applyFilters">Áp dụng</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có phiếu nhập hàng nào.</div>
      <template v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
            <RouterLink
              v-for="item in items"
              :key="item.id"
              :to="{ name: 'purchases.detail', params: { id: item.id } }"
              class="block"
            >
              <PurchaseListItemCard :purchase="item" :format-money="formatMoney" :format-date-time="formatDateTime" />
            </RouterLink>
        </transition-group>
      </template>
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>