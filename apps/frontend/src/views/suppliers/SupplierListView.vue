<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useSuppliersStore } from '../../stores/suppliers'

const suppliers = useSuppliersStore()
const keyword = ref('')

onMounted(async () => {
  await suppliers.fetchList({ page: 1 })
  keyword.value = suppliers.query
})

async function handleSearch(): Promise<void> {
  await suppliers.fetchList({ page: 1, search: keyword.value.trim() })
}

async function goToPage(nextPage: number): Promise<void> {
  await suppliers.fetchList({ page: nextPage })
}

async function handleDelete(id: number): Promise<void> {
  if (!window.confirm('Bạn chắc chắn muốn xóa nhà cung cấp này?')) return
  await suppliers.remove(id)
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Nhà cung cấp</h2>
        <p class="text-sm text-ink/60">Quản lý nguồn nhập hàng và công nợ nhập.</p>
      </div>
      <RouterLink to="/suppliers/new" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Thêm nhà cung cấp</RouterLink>
    </header>

    <form class="flex gap-2" @submit.prevent="handleSearch">
      <input v-model="keyword" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm theo tên, điện thoại" />
      <button type="submit" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Tìm</button>
    </form>

    <p v-if="suppliers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ suppliers.error }}</p>

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Tên</th>
            <th class="px-3 py-2">Điện thoại</th>
            <th class="px-3 py-2">Email</th>
            <th class="px-3 py-2">Công nợ</th>
            <th class="px-3 py-2">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="suppliers.loading" v-for="n in 6" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="5"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="suppliers.items.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="5">Không có dữ liệu nhà cung cấp.</td>
          </tr>
          <tr v-else v-for="item in suppliers.items" :key="item.id" class="border-t border-black/5">
            <td class="px-3 py-2 font-medium">{{ item.name }}</td>
            <td class="px-3 py-2">{{ item.phone || '-' }}</td>

            <td class="px-3 py-2">{{ item.total_debt ?? 0 }}</td>
            <td class="px-3 py-2">
              <div class="flex flex-wrap gap-2">
                <RouterLink :to="`/suppliers/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
                <RouterLink :to="`/suppliers/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
                <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ suppliers.page }} / {{ suppliers.totalPages }} · Tổng {{ suppliers.total }} nhà cung cấp</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="suppliers.page <= 1 || suppliers.loading" @click="goToPage(suppliers.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="suppliers.page >= suppliers.totalPages || suppliers.loading" @click="goToPage(suppliers.page + 1)">Sau</button>
      </div>
    </div>
  </section>
</template>
