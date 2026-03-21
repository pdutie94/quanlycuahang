import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { customerService, type Customer, type CustomerDetail, type CustomerPayload } from '../services/customerService'

export const useCustomersStore = defineStore('customers', () => {
  const loading = ref(false)
  const saving = ref(false)
  const detailLoading = ref(false)
  const paymentSaving = ref(false)
  const error = ref('')
  const items = ref<Customer[]>([])
  const detail = ref<CustomerDetail | null>(null)
  const page = ref(1)
  const perPage = ref(10)
  const total = ref(0)
  const query = ref('')

  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

  async function fetchList(override?: { page?: number; search?: string }): Promise<void> {
    loading.value = true
    error.value = ''

    if (override?.page !== undefined) page.value = override.page
    if (override?.search !== undefined) query.value = override.search

    try {
      const result = await customerService.getList({
        page: page.value,
        per_page: perPage.value,
        search: query.value || undefined,
      })
      items.value = result.data
      total.value = result.meta.total
      page.value = result.meta.page
      perPage.value = result.meta.per_page
    } catch {
      error.value = 'Không tải được danh sách khách hàng.'
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number): Promise<void> {
    detailLoading.value = true
    error.value = ''
    try {
      detail.value = await customerService.getById(id)
    } catch {
      error.value = 'Không tải được chi tiết khách hàng.'
      throw new Error('Fetch customer detail failed')
    } finally {
      detailLoading.value = false
    }
  }

  async function create(payload: CustomerPayload): Promise<number> {
    saving.value = true
    error.value = ''
    try {
      return await customerService.create(payload)
    } catch {
      error.value = 'Không thể tạo khách hàng.'
      throw new Error('Create customer failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: CustomerPayload): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await customerService.update(id, payload)
    } catch {
      error.value = 'Không thể cập nhật khách hàng.'
      throw new Error('Update customer failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    error.value = ''
    try {
      await customerService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
      total.value = Math.max(0, total.value - 1)
    } catch {
      error.value = 'Không thể xóa khách hàng.'
      throw new Error('Delete customer failed')
    }
  }

  async function payDebt(id: number, payload: { amount: number; payment_method?: string; notes?: string }): Promise<void> {
    paymentSaving.value = true
    error.value = ''
    try {
      await customerService.payDebt(id, payload)
      await fetchById(id)
    } catch {
      error.value = 'Không thể ghi nhận thanh toán công nợ.'
      throw new Error('Pay debt failed')
    } finally {
      paymentSaving.value = false
    }
  }

  return {
    loading,
    saving,
    detailLoading,
    paymentSaving,
    error,
    items,
    detail,
    page,
    perPage,
    total,
    query,
    totalPages,
    fetchList,
    fetchById,
    create,
    update,
    remove,
    payDebt,
  }
})
