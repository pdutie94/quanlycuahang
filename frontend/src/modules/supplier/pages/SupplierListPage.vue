<script setup lang="ts">
import SupplierItemCard from '../../../shared/components/SupplierItemCard.vue';
import { computed, watch, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useSupplierList } from '../composables/useSupplierList';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import { useUrlFilters } from '../../../shared/composables/useUrlFilters';
import { useDebouncedCallback } from '../../../shared/composables/useDebounce';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import SupplierListSkeleton from '../components/SupplierListSkeleton.vue';

const route = useRoute();
const toast = useToast();

const { filters, applyFilters, clearFilters } = useUrlFilters({
  q: { default: '' },
  debt_status: { default: '' }
});

// Local search query for v-model
const searchQuery = ref(filters.value.q);

// Debounced search - wait 300ms after user stops typing
const debouncedUpdateSearch = useDebouncedCallback((query: string) => {
  if (query !== filters.value.q) {
    applyFilters({ q: query });
  }
}, 300);

// Watch local query and trigger debounced update
watch(searchQuery, (newValue) => {
  debouncedUpdateSearch(newValue);
});

// Watch when filters are cleared from URL
watch(() => filters.value.q, (newValue) => {
  if (newValue !== searchQuery.value) {
    searchQuery.value = newValue;
  }
});

const { suppliers, meta, loading, error, load } = useSupplierList();

const {
  hasMore,
  loadingMore,
  isInitialLoading,
  infiniteSentinel,
  refresh
} = useInfiniteList({
  itemsRef: suppliers,
  metaRef: meta,
  loadingRef: loading,
  autoLoad: false,
  fetchPage: (page: number) =>
    load({
      q: filters.value.q,
      debt_status: filters.value.debt_status,
      page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách nhà cung cấp.');
  }
});

// Watch for URL changes to refresh list
watch(
  () => route.query,
  async () => {
    await refresh();
  },
  { immediate: true }
);

const hasAnyFilter = computed(() => filters.value.debt_status !== '');
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="searchQuery"
      title="Nhà cung cấp"
      subtitle="Quản lý danh sách nhà cung cấp và công nợ nhập hàng."
      :create-to="{ name: 'suppliers.create' }"
      create-label="Thêm nhà cung cấp"
      search-placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
      chips-class="mt-2 flex items-center gap-2 overflow-x-auto text-sm"
      @search="debouncedUpdateSearch(searchQuery)"
    >
      <template #chips>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="filters.debt_status === 'debt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyFilters({ debt_status: 'debt' })"
        >
          Còn nợ
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="filters.debt_status === 'nodebt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyFilters({ debt_status: 'nodebt' })"
        >
          Không nợ
        </button>
        <FilterClearChip :active="hasAnyFilter" @clear="clearFilters" />
      </template>
    </ListHeaderBar>

    <div class="space-y-3">
      <SupplierListSkeleton v-if="isInitialLoading" />

      <div v-else-if="!suppliers.length" class="app-empty-state">Chưa có nhà cung cấp nào.</div>

      <div v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <SupplierItemCard
            v-for="supplier in suppliers"
            :key="supplier.id"
            :supplier="supplier"
            :to="{ name: 'suppliers.detail', params: { id: supplier.id } }"
          />
        </transition-group>
      </div>
    </div>

    <InfiniteListStatus :visible="suppliers.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="suppliers.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>
