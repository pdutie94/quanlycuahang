<script setup lang="ts">
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime } = useFormat();
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PurchaseListItemCard from '../components/PurchaseListItemCard.vue';
import { usePurchases } from '../composables/usePurchases';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';
import AppModalSheet from '../../../shared/components/AppModalSheet.vue';
import EntityListState from '../../../shared/components/EntityListState.vue';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import { useUrlFilters } from '../../../shared/composables/useUrlFilters';

const route = useRoute();
const toast = useToast();

const { filters, applyFilters, clearFilters: resetFilters } = useUrlFilters({
  q: { default: '' },
  supplier_id: { default: '' },
  from_date: { default: '' },
  to_date: { default: '' },
  payment_status: { default: '' }
});

const showFilters = ref(false);

const { items, suppliers, meta, loading, error, load } = usePurchases();

const {
  hasMore,
  loadingMore,
  isInitialLoading,
  infiniteSentinel,
  refresh
} = useInfiniteList({
  itemsRef: items,
  metaRef: meta,
  loadingRef: loading,
  fetchPage: (page: number) => load({
    ...filters.value,
    page
  }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách phiếu nhập.');
  }
});

const applySearch = () => applyFilters({ q: filters.value.q });

const applyAdvancedFilters = () => {
  showFilters.value = false;
  applyFilters({
    supplier_id: filters.value.supplier_id,
    from_date: filters.value.from_date,
    to_date: filters.value.to_date
  });
};

const hasAnyFilter = computed(() =>
  filters.value.payment_status !== '' ||
  filters.value.supplier_id !== '' ||
  filters.value.from_date !== '' ||
  filters.value.to_date !== ''
);

watch(
  () => route.query,
  async () => {
    await refresh();
  },
  { immediate: true }
);
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="filters.q"
      title="Phiếu nhập hàng"
      subtitle="Quản lý danh sách phiếu nhập hàng và công nợ nhập."
      :create-to="{ name: 'purchases.create' }"
      create-label="Tạo phiếu"
      search-placeholder="Tìm theo mã phiếu, nhà cung cấp, SĐT..."
      filter-type="filter"
      chips-class="mt-2 flex items-center gap-2 overflow-x-auto text-sm"
      @search="applySearch"
      @filter-click="showFilters = true"
    >
      <template #chips>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="filters.payment_status === 'paid' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyFilters({ payment_status: 'paid' })"
        >
          Đã thanh toán
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="filters.payment_status === 'debt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyFilters({ payment_status: 'debt' })"
        >
          Còn nợ
        </button>
        <FilterClearChip :active="hasAnyFilter" @clear="resetFilters" />
      </template>
    </ListHeaderBar>

    <AppModalSheet
      :open="showFilters"
      title="Lọc phiếu nhập"
      @close="showFilters = false"
    >
      <div class="space-y-4">
        <div class="grid gap-3">
          <label class="space-y-1">
            <span class="app-label">Nhà cung cấp</span>
            <div class="relative">
              <select
                v-model="filters.supplier_id"
                class="block h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500"
              >
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
            <input
              v-model="filters.from_date"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
          <label class="space-y-1">
            <span class="app-label">Đến ngày</span>
            <input
              v-model="filters.to_date"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
        </div>

        <div class="border-t border-slate-100 pt-4">
          <div class="flex w-full gap-2">
            <button type="button" class="app-btn-secondary h-10 px-4 flex-1" @click="showFilters = false">Đóng</button>
            <button type="button" class="app-btn-primary h-10 px-6 flex-1" @click="applyAdvancedFilters">Áp dụng</button>
          </div>
        </div>
      </div>
    </AppModalSheet>

    <EntityListState :loading="isInitialLoading" :has-items="items.length > 0" empty-text="Chưa có phiếu nhập hàng nào.">
      <template #default>
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
    </EntityListState>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>