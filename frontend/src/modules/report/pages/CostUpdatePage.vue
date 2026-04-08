<script setup>
import ReportNavButtons from '../components/ReportNavButtons.vue';
import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney } = useFormat();

// Sử dụng formatter toàn cục từ main.js
const moneyFormatter = new Intl.NumberFormat('vi-VN');
function formatMoneyInputValue(rawValue) {
  const digits = String(rawValue ?? '').replace(/[^0-9]/g, '');
  if (!digits) return '';
  return moneyFormatter.format(Number(digits));
}
import { onMounted, reactive } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useCostUpdate } from '../composables/useCostUpdate';

const toast = useToast();
const { items, loading, error, load, refresh, update, updateLoading, updateError } = useCostUpdate();
const priceMap = reactive({});

function formatCost(val, minStep) {
  const step = Number(minStep) || 1;
  if (step >= 1) {
    return String(Math.round(Number(val)));
  }
  const decimals = step.toString().split('.')[1]?.length || 0;
  return Number(val).toFixed(decimals).replace(/\.0+$/, '');
}

const syncPriceMap = () => {
  for (const item of items.value) {
    priceMap[item.id] = formatMoneyInputValue(item.price_cost ?? 0);
  }
};

const loadPage = async () => {
  try {
    await load();
    syncPriceMap();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
};

const refreshPage = async () => {
  try {
    await refresh();
    syncPriceMap();
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
};

const parseMoneyInput = (val) => {
  // Chấp nhận cả số thập phân, loại bỏ dấu ngăn cách nghìn
  if (typeof val !== 'string') return val;
  return parseFloat(val.replaceAll('.', '').replace(',', '.'));
};

const submitUpdate = async (item) => {
  try {
    const raw = priceMap[item.id] ?? '';
    const parsed = parseMoneyInput(raw);
    const payload = { product_id: item.id, price_cost: isNaN(parsed) ? '' : parsed };
    const result = await update(payload);
    if (result?.success) {
      toast.success(result?.message || 'Đã cập nhật giá vốn.');
      await refreshPage();
      return;
    }
    toast.error(result?.message || updateError.value || 'Không thể cập nhật giá vốn.');
  } catch (_err) {
    toast.error(updateError.value || 'Không thể cập nhật giá vốn.');
  }
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header>
      <h1 class="text-lg font-semibold text-slate-900">Cập nhật giá vốn</h1>
      <ReportNavButtons />
    </header>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!items.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Không có dữ liệu sản phẩm.</div>

    <section v-else class="space-y-2">
      <article v-for="item in items" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-3 text-sm">
        <div class="text-sm font-medium text-slate-900">
          {{ item.name }} <span class="text-slate-500">- {{ item.base_unit_name }}</span>
        </div>
        <div class="text-slate-500 mt-1">Giá vốn hiện tại: <b>{{ formatMoney(item.price_cost) }}</b></div>
        <div class="mt-1 flex flex-wrap items-end gap-2">
          <input
            v-model="priceMap[item.id]"
            type="text"
            inputmode="numeric"
            pattern="[0-9.,]*"
            min="0"
            class="h-10 w-40 rounded-lg border border-slate-300 px-3"
          />
          <button type="button" class="h-10 rounded-lg bg-brand-600 px-3 text-white" :disabled="updateLoading" @click="submitUpdate(item)">Cập nhật</button>
        </div>
      </article>
    </section>
  </section>
</template>
