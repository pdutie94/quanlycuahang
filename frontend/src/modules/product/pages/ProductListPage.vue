<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { Box, CirclePlus, LayoutGrid, Search } from '@lucide/vue';
import { useProducts } from '../composables/useProducts';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';

const keyword = ref('');
const stockFilter = ref('all');
const { items, meta, loading, error, load } = useProducts();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();

const loadPage = async () => {
  try {
    await load({ q: keyword.value, stock: stockFilter.value, page: page.value });
    setMeta(meta.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
};

const applySearch = async () => {
  page.value = 1;
  await loadPage();
};

const applyStock = async (value) => {
  if (stockFilter.value === value) {
    return;
  }
  stockFilter.value = value;
  page.value = 1;
  await loadPage();
};

watch(page, async () => {
  await loadPage();
});

onMounted(async () => {
  await loadPage();
});

const formatMoney = (value) => Number(value || 0).toLocaleString('vi-VN');

const formatQty = (value) => Number(value || 0).toLocaleString('vi-VN');

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
    <header>
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="font-display text-xl font-bold text-slate-900 md:text-2xl">Sản phẩm</h1>
          <p class="mt-1 text-sm leading-6 text-slate-500">Quản lý danh sách sản phẩm đang bán.</p>
        </div>
        <RouterLink to="/products/create" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-white transition hover:bg-brand-700">
          <CirclePlus class="h-5 w-5" />
          <span>Tạo mới</span>
        </RouterLink>
      </div>

      <form class="mt-3" @submit.prevent="applySearch">
        <div class="flex items-center gap-2">
          <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
              <Search class="h-4 w-4" />
            </span>
            <input
              v-model="keyword"
              type="search"
              placeholder="Tìm kiếm theo tên, SKU..."
              class="h-10 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none focus:border-brand-500"
            />
          </div>
          <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" aria-label="Lưới">
            <LayoutGrid class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-2 flex items-center gap-2 overflow-x-auto pb-0.5 text-sm">
          <button type="button" class="border inline-flex min-h-8 shrink-0 items-center rounded-chip px-3 py-1.5 text-sm font-medium" :class="stockFilter === 'all' ? 'border-brand-600 bg-brand-600 text-white' : 'bg-white text-slate-700 border-slate-300'" @click="applyStock('all')">Tất cả</button>
          <button type="button" class="border inline-flex min-h-8 shrink-0 items-center rounded-chip px-3 py-1.5 text-sm font-medium" :class="stockFilter === 'in_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'bg-white text-slate-700 border-slate-300'" @click="applyStock('in_stock')">Còn hàng</button>
          <button type="button" class="border inline-flex min-h-8 shrink-0 items-center rounded-chip px-3 py-1.5 text-sm font-medium" :class="stockFilter === 'low_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'bg-white text-slate-700 border-slate-300'" @click="applyStock('low_stock')">Tồn thấp</button>
          <button type="button" class="border inline-flex min-h-8 shrink-0 items-center rounded-chip px-3 py-1.5 text-sm font-medium" :class="stockFilter === 'out_of_stock' ? 'border-brand-600 bg-brand-600 text-white' : 'bg-white text-slate-700 border-slate-300'" @click="applyStock('out_of_stock')">Hết hàng</button>
        </div>
      </form>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có sản phẩm nào.</div>
      <RouterLink
        v-for="item in items"
        v-else
        :key="item.id"
        :to="{ name: 'products.edit', params: { id: item.id } }"
        class="app-list-card relative cursor-pointer"
      >
        <div class="flex items-center gap-2.5">
          <div class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-300">
            <Box class="h-4 w-4" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-x-2">
              <div class="truncate text-sm font-medium text-slate-900">{{ item.name }}</div>
              <span class="flex-none text-sm font-medium" :class="getStatus(item).className">{{ getStatus(item).label }}</span>
            </div>

            <div class="mt-0.5 flex items-center gap-1 truncate text-sm text-slate-500">
              <span>{{ item.code || '-' }}</span>
              <span class="text-slate-300">·</span>
              <span class="truncate">{{ getCategoryName(item) }}</span>
              <span class="text-slate-300">·</span>
              <span>Kho: {{ formatQty(item.inventory_qty_base || item.qty_base) }} {{ item.base_unit_name || '' }}</span>
            </div>

            <div class="mt-0.5 flex items-center gap-x-2 text-sm">
              <span class="font-semibold text-brand-600">{{ formatMoney(item.price_sell) }} đ<span v-if="getPrimaryUnit(item)">/{{ getPrimaryUnit(item) }}</span></span>
              <span v-if="Number(item.price_cost || 0) > 0" class="text-slate-500">{{ formatMoney(item.price_cost) }} đ<span v-if="getPrimaryUnit(item)">/{{ getPrimaryUnit(item) }}</span></span>
            </div>
          </div>
        </div>
      </RouterLink>
    </div>

    <footer class="app-card flex items-center justify-between px-4 py-3">
      <span class="text-sm text-slate-600">Trang {{ page }} / {{ totalPages }}</span>
      <div class="flex gap-2">
        <button
          class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50"
          :disabled="!canPrev || loading"
          @click="prev"
        >
          Trước
        </button>
        <button
          class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50"
          :disabled="!canNext || loading"
          @click="next"
        >
          Sau
        </button>
      </div>
    </footer>
  </section>
</template>
