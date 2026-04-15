<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useOrders } from '../composables/useOrders';
import { useToast } from '../../../shared/composables/useToast';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';
import AppModalSheet from '../../../shared/components/AppModalSheet.vue';
import EntityListState from '../../../shared/components/EntityListState.vue';
import { useUrlFilters } from '../../../shared/composables/useUrlFilters';

const route = useRoute();
const toast = useToast();

const { filters, applyFilters, clearFilters: resetFilters } = useUrlFilters({
  q: { default: '' },
  status: { default: '' },
  order_status: { default: '' },
  from_date: { default: '' },
  to_date: { default: '' }
});

const showAdvancedFilter = ref(false);

const { items, meta, loading, error, load } = useOrders();

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
  fetchPage: (page) =>
    load({
      ...filters.value,
      page: (route.query.page as string) || page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách đơn hàng.');
  }
});

const applySearch = () => applyFilters({ q: filters.value.q });
const applyOrderStatus = (value: string) => applyFilters({ order_status: value });

const applyAdvancedFilter = () => {
  showAdvancedFilter.value = false;
  applyFilters({
    status: filters.value.status,
    from_date: filters.value.from_date,
    to_date: filters.value.to_date
  });
};

const clearAdvancedFilter = () => {
  filters.value.status = '';
  filters.value.from_date = '';
  filters.value.to_date = '';
  applyAdvancedFilter();
};

const hasAdvancedFilter = computed(() => filters.value.status !== '' || filters.value.from_date !== '' || filters.value.to_date !== '');
const hasAnyFilter = computed(() => filters.value.order_status !== '' || hasAdvancedFilter.value);

// Watch query để reload data
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
      title="Đơn hàng"
      subtitle="Quản lý danh sách đơn hàng bán ra."
      :create-to="{ name: 'pos.index' }"
      create-label="Tạo đơn"
      search-placeholder="Tìm theo mã đơn, tên khách, SĐT..."
      filter-type="filter"
      chips-class="mt-2 flex items-center gap-1.5 overflow-x-auto overflow-y-hidden whitespace-nowrap text-sm"
      @search="applySearch"
      @filter-click="showAdvancedFilter = true"
    >
      <template #chips>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium transition-colors"
          :class="filters.order_status === 'completed' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('completed')"
        >
          Hoàn thành
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium transition-colors"
          :class="filters.order_status === 'pending' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('pending')"
        >
          Chưa hoàn thành
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium transition-colors"
          :class="filters.order_status === 'cancelled' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('cancelled')"
        >
          Đã hủy
        </button>
        <RouterLink to="/orders/deleted" class="border inline-flex items-center rounded-lg border-rose-300 bg-rose-50 px-3 py-1 text-sm font-medium text-rose-700">
          Đơn đã xóa
        </RouterLink>
        <FilterClearChip :active="hasAnyFilter" @clear="resetFilters" />
      </template>
    </ListHeaderBar>

    <AppModalSheet
      :open="showAdvancedFilter"
      title="Lọc nâng cao"
      @close="showAdvancedFilter = false"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <label class="space-y-1 text-sm text-slate-700">
            <span>Trạng thái thanh toán</span>
            <div class="relative">
              <select
                v-model="filters.status"
                class="h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500"
              >
                <option value="">Tất cả</option>
                <option value="paid">Đã thanh toán</option>
                <option value="debt">Còn nợ</option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
              </div>
            </div>
          </label>
          <label class="space-y-1 text-sm text-slate-700">
            <span>Từ ngày</span>
            <input
              v-model="filters.from_date"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
          <label class="space-y-1 text-sm text-slate-700">
            <span>Đến ngày</span>
            <input
              v-model="filters.to_date"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
        </div>

        <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="text-sm font-medium text-slate-500 disabled:opacity-50 hover:text-rose-600 transition-colors"
            :disabled="!hasAdvancedFilter || loading"
            @click="clearAdvancedFilter"
          >
            Xóa lọc
          </button>
          <div class="flex gap-2">
            <button
              type="button"
              class="app-btn-secondary h-10 px-4"
              @click="showAdvancedFilter = false"
            >
              Đóng
            </button>
            <button
              type="button"
              class="app-btn-primary h-10 px-6"
              :disabled="loading"
              @click="applyAdvancedFilter"
            >
              Áp dụng
            </button>
          </div>
        </div>
      </div>
    </AppModalSheet>

    <EntityListState :loading="isInitialLoading" :has-items="items.length > 0" empty-text="Chưa có đơn hàng nào.">
      <template #default>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <OrderItemCard
            v-for="item in items"
            :key="item.id"
            :order="item"
            :to="{ name: 'orders.detail', params: { id: item.id } }"
            customer-fallback="Khách lẻ"
          />
        </transition-group>
      </template>
    </EntityListState>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>