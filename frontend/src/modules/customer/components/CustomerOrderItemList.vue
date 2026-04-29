<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { useFormat } from '../../../shared/composables/useFormat';
import { fetchOrderItems } from '../../order/services/order.api';
import { Eye } from '@lucide/vue';
import { useRouter } from 'vue-router';

const { formatMoney, formatDateTime, formatNumber } = useFormat();
const router = useRouter();

interface Props {
  orders: Record<string, any>[];
}

const props = defineProps<Props>();

// Cache for loaded items by order id
const itemsCache = ref<Record<string | number, { items: any[]; manual_items: any[]; loading: boolean; error: string | null }>>({});

// Get or initialize cache entry for an order
function getCacheEntry(orderId: number | string) {
  if (!itemsCache.value[orderId]) {
    itemsCache.value[orderId] = { items: [], manual_items: [], loading: false, error: null };
  }
  return itemsCache.value[orderId];
}

// Navigate to order detail
function viewOrderDetail(orderId: number | string) {
  router.push({ name: 'orders.detail', params: { id: orderId } });
}

// Load items for a specific order
async function loadItemsForOrder(orderId: number | string) {
  const entry = getCacheEntry(orderId);
  if (entry.loading || entry.items.length > 0 || entry.manual_items.length > 0) {
    return; // Already loaded or loading
  }

  entry.loading = true;
  entry.error = null;

  try {
    const response = await fetchOrderItems(orderId);
    entry.items = response?.data?.items || [];
    entry.manual_items = response?.data?.manual_items || [];
  } catch (err: any) {
    entry.error = err?.message || 'Không thể tải sản phẩm';
  } finally {
    entry.loading = false;
  }
}

// Setup intersection observer for lazy loading
const observerRef = ref<IntersectionObserver | null>(null);
const orderRefs = ref<Record<string | number, HTMLElement | null>>({});

function setupObserver() {
  if (observerRef.value) {
    observerRef.value.disconnect();
  }

  observerRef.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const orderId = entry.target.getAttribute('data-order-id');
          if (orderId) {
            loadItemsForOrder(orderId);
          }
        }
      });
    },
    { rootMargin: '100px' }
  );

  // Observe all order elements
  Object.entries(orderRefs.value).forEach(([id, el]) => {
    if (el) {
      observerRef.value?.observe(el);
    }
  });
}

// Watch for orders changes and setup observer after DOM updates
watch(() => props.orders, () => {
  nextTick(() => {
    setupObserver();
  });
}, { immediate: true });

// Combine regular items and manual items for display
function getCombinedItems(orderId: number | string) {
  const entry = itemsCache.value[orderId];
  if (!entry) return [];

  const regularItems = entry.items.map(item => ({
    ...item,
    type: 'product',
    name: item.product_name,
  }));

  const manualItems = entry.manual_items.map(item => ({
    ...item,
    type: 'manual',
    name: item.item_name,
  }));

  return [...regularItems, ...manualItems];
}
</script>

<template>
  <section class="space-y-3">
    <div class="text-sm font-medium text-slate-600">Lịch sử đơn hàng</div>
    <div v-if="!orders.length" class="app-empty-state">Chưa có đơn hàng nào.</div>
    <!-- Each order as separate card -->
    <div v-else class="space-y-4">
      <div
        v-for="order in orders"
        :key="order.id"
        :ref="(el) => { orderRefs[order.id] = el as HTMLElement }"
        :data-order-id="order.id"
        class="rounded-xl border border-slate-200 bg-white overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-2">
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <span class="text-sm text-slate-700">{{ formatDateTime(order.order_date) }}</span>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-brand-600 transition-colors"
              @click.stop="viewOrderDetail(order.id)"
              title="Xem chi tiết đơn hàng"
            >
              <Eye class="h-4 w-4" />
            </button>
          </div>
          <div class="text-sm font-medium text-slate-900 shrink-0 ml-4">
            {{ formatMoney(order.total_amount) }}
          </div>
        </div>

        <!-- Items List -->
        <div class="divide-y divide-slate-100">
          <!-- Loading State -->
          <div v-if="getCacheEntry(order.id).loading" class="px-4 py-3 text-center">
            <div class="inline-flex items-center gap-2 text-sm text-slate-400">
              <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Đang tải...</span>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="getCacheEntry(order.id).error" class="px-4 py-3 text-center">
            <div class="text-sm text-rose-500">{{ getCacheEntry(order.id).error }}</div>
          </div>

          <!-- Empty Items -->
          <div v-else-if="getCombinedItems(order.id).length === 0 && !getCacheEntry(order.id).loading" class="px-4 py-3 text-center">
            <div class="text-sm text-slate-400">Không có sản phẩm</div>
          </div>

          <!-- Items: Group 1 (name, qty, price) flex-wrap + Group 2 (total) separate -->
          <div
            v-for="item in getCombinedItems(order.id)"
            :key="`${order.id}-${item.id}`"
            class="flex items-center justify-between gap-3 px-4 py-2 hover:bg-slate-50/60"
          >
            <!-- Cụm 1: Tên SP + SL + Giá bán (flex-wrap) -->
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 flex-1 min-w-0">
              <div class="text-sm font-medium text-slate-800">
                {{ item.name }}
              </div>
              <div class="text-sm">
                <span class="font-semibold text-brand-600">{{ formatNumber(item.qty) }}</span> <span class="text-slate-500">{{ item.unit_name }}</span>
              </div>
              <div class="text-sm text-amber-700">
                {{ formatMoney(item.price_sell) }}
              </div>
            </div>
            <!-- Cụm 2: Tổng (manual items use amount_sell, regular items use amount) -->
            <div class="shrink-0 text-sm font-semibold text-emerald-700 w-24 text-right">
              {{ formatMoney(item.amount || item.amount_sell || 0) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
