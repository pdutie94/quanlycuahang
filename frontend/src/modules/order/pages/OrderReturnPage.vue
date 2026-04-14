<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useOrderReturn } from '../composables/useOrderReturn';
import { useToast } from '../../../shared/composables/useToast';
import DetailHeaderBar from '../../../shared/components/DetailHeaderBar.vue';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const { order, items, loading, error, load, submit, submitting, submitError } = useOrderReturn();

const orderId = computed(() => Number(route.params.id || 0));
const returnAll = ref(false);
const quantities = ref({});

import { useFormat } from '../../../shared/composables/useFormat';
const { formatMoney, formatDateTime, parseAmount, formatNumber } = useFormat();
// formatQty dùng formatNumber từ useFormat
const total = computed(() => Number(order.value?.total_amount || 0));
const paid = computed(() => Number(order.value?.paid_amount || 0));
const debt = computed(() => Math.max(total.value - paid.value, 0));

const submitReturn = async () => {
  if (orderId.value <= 0) {
    return;
  }

  try {
    const payload = {
      return_all: returnAll.value ? '1' : '0',
      return_qty: quantities.value
    };
    const result = await submit(orderId.value, payload);
    toast.success(result?.message || 'Đã ghi nhận trả hàng.');
    await router.push({ name: 'orders.detail', params: { id: orderId.value } });
  } catch (_err: any) {
    toast.error(submitError.value || 'Không thể ghi nhận trả hàng.');
  }
};

onMounted(async () => {
  if (orderId.value <= 0) {
    toast.error('Mã đơn hàng không hợp lệ.');
    return;
  }

  try {
    await load(orderId.value);
    const next: Record<number, string> = {};
    for (const item of items.value) {
      const id = Number(item.id || 0);
      if (id > 0) {
        next[id] = '';
      }
    }
    quantities.value = next;
  } catch (_err: unknown) {
    toast.error(error.value || 'Không thể tải thông tin trả hàng.');
  }
});
</script>

<template>
  <section class="space-y-4">
    <DetailHeaderBar :title="`Trả hàng đơn #${order?.order_code || orderId}`" :back-to="{ name: 'orders.detail', params: { id: route.params.id } }" />

    <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">Đang tải...</div>

    <template v-else-if="order">
      <section class="app-card text-sm">
        <div class="flex flex-wrap items-center gap-4">
          <div><span class="text-slate-500">Tổng tiền:</span> <span class="font-medium text-slate-900">{{ formatMoney(total) }}</span></div>
          <div><span class="text-slate-500">Đã thu:</span> <span class="font-medium text-brand-700">{{ formatMoney(paid) }}</span></div>
          <div><span class="text-slate-500">Còn nợ:</span> <span class="font-medium" :class="debt > 0 ? 'text-rose-700' : 'text-slate-900'">{{ formatMoney(debt) }}</span></div>
        </div>
      </section>

      <section class="app-card space-y-3">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="returnAll" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-brand-600" />
          <span>Trả toàn bộ số lượng</span>
        </label>

        <div v-if="!items.length" class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Đơn hàng không có mặt hàng nào để trả.</div>

        <div v-else class="space-y-3">
          <div v-for="item in items" :key="item.id" class="rounded-xl border border-slate-200 p-3">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <div class="font-medium text-slate-900">{{ item.product_name }}</div>
                <div class="mt-1 text-sm text-slate-600">Đã bán: <span class="font-medium text-slate-900">{{ formatNumber(item.qty) }}</span> {{ item.unit_name }}</div>
                <div class="mt-1 text-sm text-slate-500">Đơn giá: <span class="font-medium text-slate-700">{{ formatMoney(item.price_sell) }}</span></div>
              </div>
              <div class="w-full max-w-36">
                <label class="mb-1 block text-sm text-slate-600">Số lượng trả</label>
                <input
                  v-model="(quantities as any)[item.id]"
                  :disabled="returnAll"
                  type="number"
                  min="0"
                  :max="item.qty"
                  step="0.01"
                  class="h-10 w-full rounded-xl border border-slate-300 px-3 text-right text-sm outline-none focus:border-brand-500 disabled:bg-slate-100"
                  placeholder="0"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <RouterLink :to="{ name: 'orders.detail', params: { id: route.params.id } }" class="inline-flex h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-medium text-slate-700">Hủy</RouterLink>
          <button type="button" class="inline-flex h-10 items-center rounded-xl border border-rose-600 bg-rose-600 px-4 text-sm font-medium text-white disabled:opacity-50" :disabled="submitting" @click="submitReturn">Ghi nhận trả hàng</button>
        </div>
      </section>
    </template>

    <div v-else class="app-empty-state">Không thể tải dữ liệu trả hàng.</div>
  </section>
</template>
