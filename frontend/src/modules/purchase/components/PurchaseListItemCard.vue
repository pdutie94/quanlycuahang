
<template>
  <div class="app-list-card min-w-0 flex-1 cursor-pointer">
    <template v-if="showCodeOnTop">
      <div class="flex items-center justify-between gap-2">
        <div class="truncate text-sm font-mono font-semibold text-brand-700">#{{ purchase.purchase_code }}</div>
        <span
          class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold"
          :class="purchase.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'"
        >
          {{ purchase.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}
        </span>
      </div>
      <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-600 leading-none">
        <span class="truncate">{{ formatDateTime(purchase.purchase_date) }}</span>
      </div>
    </template>
    <template v-else>
      <div class="flex items-center justify-between gap-2">
        <div v-if="showSupplierName" class="truncate text-sm font-medium text-slate-900">
          {{ purchase.supplier_name || supplierNameFallback }}
        </div>
        <span
          class="inline-flex shrink-0 items-center rounded-md px-2 py-0.5 text-xs font-semibold"
          :class="purchase.status === 'paid' ? 'bg-brand-50 text-brand-700' : 'bg-amber-50 text-amber-700'"
        >
          {{ purchase.status === 'paid' ? 'Đã thanh toán' : 'Còn nợ' }}
        </span>
      </div>
      <div class="mt-1 flex items-center gap-1 truncate text-sm text-slate-600 leading-none">
        <span class="truncate">{{ purchase.purchase_code }}</span>
        <span class="text-slate-300">·</span>
        <span class="truncate">{{ formatDateTime(purchase.purchase_date) }}</span>
      </div>
    </template>
    <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600">
      <span>
        Tổng:
        <span class="font-medium text-slate-900">{{ formatMoney(purchase.total_amount) }}</span>
      </span>
      <span>
        Trả:
        <span class="font-medium text-brand-600">{{ formatMoney(purchase.paid_amount) }}</span>
      </span>
      <span>
        Nợ:
        <span
          class="font-medium"
          :class="Number(purchase.total_amount || 0) - Number(purchase.paid_amount || 0) > 0 ? 'text-rose-600' : 'text-slate-700'"
        >
          {{ formatMoney(Number(purchase.total_amount || 0) - Number(purchase.paid_amount || 0)) }}
        </span>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  purchase: any;
  supplierNameFallback?: string;
  formatMoney: (value: any) => string;
  formatDateTime: (value: any) => string;
  showSupplierName?: boolean;
  showCodeOnTop?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  supplierNameFallback: 'Chưa có nhà cung cấp',
  showSupplierName: true,
  showCodeOnTop: false,
});
</script>
