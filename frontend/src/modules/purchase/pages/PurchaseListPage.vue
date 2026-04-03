<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import { X } from '@lucide/vue';
import { usePurchases } from '../composables/usePurchases';
import { useToast } from '../../../shared/composables/useToast';
import { useInfiniteList } from '../../../shared/composables/useInfiniteList';
import ListHeaderBar from '../../../shared/components/ListHeaderBar.vue';

const keyword = ref('');
const supplierId = ref('');
const fromDate = ref('');
const toDate = ref('');
const showFilters = ref(false);

const { items, suppliers, meta, loading, error, load } = usePurchases();
const toast = useToast();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;
const formatDateTime = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
};

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
  fetchPage: (page) => load({ q: keyword.value, supplier_id: supplierId.value, from_date: fromDate.value, to_date: toDate.value, page }),
  onError: () => {
    toast.error(error.value || 'Không thể tải danh sách phiếu nhập.');
  }
});

const applyFilters = async () => {
  showFilters.value = false;
  await refresh();
};
</script>

<template>
  <section class="space-y-3">
    <ListHeaderBar
      v-model="keyword"
      title="Phiếu nhập hàng"
      subtitle="Quản lý danh sách phiếu nhập hàng và công nợ nhập."
      :create-to="{ name: 'purchases.create' }"
      create-label="Tạo phiếu"
      search-placeholder="Tìm theo mã phiếu, nhà cung cấp, SĐT..."
      filter-type="filter"
      @search="applyFilters"
      @filter-click="showFilters = true"
    />

    <Teleport to="body">
      <transition name="app-modal-fade-up">
        <div v-if="showFilters" class="app-modal-overlay app-modal-open" @click.self="showFilters = false">
          <div class="app-modal-sheet-sm">
            <div class="app-modal-header">
              <h2 class="app-modal-title">Lọc phiếu nhập</h2>
              <button type="button" class="app-modal-close" @click="showFilters = false">
                <X class="h-4 w-4" />
              </button>
            </div>
            <div class="app-modal-body space-y-4">
              <div class="grid gap-3">
          <select v-model="supplierId" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500">
            <option value="">Tất cả nhà cung cấp</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">{{ supplier.name }}</option>
          </select>
          <input v-model="fromDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
          <input v-model="toDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
        </div>

              <div class="flex items-center justify-between gap-2 pt-1">
                <button type="button" class="text-sm font-medium text-slate-500" @click="supplierId = ''; fromDate = ''; toDate = ''; applyFilters()">Xóa lọc</button>
                <div class="flex gap-2">
                  <button type="button" class="app-btn-secondary" @click="showFilters = false">Đóng</button>
                  <button type="button" class="app-btn-primary" @click="applyFilters">Áp dụng</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </Teleport>

    <div class="space-y-3">
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có phiếu nhập hàng nào.</div>
      <template v-else>
        <transition-group name="app-list-fade" tag="div" class="space-y-3" appear>
          <RouterLink v-for="item in items" :key="item.id" :to="{ name: 'purchases.detail', params: { id: item.id } }" class="app-list-card">
            <div class="space-y-1.5">
              <div class="flex items-center justify-between gap-2">
                <div class="truncate text-sm font-medium text-slate-900">{{ item.supplier_name || 'Chưa có nhà cung cấp' }}</div>
                <span class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="item.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">{{ item.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}</span>
              </div>
              <div class="flex items-center gap-1 truncate text-sm text-slate-600">
                <span class="truncate font-mono text-brand-700">#{{ item.purchase_code }}</span>
                <span class="text-slate-300">·</span>
                <span class="truncate text-slate-500">{{ formatDateTime(item.purchase_date) }}</span>
              </div>
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
                <span>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(item.total_amount) }}</span></span>
                <span>Trả: <span class="font-medium text-brand-600">{{ formatMoney(item.paid_amount) }}</span></span>
                <span>Nợ: <span class="font-medium" :class="Number(item.total_amount || 0) - Number(item.paid_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(Number(item.total_amount || 0) - Number(item.paid_amount || 0)) }}</span></span>
              </div>
            </div>
          </RouterLink>
        </transition-group>
      </template>
    </div>

    <div v-if="items.length" class="px-2 py-1 text-center text-sm text-slate-500">
      <span v-if="loadingMore">Đang tải thêm...</span>
      <span v-else-if="!hasMore">Đã hiển thị hết danh sách.</span>
      <span v-else>Cuộn xuống để tải thêm</span>
    </div>
    <div v-if="items.length && hasMore" ref="infiniteSentinel" class="h-1 w-full"></div>
  </section>
</template>