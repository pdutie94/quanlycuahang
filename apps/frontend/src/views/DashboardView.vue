<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useDashboardStore } from '../stores/dashboard'
import { formatDate, formatMoney } from '../lib/format'

const dashboard = useDashboardStore()

const cards = computed(() => [
  {
    key: 'today_revenue',
    title: 'Doanh thu hôm nay',
    value: formatMoney(dashboard.metrics.orders_today.total_amount),
    helper: `Lợi nhuận: ${formatMoney(dashboard.metrics.orders_today.profit)}`,
  },
  {
    key: 'month_revenue',
    title: 'Doanh thu tháng',
    value: formatMoney(dashboard.metrics.orders_month.total_amount),
    helper: `Còn nợ: ${formatMoney(dashboard.metrics.orders_month.debt_amount)}`,
  },
  {
    key: 'purchase_month',
    title: 'Nhập hàng tháng',
    value: formatMoney(dashboard.metrics.purchases_month.total_amount),
    helper: `Đã trả: ${formatMoney(dashboard.metrics.purchases_month.paid_amount)}`,
  },
  {
    key: 'customer_debt',
    title: 'Nợ khách hàng',
    value: formatMoney(dashboard.metrics.customer_debt),
    helper: 'Cộng dồn tất cả đơn chưa thanh toán đủ',
  },
  {
    key: 'supplier_debt',
    title: 'Nợ nhà cung cấp',
    value: formatMoney(dashboard.metrics.supplier_debt),
    helper: 'Cộng dồn tất cả phiếu nhập chưa trả đủ',
  },
])

onMounted(async () => {
  await dashboard.fetchMetrics()
})
</script>

<template>
  <section class="space-y-5">
    <header class="space-y-1">
      <h2 class="text-xl font-semibold">Tổng quan kinh doanh</h2>
      <p class="text-sm text-ink/60">Số liệu realtime từ API dashboard metrics.</p>
    </header>

    <p v-if="dashboard.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">
      {{ dashboard.error }}
    </p>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="card in cards"
        :key="card.key"
        class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm"
      >
        <template v-if="dashboard.loading">
          <div class="h-4 w-28 animate-pulse rounded bg-black/10" />
          <div class="mt-3 h-8 w-36 animate-pulse rounded bg-black/10" />
          <div class="mt-2 h-3 w-44 animate-pulse rounded bg-black/10" />
        </template>
        <template v-else>
          <p class="text-xs uppercase tracking-wider text-ink/60">{{ card.title }}</p>
          <p class="mt-2 text-2xl font-semibold">{{ card.value }}</p>
          <p class="mt-1 text-xs text-ink/60">{{ card.helper }}</p>
        </template>
      </article>
    </div>

    <section class="rounded-2xl border border-black/10 bg-white p-4 shadow-sm">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-ink/70">Đơn gần đây</h3>

      <div v-if="dashboard.loading" class="mt-3 space-y-2">
        <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded bg-black/10" />
      </div>

      <div v-else-if="dashboard.metrics.recent_orders.length === 0" class="mt-3 rounded-xl bg-black/5 px-3 py-2 text-sm text-ink/60">
        Chưa có đơn hàng gần đây.
      </div>

      <ul v-else class="mt-3 space-y-2">
        <li
          v-for="order in dashboard.metrics.recent_orders"
          :key="order.id"
          class="flex items-center justify-between rounded-xl border border-black/10 px-3 py-2"
        >
          <div>
            <p class="text-sm font-medium">#{{ order.id }} · {{ order.customer_name || 'Khách lẻ' }}</p>
            <p class="text-xs text-ink/60">{{ formatDate(order.order_date) }} · {{ order.items_count }} sản phẩm</p>
          </div>
          <p class="text-sm font-semibold">{{ formatMoney(order.total_amount) }}</p>
        </li>
      </ul>
    </section>
  </section>
</template>
