<script setup>
import { onMounted, reactive } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useInventoryReport } from '../composables/useInventoryReport';

const toast = useToast();
const { items, loading, error, load, refresh, adjust, adjustLoading, adjustError } = useInventoryReport();
const qtyMap = reactive({});

const syncQtyMap = () => {
  for (const item of items.value) {
    qtyMap[item.id] = String(item.qty_base ?? 0);
  }
};

const loadPage = async () => {
  try {
    await load();
    syncQtyMap();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo tồn kho.');
  }
};

const refreshPage = async () => {
  try {
    await refresh();
    syncQtyMap();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải báo cáo tồn kho.');
  }
};

const submitAdjust = async (item) => {
  try {
    const payload = { product_id: item.id, qty_base: qtyMap[item.id] ?? '' };
    const result = await adjust(payload);
    if (result?.success) {
      toast.success(result?.message || 'Đã cập nhật tồn kho.');
      await refreshPage();
      return;
    }
    toast.error(result?.message || adjustError.value || 'Không thể cập nhật tồn kho.');
  } catch (_err) {
    toast.error(adjustError.value || 'Không thể cập nhật tồn kho.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header class="app-card">
      <h1 class="text-lg font-semibold text-slate-900">Báo cáo tồn kho</h1>
      <p class="mt-1 text-sm text-slate-600">Cập nhật tồn kho trực tiếp từ API /api/reports/inventory.</p>
    </header>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!items.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Không có dữ liệu tồn kho.</div>

    <section v-else class="space-y-2">
      <article v-for="item in items" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <div class="font-medium text-slate-900">{{ item.name }}</div>
        <div class="mt-1 text-slate-500">{{ item.code }} - {{ item.category_name || 'Không có danh mục' }}</div>
        <div class="mt-2 flex flex-wrap items-end gap-2">
          <input v-model="qtyMap[item.id]" type="number" step="0.01" min="0" class="h-10 w-40 rounded-lg border border-slate-300 px-3" />
          <button type="button" class="h-10 rounded-lg bg-brand-600 px-3 text-white" :disabled="adjustLoading" @click="submitAdjust(item)">Cập nhật</button>
          <span class="text-slate-500">Đơn vị: {{ item.base_unit_name }}</span>
        </div>
      </article>
    </section>
  </section>
</template>
