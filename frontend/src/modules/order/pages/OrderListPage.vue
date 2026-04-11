<script setup>
import { computed, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { X } from '@lucide/vue';
import { useOrders } from '../composables/useOrders';
import { useToast } from '../../../shared/composables/useToast';
import FilterClearChip from '../../../shared/components/FilterClearChip.vue';
import InfiniteListStatus from '../../../shared/components/InfiniteListStatus.vue';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';


const route = useRoute();
const router = useRouter();

const keyword = ref('');
const paymentStatus = ref('');
const orderStatus = ref('');
const fromDate = ref('');
const toDate = ref('');
const showAdvancedFilter = ref(false);

// Đọc filter từ URL query khi load
const initFromQuery = () => {
  keyword.value = String(route.query.q || '');
  paymentStatus.value = String(route.query.status || '');
  orderStatus.value = String(route.query.order_status || '');
  fromDate.value = String(route.query.from_date || '');
  toDate.value = String(route.query.to_date || '');
};

initFromQuery();

const { items, meta, loading, error, load } = useOrders();
const toast = useToast();


const buildParams = () => ({
  q: keyword.value,
  status: paymentStatus.value,
  order_status: orderStatus.value,
  from_date: fromDate.value,
  to_date: toDate.value
});


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
      status: paymentStatus.value,
      order_status: orderStatus.value,
      from_date: fromDate.value,
      to_date: toDate.value,
      page: route.query.page || page
    }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách đơn hàng.');
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

const applyOrderStatus = async (value) => {
  orderStatus.value = value;
  router.push({
    query: {
      ...route.query,
      order_status: value || undefined,
      page: undefined
    }
  });
};

const applyAdvancedFilter = async () => {
  showAdvancedFilter.value = false;
  router.push({
    query: {
      ...route.query,
      status: paymentStatus.value || undefined,
      from_date: fromDate.value || undefined,
      to_date: toDate.value || undefined,
      page: undefined
    }
  });
};

const clearAdvancedFilter = async () => {
  paymentStatus.value = '';
  fromDate.value = '';
  toDate.value = '';
  showAdvancedFilter.value = false;
  router.push({
    query: {
      ...route.query,
      status: undefined,
      from_date: undefined,
      to_date: undefined,
      page: undefined
    }
  });
};


const hasAdvancedFilter = computed(() => paymentStatus.value !== '' || fromDate.value !== '' || toDate.value !== '');
const hasAnyFilter = computed(() => orderStatus.value !== '' || hasAdvancedFilter.value);

const clearFilters = async () => {
  orderStatus.value = '';
  paymentStatus.value = '';
  fromDate.value = '';
  toDate.value = '';
  router.push({ query: {} });
};

// Watch query để reload data và sync filter từ URL
watch(
  () => route.query,
  async () => {
    initFromQuery();
    await refresh();
  },
  { immediate: true }
);
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
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
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'completed' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('completed')"
        >
          Hoàn thành
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'pending' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('pending')"
        >
          Chưa hoàn thành
        </button>
        <button
          type="button"
          class="border inline-flex items-center rounded-lg px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'cancelled' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('cancelled')"
        >
          Đã hủy
        </button>
        <RouterLink to="/orders/deleted" class="border inline-flex items-center rounded-lg border-rose-300 bg-rose-50 px-3 py-1 text-sm font-medium text-rose-700">
          Đơn đã xóa
        </RouterLink>
        <FilterClearChip :active="hasAnyFilter" @clear="clearFilters" />
      </template>
    </ListHeaderBar>

    <Teleport to="body">
      <transition name="app-modal-fade-up">
        <div v-if="showAdvancedFilter" class="app-modal-overlay app-modal-open" @click.self="showAdvancedFilter = false">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Lọc nâng cao</h2>
              <button type="button" class="app-modal-close" @click="showAdvancedFilter = false">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <label class="space-y-1 text-sm text-slate-700">
            <span>Trạng thái thanh toán</span>
            <div class="relative">
              <select
                v-model="paymentStatus"
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
              v-model="fromDate"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
          <label class="space-y-1 text-sm text-slate-700">
            <span>Đến ngày</span>
            <input
              v-model="toDate"
              type="date"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            />
          </label>
        </div>

              <div class="flex items-center justify-between gap-2 pt-1">
                <button
                  type="button"
                  class="text-sm font-medium text-slate-500 disabled:opacity-50"
                  :disabled="!hasAdvancedFilter || loading"
                  @click="clearAdvancedFilter"
                >
                  Xóa lọc
                </button>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="app-btn-secondary"
                    @click="showAdvancedFilter = false"
                  >
                    Đóng
                  </button>
                  <button
                    type="button"
                    class="app-btn-primary"
                    :disabled="loading"
                    @click="applyAdvancedFilter"
                  >
                    Áp dụng
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">
        Đang tải...
      </div>

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có đơn hàng nào.
      </div>
      <template v-else>
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
    </div>

    <InfiniteListStatus :visible="items.length > 0" :loading-more="loadingMore" :has-more="hasMore" />
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>