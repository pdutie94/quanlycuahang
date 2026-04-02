<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useSupplierList } from '../composables/useSupplierList';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';

const keyword = ref('');
const { suppliers, meta, loading, error, load } = useSupplierList();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();

const currencyFormatter = new Intl.NumberFormat('vi-VN');
const formatMoney = (amount) => currencyFormatter.format(Number(amount || 0));

const loadPage = async () => {
  try {
    await load(page.value, keyword.value);
    setMeta(meta.value);
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách nhà cung cấp.');
  }
};

const applySearch = async () => {
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
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Nhà cung cấp</h1>
        </div>
        <RouterLink to="/suppliers/create" class="inline-flex h-10 items-center rounded-xl bg-brand-600 px-4 text-sm font-medium text-white">Thêm nhà cung cấp</RouterLink>
      </div>

      <form class="mt-3 flex gap-2" @submit.prevent="applySearch">
        <input
          v-model="keyword"
          type="search"
          placeholder="Tìm kiếm theo tên, SĐT, địa chỉ..."
          class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
        />
        <button type="submit" class="h-10 rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">
          Tìm
        </button>
      </form>
    </header>

    <div class="space-y-3">
      <div v-if="loading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!suppliers.length" class="app-empty-state">Chưa có nhà cung cấp nào.</div>
      <RouterLink
        v-for="supplier in suppliers"
        v-else
        :key="supplier.id"
        :to="{ name: 'suppliers.detail', params: { id: supplier.id } }"
        class="app-list-card"
      >
        <div class="text-sm font-medium text-slate-900">{{ supplier.name }}</div>
        <div v-if="supplier.phone || supplier.address" class="mt-1 space-y-1 text-sm text-slate-600">
          <div v-if="supplier.phone">SĐT: {{ supplier.phone }}</div>
          <div v-if="supplier.address" class="line-clamp-1">Địa chỉ: {{ supplier.address }}</div>
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
