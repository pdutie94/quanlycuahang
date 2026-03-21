import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { supplierService, type Supplier, type SupplierDetail, type SupplierPayload } from '../services/supplierService'

export const useSuppliersStore = defineStore('suppliers', () => {
  const loading = ref(false)
  const saving = ref(false)
  const detailLoading = ref(false)
  const error = ref('')
  const items = ref<Supplier[]>([])
  const detail = ref<SupplierDetail | null>(null)
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
      const result = await supplierService.getList({
        page: page.value,
        per_page: perPage.value,
        search: query.value || undefined,
      })
      items.value = result.data
      total.value = result.meta.total
      page.value = result.meta.page
      perPage.value = result.meta.per_page
    } catch {
      error.value = 'Không tải được danh sách nhà cung cấp.'
    } finally {
      loading.value = false
    }
  }

  async function fetchById(id: number): Promise<void> {
    detailLoading.value = true
    error.value = ''
    try {
      detail.value = await supplierService.getById(id)
    } catch {
      error.value = 'Không tải được chi tiết nhà cung cấp.'
      throw new Error('Fetch supplier detail failed')
    } finally {
      detailLoading.value = false
    }
  }

  async function create(payload: SupplierPayload): Promise<number> {
    saving.value = true
    error.value = ''
    try {
      return await supplierService.create(payload)
    } catch {
      error.value = 'Không thể tạo nhà cung cấp.'
      throw new Error('Create supplier failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: SupplierPayload): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await supplierService.update(id, payload)
    } catch {
      error.value = 'Không thể cập nhật nhà cung cấp.'
      throw new Error('Update supplier failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    error.value = ''
    try {
      await supplierService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
      total.value = Math.max(0, total.value - 1)
    } catch {
      error.value = 'Không thể xóa nhà cung cấp.'
      throw new Error('Delete supplier failed')
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
    totalPages,
    fetchList,
    fetchById,
    create,
    update,
    remove,
  }
})
