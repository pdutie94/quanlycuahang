<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { formatMoney } from '../../lib/format'
import { reportService, type ReportMissingCostRow } from '../../services/reportService'

const loading = ref(false)
const error = ref('')
const rows = ref<ReportMissingCostRow[]>([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)

async function fetchData(nextPage = page.value): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await reportService.getMissingCost({ page: nextPage, per_page: 20 })
    rows.value = result.data
    page.value = result.meta.page
    totalPages.value = result.meta.last_page
    total.value = result.meta.total
  } catch {
    error.value = 'Không tải được báo cáo thiếu giá vốn.'
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
      <h2 class="text-xl font-semibold">Báo cáo thiếu giá vốn</h2>
      <p class="text-sm text-ink/60">Các dòng bán hàng có giá vốn bằng 0 cần xử lý.</p>
    </header>

    <p v-if="error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <div class="space-y-2">
      <div v-if="loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="h-6 animate-pulse rounded bg-black/10" />
      </div>
      <div v-else-if="rows.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">Không có dòng thiếu giá vốn.</div>
      <article v-else v-for="row in rows" :key="row.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <h3 class="font-semibold">{{ row.order_code }}</h3>
          <p class="text-xs text-ink/60">SL {{ row.qty }}</p>
        </div>
        <p class="mt-1 text-sm text-ink/70">{{ row.product_name }} ({{ row.unit_name }})</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-2 text-sm">
          <p class="rounded-xl bg-red-50 px-3 py-2 text-red-600">Giá vốn hiện tại: <strong>{{ formatMoney(row.price_cost) }}</strong></p>
          <p class="rounded-xl bg-black/5 px-3 py-2">Giá vốn đơn vị: <strong>{{ formatMoney(row.current_unit_price_cost) }}</strong></p>
        </div>
      </article>
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
