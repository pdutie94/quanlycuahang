<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOrdersStore } from '../../stores/orders'
import { formatMoney } from '../../lib/format'

const route = useRoute()
const router = useRouter()
const orders = useOrdersStore()

const status = ref<'pending' | 'completed' | 'cancelled'>('pending')
const statusNote = ref('')
const paymentAmount = ref(0)
const paymentMethod = ref<'cash' | 'bank'>('cash')
const paymentNote = ref('')
const returnAll = ref(false)
const returnQtyByItemId = ref<Record<number, number>>({})
const returnNote = ref('')

const orderId = computed(() => Number(route.params.id || 0))

onMounted(async () => {
  if (!orderId.value) {
    router.replace('/orders')
    return
  }

  await orders.fetchById(orderId.value)
  if (orders.detail?.order.order_status) {
    status.value = orders.detail.order.order_status
  }
  resetReturnQtyInputs()
})

function resetReturnQtyInputs(): void {
  const next: Record<number, number> = {}
  for (const item of orders.detail?.items || []) {
    next[item.id] = 0
  }
  returnQtyByItemId.value = next
}

async function submitStatus(): Promise<void> {
  if (!orderId.value) return
  await orders.updateStatus(orderId.value, { order_status: status.value, note: statusNote.value || undefined })
}

async function submitPayment(): Promise<void> {
  if (!orderId.value || paymentAmount.value <= 0) return
  await orders.addPayment(orderId.value, {
    amount: paymentAmount.value,
    payment_method: paymentMethod.value,
    note: paymentNote.value || undefined,
  })
  paymentAmount.value = 0
  paymentNote.value = ''
}

async function submitReturn(): Promise<void> {
  if (!orderId.value) return

  const items = Object.entries(returnQtyByItemId.value)
    .map(([itemId, qty]) => ({ order_item_id: Number(itemId), qty: Number(qty) }))
    .filter((entry) => entry.order_item_id > 0 && entry.qty > 0)

  if (!returnAll.value && items.length === 0) return

  await orders.addReturn(orderId.value, {
    return_all: returnAll.value || undefined,
    items: returnAll.value ? undefined : items,
    note: returnNote.value || undefined,
  })

  await orders.fetchById(orderId.value)
  resetReturnQtyInputs()
  returnAll.value = false
  returnNote.value = ''
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold">Chi tiết đơn hàng</h2>
        <p class="text-sm text-ink/60">Theo dõi sản phẩm, thanh toán và cập nhật trạng thái đơn.</p>
      </div>
      <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push('/orders')">Quay lại</button>
    </header>

    <p v-if="orders.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ orders.error }}</p>

    <div v-if="orders.detailLoading" class="rounded-2xl border border-black/10 bg-white p-4">
      <div class="h-24 animate-pulse rounded bg-black/10" />
    </div>

    <template v-else-if="orders.detail">
      <div class="grid gap-4 rounded-2xl border border-black/10 bg-white p-4 md:grid-cols-2">
        <div class="space-y-1 text-sm">
          <p><strong>Mã đơn:</strong> {{ orders.detail.order.order_code }}</p>
          <p><strong>Khách:</strong> {{ orders.detail.order.customer_name || 'Khách lẻ' }}</p>
          <p><strong>Ngày:</strong> {{ orders.detail.order.order_date }}</p>
          <p><strong>Tổng tiền:</strong> {{ formatMoney(orders.detail.order.total_amount) }}</p>
          <p><strong>Đã thu:</strong> {{ formatMoney(orders.detail.order.paid_amount) }}</p>
          <p><strong>Còn nợ:</strong> {{ formatMoney(orders.detail.summary.debt) }}</p>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Trạng thái đơn</label>
          <select v-model="status" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
            <option value="pending">pending</option>
            <option value="completed">completed</option>
            <option value="cancelled">cancelled</option>
          </select>
          <label class="block text-sm font-medium text-gray-700">Ghi chú trạng thái</label>
          <input v-model="statusNote" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" placeholder="Ghi chú" />
          <button type="button" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="orders.saving" @click="submitStatus">Cập nhật trạng thái</button>
        </div>
      </div>

      <div class="rounded-2xl border border-black/10 bg-white p-4">
        <h3 class="mb-2 text-base font-semibold">Sản phẩm</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-black/5 text-left text-xs uppercase tracking-wider text-ink/60">
              <tr>
                <th class="px-3 py-2">Sản phẩm</th>
                <th class="px-3 py-2">Đơn vị</th>
                <th class="px-3 py-2">SL</th>
                <th class="px-3 py-2">Giá</th>
                <th class="px-3 py-2">Thành tiền</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in orders.detail.items" :key="item.id" class="border-t border-black/5">
                <td class="px-3 py-2">{{ item.product_name }}</td>
                <td class="px-3 py-2">{{ item.unit_name }}</td>
                <td class="px-3 py-2">{{ item.qty }}</td>
                <td class="px-3 py-2">{{ formatMoney(item.price_sell) }}</td>
                <td class="px-3 py-2">{{ formatMoney(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <h3 class="mb-3 text-base font-semibold">Ghi nhận thanh toán</h3>
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Số tiền</label>
            <input v-model.number="paymentAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
            <label class="block text-sm font-medium text-gray-700">Phương thức</label>
            <select v-model="paymentMethod" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
              <option value="cash">Tiền mặt</option>
              <option value="bank">Chuyển khoản</option>
            </select>
            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
            <input v-model="paymentNote" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
            <button type="button" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="orders.saving" @click="submitPayment">Thu tiền</button>
          </div>
        </div>

        <div class="rounded-2xl border border-black/10 bg-white p-4">
          <h3 class="mb-3 text-base font-semibold">Trả hàng theo item</h3>
          <div class="space-y-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
              <input v-model="returnAll" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
              Trả toàn bộ tất cả item
            </label>

            <div class="max-h-44 space-y-2 overflow-auto rounded-xl border border-black/10 p-2">
              <div v-for="item in orders.detail.items" :key="item.id" class="grid grid-cols-[1fr_86px] items-center gap-2">
                <p class="truncate text-sm">{{ item.product_name }} (còn {{ item.qty }})</p>
                <input
                  v-model.number="returnQtyByItemId[item.id]"
                  type="number"
                  min="0"
                  :max="item.qty"
                  :disabled="returnAll"
                  class="w-full rounded-lg border border-gray-300 px-2 py-1 text-sm"
                />
              </div>
            </div>

            <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
            <input v-model="returnNote" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
            <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" :disabled="orders.saving" @click="submitReturn">Xác nhận trả hàng</button>
          </div>
        </div>
      </div>
    </template>
  </section>
</template>
