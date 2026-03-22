<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { reportService, type ReportInventoryRow } from '../../services/reportService'

const loading = ref(false)
const error = ref('')
const rows = ref<ReportInventoryRow[]>([])
const page = ref(1)
const totalPages = ref(1)
const total = ref(0)
const keyword = ref('')

async function fetchData(nextPage = page.value): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await reportService.getInventory({
      page: nextPage,
      per_page: 20,
      q: keyword.value || undefined,
    })
    rows.value = result.data
    page.value = result.meta.page
    totalPages.value = result.meta.last_page
    total.value = result.meta.total
  } catch {
    error.value = 'Không tải được báo cáo tồn kho.'
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
      <h2 class="text-xl font-semibold">Báo cáo tồn kho</h2>
      <p class="text-sm text-ink/60">Theo dõi tồn kho thực tế và cảnh báo dưới mức tối thiểu.</p>
    </header>

    <form class="flex gap-2" @submit.prevent="fetchData(1)">
      <input v-model="keyword" type="text" class="w-full max-w-md rounded-xl border border-gray-300 px-3 py-2 text-sm" placeholder="Tìm sản phẩm" />
      <button type="submit" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white">Lọc</button>
    </form>

    <p v-if="error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <div class="space-y-2">
      <div v-if="loading" v-for="n in 6" :key="n" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="h-6 animate-pulse rounded bg-black/10" />
      </div>
      <div v-else-if="rows.length === 0" class="rounded-2xl border border-black/10 bg-white px-3 py-4 text-center text-sm text-ink/60">Không có dữ liệu.</div>
      <article v-else v-for="row in rows" :key="row.id" class="rounded-2xl border border-black/10 bg-white p-4">
        <div class="flex items-start justify-between gap-2">
          <div>
            <h3 class="font-semibold">{{ row.name }}</h3>
            <p class="text-xs text-ink/60">{{ row.code }}</p>
          </div>
          <span v-if="row.is_low_stock" class="rounded-lg bg-red-100 px-2 py-1 text-xs text-red-700">Thiếu hàng</span>
          <span v-else class="rounded-lg bg-emerald-100 px-2 py-1 text-xs text-emerald-700">Ổn</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
          <p class="rounded-xl bg-black/5 px-3 py-2">Tồn kho: <strong>{{ row.qty_base }} {{ row.base_unit_name }}</strong></p>
          <p class="rounded-xl bg-black/5 px-3 py-2">Tối thiểu: <strong>{{ row.min_stock_qty ?? 0 }}</strong></p>
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
