<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { formatDate, formatMoney } from '../../lib/format'
import { orderService, type OrderDetail } from '../../services/orderService'
import SkeletonBlock from '../SkeletonBlock.vue'
import AppModal from '../AppModal.vue'

const props = defineProps<{
  orderId: number | null
  open: boolean
}>()

const emit = defineEmits<{ close: [] }>()

const router = useRouter()
const loading = ref(false)
const error = ref('')
const detail = ref<OrderDetail | null>(null)

const debt = computed(() => detail.value?.summary.debt ?? 0)
const paid = computed(() => detail.value?.summary.paid ?? 0)
const total = computed(() => detail.value?.summary.total ?? 0)
const isPaidFull = computed(() => debt.value <= 0)
const modalTitle = computed(() => detail.value?.order.customer_name || 'Khách hàng')
const modalSubtitle = computed(() => {
  const code = detail.value?.order.order_code || `DH-${props.orderId}`
  const date = detail.value?.order.order_date ? formatDate(detail.value.order.order_date) : ''
  return date ? `${code}, ${date}` : code
})

function translateOrderStatus(status: string): string {
  const statusMap: Record<string, string> = {
    'pending': 'Chờ xử lý',
    'completed': 'Hoàn thành',
    'cancelled': 'Đã hủy',
  }
  return statusMap[status] || status
}

async function fetchPreview(id: number): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    detail.value = await orderService.getById(id)
  } catch {
    detail.value = null
    error.value = 'Không tải được preview đơn hàng.'
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.open, props.orderId] as const,
  async ([open, orderId]) => {
    if (!open || !orderId) return
    await fetchPreview(orderId)
  },
  { immediate: true },
)

async function goToDetail(): Promise<void> {
  if (!detail.value) return
  emit('close')
  await router.push(`/orders/${detail.value.order.id}`)
}
</script>

<template>
  <AppModal :open="open" :title="modalTitle" :subtitle="modalSubtitle" @close="emit('close')">
    <!-- body -->
    <div v-if="loading" class="space-y-3">
      <SkeletonBlock height-class="h-6" rounded-class="rounded-lg" />
      <SkeletonBlock height-class="h-24" rounded-class="rounded-xl" />
      <SkeletonBlock height-class="h-32" rounded-class="rounded-xl" />
    </div>

    <p v-else-if="error" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ error }}</p>

    <template v-else-if="detail">
      <div class="mb-1 flex flex-wrap gap-2">
        <span class="rounded-md px-2 py-1 text-xs font-semibold" :class="isPaidFull ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
          {{ isPaidFull ? 'Đã thanh toán' : 'Còn nợ' }}
        </span>
        <span class="rounded-md bg-sky-100 px-2 py-1 text-xs font-semibold text-sky-700">
          {{ translateOrderStatus(detail.order.order_status) }}
        </span>
      </div>

      <div class="divide-y divide-slate-200">
        <div class="flex items-center justify-between py-2 text-sm">
          <span class="text-slate-600">Tạm tính</span>
          <strong class="text-slate-800">{{ formatMoney(total) }}</strong>
        </div>
        <div class="flex items-center justify-between py-2 text-sm">
          <span class="text-slate-500">Giảm giá</span>
          <span class="text-slate-500">{{ formatMoney(detail.order.discount_amount || 0) }}</span>
        </div>
        <div class="flex items-center justify-between py-2 text-sm">
          <span class="text-slate-500">Phụ thu</span>
          <span class="text-slate-500">{{ formatMoney(detail.order.surcharge_amount || 0) }}</span>
        </div>
        <div class="flex items-center justify-between py-2">
          <span class="font-semibold text-slate-700">Tổng cộng</span>
          <strong class="text-xl text-slate-900">{{ formatMoney(total) }}</strong>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <div class="fancy-box tone-mint rounded-xl px-3 py-2">
          <p class="text-xs text-slate-500">Đã thanh toán</p>
          <p class="text-lg font-semibold text-teal-700">{{ formatMoney(paid) }}</p>
        </div>
        <div class="fancy-box rounded-xl px-3 py-2" :class="debt > 0 ? 'tone-rose' : 'tone-sky'">
          <p class="text-xs text-slate-500">Còn nợ</p>
          <p class="text-lg font-semibold" :class="debt > 0 ? 'text-rose-700' : 'text-slate-600'">{{ formatMoney(debt) }}</p>
        </div>
      </div>

      <section class="mt-3 rounded-xl border border-slate-200">
        <h4 class="border-b border-slate-200 px-3 py-2 text-sm font-semibold">Sản phẩm</h4>
        <ul class="divide-y divide-slate-200">
          <li v-for="item in detail.items" :key="`item-${item.id}`" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
            <div>
              <p class="font-medium text-slate-800">{{ item.product_name }}</p>
              <p class="text-xs text-slate-500">SL: {{ item.qty }} {{ item.unit_name }} · Giá: {{ formatMoney(item.price_sell) }}</p>
            </div>
            <p class="font-semibold text-slate-700">{{ formatMoney(item.amount) }}</p>
          </li>
          <li v-for="manual in detail.manual_items" :key="`manual-${manual.id}`" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
            <div>
              <p class="font-medium text-slate-800">{{ manual.item_name }}</p>
              <p class="text-xs text-slate-500">SL: {{ manual.qty }} {{ manual.unit_name || '' }} · Giá: {{ formatMoney(manual.price_sell) }}</p>
            </div>
            <p class="font-semibold text-slate-700">{{ formatMoney(manual.amount_sell) }}</p>
          </li>
        </ul>
      </section>
    </template>

    <template #footer>
      <div class="flex items-center justify-end gap-2">
        <button type="button" class="rounded-xl bg-teal-600 px-4 py-1.5 text-white" :disabled="!detail" @click="goToDetail">Xem chi tiết</button>
        <button type="button" class="rounded-xl border border-slate-300 px-4 py-1.5 text-slate-700" @click="emit('close')">Đóng</button>
      </div>
    </template>
  </AppModal>
</template>
