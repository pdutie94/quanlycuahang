<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useOrdersStore } from '../../stores/orders'
import { formatMoney } from '../../lib/format'

const orders = useOrdersStore()
const keyword = ref('')

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
</script>

<template>
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

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Mã đơn</th>
            <th class="px-3 py-2">Khách hàng</th>
            <th class="px-3 py-2">Ngày</th>
            <th class="px-3 py-2">Tổng tiền</th>
            <th class="px-3 py-2">Đã thu</th>
            <th class="px-3 py-2">Trạng thái</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="orders.loading" v-for="n in 8" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="7"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="orders.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="7">Chưa có đơn hàng.</td>
          </tr>
          <tr v-else v-for="item in orders.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2 font-medium">{{ item.order_code }}</td>
            <td class="px-3 py-2">{{ item.customer_name || 'Khách lẻ' }}</td>
            <td class="px-3 py-2">{{ item.order_date }}</td>
            <td class="px-3 py-2">{{ formatMoney(item.total_amount) }}</td>
            <td class="px-3 py-2">{{ formatMoney(item.paid_amount) }}</td>
            <td class="px-3 py-2">
              <span class="rounded-lg px-2 py-1 text-xs font-medium" :class="item.order_status === 'completed' ? 'bg-emerald-100 text-emerald-700' : item.order_status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'">
                {{ item.order_status }}
              </span>
            </td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-2">
                <RouterLink :to="`/orders/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
                <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ orders.page }} / {{ orders.totalPages }} · Tổng {{ orders.total }} đơn</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="orders.page <= 1 || orders.loading" @click="goToPage(orders.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="orders.page >= orders.totalPages || orders.loading" @click="goToPage(orders.page + 1)">Sau</button>
      </div>
    </div>
  </section>
</template>
