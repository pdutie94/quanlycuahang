<script setup lang="ts">
import ReportNavButtons from '../components/ReportNavButtons.vue';
import { useFormat } from '../../../shared/composables/useFormat';
import { ref, computed, onMounted, reactive, watch } from 'vue';
import { useToast } from '../../../shared/composables/useToast';
import { useCostUpdate } from '../composables/useCostUpdate';
import type { CostUpdateItem } from '../types';
import { nextTick } from 'vue';

const toast = useToast();
const { formatMoneyInput } = useFormat();
const { items, loading, error, load, refresh, update, updateLoading, updateError } = useCostUpdate();
const priceMap = reactive<Record<string, any>>({});
const rowLoading = reactive<Record<string, any>>({});

const keyword = ref('');
const search = ref('');
const searchTimeout = ref<any>(null);

// For focusing input on row click
const inputRefs = ref<any[]>([]);

const focusInput = (idx: number) => {
  nextTick(() => {
    // Defensive: inputRefs.value may be sparse if v-for changes
    const input = Array.isArray(inputRefs.value) ? inputRefs.value[idx] : null;
    if (input && typeof input.focus === 'function') {
      input.focus();
    }
  });
};

const filteredItems = computed(() => {
  if (!search.value) return items.value;
  const kw = search.value.trim().toLowerCase();
  return items.value.filter((item: CostUpdateItem) =>
    (item.name && item.name.toLowerCase().includes(kw)) ||
    (item.code && item.code.toLowerCase().includes(kw))
  );
});

const syncPriceMap = () => {
  for (const item of items.value) {
    priceMap[item.id] = formatMoneyInput(item.price_cost ?? '');
  }
};

const loadPage = async () => {
  try {
    await load();
    syncPriceMap();
  } catch (_err: unknown) {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
};

const refreshPage = async () => {
  try {
    await refresh();
    syncPriceMap();
  } catch (_err: unknown) {
    toast.error(error.value || 'Không thể tải danh sách sản phẩm.');
  }
};

const parseMoneyInput = (val: any) => {
  if (typeof val !== 'string') return val;
  return parseFloat(val.replaceAll('.', '').replace(',', '.'));
};

const submitUpdate = async (item: CostUpdateItem) => {
  try {
    rowLoading[item.id] = true;
    const raw = priceMap[item.id] ?? '';
    const parsed = parseMoneyInput(raw);
    const payload = { product_id: item.id, price_cost: isNaN(parsed) ? '' : parsed };
    const result = await update(payload);
    if (result?.success) {
      toast.success(result?.message || 'Đã cập nhật giá vốn.');
      await refreshPage();
      rowLoading[item.id] = false;
      return;
    }
    toast.error(result?.message || updateError.value || 'Không thể cập nhật giá vốn.');
  } catch (_err: unknown) {
    toast.error(updateError.value || 'Không thể cập nhật giá vốn.');
  } finally {
    rowLoading[item.id] = false;
  }
};

const onSearchInput = (e: Event) => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(() => {
    search.value = keyword.value;
  }, 300);
};

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-4">
    <header>
      <div>
        <h1 class="text-lg font-semibold text-slate-900">Cập nhật giá vốn</h1>
        <p class="text-slate-500 text-sm mt-1">Cập nhật giá vốn cho sản phẩm. Tìm kiếm theo tên hoặc mã.</p>
      </div>
      <ReportNavButtons />
    </header>
    <div class="flex items-center gap-2 mt-2">
        <input
          v-model="keyword"
          @input="onSearchInput"
          type="text"
          placeholder="Tìm kiếm tên, mã sản phẩm..."
          class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm focus:border-brand-500 outline-none"
        />
      </div>
    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">Đang tải...</div>
    <div v-else-if="!filteredItems.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">Không có dữ liệu sản phẩm.</div>

    <div v-else class="overflow-x-auto">
      <div class="border border-slate-300 rounded-xl overflow-hidden min-w-[340px] bg-white">
        <table class="min-w-full table-auto bg-white">
          <thead>
            <tr class="text-sm text-slate-600 bg-slate-50">
              <th class="p-2 text-left border-b border-slate-300">Sản phẩm</th>
              <th class="p-2 text-right border-b border-slate-300">Giá vốn</th>
            </tr>
          </thead>
          <tbody>
            <tr 
            v-for="(item, idx) in filteredItems" 
            :key="item.id" class="hover:bg-slate-50 transition cursor-pointer" 
            @click="focusInput(idx)"
            >
              <td
                class="p-2 align-top"
                :class="idx !== filteredItems.length - 1 ? 'border-b border-slate-200' : ''"
              >
                <div class="font-medium text-slate-900 text-sm">
                  <span class="truncate max-w-[180px]">{{ item.name }}</span>
                  <span class="text-xs text-slate-500"> - {{ item.base_unit_name }}</span>
                </div>
                <div class="text-xs text-slate-500 font-mono">{{ item.code }}</div>
              </td>
              <td
                class="p-2 text-right align-top"
                :class="idx !== filteredItems.length - 1 ? 'border-b border-slate-200' : ''"
              >
                <div class="flex items-center justify-end gap-2">
                  <input
                    v-model="priceMap[item.id]"
                    type="text"
                    v-money-input
                    min="0"
                    class="h-9 w-[6rem] md:w-28 rounded-md border border-slate-400 px-2 text-right text-sm focus:border-brand-500 outline-none"
                    :aria-label="`Giá vốn mới cho ${item.name}`"
                    @keyup.enter="submitUpdate(item)"
                    ref="inputRefs"
                  />
                  <button
                    type="button"
                    class="h-9 w-9 flex items-center justify-center rounded-md bg-brand-600 text-white text-xs font-semibold hover:bg-brand-700 disabled:opacity-60 shadow-sm"
                    :disabled="rowLoading[item.id]"
                    @click="submitUpdate(item)"
                    :aria-label="`Cập nhật giá vốn cho ${item.name}`"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
