<script setup lang="ts">
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatNumber } = useFormat();
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { Check } from '@lucide/vue';
import { useProducts } from '../composables/useProducts';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import { useUrlFilters } from '../../../shared/composables/useUrlFilters';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';
import AppModalSheet from '../../../shared/components/AppModalSheet.vue';
import ProductListSkeleton from '../components/ProductListSkeleton.vue';

const route = useRoute();
const toast = useToast();
const showCategoryModal = ref(false);

const { filters, applyFilters, clearFilters } = useUrlFilters({
  q: { default: '' },
  stock: { default: 'all' },
  category_id: { default: '' }
});

const { items, meta, categories, loading, error, load } = useProducts();

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
  fetchPage: (page) =>
    load({
      q: filters.value.q,
      stock: filters.value.stock,
      category_id: filters.value.category_id || '',
      page: route.query.page || page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
});

// Watch filters from URL to reload data
watch(
  () => route.query,
  async () => {
    await refresh();
  },
  { immediate: true }
);

const hasAnyFilter = computed(() => filters.value.stock !== 'all' || filters.value.category_id !== '');
const selectedCategoryIdNumber = computed(() => Number(filters.value.category_id || 0));

const handleApplyCategory = (id: string | number) => {
  applyFilters({ category_id: String(id || '') });
  showCategoryModal.value = false;
};

const hasSellPrice = (item: Record<string, any>) => {
  return Number(item.display_price_sell || 0) > 0;
};

const getStatus = (item: Record<string, any>) => {
  const qty = Number(item.inventory_qty_base || item.qty_base || 0);
  const minStock = Number(item.min_stock_qty || 0);

  if (qty <= 0) {
    return { label: 'Hết hàng', className: 'text-rose-700' };
  }

  if (minStock > 0 && qty <= minStock) {
    return { label: 'Tồn thấp', className: 'text-amber-700' };
  }

  return { label: 'Còn hàng', className: 'text-brand-700' };
};

const getPrimaryUnit = (item: Record<string, any>) => item.base_unit_name || '';
const getCategoryName = (item: Record<string, any>) => item.category_name || 'Chưa phân loại';
const displayItems = computed(() =>
  items.value.map((item: Record<string, any>) => {
    const status = getStatus(item);
    const primaryUnit = getPrimaryUnit(item);
    return {
      item,
      status,
      categoryName: getCategoryName(item),
      inventoryText: formatNumber(item.inventory_qty_base || item.qty_base),
      hasSellPrice: hasSellPrice(item),
      primaryUnit
    };
  })
);
</script>

<template>
  <section class="space-y-4">
    <ListHeaderBar
      v-model="filters.q"
      title="Sản phẩm"
      subtitle="Quản lý danh sách sản phẩm đang bán."
      :create-to="{ name: 'products.create' }"
      search-placeholder="Tìm kiếm theo tên, SKU..."
      filter-type="grid"
      @search="applyFilters({ q: filters.q })"
      @filter-click="showCategoryModal = true"
    >
      <template #chips>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="filters.stock === 'in_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyFilters({ stock: 'in_stock' })">Còn hàng</button>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="filters.stock === 'low_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyFilters({ stock: 'low_stock' })">Tồn thấp</button>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="filters.stock === 'out_of_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyFilters({ stock: 'out_of_stock' })">Hết hàng</button>
          <FilterClearChip :active="hasAnyFilter" @clear="clearFilters" />
      </template>
    </ListHeaderBar>

    <AppModalSheet :open="showCategoryModal" title="Lọc theo danh mục" @close="showCategoryModal = false">
      <div class="space-y-2">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm"
          :class="filters.category_id === '' ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
          @click="handleApplyCategory('')"
        >
          <span>Tất cả danh mục</span>
          <Check v-if="filters.category_id === ''" class="h-4 w-4" />
        </button>
        <button
          v-for="category in categories"
          :key="category.id"
          type="button"
          class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm"
          :class="selectedCategoryIdNumber === Number(category.id) ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
          @click="handleApplyCategory(category.id)"
        >
          <span class="truncate">{{ category.name }}</span>
          <Check v-if="selectedCategoryIdNumber === Number(category.id)" class="h-4 w-4" />
        </button>
      </div>
    </AppModalSheet>

    <ProductListSkeleton v-if="isInitialLoading" />

    <div v-else-if="displayItems.length === 0" class="app-empty-state">Chưa có sản phẩm nào.</div>

    <div v-else class="space-y-3">
      <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
        <RouterLink
          v-for="entry in displayItems"
          :key="entry.item.id"
          :to="{ name: 'products.edit', params: { id: entry.item.id } }"
          class="app-list-card relative cursor-pointer"
        >
          <div class="flex items-center">
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-x-2">
                <div class="truncate text-sm font-medium text-slate-900">{{ entry.item.name }}</div>
                <span class="flex-none text-xs font-medium" :class="entry.status.className">{{ entry.status.label }}</span>
              </div>

              <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-500 leading-none">
                <span>{{ entry.item.code || '-' }}</span>
                <span class="text-slate-300">·</span>
                <span class="truncate">{{ entry.categoryName }}</span>
                <span class="text-slate-300">·</span>
                <span>Kho: {{ entry.inventoryText }} {{ entry.item.base_unit_name || '' }}</span>
              </div>

              <div class="mt-0.5 flex items-center gap-x-2 text-sm">
                <span v-if="entry.hasSellPrice" class="font-medium text-brand-600">{{ formatMoney(entry.item.display_price_sell) }}<span v-if="entry.item.display_price_unit_name || entry.primaryUnit">/{{ entry.item.display_price_unit_name || entry.primaryUnit }}</span></span>
                <span v-else class="font-medium text-slate-500">Chưa có giá</span>
              </div>
            </div>
          </div>
        </RouterLink>
      </transition-group>
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>
