<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useOrdersStore } from '../../stores/orders'
import SkeletonBlock from '../../components/SkeletonBlock.vue'
import OrderSummaryCard from '../../components/orders/OrderSummaryCard.vue'
import OrderPreviewModal from '../../components/orders/OrderPreviewModal.vue'

const orders = useOrdersStore()
const keyword = ref('')
const previewOrderId = ref<number | null>(null)

onMounted(async () => {
  await orders.fetchList({ page: 1 })
  keyword.value = orders.query
})

async function handleSearch(): Promise<void> {
  await orders.fetchList({ page: 1, q: keyword.value.trim() })
}

async function handleDelete(id: number): Promise<void> {
  if (!window.confirm('Bạn chắc chắn muốn xóa đơn hàng này?')) return
  await orders.remove(id)
}

async function goToPage(nextPage: number): Promise<void> {
  await orders.fetchList({ page: nextPage })
}

async function handleRefresh(): Promise<void> {
  await orders.fetchList({ page: 1, q: keyword.value.trim() || undefined })
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
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Đơn hàng</h2>
        <p class="text-sm text-ink/60">Theo dõi bán hàng, công nợ và trạng thái xử lý đơn.</p>
      </div>
      <div class="flex gap-2">
        <RouterLink to="/orders/new" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tạo đơn</RouterLink>
        <RouterLink to="/pos" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Mở POS</RouterLink>
      </div>
    </header>

    <form class="flex gap-2" @submit.prevent="handleSearch">
      <input v-model="keyword" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm theo mã đơn, tên khách, SĐT" />
      <button type="submit" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tìm</button>
    </form>

    <p v-if="orders.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ orders.error }}</p>

    <div class="space-y-2">
      <div v-if="orders.loading" v-for="n in 8" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      </div>
      <div v-else-if="orders.items.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">
        Chưa có đơn hàng.
      </div>
      <article v-else v-for="item in orders.items" :key="item.id" class="space-y-2">
        <OrderSummaryCard
          :order="{
            id: item.id,
            orderCode: item.order_code,
            customerName: item.customer_name,
            orderDate: item.order_date,
            totalAmount: item.total_amount,
            paidAmount: item.paid_amount,
            totalCost: item.total_cost,
            orderStatus: item.order_status,
          }"
          :tone="(item.total_amount - item.paid_amount) > 0 ? 'tone-rose' : 'tone-mint'"
          @preview="openPreview"
        />
        <div class="flex justify-end">
          <button type="button" class="rounded-lg border border-red-200 bg-white px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
        </div>
      </article>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ orders.page }} / {{ orders.totalPages }} · Tổng {{ orders.total }} đơn</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="orders.page <= 1 || orders.loading" @click="goToPage(orders.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="orders.page >= orders.totalPages || orders.loading" @click="goToPage(orders.page + 1)">Sau</button>
      </div>
    </div>
  </section>

  <OrderPreviewModal :open="previewOrderId !== null" :order-id="previewOrderId" @close="closePreview" />
  </PullToRefresh>
</template>
