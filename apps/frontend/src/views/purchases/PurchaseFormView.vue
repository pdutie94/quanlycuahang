<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePurchasesStore } from '../../stores/purchases'

type FormLine = {
  product_unit_id: number
  qty: number
  price_cost: number
}

const route = useRoute()
const router = useRouter()
const purchases = usePurchasesStore()

const purchaseId = computed(() => Number(route.params.id || 0))
const isEdit = computed(() => purchaseId.value > 0)

const supplierId = ref(0)
const purchaseDate = ref('')
const note = ref('')
const paidAmount = ref(0)
const paymentMethod = ref<'cash' | 'bank'>('cash')
const lines = ref<FormLine[]>([{ product_unit_id: 0, qty: 1, price_cost: 0 }])

onMounted(async () => {
  await purchases.fetchCreateData()

  if (isEdit.value) {
    await purchases.fetchById(purchaseId.value)
    if (purchases.detail) {
      const current = purchases.detail.purchase
      supplierId.value = current.supplier_id
      purchaseDate.value = current.purchase_date?.slice(0, 16) || ''
      note.value = current.note || ''
      paidAmount.value = current.paid_amount || 0
      lines.value = purchases.detail.items.map((item) => ({
        product_unit_id: item.product_unit_id,
        qty: item.qty,
        price_cost: item.price_cost,
      }))
    }
  }
})

function addLine(): void {
  lines.value.push({ product_unit_id: 0, qty: 1, price_cost: 0 })
}

function removeLine(index: number): void {
  if (lines.value.length === 1) return
  lines.value.splice(index, 1)
}

async function submitForm(): Promise<void> {
  if (!supplierId.value) return

  const payload = {
    supplier_id: supplierId.value,
    purchase_date: purchaseDate.value || undefined,
    paid_amount: isEdit.value ? undefined : paidAmount.value,
    payment_method: isEdit.value ? undefined : paymentMethod.value,
    note: note.value || undefined,
    items: lines.value
      .map((line) => ({
        product_unit_id: Number(line.product_unit_id),
        qty: Number(line.qty),
        price_cost: Number(line.price_cost),
      }))
      .filter((line) => line.product_unit_id > 0 && line.qty > 0),
  }

  if (payload.items.length === 0) return

  try {
    if (isEdit.value) {
      await purchases.update(purchaseId.value, payload)
      await router.push(`/purchases/${purchaseId.value}`)
    } else {
      const result = await purchases.create(payload)
      await router.push(`/purchases/${result.id}`)
    }
  } catch {
    // Store already sets error message
  }
}
</script>

<template>
  <section class="space-y-4">
    <header class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold">{{ isEdit ? 'Cập nhật phiếu nhập' : 'Tạo phiếu nhập' }}</h2>
        <p class="text-sm text-ink/60">Nhập hàng vào kho và quản lý công nợ theo từng phiếu.</p>
      </div>
      <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push('/purchases')">Quay lại</button>
    </header>

    <p v-if="purchases.error" class="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-600">{{ purchases.error }}</p>

    <div class="grid gap-4 rounded-2xl border border-black/10 bg-white p-4 md:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Nhà cung cấp</label>
        <select v-model.number="supplierId" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
          <option :value="0">Chọn nhà cung cấp</option>
          <option v-for="supplier in purchases.createData?.suppliers || []" :key="supplier.id" :value="supplier.id">
            {{ supplier.name }} - {{ supplier.phone || '-' }}
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Ngày nhập</label>
        <input v-model="purchaseDate" type="datetime-local" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-gray-700">Ghi chú</label>
        <input v-model="note" type="text" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
      </div>

      <template v-if="!isEdit">
        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Đã thanh toán lúc tạo phiếu</label>
          <input v-model.number="paidAmount" type="number" min="0" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-gray-700">Phương thức thanh toán</label>
          <select v-model="paymentMethod" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
            <option value="cash">Tiền mặt</option>
            <option value="bank">Chuyển khoản</option>
          </select>
        </div>
      </template>
    </div>

    <div class="rounded-2xl border border-black/10 bg-white p-4">
      <div class="mb-3 flex items-center justify-between">
        <h3 class="text-base font-semibold">Mặt hàng nhập</h3>
        <button type="button" class="rounded-lg border border-black/15 px-3 py-1 text-sm" @click="addLine">+ Thêm dòng</button>
      </div>

      <div class="space-y-3">
        <div v-for="(line, index) in lines" :key="index" class="grid gap-2 rounded-xl border border-black/10 p-3 md:grid-cols-[1.4fr_0.6fr_0.6fr_auto]">
          <select v-model.number="line.product_unit_id" class="rounded-lg border border-gray-300 px-2 py-2 text-sm">
            <option :value="0">Chọn sản phẩm / đơn vị</option>
            <option v-for="unit in purchases.createData?.product_units || []" :key="unit.product_unit_id" :value="unit.product_unit_id">
              {{ unit.product_name }} ({{ unit.unit_name }})
            </option>
          </select>
          <input v-model.number="line.qty" type="number" min="0" step="0.01" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" placeholder="SL" />
          <input v-model.number="line.price_cost" type="number" min="0" class="rounded-lg border border-gray-300 px-2 py-2 text-sm" placeholder="Giá nhập" />
          <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600" @click="removeLine(index)">Xóa</button>
        </div>
      </div>
    </div>

    <div class="flex justify-end gap-2">
      <button type="button" class="rounded-xl border border-black/15 bg-white px-4 py-2 text-sm font-medium" @click="router.push('/purchases')">Hủy</button>
      <button type="button" class="rounded-xl bg-pine px-4 py-2 text-sm font-medium text-white" :disabled="purchases.saving" @click="submitForm">
        {{ isEdit ? 'Lưu cập nhật' : 'Tạo phiếu nhập' }}
      </button>
    </div>
  </section>
</template>
