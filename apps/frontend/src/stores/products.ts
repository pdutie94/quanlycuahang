import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { productService, type Product, type ProductPayload } from '../services/productService'

export const useProductsStore = defineStore('products', () => {
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const items = ref<Product[]>([])
  const page = ref(1)
  const perPage = ref(20)
  const total = ref(0)
  const query = ref('')

  const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)))

  async function fetchList(override?: { page?: number; q?: string }): Promise<void> {
    loading.value = true
    error.value = ''

    if (override?.page !== undefined) page.value = override.page
    if (override?.q !== undefined) query.value = override.q

    try {
      const result = await productService.getList({
        page: page.value,
        per_page: perPage.value,
        q: query.value || undefined,
      })
      items.value = result.data
      total.value = result.meta.total
      page.value = result.meta.page
      perPage.value = result.meta.per_page
    } catch {
      error.value = 'Không tải được danh sách sản phẩm.'
    } finally {
      loading.value = false
    }
  }

  async function create(payload: ProductPayload): Promise<number> {
    saving.value = true
    error.value = ''
    try {
      return await productService.create(payload)
    } catch {
      error.value = 'Không thể tạo sản phẩm.'
      throw new Error('Create product failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, payload: ProductPayload): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await productService.update(id, payload)
    } catch {
      error.value = 'Không thể cập nhật sản phẩm.'
      throw new Error('Update product failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    error.value = ''
    try {
      await productService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
      total.value = Math.max(0, total.value - 1)
    } catch {
      error.value = 'Không thể xóa sản phẩm.'
      throw new Error('Delete product failed')
    }
  }

  return {
    loading,
    saving,
    error,
    items,
    page,
    perPage,
    total,
    query,
    totalPages,
    fetchList,
    create,
    update,
    remove,
  }
})
