<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useCustomersStore } from '../../stores/customers'
import PullToRefresh from '../../components/PullToRefresh.vue'
import SkeletonBlock from '../../components/SkeletonBlock.vue'

const customers = useCustomersStore()
const keyword = ref('')

onMounted(async () => {
  await customers.fetchList({ page: 1 })
  keyword.value = customers.query
})

async function handleSearch(): Promise<void> {
  await customers.fetchList({ page: 1, search: keyword.value.trim() })
}

async function goToPage(nextPage: number): Promise<void> {
  await customers.fetchList({ page: nextPage })
}

async function handleDelete(id: number): Promise<void> {
  if (!window.confirm('Bạn chắc chắn muốn xóa khách hàng này?')) return
  await customers.remove(id)
}

async function handleRefresh(): Promise<void> {
  await customers.fetchList({ page: 1, search: keyword.value.trim() || undefined })
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Khách hàng</h2>
        <p class="text-sm text-ink/60">Quản lý hồ sơ khách và công nợ bán hàng.</p>
      </div>
      <RouterLink to="/customers/new" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Thêm khách hàng</RouterLink>
    </header>

    <form class="flex gap-2" @submit.prevent="handleSearch">
      <input v-model="keyword" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm theo tên, điện thoại" />
      <button type="submit" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tìm</button>
    </form>

    <p v-if="customers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ customers.error }}</p>

    <div class="space-y-2">
      <div v-if="customers.loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      </div>
      <div v-else-if="customers.items.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">
        Không có dữ liệu khách hàng.
      </div>
      <article v-else v-for="item in customers.items" :key="item.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-semibold">{{ item.name }}</h3>
            <p class="text-xs text-ink/60">{{ item.phone || '-' }}</p>
          </div>
          <RouterLink :to="`/customers/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
          <p class="rounded-xl bg-black/5 px-3 py-2">Tổng mua: <strong>{{ item.total_spent ?? 0 }}</strong></p>
          <p class="rounded-xl bg-red-50 px-3 py-2 text-red-600">Công nợ: <strong>{{ item.total_debt ?? 0 }}</strong></p>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
          <RouterLink :to="`/customers/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
          <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
        </div>
      </article>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ customers.page }} / {{ customers.totalPages }} · Tổng {{ customers.total }} khách hàng</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="customers.page <= 1 || customers.loading" @click="goToPage(customers.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="customers.page >= customers.totalPages || customers.loading" @click="goToPage(customers.page + 1)">Sau</button>
      </div>
    </div>
  </section>
  </PullToRefresh>
</template>
