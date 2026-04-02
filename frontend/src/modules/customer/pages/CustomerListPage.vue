<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useCustomers } from '../composables/useCustomers';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';

const keyword = ref('');
const debtStatus = ref('');

const { items, meta, loading, error, load } = useCustomers();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();

const currencyFormatter = new Intl.NumberFormat('vi-VN');

const formatMoney = (amount) => currencyFormatter.format(Number(amount || 0));

const loadPage = async () => {
  try {
    await load({
      q: keyword.value,
      debt_status: debtStatus.value,
      page: page.value
    });
    setMeta(meta.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách khách hàng.');
  }
};

const applySearch = async () => {
  page.value = 1;
  await loadPage();
};

const applyDebtStatus = async (value) => {
  debtStatus.value = value;
  page.value = 1;
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
      <h1 class="text-lg font-semibold text-slate-900">Khách hàng</h1>

      <form class="mt-3 flex gap-2" @submit.prevent="applySearch">
        <input
          v-model="keyword"
          type="search"
          placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
          class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
        />
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
          :class="debtStatus === '' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyDebtStatus('')"
        >
          Tất cả
        </button>
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="debtStatus === 'debt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyDebtStatus('debt')"
        >
          Còn nợ
        </button>
        <button
          type="button"
          class="rounded-lg border px-3 py-1 text-sm font-medium"
          :class="debtStatus === 'nodebt' ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
          :disabled="loading"
          @click="applyDebtStatus('nodebt')"
        >
          Không nợ
        </button>
      </div>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">
        Đang tải...
      </div>

      <div v-else-if="!items.length" class="app-empty-state">
        Chưa có khách hàng nào.
      </div>

      <RouterLink
        v-for="item in items"
        v-else
        :key="item.id"
        :to="{ name: 'customers.detail', params: { id: item.id } }"
        class="app-list-card"
      >
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <div class="truncate text-sm font-medium text-slate-900">{{ item.name }}</div>
            <div class="mt-1 space-y-1 text-sm text-slate-600">
              <div v-if="item.phone">SĐT: {{ item.phone }}</div>
              <div v-if="item.address" class="line-clamp-1">Địa chỉ: {{ item.address }}</div>
            </div>
          </div>
          <div class="flex flex-col items-end gap-1 text-right text-sm">
            <div class="text-slate-500">Nợ hiện tại</div>
            <div :class="Number(item.debt_amount || 0) > 0 ? 'font-medium text-rose-600' : 'font-medium text-slate-700'">
              {{ formatMoney(item.debt_amount) }}
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