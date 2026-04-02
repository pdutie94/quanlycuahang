<script setup>
import { onMounted, reactive } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useInventoryReport } from '../composables/useInventoryReport';

const toast = useToast();
const { items, loading, error, load, adjust, adjustLoading, adjustError } = useInventoryReport();
const qtyMap = reactive({});

const loadPage = async () => {
  try {
    await load();
    for (const item of items.value) {
      qtyMap[item.id] = String(item.qty_base ?? 0);
    }
  } catch (_err) {
    toast.error(error.value || 'Khong the tai bao cao ton kho.');
  }
};

const submitAdjust = async (item) => {
  try {
    const payload = { product_id: item.id, qty_base: qtyMap[item.id] ?? '' };
    const result = await adjust(payload);
    if (result?.success) {
      toast.success(result?.message || 'Da cap nhat ton kho.');
      await loadPage();
      return;
    }
    toast.error(result?.message || adjustError.value || 'Khong the cap nhat ton kho.');
  } catch (_err) {
    toast.error(adjustError.value || 'Khong the cap nhat ton kho.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Bao cao ton kho</h1>
      <p class="mt-1 text-sm text-slate-600">Cap nhat ton kho truc tiep tu API /api/reports/inventory.</p>
    </header>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Dang tai...</div>
    <div v-else-if="!items.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Khong co du lieu ton kho.</div>

    <section v-else class="space-y-2">
      <article v-for="item in items" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <div class="font-medium text-slate-900">{{ item.name }}</div>
        <div class="mt-1 text-slate-500">{{ item.code }} - {{ item.category_name || 'Khong danh muc' }}</div>
        <div class="mt-2 flex flex-wrap items-end gap-2">
          <input v-model="qtyMap[item.id]" type="number" step="0.01" min="0" class="h-10 w-40 rounded-lg border border-slate-300 px-3" />
          <button type="button" class="h-10 rounded-lg bg-brand-600 px-3 text-white" :disabled="adjustLoading" @click="submitAdjust(item)">Cap nhat</button>
          <span class="text-slate-500">Don vi: {{ item.base_unit_name }}</span>
        </div>
      </article>
    </section>
  </section>
</template>
