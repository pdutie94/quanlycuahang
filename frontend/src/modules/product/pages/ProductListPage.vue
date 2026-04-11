<script setup>
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { Check, X } from '@lucide/vue';
import { useProducts } from '../composables/useProducts';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';


const route = useRoute();
const router = useRouter();

const keyword = ref('');
const stockFilter = ref('all');
const categoryId = ref('');
const showCategoryModal = ref(false);
const { items, meta, filters, categories, loading, error, load } = useProducts();
const toast = useToast();

// Đọc filter từ URL query khi load
const initFromQuery = () => {
  keyword.value = String(route.query.q || '');
  stockFilter.value = String(route.query.stock || 'all');
  categoryId.value = route.query.category_id ? String(route.query.category_id) : '';
};

initFromQuery();

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
      q: keyword.value,
      stock: stockFilter.value,
      category_id: categoryId.value || '',
      page: route.query.page || page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
});


const applySearch = async () => {
  router.push({
    query: {
      ...route.query,
      q: keyword.value || undefined,
      page: undefined // reset page
    }
  });
};

const applyStock = async (value) => {
  if (stockFilter.value === value) return;
  stockFilter.value = value;
  router.push({
    query: {
      ...route.query,
      stock: value !== 'all' ? value : undefined,
      page: undefined
    }
  });
};

const hasAnyFilter = computed(() => stockFilter.value !== 'all' || categoryId.value !== '');

const applyCategory = async (value) => {
  const nextValue = String(value || '');
  if (categoryId.value === nextValue) {
    showCategoryModal.value = false;
    return;
  }
  categoryId.value = nextValue;
  showCategoryModal.value = false;
  router.push({
    query: {
      ...route.query,
      category_id: nextValue || undefined,
      page: undefined
    }
  });
};

const clearFilters = async () => {
  router.push({ query: {} });
};

const selectedCategoryIdNumber = computed(() => Number(categoryId.value || 0));


// Watch query để reload data và sync filter từ URL
watch(
  () => route.query,
  async () => {
    initFromQuery();
    await refresh();
  },
  { immediate: true }
);

// Đã thay thế bằng useFormat

const formatQty = (value) => Number(value || 0).toLocaleString('vi-VN');

const hasSellPrice = (item) => {
  return Number(item.display_price_sell || 0) > 0;
}
const getStatus = (item) => {
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

const getPrimaryUnit = (item) => item.base_unit_name || '';

const getCategoryName = (item) => item.category_name || 'Chưa phân loại';
</script>

<template>
  <section class="space-y-4">
    <ListHeaderBar
      v-model="keyword"
      title="Sản phẩm"
      subtitle="Quản lý danh sách sản phẩm đang bán."
      :create-to="{ name: 'products.create' }"
      search-placeholder="Tìm kiếm theo tên, SKU..."
      filter-type="grid"
      @search="applySearch"
      @filter-click="showCategoryModal = true"
    >
      <template #chips>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="stockFilter === 'in_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyStock('in_stock')">Còn hàng</button>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="stockFilter === 'low_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyStock('low_stock')">Tồn thấp</button>
          <button type="button" class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium" :class="stockFilter === 'out_of_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'" @click="applyStock('out_of_stock')">Hết hàng</button>
          <FilterClearChip :active="hasAnyFilter" @clear="clearFilters" />
      </template>
    </ListHeaderBar>

    <Teleport to="body">
      <transition name="app-modal-fade-up">
        <div v-if="showCategoryModal" class="app-modal-overlay app-modal-open" @click.self="showCategoryModal = false">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Lọc theo danh mục</h2>
              <button type="button" class="app-modal-close" @click="showCategoryModal = false">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-2">
              <button
                type="button"
                class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm"
                :class="categoryId === '' ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                @click="applyCategory('')"
              >
                <span>Tất cả danh mục</span>
                <Check v-if="categoryId === ''" class="h-4 w-4" />
              </button>
              <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-sm"
                :class="selectedCategoryIdNumber === Number(category.id) ? 'border-brand-500 text-brand-700' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                @click="applyCategory(category.id)"
              >
                <span class="truncate">{{ category.name }}</span>
                <Check v-if="selectedCategoryIdNumber === Number(category.id)" class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có sản phẩm nào.</div>
      <template v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <RouterLink
            v-for="item in items"
            :key="item.id"
            :to="{ name: 'products.edit', params: { id: item.id } }"
            class="app-list-card relative cursor-pointer"
          >
            <div class="flex items-center">
              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-x-2">
                  <div class="truncate text-sm font-medium text-slate-900">{{ item.name }}</div>
                  <span class="flex-none text-xs font-medium" :class="getStatus(item).className">{{ getStatus(item).label }}</span>
                </div>

                <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-500 leading-none">
                  <span>{{ item.code || '-' }}</span>
                  <span class="text-slate-300">·</span>
                  <span class="truncate">{{ getCategoryName(item) }}</span>
                  <span class="text-slate-300">·</span>
                  <span>Kho: {{ formatQty(item.inventory_qty_base || item.qty_base) }} {{ item.base_unit_name || '' }}</span>
                </div>

                <div class="mt-0.5 flex items-center gap-x-2 text-sm">
                  <span v-if="hasSellPrice(item)" class="font-medium text-brand-600">{{ formatMoney(item.display_price_sell) }}<span v-if="item.display_price_unit_name || getPrimaryUnit(item)">/{{ item.display_price_unit_name || getPrimaryUnit(item) }}</span></span>
                  <span v-else class="font-medium text-slate-500">Chưa có giá</span>
                </div>
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
