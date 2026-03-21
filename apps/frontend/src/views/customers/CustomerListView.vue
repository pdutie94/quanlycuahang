<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useCustomersStore } from '../../stores/customers'

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
</script>

<template>
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

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Tên</th>
            <th class="px-3 py-2">Điện thoại</th>
            <th class="px-3 py-2">Tổng mua</th>
            <th class="px-3 py-2">Công nợ</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="customers.loading" v-for="n in 6" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="5"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="customers.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="5">Không có dữ liệu khách hàng.</td>
          </tr>
          <tr v-else v-for="item in customers.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2 font-medium">{{ item.name }}</td>
            <td class="px-3 py-2">{{ item.phone || '-' }}</td>
            <td class="px-3 py-2">{{ item.total_spent ?? 0 }}</td>
            <td class="px-3 py-2 text-red-600">{{ item.total_debt ?? 0 }}</td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-2">
                <RouterLink :to="`/customers/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
                <RouterLink :to="`/customers/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
                <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ customers.page }} / {{ customers.totalPages }} · Tổng {{ customers.total }} khách hàng</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="customers.page <= 1 || customers.loading" @click="goToPage(customers.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="customers.page >= customers.totalPages || customers.loading" @click="goToPage(customers.page + 1)">Sau</button>
      </div>
    </div>
  </section>
</template>
