<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import { useToast } from '../../../shared/composables/useToast';
import { useSalesReport } from '../composables/useSalesReport';
import OrderItemCard from '../../../shared/components/OrderItemCard.vue';

const toast = useToast();
const { rows, loading, error, load } = useSalesReport();
const form = reactive({ filter_mode: 'day', day: '', page: 1 });

const loadPage = async () => {
  try {
    await load({ filter_mode: form.filter_mode, day: form.day, page: form.page });
  } catch (_err) {
    toast.error(error.value || 'Khong the tai danh sach don hang.');
  }
};

onMounted(async () => {
  form.day = new Date().toISOString().slice(0, 10);
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Danh sach don hang theo bao cao</h1>
      <div class="mt-2 flex gap-2">
        <RouterLink to="/reports/sales" class="rounded-lg border border-slate-300 px-3 py-1 text-sm">Bao cao doanh thu</RouterLink>
      </div>
      <form class="mt-3 flex gap-2" @submit.prevent="loadPage">
        <input v-model="form.day" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        <button type="submit" class="h-10 rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Loc</button>
      </form>
    </header>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Dang tai...</div>
    <div v-else-if="!rows.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Khong co don hang.</div>

    <section v-else class="space-y-2">
      <OrderItemCard
        v-for="row in rows"
        :key="row.id"
        :order="{ ...row, order_code: row.order_code || row.code, order_date: row.order_date || row.doc_date }"
        :to="{ name: 'orders.detail', params: { id: row.id } }"
        customer-fallback="Khách lẻ"
      />
    </section>
  </section>
</template>
