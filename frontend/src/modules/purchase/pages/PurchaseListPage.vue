<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { usePurchases } from '../composables/usePurchases';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';

const keyword = ref('');
const supplierId = ref('');
const fromDate = ref('');
const toDate = ref('');
const showFilters = ref(false);

const { items, suppliers, meta, loading, error, load } = usePurchases();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();

const formatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => `${formatter.format(Number(amount || 0))} đ`;
const formatDateTime = (value) => {
  if (!value) return '';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' }).format(date);
};

const loadPage = async () => {
  try {
    await load({ q: keyword.value, supplier_id: supplierId.value, from_date: fromDate.value, to_date: toDate.value, page: page.value });
    setMeta(meta.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách phiếu nhập.');
  }
};

const applyFilters = async () => {
  page.value = 1;
  showFilters.value = false;
  await loadPage();
};

watch(page, async () => {
  await loadPage();
});

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-3">
    <header class="app-card">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Phiếu nhập hàng</h1>
        </div>
        <RouterLink to="/purchases/create" class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white">Tạo phiếu</RouterLink>
      </div>

      <form class="mt-3 flex gap-2" @submit.prevent="applyFilters">
        <input v-model="keyword" type="search" placeholder="Tìm theo mã phiếu, nhà cung cấp, SĐT..." class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500" />
        <button type="button" class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700" @click="showFilters = !showFilters">Lọc</button>
        <button type="submit" class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white">Tìm</button>
      </form>

      <div v-if="showFilters" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="grid gap-3 md:grid-cols-3">
          <select v-model="supplierId" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500">
            <option value="">Tất cả nhà cung cấp</option>
            <option v-for="supplier in suppliers" :key="supplier.id" :value="String(supplier.id)">{{ supplier.name }}</option>
          </select>
          <input v-model="fromDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
          <input v-model="toDate" type="date" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500" />
        </div>
        <div class="mt-4 flex items-center justify-between gap-2">
          <button type="button" class="text-sm font-medium text-slate-500" @click="supplierId = ''; fromDate = ''; toDate = ''; applyFilters()">Xóa lọc</button>
          <div class="flex gap-2">
            <button type="button" class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700" @click="showFilters = false">Đóng</button>
            <button type="button" class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white" @click="applyFilters">Áp dụng</button>
          </div>
        </div>
      </div>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có phiếu nhập hàng nào.</div>
      <RouterLink v-for="item in items" v-else :key="item.id" :to="{ name: 'purchases.detail', params: { id: item.id } }" class="app-list-card">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <div class="text-sm font-mono font-semibold text-brand-700">#{{ item.purchase_code }}</div>
              <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="item.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'">{{ item.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ formatDateTime(item.purchase_date) }}</div>
            <div class="mt-1 text-sm text-slate-600">Nhà cung cấp: <span class="font-medium text-slate-800">{{ item.supplier_name }}</span></div>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
              <span>Tổng: <span class="font-medium text-slate-900">{{ formatMoney(item.total_amount) }}</span></span>
              <span>Đã trả: <span class="font-medium text-brand-600">{{ formatMoney(item.paid_amount) }}</span></span>
              <span>Còn nợ: <span class="font-medium" :class="Number(item.total_amount || 0) - Number(item.paid_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-700'">{{ formatMoney(Number(item.total_amount || 0) - Number(item.paid_amount || 0)) }}</span></span>
            </div>
          </div>
        </div>
      </RouterLink>
    </div>

    <footer class="app-card flex items-center justify-between px-4 py-3">
      <span class="text-sm text-slate-600">Trang {{ page }} / {{ totalPages }}</span>
      <div class="flex gap-2">
        <button class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50" :disabled="!canPrev || loading" @click="prev">Trước</button>
        <button class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50" :disabled="!canNext || loading" @click="next">Sau</button>
      </div>
    </footer>
  </section>
</template>