<script setup lang="ts">
import { BarChart2, Package, User, Truck, Tag, DollarSign } from '@lucide/vue';
import { RouterLink } from 'vue-router';

defineProps<{
  active?: string; // route path đang active (nếu cần highlight)
}>();

const navs = [
  {
    to: '/reports/sales',
    label: 'Doanh thu chi tiết',
    icon: BarChart2,
    class: 'border-brand-300 bg-brand-50 text-brand-700',
    iconClass: 'bg-brand-500'
  },
  {
    to: '/reports/inventory',
    label: 'Cập nhật tồn kho',
    icon: Package,
    class: 'border-sky-300 bg-sky-50 text-sky-700',
    iconClass: 'bg-sky-500'
  },
  {
    to: '/reports/customer-debt',
    label: 'Công nợ KH',
    icon: User,
    class: 'border-rose-300 bg-rose-50 text-rose-700',
    iconClass: 'bg-rose-500'
  },
  {
    to: '/reports/supplier-debt',
    label: 'Công nợ NCC',
    icon: Truck,
    class: 'border-violet-300 bg-violet-50 text-violet-700',
    iconClass: 'bg-violet-500'
  },
  {
    to: '/reports/missing-cost',
    label: 'Giá vốn thiếu',
    icon: Tag,
    class: 'border-amber-300 bg-amber-50 text-amber-700',
    iconClass: 'bg-amber-500'
  },
  {
    to: '/reports/cost-update',
    label: 'Giá vốn SP',
    icon: DollarSign,
    class: 'border-green-300 bg-green-50 text-green-700',
    iconClass: 'bg-green-500'
  }
];
</script>

<template>
  <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-2 overflow-x-auto">
    <RouterLink
      v-for="nav in navs"
      :key="nav.to"
      :to="nav.to"
      class="inline-flex items-center gap-1 rounded-lg border pl-1 pr-2 py-1 text-sm font-medium"
      :class="nav.class + (active === nav.to ? ' ring-2 ring-brand-500' : '')"
    >
      <span class="inline-flex h-6 w-6 items-center justify-center !rounded-[0.55rem] text-white" :class="nav.iconClass">
        <component :is="nav.icon" class="h-4 w-4" />
      </span>
      <span>{{ nav.label }}</span>
    </RouterLink>
  </div>
</template>
