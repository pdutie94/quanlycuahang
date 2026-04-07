<script setup>
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { useSupplierList } from '../composables/useSupplierList';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';

const keyword = ref('');
const { suppliers, meta, loading, error, load } = useSupplierList();
const toast = useToast();

// Đã thay thế bằng useFormat

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
  fetchPage: (page) => load(page, keyword.value),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách nhà cung cấp.');
  }
});

const applySearch = async () => {
  await refresh();
};
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
      title="Nhà cung cấp"
      subtitle="Quản lý danh sách nhà cung cấp và công nợ nhập hàng."
      :create-to="{ name: 'suppliers.create' }"
      create-label="Thêm nhà cung cấp"
      search-placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
      @search="applySearch"
    />

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!suppliers.length" class="app-empty-state">Chưa có nhà cung cấp nào.</div>
      <template v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <RouterLink
            v-for="supplier in suppliers"
            :key="supplier.id"
            :to="{ name: 'suppliers.detail', params: { id: supplier.id } }"
            class="app-list-card"
          >
            <div class="text-sm font-medium text-slate-900">{{ supplier.name }}</div>
            <div v-if="supplier.phone || supplier.address" class="mt-1 flex items-center gap-1 truncate text-sm text-slate-500 leading-none">
              <div v-if="supplier.phone">SĐT: {{ supplier.phone }}</div>
              <span v-if="supplier.phone && supplier.address" class="text-slate-300">·</span>
              <div v-if="supplier.address" class="line-clamp-1">Địa chỉ: {{ supplier.address }}</div>
            </div>
          </RouterLink>
        </transition-group>
      </template>
    </div>

    <InfiniteListStatus :visible="suppliers.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="suppliers.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>
