<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye } from 'lucide-vue-next'
import { formatDate, formatMoney } from '../../lib/format'

export type OrderCardItem = {
  id: number
  orderCode?: string | null
  customerName?: string | null
  orderDate?: string | null
  totalAmount: number | string
  paidAmount?: number | string | null
  debtAmount?: number | string | null
  totalCost?: number | string | null
  profitAmount?: number | string | null
  orderStatus?: string | null
}

const props = withDefaults(
  defineProps<{
    order: OrderCardItem
    tone?: string
    showStatusChip?: boolean
  }>(),
  {
    tone: 'tone-sky',
    showStatusChip: true,
  },
)

const emit = defineEmits<{
  preview: [orderId: number]
}>()

function parseAmount(value: number | string | null | undefined): number {
  if (value === null || value === undefined || value === '') return 0
  const numeric = Number(String(value).replace(/[^0-9-]/g, ''))
  return Number.isFinite(numeric) ? numeric : 0
}

const totalAmount = computed(() => parseAmount(props.order.totalAmount))
const paidAmount = computed(() => parseAmount(props.order.paidAmount))
const debtAmount = computed(() => {
  if (props.order.debtAmount !== undefined && props.order.debtAmount !== null) {
    return parseAmount(props.order.debtAmount)
  }

  return Math.max(0, totalAmount.value - paidAmount.value)
})

const isPaidFull = computed(() => debtAmount.value <= 0)
const profitAmount = computed<number | null>(() => {
  if (props.order.profitAmount !== undefined && props.order.profitAmount !== null) {
    return parseAmount(props.order.profitAmount)
  }

  if (props.order.totalCost !== undefined && props.order.totalCost !== null) {
    return totalAmount.value - parseAmount(props.order.totalCost)
  }

  return null
})

const secondaryLabel = computed(() => (isPaidFull.value ? 'Lợi nhuận' : 'Còn nợ'))
const secondaryValue = computed(() => {
  if (isPaidFull.value) {
    return profitAmount.value
  }

  return debtAmount.value
})

const chipClass = computed(() => (isPaidFull.value ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'))
const chipText = computed(() => (isPaidFull.value ? 'Đã xong' : 'Còn nợ'))

const normalizedCode = computed(() => props.order.orderCode || `DH-${props.order.id}`)
const normalizedCustomer = computed(() => props.order.customerName || 'Khách lẻ')
</script>

<template>
  <RouterLink
    :to="`/orders/${order.id}`"
    class="fancy-box fancy-box-soft block rounded-xl px-3 py-2"
    :class="tone"
  >
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="flex items-center gap-2">
          <p class="text-sm font-semibold text-slate-900">{{ normalizedCustomer }}</p>
          <span v-if="showStatusChip" class="rounded-md px-1.5 py-0.5 text-xs font-semibold" :class="chipClass">
            {{ chipText }}
          </span>
        </div>
        <p class="mt-1 text-xs text-slate-500">{{ normalizedCode }} · {{ formatDate(order.orderDate) }}</p>
      </div>
      <button
        type="button"
        class="mt-1 inline-flex rounded-lg border border-slate-300 bg-white/80 p-1 text-slate-600"
        @click.stop.prevent="emit('preview', order.id)"
      >
        <Eye :size="16" />
      </button>
    </div>

    <p class="mt-1 text-sm text-slate-700">
      Tổng: <strong>{{ formatMoney(totalAmount) }}</strong>
      <span class="ml-2" :class="isPaidFull ? 'text-emerald-700' : 'text-rose-700'">
        {{ secondaryLabel }}:
        <strong>{{ secondaryValue === null ? '--' : formatMoney(secondaryValue) }}</strong>
      </span>
    </p>
  </RouterLink>
</template>
