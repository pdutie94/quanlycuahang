<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { useFormat } from '../../../shared/composables/useFormat';
import { fetchPurchaseItems } from '../../purchase/services/purchase.api';
import { Eye } from '@lucide/vue';
import { useRouter } from 'vue-router';

const { formatMoney, formatDateTime, formatNumber } = useFormat();
const router = useRouter();

interface Props {
  purchases: Record<string, any>[];
}

const props = defineProps<Props>();

// Cache for loaded items by purchase id
const itemsCache = ref<Record<string | number, { items: any[]; manual_items: any[]; loading: boolean; error: string | null }>>({});

// Get or initialize cache entry for a purchase
function getCacheEntry(purchaseId: number | string) {
  if (!itemsCache.value[purchaseId]) {
    itemsCache.value[purchaseId] = { items: [], manual_items: [], loading: false, error: null };
  }
  return itemsCache.value[purchaseId];
}

// Navigate to purchase detail
function viewPurchaseDetail(purchaseId: number | string) {
  router.push({ name: 'purchases.detail', params: { id: purchaseId } });
}

// Load items for a specific purchase
async function loadItemsForPurchase(purchaseId: number | string) {
  const entry = getCacheEntry(purchaseId);
  if (entry.loading || entry.items.length > 0 || entry.manual_items.length > 0) {
    return; // Already loaded or loading
  }

  entry.loading = true;
  entry.error = null;

  try {
    const response = await fetchPurchaseItems(purchaseId);
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
const purchaseRefs = ref<Record<string | number, HTMLElement | null>>({});

function setupObserver() {
  if (observerRef.value) {
    observerRef.value.disconnect();
  }

  observerRef.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const purchaseId = entry.target.getAttribute('data-purchase-id');
          if (purchaseId) {
            loadItemsForPurchase(purchaseId);
          }
        }
      });
    },
    { rootMargin: '100px' }
  );

  // Observe all purchase elements
  Object.entries(purchaseRefs.value).forEach(([id, el]) => {
    if (el) {
      observerRef.value?.observe(el);
    }
  });
}

// Watch for purchases changes and setup observer after DOM updates
watch(() => props.purchases, () => {
  nextTick(() => {
    setupObserver();
  });
}, { immediate: true });

// Combine regular items and manual items for display
function getCombinedItems(purchaseId: number | string) {
  const entry = itemsCache.value[purchaseId];
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
    <div class="text-sm font-medium text-slate-600">Lịch sử nhập hàng</div>
    <div v-if="!purchases.length" class="app-empty-state">Chưa có phiếu nhập nào.</div>
    <!-- Each purchase as separate card -->
    <div v-else class="space-y-4">
      <div
        v-for="purchase in purchases"
        :key="purchase.id"
        :ref="(el) => { purchaseRefs[purchase.id] = el as HTMLElement }"
        :data-purchase-id="purchase.id"
        class="rounded-xl border border-slate-200 bg-white overflow-hidden"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-2">
          <div class="flex items-center gap-2 min-w-0 flex-1">
            <span class="text-sm text-slate-700">{{ formatDateTime(purchase.purchase_date) }}</span>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-brand-600 transition-colors"
              @click.stop="viewPurchaseDetail(purchase.id)"
              title="Xem chi tiết phiếu nhập"
            >
              <Eye class="h-4 w-4" />
            </button>
          </div>
          <div class="text-sm font-medium text-slate-900 shrink-0 ml-4">
            {{ formatMoney(purchase.total_amount) }}
          </div>
        </div>

        <!-- Items List -->
        <div class="divide-y divide-slate-100">
          <!-- Loading State -->
          <div v-if="getCacheEntry(purchase.id).loading" class="px-4 py-3 text-center">
            <div class="inline-flex items-center gap-2 text-sm text-slate-400">
              <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Đang tải...</span>
            </div>
          </div>

          <!-- Error State -->
          <div v-else-if="getCacheEntry(purchase.id).error" class="px-4 py-3 text-center">
            <div class="text-sm text-rose-500">{{ getCacheEntry(purchase.id).error }}</div>
          </div>

          <!-- Empty Items -->
          <div v-else-if="getCombinedItems(purchase.id).length === 0 && !getCacheEntry(purchase.id).loading" class="px-4 py-3 text-center">
            <div class="text-sm text-slate-400">Không có sản phẩm</div>
          </div>

          <!-- Items - Single line format: Tên + SL ĐVT + Giá + Tổng -->
          <div
            v-for="item in getCombinedItems(purchase.id)"
            :key="`${purchase.id}-${item.id}`"
            class="flex items-center justify-between gap-3 px-4 py-2 hover:bg-slate-50/60"
          >
            <!-- Tên sản phẩm -->
            <div class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800">
              {{ item.name }}
            </div>
            <!-- SL + ĐVT -->
            <div class="shrink-0 text-sm">
              <span class="font-semibold text-brand-600">{{ formatNumber(item.qty) }}</span>
              <span class="text-slate-500">{{ item.unit_name }}</span>
            </div>
            <!-- Giá nhập -->
            <div class="shrink-0 text-sm text-amber-700 w-20 text-right">
              {{ formatMoney(item.price_cost) }}
            </div>
            <!-- Tổng -->
            <div class="shrink-0 text-sm font-semibold text-emerald-700 w-24 text-right">
              {{ formatMoney(item.amount) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
