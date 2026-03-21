import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { purchaseService, type Purchase, type PurchaseCreateData, type PurchaseDetail, type PurchasePayload } from '../services/purchaseService'

export const usePurchasesStore = defineStore('purchases', () => {
  const loading = ref(false)
  const saving = ref(false)
  const detailLoading = ref(false)
  const error = ref('')

  const items = ref<Purchase[]>([])
  const detail = ref<PurchaseDetail | null>(null)
  const createData = ref<PurchaseCreateData | null>(null)

  const page = ref(1)
  const perPage = ref(20)
  const total = ref(0)
  const query = ref('')
  const supplierId = ref<number | null>(null)

  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

  async function fetchList(override?: { page?: number; q?: string; supplier_id?: number | null }): Promise<void> {
    loading.value = true
    error.value = ''

    if (override?.page !== undefined) page.value = override.page
    if (override?.q !== undefined) query.value = override.q
    if (override?.supplier_id !== undefined) supplierId.value = override.supplier_id

    try {
      const result = await purchaseService.getList({
        page: page.value,
        per_page: perPage.value,
        q: query.value || undefined,
        supplier_id: supplierId.value || undefined,
      })
      items.value = result.data
      total.value = result.meta.total
      page.value = result.meta.page
      perPage.value = result.meta.per_page
    } catch {
      error.value = 'Không tải được danh sách phiếu nhập.'
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number): Promise<void> {
    detailLoading.value = true
    error.value = ''
    try {
      detail.value = await purchaseService.getById(id)
    } catch {
      error.value = 'Không tải được chi tiết phiếu nhập.'
      throw new Error('Fetch purchase detail failed')
    } finally {
      detailLoading.value = false
    }
  }

  async function fetchCreateData(): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      createData.value = await purchaseService.getCreateData()
    } catch {
      error.value = 'Không tải được dữ liệu tạo phiếu nhập.'
      throw new Error('Fetch purchase create data failed')
    } finally {
      loading.value = false
    }
  }

  async function create(payload: PurchasePayload): Promise<{ id: number; purchase_code: string }> {
    saving.value = true
    error.value = ''
    try {
      return await purchaseService.create(payload)
    } catch {
      error.value = 'Không thể tạo phiếu nhập.'
      throw new Error('Create purchase failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: PurchasePayload): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await purchaseService.update(id, payload)
    } catch {
      error.value = 'Không thể cập nhật phiếu nhập.'
      throw new Error('Update purchase failed')
    } finally {
      saving.value = false
    }
  }

  async function addPayment(id: number, payload: { amount: number; payment_method?: 'cash' | 'bank'; note?: string }): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await purchaseService.addPayment(id, payload)
      await fetchById(id)
    } catch {
      error.value = 'Không thể ghi nhận thanh toán phiếu nhập.'
      throw new Error('Purchase payment failed')
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
    createData,
    page,
    perPage,
    total,
    query,
    supplierId,
    totalPages,
    fetchList,
    fetchById,
    fetchCreateData,
    create,
    update,
    addPayment,
  }
})
