<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { reportService, type ReportInventoryAdjustRow } from '../../services/reportService'

const loading = ref(false)
const error = ref('')
const rows = ref<ReportInventoryAdjustRow[]>([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)

async function fetchData(nextPage = page.value): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await reportService.getInventoryAdjust({ page: nextPage, per_page: 20 })
    rows.value = result.data
    page.value = result.meta.page
    totalPages.value = result.meta.last_page
    total.value = result.meta.total
  } catch {
    error.value = 'Không tải được lịch sử điều chỉnh tồn kho.'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchData(1)
})
</script>

<template>
  <section class="space-y-4">
    <header>
      <h2 class="text-xl font-semibold">Lịch sử điều chỉnh kho</h2>
      <p class="text-sm text-ink/60">Theo dõi các thay đổi tồn kho được ghi log từ hệ thống.</p>
    </header>

    <p v-if="error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Thời gian</th>
            <th class="px-3 py-2">Sản phẩm</th>
            <th class="px-3 py-2">Hành động</th>
            <th class="px-3 py-2">Chi tiết</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" v-for="n in 6" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="4"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="rows.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="4">Chưa có lịch sử điều chỉnh.</td>
          </tr>
          <tr v-else v-for="row in rows" :key="row.id" class="border-t border-black/5">
            <td class="px-3 py-2">{{ row.created_at }}</td>
            <td class="px-3 py-2">{{ row.product_name }}</td>
            <td class="px-3 py-2">{{ row.action }}</td>
            <td class="px-3 py-2">{{ row.detail }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between text-sm text-ink/70">
      <p>Trang {{ page }} / {{ totalPages }} · Tổng {{ total }} dòng</p>
      <div class="flex gap-2">
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="page <= 1 || loading" @click="fetchData(page - 1)">Trước</button>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 disabled:opacity-50" :disabled="page >= totalPages || loading" @click="fetchData(page + 1)">Sau</button>
      </div>
    </div>
  </section>
</template>
