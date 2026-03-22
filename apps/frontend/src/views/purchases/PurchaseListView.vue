<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { usePurchasesStore } from '../../stores/purchases'
import { formatMoney } from '../../lib/format'
import PullToRefresh from '../../components/PullToRefresh.vue'
import SkeletonBlock from '../../components/SkeletonBlock.vue'

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

async function handleRefresh(): Promise<void> {
  await purchases.fetchList({ page: 1, q: keyword.value.trim() || undefined })
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
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

    <div class="space-y-2">
      <div v-if="purchases.loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      </div>
      <div v-else-if="purchases.items.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">
        Chưa có phiếu nhập hàng.
      </div>
      <article v-else v-for="item in purchases.items" :key="item.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-semibold">{{ item.purchase_code }}</h3>
            <p class="text-xs text-ink/60">{{ item.supplier_name }} · {{ item.purchase_date }}</p>
          </div>
          <RouterLink :to="`/purchases/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
          <p class="rounded-xl bg-black/5 px-3 py-2">Tổng tiền: <strong>{{ formatMoney(item.total_amount) }}</strong></p>
          <p class="rounded-xl bg-black/5 px-3 py-2">Đã trả: <strong>{{ formatMoney(item.paid_amount) }}</strong></p>
        </div>
      </article>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ purchases.page }} / {{ purchases.totalPages }} · Tổng {{ purchases.total }} phiếu</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="purchases.page <= 1 || purchases.loading" @click="goToPage(purchases.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="purchases.page >= purchases.totalPages || purchases.loading" @click="goToPage(purchases.page + 1)">Sau</button>
      </div>
    </div>
  </section>
  </PullToRefresh>
</template>
