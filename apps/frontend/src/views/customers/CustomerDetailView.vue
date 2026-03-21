- <script setup lang="ts">
import { computed, onMounted, reactive } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useCustomersStore } from '../../stores/customers'

const route = useRoute()
const customers = useCustomersStore()

const customerId = computed(() => Number(route.params.id || 0))

const paymentForm = reactive({
  amount: '',
  payment_method: 'cash',
  notes: '',
})

onMounted(async () => {
  if (customerId.value > 0) {
    await customers.fetchById(customerId.value)
  }
})

async function submitPayment(): Promise<void> {
  const amount = Number(paymentForm.amount)
  if (!Number.isFinite(amount) || amount <= 0 || customerId.value <= 0) return

  await customers.payDebt(customerId.value, {
    amount,
    payment_method: paymentForm.payment_method,
    notes: paymentForm.notes.trim(),
  })

  paymentForm.amount = ''
  paymentForm.notes = ''
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">Chi tiết khách hàng</h2>
        <p class="text-sm text-ink/60">Xem lịch sử mua và ghi nhận thanh toán nợ.</p>
      </div>
      <RouterLink :to="`/customers/${customerId}/edit`" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Sửa thông tin</RouterLink>
    </header>

    <p v-if="customers.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ customers.error }}</p>

    <div v-if="customers.detailLoading" class="space-y-2">
      <div class="h-20 animate-pulse rounded-2xl bg-black/10" />
      <div class="h-40 animate-pulse rounded-2xl bg-black/10" />
    </div>

    <template v-else-if="customers.detail">
      <div class="rounded-2xl border border-black/10 bg-white p-4">
        <h3 class="text-lg font-semibold">{{ customers.detail.customer.name }}</h3>
        <p class="text-sm text-ink/70">SĐT: {{ customers.detail.customer.phone || '-' }}</p>
        <p class="text-sm text-ink/70">Điện thoại: {{ customers.detail.customer.phone || '-' }}</p>
        <p class="text-sm text-ink/70">Địa chỉ: {{ customers.detail.customer.address || '-' }}</p>
      </div>

      <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <p class="text-xs uppercase tracking-wider text-ink/50">Tổng mua</p>
          <p class="mt-1 text-2xl font-semibold">{{ customers.detail.summary.total_spent }}</p>
        </div>
        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <p class="text-xs uppercase tracking-wider text-ink/50">Công nợ hiện tại</p>
          <p class="mt-1 text-2xl font-semibold text-red-600">{{ customers.detail.summary.total_debt }}</p>
        </div>
      </div>

      <form class="rounded-2xl border border-black/10 bg-white p-4" @submit.prevent="submitPayment">
        <h3 class="text-base font-semibold">Thanh toán công nợ</h3>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
          <input v-model="paymentForm.amount" type="number" min="0" step="1000" class="rounded-xl border border-gray-300 px-3 py-2" placeholder="Số tiền" />
          <select v-model="paymentForm.payment_method" class="rounded-xl border border-gray-300 px-3 py-2">
            <option value="cash">Tiền mặt</option>
            <option value="bank">Chuyển khoản</option>
            <option value="other">Khác</option>
          </select>
          <input v-model="paymentForm.notes" type="text" class="rounded-xl border border-gray-300 px-3 py-2" placeholder="Ghi chú" />
        </div>
        <button type="submit" class="mt-3 rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="customers.paymentSaving">
          {{ customers.paymentSaving ? 'Đang ghi nhận...' : 'Xác nhận thanh toán' }}
        </button>
      </form>

      <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
        <h3 class="border-b border-black/10 px-3 py-2 text-sm font-semibold">Đơn hàng gần đây</h3>
        <table class="min-w-full text-sm">
          <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
            <tr>
              <th class="px-3 py-2">Mã đơn</th>
              <th class="px-3 py-2">Ngày</th>
              <th class="px-3 py-2">Giá trị</th>
              <th class="px-3 py-2">Nợ</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="customers.detail.latest_orders.length === 0" class="border-t border-black/5">
              <td class="px-3 py-4 text-center text-ink/60" colspan="4">Chưa có đơn hàng gần đây.</td>
            </tr>
            <tr v-else v-for="order in customers.detail.latest_orders" :key="order.id" class="border-t border-black/5">
              <td class="px-3 py-2">{{ order.order_code }}</td>
              <td class="px-3 py-2">{{ order.order_date }}</td>
              <td class="px-3 py-2">{{ order.final_amount }}</td>
              <td class="px-3 py-2 text-red-600">{{ order.debt_amount }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
        <h3 class="border-b border-black/10 px-3 py-2 text-sm font-semibold">Thanh toán gần đây</h3>
        <table class="min-w-full text-sm">
          <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
            <tr>
              <th class="px-3 py-2">Ngày</th>
              <th class="px-3 py-2">Số tiền</th>
              <th class="px-3 py-2">Phương thức</th>
              <th class="px-3 py-2">Ghi chú</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="customers.detail.recent_payments.length === 0" class="border-t border-black/5">
              <td class="px-3 py-4 text-center text-ink/60" colspan="4">Chưa có thanh toán gần đây.</td>
            </tr>
            <tr v-else v-for="payment in customers.detail.recent_payments" :key="payment.id" class="border-t border-black/5">
              <td class="px-3 py-2">{{ payment.payment_date }}</td>
              <td class="px-3 py-2">{{ payment.amount }}</td>
              <td class="px-3 py-2">{{ payment.payment_method }}</td>
              <td class="px-3 py-2">{{ payment.notes || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </section>
</template>
