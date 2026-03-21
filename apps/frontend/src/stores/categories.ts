import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryService, type Category } from '../services/categoryService'

export const useCategoriesStore = defineStore('categories', () => {
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const items = ref<Category[]>([])

  async function fetchList(): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      items.value = await categoryService.getList()
    } catch {
      error.value = 'Không tải được danh mục.'
    } finally {
      loading.value = false
    }
  }

  async function create(name: string): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await categoryService.create(name)
      await fetchList()
    } catch {
      error.value = 'Không thể tạo danh mục.'
      throw new Error('Create category failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, name: string): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await categoryService.update(id, name)
      await fetchList()
    } catch {
      error.value = 'Không thể cập nhật danh mục.'
      throw new Error('Update category failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    error.value = ''
    try {
      await categoryService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
    } catch {
      error.value = 'Không thể xóa danh mục.'
      throw new Error('Delete category failed')
    }
  }

  return {
    loading,
    saving,
    error,
    items,
    fetchList,
    create,
    update,
    remove,
  }
})
