<script setup>
import { computed, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useCustomers } from '../composables/useCustomers';
import { useToast } from '../../../shared/composables/useToast';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';

const keyword = ref('');
const debtStatus = ref('');

const { items, meta, loading, error, load } = useCustomers();
const toast = useToast();

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();

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
      q: keyword.value,
      debt_status: debtStatus.value,
      page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách khách hàng.');
  }
});

const applySearch = async () => {
  await refresh();
};

const applyDebtStatus = async (value) => {
  debtStatus.value = value;
  await refresh();
};

const hasAnyFilter = computed(() => debtStatus.value !== '');

const clearFilters = async () => {
  debtStatus.value = '';
  await refresh();
};
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
      title="Khách hàng"
      subtitle="Quản lý danh sách khách hàng và công nợ."
      :create-to="{ name: 'customers.create' }"
      create-label="Thêm khách hàng"
      search-placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
      chips-class="mt-2 flex items-center gap-2 overflow-x-auto text-sm"
      @search="applySearch"
    >
      <template #chips>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="debtStatus === 'debt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyDebtStatus('debt')"
        >
          Còn nợ
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="debtStatus === 'nodebt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyDebtStatus('nodebt')"
        >
          Không nợ
        </button>
        <FilterClearChip :active="hasAnyFilter" @clear="clearFilters" />
      </template>
    </ListHeaderBar>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">
        Đang tải...
      </div>

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có khách hàng nào.
      </div>

      <template v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <RouterLink
            v-for="item in items"
            :key="item.id"
            :to="{ name: 'customers.detail', params: { id: item.id } }"
            class="app-list-card"
          >
            <div class="min-w-0 flex-1">
              <div class="">
                <div class="min-w-0 truncate text-sm font-medium text-slate-900">{{ item.name }}</div>
              </div>
              <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-600 leading-none">
                <span v-if="item.phone" class="truncate">{{ item.phone || 'Chưa có SĐT' }}</span>
                <span v-if="item.phone && item.address" class="text-slate-300">·</span>
                <span v-if="item.address" class="truncate">{{ item.address || 'Chưa có địa chỉ' }}</span>
              </div>
              <div class="text-sm text-slate-500 mt-0.5">
                Nợ: <span class="font-medium" :class="Number(item.debt_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(item.debt_amount) }}</span>
              </div>
            </div>
          </RouterLink>
        </transition-group>
      </template>
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>