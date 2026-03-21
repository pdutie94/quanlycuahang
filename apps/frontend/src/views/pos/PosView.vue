<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { formatMoney } from '../../lib/format'
import { usePosStore } from '../../stores/pos'

const router = useRouter()
const pos = usePosStore()
const keyword = ref('')

onMounted(async () => {
  await pos.loadCreateData()
})

const filteredProducts = computed(() => {
  const q = keyword.value.trim().toLowerCase()
  if (!q) return pos.products
  return pos.products.filter((product) => `${product.name} ${product.code}`.toLowerCase().includes(q))
})

async function handleCheckout(): Promise<void> {
  try {
    const result = await pos.checkout()
    window.alert(`Đã tạo đơn ${result.order_code}`)
    await router.push(`/orders/${result.id}`)
  } catch {
    // Error shown by store
  }
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-xl font-semibold">POS bán hàng</h2>
        <p class="text-sm text-ink/60">Thêm sản phẩm nhanh, chỉnh giỏ cục bộ và checkout một lần.</p>
      </div>
      <RouterLink to="/orders" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium">Xem đơn hàng</RouterLink>
    </header>

    <p v-if="pos.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ pos.error }}</p>

    <div class="grid gap-4 lg:grid-cols-3">
      <div class="rounded-2xl border border-black/10 bg-white p-4 lg:col-span-2">
        <input v-model="keyword" type="text" class="mb-3 w-full rounded-xl border border-gray-300 px-3 py-2" placeholder="Tìm sản phẩm theo tên/mã" />
        <div class="grid max-h-[520px] gap-2 overflow-auto sm:grid-cols-2">
          <button
            v-for="product in filteredProducts"
            :key="product.id"
            type="button"
            class="rounded-xl border border-black/10 p-3 text-left transition hover:bg-black/5"
            @click="product.units[0] && pos.addItem(product, product.units[0])"
          >
            <p class="font-medium">{{ product.name }}</p>
            <p class="text-xs text-ink/60">{{ product.code }}</p>
            <p class="text-xs text-ink/70">{{ product.units[0]?.unit_name }} · {{ formatMoney(product.units[0]?.price_sell || 0) }}</p>
          </button>
        </div>
      </div>

      <div class="space-y-3 rounded-2xl border border-black/10 bg-white p-4">
        <h3 class="text-base font-semibold">Giỏ hàng</h3>
        <div class="max-h-[360px] space-y-2 overflow-auto">
          <div v-if="pos.cart.length === 0" class="rounded-xl border border-dashed border-black/15 px-3 py-4 text-sm text-ink/60">Chưa có sản phẩm trong giỏ.</div>
          <div v-for="line in pos.cart" :key="line.key" class="rounded-xl border border-black/10 p-2">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-sm font-medium">{{ line.productName }}</p>
                <p class="text-xs text-ink/60">{{ line.unitName }}</p>
              </div>
              <button type="button" class="text-xs text-red-600" @click="pos.removeItem(line.key)">Xóa</button>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-2">
              <input :value="line.qty" type="number" min="0" class="rounded-lg border border-gray-300 px-2 py-1 text-sm" @input="pos.updateQty(line.key, Number(($event.target as HTMLInputElement).value))" />
              <input :value="line.priceSell" type="number" min="0" class="rounded-lg border border-gray-300 px-2 py-1 text-sm" @input="pos.updatePrice(line.key, Number(($event.target as HTMLInputElement).value))" />
            </div>
            <p class="mt-2 text-right text-sm font-medium">{{ formatMoney(line.qty * line.priceSell) }}</p>
          </div>
        </div>

        <div class="rounded-xl bg-black/5 px-3 py-2 text-sm">Tạm tính: {{ formatMoney(pos.subtotal) }}</div>

        <label class="block text-sm font-medium text-gray-700">Số tiền thu</label>
        <input v-model.number="pos.paymentAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />

        <label class="block text-sm font-medium text-gray-700">Hình thức</label>
        <select v-model="pos.paymentStatus" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
          <option value="debt">Ghi nợ</option>
          <option value="pay">Thu ngay</option>
        </select>

        <div class="text-lg font-semibold">Tổng: {{ formatMoney(pos.grandTotal) }}</div>

        <button type="button" class="w-full rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="pos.saving || pos.cart.length === 0" @click="handleCheckout">Checkout</button>
      </div>
    </div>
  </section>
</template>
