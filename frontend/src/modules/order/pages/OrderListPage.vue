<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useOrders } from '../composables/useOrders';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const keyword = ref('');
const paymentStatus = ref('');
const orderStatus = ref('');
const fromDate = ref('');
const toDate = ref('');
const showAdvancedFilter = ref(false);

const { items, meta, loading, error, load } = useOrders();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();

const buildParams = () => ({
  q: keyword.value,
  status: paymentStatus.value,
  order_status: orderStatus.value,
  from_date: fromDate.value,
  to_date: toDate.value,
  page: page.value
});

const loadPage = async () => {
  try {
    await load(buildParams());
    setMeta(meta.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách đơn hàng.');
  }
};

const applySearch = async () => {
  page.value = 1;
  await loadPage();
};

const applyOrderStatus = async (value) => {
  orderStatus.value = value;
  page.value = 1;
  await loadPage();
};

const applyAdvancedFilter = async () => {
  page.value = 1;
  showAdvancedFilter.value = false;
  await loadPage();
};

const clearAdvancedFilter = async () => {
  paymentStatus.value = '';
  fromDate.value = '';
  toDate.value = '';
  page.value = 1;
  showAdvancedFilter.value = false;
  await loadPage();
};

const hasAdvancedFilter = computed(() => paymentStatus.value !== '' || fromDate.value !== '' || toDate.value !== '');

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
          <h1 class="text-lg font-semibold text-slate-900">Đơn hàng</h1>
        </div>
        <RouterLink
          :to="{ name: 'orders.create' }"
          class="inline-flex h-10 items-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white"
        >
          Tạo đơn
        </RouterLink>
      </div>

      <form class="mt-3 flex gap-2" @submit.prevent="applySearch">
        <input
          v-model="keyword"
          type="search"
          placeholder="Tìm theo mã đơn, tên khách, SĐT..."
          class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
        />
        <button
          type="button"
          class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700"
          :disabled="loading"
          @click="showAdvancedFilter = !showAdvancedFilter"
        >
          Lọc
        </button>
        <button
          type="submit"
          class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white"
          :disabled="loading"
        >
          Tìm
        </button>
      </form>

      <div class="mt-3 flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="orderStatus === '' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('')"
        >
          Tất cả
        </button>
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'completed' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('completed')"
        >
          Hoàn thành
        </button>
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'pending' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('pending')"
        >
          Chưa hoàn thành
        </button>
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="orderStatus === 'cancelled' ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyOrderStatus('cancelled')"
        >
          Đã hủy
        </button>
      </div>

      <div v-if="showAdvancedFilter" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
          <label class="space-y-1 text-sm text-slate-700">
            <span>Trạng thái thanh toán</span>
            <select
              v-model="paymentStatus"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-brand-500"
            >
              <option value="">Tất cả</option>
              <option value="paid">Đã thanh toán</option>
              <option value="debt">Còn nợ</option>
            </select>
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

        <div class="mt-4 flex items-center justify-between gap-2">
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
              class="h-10 rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700"
              @click="showAdvancedFilter = false"
            >
              Đóng
            </button>
            <button
              type="button"
              class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white"
              :disabled="loading"
              @click="applyAdvancedFilter"
            >
              Áp dụng
            </button>
          </div>
        </div>
      </div>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">
        Đang tải...
      </div>

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có đơn hàng nào.
      </div>

      <OrderItemCard
        v-for="item in items"
        v-else
        :key="item.id"
        :order="item"
        :to="{ name: 'orders.detail', params: { id: item.id } }"
        customer-fallback="Khách lẻ"
      />
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