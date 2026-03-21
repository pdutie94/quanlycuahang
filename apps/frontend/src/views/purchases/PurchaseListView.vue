<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { usePurchasesStore } from '../../stores/purchases'
import { formatMoney } from '../../lib/format'

const purchases = usePurchasesStore()
const keyword = ref('')

onMounted(async () => {
  await purchases.fetchList({ page: 1 })
  keyword.value = purchases.query
})

async function handleSearch(): Promise<void> {
  await purchases.fetchList({ page: 1, q: keyword.value.trim() })
}

async function goToPage(nextPage: number): Promise<void> {
  await purchases.fetchList({ page: nextPage })
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Phiếu nhập hàng</h2>
        <p class="text-sm text-ink/60">Quản lý nhập kho và công nợ nhà cung cấp.</p>
      </div>
      <RouterLink to="/purchases/new" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Tạo phiếu nhập</RouterLink>
    </header>

    <form class="flex gap-2" @submit.prevent="handleSearch">
      <input v-model="keyword" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm theo mã phiếu, nhà cung cấp" />
      <button type="submit" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tìm</button>
    </form>

    <p v-if="purchases.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ purchases.error }}</p>

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Mã phiếu</th>
            <th class="px-3 py-2">Nhà cung cấp</th>
            <th class="px-3 py-2">Ngày nhập</th>
            <th class="px-3 py-2">Tổng tiền</th>
            <th class="px-3 py-2">Đã trả</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="purchases.loading" v-for="n in 6" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="6"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="purchases.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="6">Chưa có phiếu nhập hàng.</td>
          </tr>
          <tr v-else v-for="item in purchases.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2 font-medium">{{ item.purchase_code }}</td>
            <td class="px-3 py-2">{{ item.supplier_name }}</td>
            <td class="px-3 py-2">{{ item.purchase_date }}</td>
            <td class="px-3 py-2">{{ formatMoney(item.total_amount) }}</td>
            <td class="px-3 py-2">{{ formatMoney(item.paid_amount) }}</td>
            <td class="px-3 py-2">
              <RouterLink :to="`/purchases/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ purchases.page }} / {{ purchases.totalPages }} · Tổng {{ purchases.total }} phiếu</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="purchases.page <= 1 || purchases.loading" @click="goToPage(purchases.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="purchases.page >= purchases.totalPages || purchases.loading" @click="goToPage(purchases.page + 1)">Sau</button>
      </div>
    </div>
  </section>
</template>
