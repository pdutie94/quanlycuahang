
<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useFormat } from '../composables/useFormat';
const { formatMoney } = useFormat();

const props = defineProps({
  supplier: {
    type: Object,
    required: true
  },
  to: {
    type: [String, Object],
    default: null
  },
  linkEnabled: {
    type: Boolean,
    default: true
  }
});

const debtAmount = computed(() => Number(props.supplier?.debt_amount ?? 0));
const totalAmount = computed(() => Number(props.supplier?.total_amount ?? 0));
const paidAmount = computed(() => Number(props.supplier?.paid_amount ?? 0));

const rootTag = computed(() => (props.linkEnabled && props.to) ? RouterLink : 'div');
const rootTo = computed(() => props.to ? props.to : { name: 'suppliers.detail', params: { id: props.supplier?.id } });
</script>

<template>
  <component
    :is="rootTag"
    class="app-list-card"
    v-bind="linkEnabled && to ? { to: rootTo } : {}"
  >
    <div class="min-w-0 flex-1">
      <div class="min-w-0 truncate text-sm font-medium text-slate-900">{{ supplier.name || 'Nhà cung cấp' }}</div>
      <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-600 leading-none">
        <span v-if="supplier.phone" class="truncate">{{ supplier.phone || 'Không có SĐT' }}</span>
        <span v-if="supplier.phone && supplier.address" class="text-slate-300">·</span>
        <span v-if="supplier.address" class="truncate">{{ supplier.address || 'Không có địa chỉ' }}</span>
      </div>
      <div class="text-sm text-slate-500 mt-0.5">
        Nợ: <span class="font-medium" :class="debtAmount > 0 ? 'text-violet-700' : 'text-slate-700'">{{ formatMoney(debtAmount) }}</span>
        <span class="ml-2">Tổng: <span class="font-medium text-slate-900">{{ formatMoney(totalAmount) }}</span></span>
        <span class="ml-2">Trả: <span class="font-medium text-brand-700">{{ formatMoney(paidAmount) }}</span></span>
      </div>
    </div>
  </component>
</template>
