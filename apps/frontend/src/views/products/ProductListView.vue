<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Package2, Plus } from 'lucide-vue-next'
import { useProductsStore } from '../../stores/products'
import { useCategoriesStore } from '../../stores/categories'
import PullToRefresh from '../../components/PullToRefresh.vue'
import { formatMoney } from '../../lib/format'
import ProductListStickyHeader from '../../components/products/ProductListStickyHeader.vue'
import AppModal from '../../components/AppModal.vue'

type FilterKey = 'all' | 'in-stock' | 'low-stock' | 'out-stock'

const products = useProductsStore()
const categories = useCategoriesStore()
const keyword = ref('')
const activeFilter = ref<FilterKey>('all')
const selectedCategoryId = ref<number | null>(null)
const isCategoryFilterOpen = ref(false)
const sentinel = ref<HTMLElement | null>(null)
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null
let observer: IntersectionObserver | null = null

onMounted(async () => {
  await products.fetchList({ page: 1 })
  keyword.value = products.query
  setupObserver()
})

onUnmounted(() => {
  observer?.disconnect()

  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }
})

watch(keyword, (value) => {
  const trimmed = value.trim()

  if (trimmed === products.query) {
    return
  }

  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }

  searchDebounceTimer = setTimeout(() => {
    void products.fetchList({ page: 1, q: trimmed || undefined })
  }, 200)
})

function setupObserver(): void {
  observer?.disconnect()
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !products.loading && products.page < products.totalPages) {
        products.fetchList({ page: products.page + 1 })
      }
    },
    { rootMargin: '120px' },
  )
  if (sentinel.value) observer.observe(sentinel.value)
}

const filteredItems = computed(() => {
  const byCategory = selectedCategoryId.value === null
    ? products.items
    : products.items.filter((item) => Number(item.category_id || 0) === selectedCategoryId.value)

  if (activeFilter.value === 'all') return byCategory

  return byCategory.filter((item) => {
    const qty = Number(item.inventory_qty_base || 0)
    const minStock = Number(item.min_stock_qty || 0)

    if (activeFilter.value === 'out-stock') return qty <= 0
    if (activeFilter.value === 'low-stock') return qty > 0 && minStock > 0 && qty <= minStock
    if (activeFilter.value === 'in-stock') return qty > 0 && (minStock <= 0 || qty > minStock)
    return true
  })
})

const filterOptions = computed(() => {
  const countBy = (filter: FilterKey) => {
    if (filter === 'all') return products.items.length

    return products.items.filter((item) => {
      const qty = Number(item.inventory_qty_base || 0)
      const minStock = Number(item.min_stock_qty || 0)

      if (filter === 'out-stock') return qty <= 0
      if (filter === 'low-stock') return qty > 0 && minStock > 0 && qty <= minStock
      return qty > 0 && (minStock <= 0 || qty > minStock)
    }).length
  }

  return [
    { key: 'all' as const, label: 'Tất cả', count: countBy('all') },
    { key: 'in-stock' as const, label: 'Còn hàng', count: countBy('in-stock') },
    { key: 'low-stock' as const, label: 'Tồn thấp', count: countBy('low-stock') },
    { key: 'out-stock' as const, label: 'Hết hàng', count: countBy('out-stock') },
  ]
})

function getStockState(item: { inventory_qty_base: number; min_stock_qty: number | null }): { label: string; className: string } {
  const qty = Number(item.inventory_qty_base || 0)
  const minStock = Number(item.min_stock_qty || 0)

  if (qty <= 0) {
    return { label: 'Hết hàng', className: 'text-rose-500' }
  }

  if (minStock > 0 && qty <= minStock) {
    return { label: 'Tồn thấp', className: 'text-amber-600' }
  }

  return { label: 'Còn hàng', className: 'text-teal-600' }
}

function formatQty(value: number | null | undefined): string {
  return new Intl.NumberFormat('vi-VN').format(Number(value || 0))
}

async function handleSearch(): Promise<void> {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
    searchDebounceTimer = null
  }

  await products.fetchList({ page: 1, q: keyword.value.trim() || '' })
}

function handleFilterChange(value: string): void {
  activeFilter.value = value as FilterKey
}

async function clearAllFilters(): Promise<void> {
  activeFilter.value = 'all'
  selectedCategoryId.value = null
  keyword.value = ''
  await products.fetchList({ page: 1 })
}
async function clearSearch(): Promise<void> {
  keyword.value = ''
  console.log( keyword.value)
  await products.fetchList({ page: 1, q: '' })
}

function openCategoryFilter(): void {
  isCategoryFilterOpen.value = true
  if (!categories.items.length && !categories.loading) {
    categories.fetchList()
  }
}

function applyCategoryFilter(categoryId: number | null): void {
  selectedCategoryId.value = categoryId
  isCategoryFilterOpen.value = false
}

