<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useDashboardStore } from '../stores/dashboard'
import { formatDate, formatMoney } from '../lib/format'
import PullToRefresh from '../components/PullToRefresh.vue'
import SkeletonBlock from '../components/SkeletonBlock.vue'
import { ShoppingCart, ClipboardList, Plus, PieChart } from 'lucide-vue-next'
import OrderSummaryCard from '../components/orders/OrderSummaryCard.vue'
import OrderPreviewModal from '../components/orders/OrderPreviewModal.vue'

const dashboard = useDashboardStore()
const previewOrderId = ref<number | null>(null)

const todayLabel = computed(() => formatDate(new Date()))

const cards = computed(() => [
  {
    key: 'revenue',
    title: 'Doanh thu',
    value: formatMoney(dashboard.metrics.orders_today.total_amount),
    variant: 'brand',
  },
  {
    key: 'profit',
    title: 'Lợi nhuận',
    value: formatMoney(dashboard.metrics.orders_today.profit),
    variant: 'brand',
  },
  {
    key: 'paid',
    title: 'Đã thu',
    value: formatMoney(dashboard.metrics.orders_today.paid_amount),
    variant: 'brand',
  },
  {
    key: 'debt',
    title: 'Còn nợ',
    value: formatMoney(dashboard.metrics.orders_today.debt_amount),
    variant: 'alert',
  },
])

const quickLinks = [
  { to: '/pos', label: 'Tạo đơn', icon: ShoppingCart },
  { to: '/orders', label: 'Đơn hàng', icon: ClipboardList },
  { to: '/products/new', label: 'Thêm SP', icon: Plus },
  { to: '/reports', label: 'Báo cáo', icon: PieChart },
]

onMounted(async () => {
  await dashboard.fetchMetrics()
})

async function handleRefresh(): Promise<void> {
  await dashboard.fetchMetrics()
}

function openPreview(orderId: number): void {
  previewOrderId.value = orderId
}

function closePreview(): void {
  previewOrderId.value = null
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
    <section class="space-y-5 pt-4">
      <header class="flex items-center justify-between gap-3">
        <h2 class="text-2xl font-bold text-slate-900">Hôm nay</h2>
        <div class="dashboard-date-pill px-3 py-1 text-xs font-semibold">
          {{ todayLabel }}
        </div>
      </header>

      <p v-if="dashboard.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">
        {{ dashboard.error }}
      </p>

      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <article
          v-for="card in cards"
          :key="card.key"
          class="fancy-box fancy-box-soft rounded-xl px-3 py-3"
          :class="card.variant === 'alert' ? 'tone-rose' : 'tone-brand'"
        >
          <template v-if="dashboard.loading">
            <SkeletonBlock height-class="h-3" width-class="w-16" />
            <div class="mt-2"><SkeletonBlock height-class="h-6" width-class="w-20" /></div>
          </template>
          <template v-else>
            <p class="text-sm text-slate-500">{{ card.title }}</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
          </template>
        </article>
      </div>

      <section class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-700">Lối tắt</h3>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <RouterLink
            v-for="item in quickLinks"
            :key="item.label"
            :to="item.to"
            class="fancy-box fancy-box-soft tone-brand flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-800"
          >
            <span class="dashboard-link-icon inline-flex h-8 w-8 items-center justify-center rounded-xl text-slate-700">
              <component :is="item.icon" :size="16" />
            </span>
            {{ item.label }}
          </RouterLink>
        </div>
      </section>

      <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-sm font-semibold text-slate-700">Đơn hàng gần đây</h3>
          <RouterLink to="/orders" class="text-sm font-semibold text-teal-700">Xem tất cả</RouterLink>
        </div>

        <div v-if="dashboard.loading" class="mt-3 space-y-3">
          <div
            v-for="n in 4"
            :key="n"
            class="fancy-box fancy-box-soft tone-brand rounded-xl px-3 py-3"
          >
            <div class="animate-pulse">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <div class="h-4 w-32 rounded-md bg-black/10"></div>
                    <div class="h-5 w-16 rounded-md bg-black/10"></div>
                  </div>
                  <div class="mt-2 h-3 w-40 rounded-md bg-black/10"></div>
                </div>
                <div class="h-8 w-8 shrink-0 rounded-lg bg-black/10"></div>
              </div>

              <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-2">
                <div class="h-4 w-10 rounded-md bg-black/10"></div>
                <div class="h-4 w-24 rounded-md bg-black/10"></div>
                <div class="h-4 w-16 rounded-md bg-black/10"></div>
                <div class="h-4 w-20 rounded-md bg-black/10"></div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="dashboard.metrics.recent_orders.length === 0" class="fancy-box fancy-box-soft tone-brand rounded-xl px-3 py-3 text-sm text-slate-600">
          Chưa có đơn hàng gần đây.
        </div>

        <ul v-else class="mt-3 space-y-3">
          <li v-for="order in dashboard.metrics.recent_orders" :key="order.id">
            <OrderSummaryCard
              :order="{
                id: order.id,
                orderCode: `DH-${order.id}`,
                customerName: order.customer_name,
                orderDate: order.order_date,
                totalAmount: order.total_amount,
                paidAmount: order.paid_amount,
              }"
              tone="tone-brand"
              @preview="openPreview"
            />
          </li>
        </ul>
      </section>
    </section>

    <OrderPreviewModal :open="previewOrderId !== null" :order-id="previewOrderId" @close="closePreview" />
  </PullToRefresh>
</template>

<style scoped>
.dashboard-date-pill {
  color: #0f766e;
  background: linear-gradient(145deg, #ecfeff, #dcfce7);
  box-shadow: inset 0 0 0 1px rgba(20, 184, 166, 0.12);
  border-radius: 0.375rem;
}

.dashboard-link-icon {
  background: linear-gradient(145deg, #ccfbf1, #99f6e4);
  box-shadow: inset 0 0 0 1px rgba(15, 118, 110, 0.12);
}


</style>
