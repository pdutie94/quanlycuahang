<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { formatMoney } from '../../lib/format'
import { reportService, type ReportDebtRow } from '../../services/reportService'

const loading = ref(false)
const error = ref('')
const rows = ref<ReportDebtRow[]>([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const keyword = ref('')
const showAll = ref(false)

async function fetchData(nextPage = page.value): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await reportService.getSupplierDebt({
      page: nextPage,
      per_page: 20,
      q: keyword.value || undefined,
      show_all: showAll.value,
    })
    rows.value = result.data
    page.value = result.meta.page
    totalPages.value = result.meta.last_page
    total.value = result.meta.total
  } catch {
    error.value = 'Không tải được báo cáo công nợ nhà cung cấp.'
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
      <h2 class="text-xl font-semibold">Công nợ nhà cung cấp</h2>
      <p class="text-sm text-ink/60">Theo dõi số tiền còn phải trả theo từng nhà cung cấp.</p>
    </header>

    <form class="flex flex-wrap items-center gap-2" @submit.prevent="fetchData(1)">
      <input v-model="keyword" type="text" class="w-full max-w-md rounded-xl border border-gray-300 px-3 py-2 text-sm" placeholder="Tìm nhà cung cấp" />
      <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input v-model="showAll" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
        Hiển thị cả NCC đã hết nợ
      </label>
      <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Lọc</button>
    </form>

    <p v-if="error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
          <tr>
            <th class="px-3 py-2">Nhà cung cấp</th>
            <th class="px-3 py-2">Điện thoại</th>
            <th class="px-3 py-2">Tổng nhập</th>
            <th class="px-3 py-2">Đã trả</th>
            <th class="px-3 py-2">Còn nợ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading" v-for="n in 6" :key="n" class="border-t border-black/5">
            <td class="px-3 py-2" colspan="5"><div class="h-6 animate-pulse rounded bg-black/10" /></td>
          </tr>
          <tr v-else-if="rows.length === 0" class="border-t border-black/5">
            <td class="px-3 py-4 text-center text-ink/60" colspan="5">Không có dữ liệu.</td>
          </tr>
          <tr v-else v-for="row in rows" :key="row.id" class="border-t border-black/5">
            <td class="px-3 py-2">{{ row.name }}</td>
            <td class="px-3 py-2">{{ row.phone || '-' }}</td>
            <td class="px-3 py-2">{{ formatMoney(row.total_amount) }}</td>
            <td class="px-3 py-2">{{ formatMoney(row.paid_amount) }}</td>
            <td class="px-3 py-2 text-red-600">{{ formatMoney(row.debt_amount) }}</td>
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
