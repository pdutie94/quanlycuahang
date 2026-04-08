<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import { useFormat } from '../composables/useFormat';
const { formatMoney } = useFormat();

const props = defineProps({
  customer: {
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


const debtAmount = computed(() => {
  const val = props.customer?.debt_amount;
  return val === undefined || val === null ? null : Number(val);
});
const totalAmount = computed(() => {
  const val = props.customer?.total_amount;
  return val === undefined || val === null ? null : Number(val);
});
const paidAmount = computed(() => {
  const val = props.customer?.paid_amount;
  return val === undefined || val === null ? null : Number(val);
});

const rootTag = computed(() => (props.linkEnabled ? RouterLink : 'div'));
const rootTo = computed(() => {
  if (props.to) return props.to;
  return { name: 'customers.detail', params: { id: props.customer?.id } };
});
</script>

<template>
  <component
    :is="rootTag"
    class="app-list-card"
    v-bind="linkEnabled ? { to: rootTo } : {}"
  >
    <div class="min-w-0 flex-1">
      <div class="min-w-0 truncate text-sm font-medium text-slate-900">{{ customer.name || 'Khách lẻ' }}</div>
      <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-600 leading-none">
        <span v-if="customer.phone" class="truncate">{{ customer.phone || 'Chưa có SĐT' }}</span>
        <span v-if="customer.phone && customer.address" class="text-slate-300">·</span>
        <span v-if="customer.address" class="truncate">{{ customer.address || 'Chưa có địa chỉ' }}</span>
      </div>
      <div class="text-sm text-slate-500 mt-0.5">
        Nợ:
        <span class="font-medium" :class="debtAmount > 0 ? 'text-rose-600' : 'text-slate-700'">
          {{ debtAmount !== null ? formatMoney(debtAmount) : '—' }}
        </span>
        <span class="ml-2">
          Tổng:
          <span class="font-medium text-slate-900">
            {{ totalAmount !== null ? formatMoney(totalAmount) : '—' }}
          </span>
        </span>
        <span class="ml-2">
          Trả:
          <span class="font-medium text-brand-700">
            {{ paidAmount !== null ? formatMoney(paidAmount) : '—' }}
          </span>
        </span>
      </div>
    </div>
  </component>
</template>
