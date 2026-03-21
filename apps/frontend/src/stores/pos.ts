import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { orderService, type PosCustomer, type PosProduct, type PosUnit } from '../services/orderService'

export type PosCartItem = {
  key: string
  productId: number
  productName: string
  productUnitId: number
  unitName: string
  qty: number
  priceSell: number
  maxStep: number
  allowFraction: boolean
}

export const usePosStore = defineStore('pos', () => {
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')

  const products = ref<PosProduct[]>([])
  const customers = ref<PosCustomer[]>([])
  const cart = ref<PosCartItem[]>([])

  const selectedCustomerId = ref<number | null>(null)
  const customerName = ref('')
  const customerPhone = ref('')
  const customerAddress = ref('')
  const note = ref('')

  const paymentStatus = ref<'pay' | 'debt'>('debt')
  const paymentMethod = ref<'cash' | 'bank'>('cash')
  const paymentAmount = ref(0)
  const discountType = ref<'none' | 'fixed' | 'percent'>('none')
  const discountValue = ref(0)
  const surchargeAmount = ref(0)

  const subtotal = computed(() => cart.value.reduce((sum, item) => sum + item.qty * item.priceSell, 0))
  const discountAmount = computed(() => {
    if (discountType.value === 'fixed') {
      return Math.min(subtotal.value, Math.max(0, discountValue.value))
    }
    if (discountType.value === 'percent') {
      return Math.min(subtotal.value, Math.round(subtotal.value * Math.min(100, Math.max(0, discountValue.value)) / 100))
    }
    return 0
  })
  const grandTotal = computed(() => Math.max(0, subtotal.value - discountAmount.value + Math.max(0, surchargeAmount.value)))

  async function loadCreateData(): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      const result = await orderService.getCreateData()
      products.value = result.products
      customers.value = result.customers
    } catch {
      error.value = 'Không tải được dữ liệu POS.'
    } finally {
      loading.value = false
    }
  }

  function addItem(product: PosProduct, unit: PosUnit): void {
    const key = `${product.id}-${unit.id}`
    const existing = cart.value.find((item) => item.key === key)
    if (existing) {
      const nextQty = existing.qty + (unit.allow_fraction ? unit.min_step : 1)
      existing.qty = normalizeQty(nextQty, !!unit.allow_fraction, unit.min_step)
      return
    }

    cart.value.unshift({
      key,
      productId: product.id,
      productName: product.name,
      productUnitId: unit.id,
      unitName: unit.unit_name,
      qty: normalizeQty(unit.allow_fraction ? unit.min_step : 1, !!unit.allow_fraction, unit.min_step),
      priceSell: unit.price_sell,
      maxStep: unit.min_step,
      allowFraction: !!unit.allow_fraction,
    })
  }

  function updateQty(key: string, qty: number): void {
    const item = cart.value.find((line) => line.key === key)
    if (!item) return

    const normalized = normalizeQty(qty, item.allowFraction, item.maxStep)
    if (normalized <= 0) {
      removeItem(key)
      return
    }

    item.qty = normalized
  }

  function updatePrice(key: string, price: number): void {
    const item = cart.value.find((line) => line.key === key)
    if (!item) return
    item.priceSell = Math.max(0, price)
  }

  function removeItem(key: string): void {
    cart.value = cart.value.filter((item) => item.key !== key)
  }

  function clearCart(): void {
    cart.value = []
    selectedCustomerId.value = null
    customerName.value = ''
    customerPhone.value = ''
    customerAddress.value = ''
    note.value = ''
    paymentStatus.value = 'debt'
    paymentMethod.value = 'cash'
    paymentAmount.value = 0
    discountType.value = 'none'
    discountValue.value = 0
    surchargeAmount.value = 0
  }

  async function checkout(): Promise<{ id: number; order_code: string }> {
    if (cart.value.length === 0) {
      throw new Error('Giỏ hàng đang trống')
    }

    saving.value = true
    error.value = ''

    try {
      const payload = {
        order_date: new Date().toISOString(),
        customer_id: selectedCustomerId.value ?? undefined,
        customer_name: customerName.value || undefined,
        customer_phone: customerPhone.value || undefined,
        customer_address: customerAddress.value || undefined,
        note: note.value || undefined,
        payment_status: paymentStatus.value,
        payment_method: paymentMethod.value,
        payment_amount: paymentStatus.value === 'pay' ? paymentAmount.value || grandTotal.value : 0,
        discount_type: discountType.value,
        discount_value: discountValue.value,
        surcharge_amount: surchargeAmount.value,
        items: cart.value.map((item) => ({
          product_unit_id: item.productUnitId,
          qty: item.qty,
          price_sell: item.priceSell,
        })),
      } as const

      const result = await orderService.create(payload)
      clearCart()
      return result
    } catch {
      error.value = 'Không thể tạo đơn hàng từ POS.'
      throw new Error('POS checkout failed')
    } finally {
      saving.value = false
    }
  }

  function normalizeQty(qty: number, allowFraction: boolean, minStep: number): number {
    if (!allowFraction) {
      return Math.max(0, Math.round(qty))
    }
    const step = Math.max(0.0001, minStep || 1)
    const steps = Math.floor((qty + 0.0000001) / step)
    return Math.max(0, steps * step)
  }

  return {
    loading,
    saving,
    error,
    products,
    customers,
    cart,
    selectedCustomerId,
    customerName,
    customerPhone,
    customerAddress,
    note,
    paymentStatus,
    paymentMethod,
    paymentAmount,
    discountType,
    discountValue,
    surchargeAmount,
    subtotal,
    discountAmount,
    grandTotal,
    loadCreateData,
    addItem,
    updateQty,
    updatePrice,
    removeItem,
    clearCart,
    checkout,
  }
})
