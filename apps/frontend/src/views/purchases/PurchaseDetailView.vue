<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePurchasesStore } from '../../stores/purchases'
import { formatMoney } from '../../lib/format'

const route = useRoute()
const router = useRouter()
const purchases = usePurchasesStore()

const paymentAmount = ref(0)
const paymentMethod = ref<'cash' | 'bank'>('cash')
const paymentNote = ref('')

const purchaseId = computed(() => Number(route.params.id || 0))

onMounted(async () => {
  if (!purchaseId.value) {
    router.replace('/purchases')
    return
  }

  await purchases.fetchById(purchaseId.value)
})

async function submitPayment(): Promise<void> {
  if (!purchaseId.value || paymentAmount.value <= 0) return

  await purchases.addPayment(purchaseId.value, {
    amount: paymentAmount.value,
    payment_method: paymentMethod.value,
    note: paymentNote.value || undefined,
  })

  paymentAmount.value = 0
  paymentNote.value = ''
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold">Chi tiết phiếu nhập</h2>
        <p class="text-sm text-ink/60">Theo dõi mặt hàng nhập kho và thanh toán NCC.</p>
      </div>
      <div class="flex gap-2">
        <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push('/purchases')">Quay lại</button>
        <button v-if="purchaseId" type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push(`/purchases/${purchaseId}/edit`)">Sửa phiếu</button>
      </div>
    </header>

    <p v-if="purchases.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ purchases.error }}</p>

    <div v-if="purchases.detailLoading" class="rounded-2xl border border-black/10 bg-white p-4">
      <div class="h-24 animate-pulse rounded bg-black/10" />
    </div>

    <template v-else-if="purchases.detail">
      <div class="grid gap-4 rounded-2xl border border-black/10 bg-white p-4 md:grid-cols-2">
        <div class="space-y-1 text-sm">
          <p><strong>Mã phiếu:</strong> {{ purchases.detail.purchase.purchase_code }}</p>
          <p><strong>Nhà cung cấp:</strong> {{ purchases.detail.purchase.supplier_name }}</p>
          <p><strong>Ngày nhập:</strong> {{ purchases.detail.purchase.purchase_date }}</p>
          <p><strong>Tổng tiền:</strong> {{ formatMoney(purchases.detail.purchase.total_amount) }}</p>
          <p><strong>Đã trả:</strong> {{ formatMoney(purchases.detail.purchase.paid_amount) }}</p>
          <p><strong>Còn nợ:</strong> {{ formatMoney(purchases.detail.purchase.total_amount - purchases.detail.purchase.paid_amount) }}</p>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Số tiền thanh toán</label>
          <input v-model.number="paymentAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
          <label class="block text-sm font-medium text-gray-700">Phương thức</label>
          <select v-model="paymentMethod" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
            <option value="cash">Tiền mặt</option>
            <option value="bank">Chuyển khoản</option>
          </select>
          <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
          <input v-model="paymentNote" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
          <button type="button" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="purchases.saving" @click="submitPayment">Ghi nhận thanh toán</button>
        </div>
      </div>

      <div class="rounded-2xl border border-black/10 bg-white p-4">
        <h3 class="mb-2 text-base font-semibold">Danh sách mặt hàng nhập</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
              <tr>
                <th class="px-3 py-2">Sản phẩm</th>
                <th class="px-3 py-2">Đơn vị</th>
                <th class="px-3 py-2">SL</th>
                <th class="px-3 py-2">Giá nhập</th>
                <th class="px-3 py-2">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in purchases.detail.items" :key="item.id" class="border-t border-black/5">
                <td class="px-3 py-2">{{ item.product_name }}</td>
                <td class="px-3 py-2">{{ item.unit_name }}</td>
                <td class="px-3 py-2">{{ item.qty }}</td>
                <td class="px-3 py-2">{{ formatMoney(item.price_cost) }}</td>
                <td class="px-3 py-2">{{ formatMoney(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </section>
</template>
