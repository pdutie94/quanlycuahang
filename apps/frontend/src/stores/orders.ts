import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { orderService, type CreateOrderPayload, type Order, type OrderDetail } from '../services/orderService'

export const useOrdersStore = defineStore('orders', () => {
  const loading = ref(false)
  const saving = ref(false)
  const detailLoading = ref(false)
  const error = ref('')

  const items = ref<Order[]>([])
  const detail = ref<OrderDetail | null>(null)

  const page = ref(1)
  const perPage = ref(20)
  const total = ref(0)
  const query = ref('')
  const status = ref('')
  const orderStatus = ref('')

  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

  async function fetchList(override?: {
    page?: number
    q?: string
    status?: string
    order_status?: string
  }): Promise<void> {
    loading.value = true
    error.value = ''

    if (override?.page !== undefined) page.value = override.page
    if (override?.q !== undefined) query.value = override.q
    if (override?.status !== undefined) status.value = override.status
    if (override?.order_status !== undefined) orderStatus.value = override.order_status

    try {
      const result = await orderService.getList({
        page: page.value,
        per_page: perPage.value,
        q: query.value || undefined,
        status: status.value || undefined,
        order_status: orderStatus.value || undefined,
      })

      items.value = result.data
      total.value = result.meta.total
      page.value = result.meta.page
      perPage.value = result.meta.per_page
    } catch {
      error.value = 'Không tải được danh sách đơn hàng.'
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number): Promise<void> {
    detailLoading.value = true
    error.value = ''
    try {
      detail.value = await orderService.getById(id)
    } catch {
      error.value = 'Không tải được chi tiết đơn hàng.'
      throw new Error('Fetch order detail failed')
    } finally {
      detailLoading.value = false
    }
  }

  async function create(payload: CreateOrderPayload): Promise<{ id: number; order_code: string }> {
    saving.value = true
    error.value = ''
    try {
      return await orderService.create(payload)
    } catch {
      error.value = 'Không thể tạo đơn hàng.'
      throw new Error('Create order failed')
    } finally {
      saving.value = false
    }
  }

  async function updateStatus(id: number, payload: { order_status: 'pending' | 'completed' | 'cancelled'; note?: string }): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await orderService.updateStatus(id, payload)
      if (detail.value?.order.id === id) {
        detail.value.order.order_status = payload.order_status
      }
    } catch {
      error.value = 'Không thể cập nhật trạng thái đơn hàng.'
      throw new Error('Update order status failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await orderService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
      total.value = Math.max(0, total.value - 1)
    } catch {
      error.value = 'Không thể xóa đơn hàng.'
      throw new Error('Delete order failed')
    } finally {
      saving.value = false
    }
  }

  async function restore(id: number): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await orderService.restore(id)
      await fetchList()
    } catch {
      error.value = 'Không thể khôi phục đơn hàng.'
      throw new Error('Restore order failed')
    } finally {
      saving.value = false
    }
  }

  async function addPayment(id: number, payload: { amount: number; note?: string; payment_method?: 'cash' | 'bank' }): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await orderService.addPayment(id, payload)
      await fetchById(id)
    } catch {
      error.value = 'Không thể ghi nhận thanh toán.'
      throw new Error('Order payment failed')
    } finally {
      saving.value = false
    }
  }

  async function addReturn(id: number, payload: {
    note?: string
    return_all?: boolean
    items?: Array<{ order_item_id: number; qty: number }>
  }): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await orderService.addReturn(id, payload)
      await fetchById(id)
    } catch {
      error.value = 'Không thể ghi nhận hoàn tiền.'
      throw new Error('Order return failed')
    } finally {
      saving.value = false
    }
  }

  return {
    loading,
    saving,
    detailLoading,
    error,
    items,
    detail,
    page,
    perPage,
    total,
    query,
    status,
    orderStatus,
    totalPages,
    fetchList,
    fetchById,
    create,
    updateStatus,
    remove,
    restore,
    addPayment,
    addReturn,
  }
})
