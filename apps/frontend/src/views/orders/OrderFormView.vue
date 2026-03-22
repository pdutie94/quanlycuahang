<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { formatMoney } from '../../lib/format'
import { usePosStore } from '../../stores/pos'

const router = useRouter()
const pos = usePosStore()
const keyword = ref('')

onMounted(async () => {
  if (pos.products.length === 0) {
    await pos.loadCreateData()
  }
})

const filteredProducts = computed(() => {
  const q = keyword.value.trim().toLowerCase()
  if (!q) return pos.products
  return pos.products.filter((product) => `${product.name} ${product.code}`.toLowerCase().includes(q))
})

async function submitOrder(): Promise<void> {
  try {
    const result = await pos.checkout()
    window.alert(`Đã tạo đơn ${result.order_code}`)
    await router.push(`/orders/${result.id}`)
  } catch {
    // Error message is shown via pos.error
  }
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold">Tạo đơn hàng</h2>
        <p class="text-sm text-ink/60">Biểu mẫu tạo nhanh theo cart local, không phụ thuộc response API.</p>
      </div>
      <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push('/orders')">Quay lại</button>
    </header>

    <p v-if="pos.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ pos.error }}</p>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="space-y-3 rounded-2xl border border-black/10 bg-white p-4 lg:col-span-2">
        <input v-model="keyword" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm sản phẩm" />

        <div class="grid max-h-[420px] gap-2 overflow-auto sm:grid-cols-2">
          <button
            v-for="product in filteredProducts"
            :key="product.id"
            type="button"
            class="rounded-xl border border-black/10 p-3 text-left hover:bg-black/5"
            @click="product.units[0] && pos.addItem(product, product.units[0])"
          >
            <p class="font-medium">{{ product.name }}</p>
            <p class="text-xs text-ink/60">{{ product.code }}</p>
            <p class="text-xs text-ink/70">{{ product.units[0]?.unit_name }} · {{ formatMoney(product.units[0]?.price_sell || 0) }}</p>
          </button>
        </div>
      </div>

      <div class="space-y-3 rounded-2xl border border-black/10 bg-white p-4">
        <label class="block text-sm font-medium text-gray-700">Khách hàng (chọn sẵn)</label>
        <select v-model.number="pos.selectedCustomerId" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
          <option :value="null">Khách lẻ</option>
          <option v-for="customer in pos.customers" :key="customer.id" :value="customer.id">{{ customer.name }} - {{ customer.phone || '-' }}</option>
        </select>

        <label class="block text-sm font-medium text-gray-700">Tên khách lẻ</label>
        <input v-model="pos.customerName" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />

        <label class="block text-sm font-medium text-gray-700">SĐT</label>
        <input v-model="pos.customerPhone" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />

        <label class="block text-sm font-medium text-gray-700">Ghi chú</label>
        <input v-model="pos.note" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
      </div>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-4">
      <h3 class="mb-3 text-base font-semibold">Giỏ hàng</h3>
      <div class="space-y-2">
        <div v-if="pos.cart.length === 0" class="rounded-xl bg-black/5 px-3 py-4 text-center text-sm text-ink/60">Giỏ hàng trống.</div>
        <article v-for="line in pos.cart" :key="line.key" class="rounded-xl border border-black/10 p-3">
          <div class="flex items-start justify-between gap-2">
            <h4 class="font-medium">{{ line.productName }}</h4>
            <p class="text-xs text-ink/60">{{ line.unitName }}</p>
          </div>
          <div class="mt-2 grid gap-2 sm:grid-cols-3">
            <input :value="line.qty" type="number" min="0" class="rounded-lg border border-gray-300 px-2 py-1 text-sm" @input="pos.updateQty(line.key, Number(($event.target as HTMLInputElement).value))" />
            <input :value="line.priceSell" type="number" min="0" class="rounded-lg border border-gray-300 px-2 py-1 text-sm" @input="pos.updatePrice(line.key, Number(($event.target as HTMLInputElement).value))" />
            <p class="rounded-lg bg-black/5 px-2 py-1 text-sm">{{ formatMoney(line.qty * line.priceSell) }}</p>
          </div>
          <button type="button" class="mt-2 rounded-lg border border-red-200 px-2 py-1 text-xs text-red-600" @click="pos.removeItem(line.key)">Xóa</button>
        </article>
      </div>

      <div class="mt-4 grid gap-3 md:grid-cols-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Thanh toán</label>
          <select v-model="pos.paymentStatus" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
            <option value="debt">Ghi nợ</option>
            <option value="pay">Thu ngay</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Số tiền thu</label>
          <input v-model.number="pos.paymentAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Giảm giá</label>
          <input v-model.number="pos.discountValue" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Phụ thu</label>
          <input v-model.number="pos.surchargeAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
        </div>
      </div>

      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-black/5 px-4 py-3">
        <div class="text-sm text-ink/70">Tạm tính: {{ formatMoney(pos.subtotal) }} · Giảm: {{ formatMoney(pos.discountAmount) }}</div>
        <div class="text-lg font-semibold">Tổng thanh toán: {{ formatMoney(pos.grandTotal) }}</div>
      </div>

      <div class="mt-3 flex justify-end gap-2">
        <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" :disabled="pos.saving" @click="pos.clearCart">Làm mới</button>
        <button type="button" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="pos.saving || pos.cart.length === 0" @click="submitOrder">Lưu đơn</button>
      </div>
    </div>
  </section>
</template>
