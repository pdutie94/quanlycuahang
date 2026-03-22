<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useSuppliersStore } from '../../stores/suppliers'
import PullToRefresh from '../../components/PullToRefresh.vue'
import SkeletonBlock from '../../components/SkeletonBlock.vue'

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

async function handleRefresh(): Promise<void> {
  await suppliers.fetchList({ page: 1, search: keyword.value.trim() || undefined })
}
</script>

<template>
  <PullToRefresh @refresh="handleRefresh">
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

    <div class="space-y-2">
      <div v-if="suppliers.loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      </div>
      <div v-else-if="suppliers.items.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">
        Không có dữ liệu nhà cung cấp.
      </div>
      <article v-else v-for="item in suppliers.items" :key="item.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-semibold">{{ item.name }}</h3>
            <p class="text-xs text-ink/60">{{ item.phone || '-' }}</p>
          </div>
          <RouterLink :to="`/suppliers/${item.id}`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Chi tiết</RouterLink>
        </div>
        <p class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">Công nợ: <strong>{{ item.total_debt ?? 0 }}</strong></p>
        <div class="mt-3 flex flex-wrap gap-2">
          <RouterLink :to="`/suppliers/${item.id}/edit`" class="rounded-lg border border-black/15 px-2 py-1 text-xs">Sửa</RouterLink>
          <button type="button" class="rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="handleDelete(item.id)">Xóa</button>
        </div>
      </article>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ suppliers.page }} / {{ suppliers.totalPages }} · Tổng {{ suppliers.total }} nhà cung cấp</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="suppliers.page <= 1 || suppliers.loading" @click="goToPage(suppliers.page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="suppliers.page >= suppliers.totalPages || suppliers.loading" @click="goToPage(suppliers.page + 1)">Sau</button>
      </div>
    </div>
  </section>
  </PullToRefresh>
</template>
