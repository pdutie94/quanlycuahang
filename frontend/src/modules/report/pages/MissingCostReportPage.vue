<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useMissingCostReport } from '../composables/useMissingCostReport';

const toast = useToast();
const { items, summary, loading, error, load, submit, submitLoading, submitError } = useMissingCostReport();
const form = reactive({ q: '', start_date: '', end_date: '' });
const selectedIds = ref([]);

const formatMoney = (amount) => `${new Intl.NumberFormat('vi-VN').format(Number(amount || 0))} đ`;

const allSelected = computed({
  get() {
    return items.value.length > 0 && selectedIds.value.length === items.value.length;
  },
  set(val) {
    selectedIds.value = val ? items.value.map((row) => Number(row.item_id)) : [];
  }
});

const loadPage = async () => {
  try {
    await load({ q: form.q, start_date: form.start_date, end_date: form.end_date });
    selectedIds.value = [];
  } catch (_err) {
    toast.error(error.value || 'Khong the tai bao cao gia von thieu.');
  }
};

const updateSelected = async () => {
  try {
    const result = await submit({ mode: 'selected', item_ids: selectedIds.value });
    if (result?.success) {
      toast.success(result?.message || 'Da cap nhat gia von.');
      await loadPage();
      return;
    }
    toast.error(result?.message || submitError.value || 'Khong the cap nhat gia von.');
  } catch (_err) {
    toast.error(submitError.value || 'Khong the cap nhat gia von.');
  }
};

const updateAll = async () => {
  try {
    const result = await submit({ mode: 'all' });
    if (result?.success) {
      toast.success(result?.message || 'Da cap nhat gia von.');
      await loadPage();
      return;
    }
    toast.error(result?.message || submitError.value || 'Khong the cap nhat gia von.');
  } catch (_err) {
    toast.error(submitError.value || 'Khong the cap nhat gia von.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Bao cao gia von thieu</h1>
      <form class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-4" @submit.prevent="loadPage">
        <label class="space-y-1">
          <span class="app-label">Từ khóa</span>
          <input v-model="form.q" type="text" placeholder="Tim theo ma don, san pham, khach hang" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <label class="space-y-1">
          <span class="app-label">Từ ngày</span>
          <input v-model="form.start_date" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <label class="space-y-1">
          <span class="app-label">Đến ngày</span>
          <input v-model="form.end_date" type="date" class="h-10 rounded-xl border border-slate-300 px-3 text-sm" />
        </label>
        <button type="submit" class="h-10 self-end rounded-xl bg-brand-600 px-4 text-sm font-medium text-white" :disabled="loading">Loc</button>
      </form>
      <div class="mt-3 flex flex-wrap gap-2">
        <button type="button" class="rounded-lg border border-slate-300 px-3 py-1 text-sm" :disabled="submitLoading || !selectedIds.length" @click="updateSelected">Cap nhat dong da chon</button>
        <button type="button" class="rounded-lg border border-brand-600 bg-brand-600 px-3 py-1 text-sm text-white" :disabled="submitLoading" @click="updateAll">Cap nhat tat ca</button>
      </div>
    </header>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">So dong</div><div class="mt-1 font-semibold">{{ Number(summary.item_count || 0) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">So don</div><div class="mt-1 font-semibold">{{ Number(summary.order_count || 0) }}</div></div>
      <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm"><div class="text-slate-500">Tang gia von du kien</div><div class="mt-1 font-semibold">{{ formatMoney(summary.total_delta_cost) }}</div></div>
    </section>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Dang tai...</div>
    <div v-else-if="!items.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Khong co dong thieu gia von.</div>

    <section v-else class="space-y-2">
      <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <input v-model="allSelected" type="checkbox" class="h-4 w-4" />
        <span>Chon tat ca</span>
      </label>
      <article v-for="item in items" :key="item.item_id" class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <label class="flex items-start gap-3">
          <input v-model="selectedIds" :value="Number(item.item_id)" type="checkbox" class="mt-1 h-4 w-4" />
          <div class="min-w-0 flex-1">
            <div class="font-medium text-slate-900">#{{ item.order_code }} - {{ item.product_name }}</div>
            <div class="mt-1 text-slate-500">{{ item.order_date }} | {{ item.customer_name || 'Khach le' }}</div>
            <div class="mt-1 flex flex-wrap gap-3">
              <span>SL: {{ item.qty }} {{ item.unit_name }}</span>
              <span>Gia von hien tai: {{ formatMoney(item.item_price_cost) }}</span>
              <span>Gia von de xuat: {{ formatMoney(item.unit_price_cost) }}</span>
            </div>
          </div>
        </label>
      </article>
    </section>
  </section>
</template>
