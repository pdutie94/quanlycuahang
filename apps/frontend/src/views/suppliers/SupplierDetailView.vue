<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useSuppliersStore } from '../../stores/suppliers'

const route = useRoute()
const suppliers = useSuppliersStore()

const supplierId = computed(() => Number(route.params.id || 0))

onMounted(async () => {
  if (supplierId.value > 0) {
    await suppliers.fetchById(supplierId.value)
  }
})
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Chi tiết nhà cung cấp</h2>
        <p class="text-sm text-ink/60">Theo dõi lịch sử nhập hàng và công nợ.</p>
      </div>
      <RouterLink :to="`/suppliers/${supplierId}/edit`" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Sửa thông tin</RouterLink>
    </header>

    <p v-if="suppliers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ suppliers.error }}</p>

    <div v-if="suppliers.detailLoading" class="space-y-2">
      <div class="h-20 animate-pulse rounded-2xl bg-black/10" />
      <div class="h-40 animate-pulse rounded-2xl bg-black/10" />
    </div>

    <template v-else-if="suppliers.detail">
      <div class="rounded-2xl border border-black/10 bg-white p-4">
        <h3 class="text-lg font-semibold">{{ suppliers.detail.supplier.name }}</h3>
        <p class="text-sm text-ink/70">SĐT: {{ suppliers.detail.supplier.phone || '-' }}</p>
        <p class="text-sm text-ink/70">Điện thoại: {{ suppliers.detail.supplier.phone || '-' }}</p>
        <p class="text-sm text-ink/70">Địa chỉ: {{ suppliers.detail.supplier.address || '-' }}</p>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <p class="text-xs uppercase tracking-wider text-ink/50">Tổng nhập</p>
          <p class="mt-1 text-2xl font-semibold">{{ suppliers.detail.summary.total_purchases }}</p>
        </div>
        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <p class="text-xs uppercase tracking-wider text-ink/50">Công nợ hiện tại</p>
          <p class="mt-1 text-2xl font-semibold text-red-600">{{ suppliers.detail.summary.total_debt }}</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
            <tr>
              <th class="px-3 py-2">Mã phiếu</th>
              <th class="px-3 py-2">Ngày nhập</th>
              <th class="px-3 py-2">Tổng tiền</th>
              <th class="px-3 py-2">Đã trả</th>
              <th class="px-3 py-2">Còn nợ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="suppliers.detail.latest_purchases.length === 0" class="border-t border-black/5">
              <td class="px-3 py-4 text-center text-ink/60" colspan="5">Chưa có phiếu nhập gần đây.</td>
            </tr>
            <tr v-else v-for="purchase in suppliers.detail.latest_purchases" :key="purchase.id" class="border-t border-black/5">
              <td class="px-3 py-2">{{ purchase.reference_code }}</td>
              <td class="px-3 py-2">{{ purchase.purchase_date }}</td>
              <td class="px-3 py-2">{{ purchase.total_amount }}</td>
              <td class="px-3 py-2">{{ purchase.paid_amount }}</td>
              <td class="px-3 py-2 text-red-600">{{ purchase.debt_amount }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </section>
</template>