async function handleRefresh(): Promise<void> {
  await products.fetchList({ page: 1, q: keyword.value.trim() || undefined })
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
    <section class="space-y-5">
      <ProductListStickyHeader
        v-model="keyword"
        :filter-options="filterOptions"
        :active-filter="activeFilter"
        :has-active-filter="activeFilter !== 'all' || selectedCategoryId !== null"
        @submit="handleSearch"
        @update:active-filter="handleFilterChange"
        @open-category-filter="openCategoryFilter"
        @clear-all-filters="clearAllFilters"
        @clear-search="clearSearch"
      />

      <p v-if="products.error" class="rounded-2xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ products.error }}</p>

      <div class="space-y-2.5">
        <div v-if="products.loading" class="space-y-2.5">
          <div v-for="n in 6" :key="`s-${n}`" class="fancy-box fancy-box-soft tone-brand rounded-xl px-3 py-2">
            <div class="animate-pulse">
              <div class="flex items-start gap-3">
                <div class="flex min-w-0 flex-1 gap-3">
                  <div class="h-11 w-11 shrink-0 rounded-xl bg-slate-200"></div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <div class="h-3.5 w-40 rounded-md bg-slate-200"></div>
                      <div class="h-3.5 w-16 rounded-md bg-slate-200"></div>
                    </div>
                    <div class="mt-1 h-3 w-56 rounded-md bg-slate-200"></div>
                    <div class="mt-1 h-3 w-36 rounded-md bg-slate-200"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="filteredItems.length === 0" class="rounded-xl border border-slate-200 bg-white px-3 py-5 text-center text-sm text-slate-500">
          Không có dữ liệu sản phẩm.
        </div>

        <TransitionGroup name="fade-slide" tag="div" class="space-y-2.5">
          <RouterLink
            v-for="item in filteredItems"
            :key="item.id"
            :to="`/products/${item.id}/edit`"
            class="fancy-box fancy-box-soft tone-brand flex items-start gap-3 rounded-xl px-3 py-2"
          >
            <div class="flex min-w-0 flex-1 gap-3">
              <span class="product-row-icon inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-400">
                <Package2 :size="18" />
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <h3 class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</h3>
                  <span :class="getStockState(item).className" class="shrink-0 text-xs font-medium">
                    {{ getStockState(item).label }}
                  </span>
                </div>
                <p class="mt-0.5 truncate text-xs text-slate-400">
                  {{ item.code }} · {{ item.category_name || 'Chưa phân loại' }} · Kho: {{ formatQty(item.inventory_qty_base) }} {{ item.base_unit_name }}
                </p>
                <p class="mt-0.5">
                  <span class="text-sm font-semibold text-teal-700">{{ formatMoney(item.price_sell) }}</span>
                </p>
              </div>
            </div>
          </RouterLink>
        </TransitionGroup>
      </div>

      <div v-if="products.page < products.totalPages" ref="sentinel" class="h-1"></div>
      <div v-if="products.loading && products.page > 1" class="flex justify-center py-3">
        <div class="h-5 w-5 animate-spin rounded-full border-2 border-teal-500 border-t-transparent"></div>
      </div>
    </section>

    <RouterLink
      to="/products/new"
      class="fixed bottom-[calc(3.5rem+1rem)] right-4 z-40 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-teal-600 text-white shadow-[0_8px_20px_-8px_rgba(13,148,136,0.6)]"
      aria-label="Tạo sản phẩm mới"
    >
      <Plus :size="20" />
    </RouterLink>

    <AppModal
      :open="isCategoryFilterOpen"
      title="Chọn danh mục"
      @close="isCategoryFilterOpen = false"
    >
      <div class="space-y-2">
        <button
          type="button"
          class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-left text-sm"
          :class="selectedCategoryId === null ? 'border-teal-500 bg-teal-50 text-teal-700' : 'border-slate-200 bg-white text-slate-700'"
          @click="applyCategoryFilter(null)"
        >
          <span>Tất cả danh mục</span>
        </button>

        <div v-if="categories.loading" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">
          Đang tải danh mục...
        </div>

        <p v-else-if="categories.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ categories.error }}</p>

        <div v-else-if="categories.items.length === 0" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500">
          Chưa có danh mục.
        </div>

        <button
          v-for="category in categories.items"
          v-else
          :key="category.id"
          type="button"
          class="flex w-full items-center justify-between rounded-xl border px-3 py-2 text-left text-sm"
          :class="selectedCategoryId === category.id ? 'border-teal-500 bg-teal-50 text-teal-700' : 'border-slate-200 bg-white text-slate-700'"
          @click="applyCategoryFilter(category.id)"
        >
          <span>{{ category.name }}</span>
        </button>
      </div>
    </AppModal>
  </PullToRefresh>
</template>

<style scoped>
.product-row-icon {
  border: 1px dashed rgba(203, 213, 225, 0.95);
  background: linear-gradient(145deg, #ffffff, #f8fafc);
}

.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-12px);
}
</style>
