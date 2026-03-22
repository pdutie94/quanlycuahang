<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { formatMoney } from '../../lib/format'
import { reportService, type ReportSalesRow } from '../../services/reportService'

const loading = ref(false)
const error = ref('')
const rows = ref<ReportSalesRow[]>([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const summary = ref<Record<string, number>>({})

const startDate = ref('')
const endDate = ref('')

async function fetchData(nextPage = page.value): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await reportService.getSales({
      page: nextPage,
      per_page: 20,
      start_date: startDate.value || undefined,
      end_date: endDate.value || undefined,
    })
    rows.value = result.data
    page.value = result.meta.page
    total.value = result.meta.total
    totalPages.value = result.meta.last_page
    summary.value = result.meta.summary || {}
  } catch {
    error.value = 'Không tải được báo cáo doanh thu.'
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
      <h2 class="text-xl font-semibold">Báo cáo doanh thu</h2>
      <p class="text-sm text-ink/60">Phân tích đơn bán, doanh thu và lợi nhuận.</p>
    </header>

    <form class="grid gap-2 rounded-2xl border border-black/10 bg-white p-3 md:grid-cols-4" @submit.prevent="fetchData(1)">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Từ ngày</label>
        <input v-model="startDate" type="date" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Đến ngày</label>
        <input v-model="endDate" type="date" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
      </div>
      <div class="md:col-span-2 md:flex md:items-end md:justify-end">
        <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Lọc</button>
      </div>
    </form>

    <p v-if="error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <div class="grid gap-2 sm:grid-cols-3">
      <div class="rounded-xl border border-black/10 bg-white p-3 text-sm">Đơn hàng: <strong>{{ summary.order_count || 0 }}</strong></div>
      <div class="rounded-xl border border-black/10 bg-white p-3 text-sm">Doanh thu: <strong>{{ formatMoney(summary.total_amount || 0) }}</strong></div>
      <div class="rounded-xl border border-black/10 bg-white p-3 text-sm">Lợi nhuận: <strong>{{ formatMoney(summary.profit || 0) }}</strong></div>
    </div>

    <div class="space-y-2">
      <div v-if="loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="h-6 animate-pulse rounded bg-black/10" />
      </div>
      <div v-else-if="rows.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">Không có dữ liệu.</div>
      <article v-else v-for="row in rows" :key="row.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <h3 class="font-semibold">{{ row.order_code }}</h3>
          <p class="text-xs text-ink/60">{{ row.order_date }}</p>
        </div>
        <p class="mt-1 text-xs text-ink/60">{{ row.customer_name || 'Khách lẻ' }}</p>
        <div class="mt-3 grid gap-2 sm:grid-cols-3 text-sm">
          <p class="rounded-xl bg-black/5 px-3 py-2">Doanh thu: <strong>{{ formatMoney(row.total_amount) }}</strong></p>
          <p class="rounded-xl bg-black/5 px-3 py-2">Giá vốn: <strong>{{ formatMoney(row.total_cost) }}</strong></p>
          <p class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-700">Lợi nhuận: <strong>{{ formatMoney(row.total_amount - row.total_cost) }}</strong></p>
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
