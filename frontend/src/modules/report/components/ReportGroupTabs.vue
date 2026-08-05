<script setup lang="ts">
import { RouterLink } from 'vue-router';

type Group = 'sales' | 'debts' | 'costs';

const props = defineProps<{ group: Group }>();
const groups = {
  sales: [
    { to: '/reports/sales/analytics', label: 'Phân tích bán hàng' },
    { to: '/reports/sales/detail', label: 'Doanh thu chi tiết' },
    { to: '/reports/sales/products', label: 'Hiệu suất sản phẩm' },
  ],
  debts: [
    { to: '/reports/debts/customer', label: 'Công nợ khách hàng' },
    { to: '/reports/debts/supplier', label: 'Công nợ nhà cung cấp' },
  ],
  costs: [
    { to: '/reports/costs/missing', label: 'Giá vốn thiếu' },
    { to: '/reports/costs/update', label: 'Cập nhật giá vốn' },
  ],
} as const;
</script>

<template>
  <nav class="mt-3 flex max-w-full gap-2 overflow-x-auto border-b border-slate-200" aria-label="Nhóm báo cáo">
    <RouterLink
      v-for="tab in groups[props.group]"
      :key="tab.to"
      :to="tab.to"
      class="report-group-tab shrink-0 border-b-2 border-transparent px-3 py-2 text-sm font-medium text-slate-500 focus:outline-none focus:ring-0"
      active-class="report-group-tab-active"
    >
      {{ tab.label }}
    </RouterLink>
  </nav>
</template>

<style scoped>
.report-group-tab-active {
  border-bottom-color: #0d9488;
  color: #0f766e;
}

.report-group-tab:focus,
.report-group-tab:focus-visible {
  outline: none;
  box-shadow: none;
}
</style>
