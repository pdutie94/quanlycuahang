<script setup lang="ts">
import CustomerItemCard from '../../../shared/components/CustomerItemCard.vue';
import { computed, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useCustomers } from '../composables/useCustomers';
import { useToast } from '../../../shared/composables/useToast';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import { useUrlFilters } from '../../../shared/composables/useUrlFilters';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';
import CustomerListSkeleton from '../components/CustomerListSkeleton.vue';

const route = useRoute();
const toast = useToast();

const { filters, applyFilters, clearFilters } = useUrlFilters({
  q: { default: '' },
  debt_status: { default: '' }
});

const { items, meta, loading, error, load } = useCustomers();

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
  autoLoad: false,
  fetchPage: (page: number) =>
    load({
      q: filters.value.q,
      debt_status: filters.value.debt_status,
      page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách khách hàng.');
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
      v-model="filters.q"
      title="Khách hàng"
      subtitle="Quản lý danh sách khách hàng và công nợ."
      :create-to="{ name: 'customers.create' }"
      create-label="Thêm khách hàng"
      search-placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
      chips-class="mt-2 flex items-center gap-2 overflow-x-auto text-sm"
      @search="applyFilters({ q: filters.q })"
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
      <CustomerListSkeleton v-if="isInitialLoading" />

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có khách hàng nào.
      </div>

      <div v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <CustomerItemCard
            v-for="item in items"
            :key="item.id"
            :customer="item"
            :to="{ name: 'customers.detail', params: { id: item.id } }"
          />
        </transition-group>
      </div>
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>