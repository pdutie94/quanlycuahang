<script setup lang="ts">
import { BarChart2, ChartPie, Package, Users, WalletCards } from '@lucide/vue';
import { RouterLink } from 'vue-router';

const navs = [
  { to: '/reports', label: 'Tổng quan', icon: ChartPie, class: 'border-indigo-300 bg-indigo-50 text-indigo-700', iconClass: 'bg-indigo-500' },
  { to: '/reports/sales/analytics', label: 'Bán hàng', icon: BarChart2, class: 'border-cyan-300 bg-cyan-50 text-cyan-700', iconClass: 'bg-cyan-500' },
  { to: '/reports/inventory', label: 'Tồn kho', icon: Package, class: 'border-sky-300 bg-sky-50 text-sky-700', iconClass: 'bg-sky-500' },
  { to: '/reports/debts/customer', label: 'Công nợ', icon: Users, class: 'border-rose-300 bg-rose-50 text-rose-700', iconClass: 'bg-rose-500' },
  { to: '/reports/costs/missing', label: 'Giá vốn', icon: WalletCards, class: 'border-amber-300 bg-amber-50 text-amber-700', iconClass: 'bg-amber-500' },
];
</script>

<template>
  <div class="mt-3 grid grid-cols-2 gap-2 overflow-x-auto md:grid-cols-3">
    <RouterLink
      v-for="nav in navs"
      :key="nav.to"
      :to="nav.to"
      class="report-nav-link inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-sm font-medium focus:outline-none focus:ring-0"
      :class="nav.class"
    >
      <span class="inline-flex h-6 w-6 items-center justify-center !rounded-[0.55rem] text-white" :class="nav.iconClass">
        <component :is="nav.icon" class="h-4 w-4" />
      </span>
      <span>{{ nav.label }}</span>
    </RouterLink>
  </div>
</template>

<style scoped>
.report-nav-link:focus,
.report-nav-link:focus-visible {
  outline: none;
  box-shadow: none;
}
</style>
