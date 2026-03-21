import { defineStore } from 'pinia'
import { ref } from 'vue'
import { unitService, type Unit } from '../services/unitService'

export const useUnitsStore = defineStore('units', () => {
  const loading = ref(false)
  const saving = ref(false)
  const error = ref('')
  const items = ref<Unit[]>([])

  async function fetchList(): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      items.value = await unitService.getList()
    } catch {
      error.value = 'Không tải được đơn vị.'
    } finally {
      loading.value = false
    }
  }

  async function create(name: string): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await unitService.create(name)
      await fetchList()
    } catch {
      error.value = 'Không thể tạo đơn vị.'
      throw new Error('Create unit failed')
    } finally {
      saving.value = false
    }
  }

  async function update(id: number, name: string): Promise<void> {
    saving.value = true
    error.value = ''
    try {
      await unitService.update(id, name)
      await fetchList()
    } catch {
      error.value = 'Không thể cập nhật đơn vị.'
      throw new Error('Update unit failed')
    } finally {
      saving.value = false
    }
  }

  async function remove(id: number): Promise<void> {
    error.value = ''
    try {
      await unitService.remove(id)
      items.value = items.value.filter((item) => item.id !== id)
    } catch {
      error.value = 'Không thể xóa đơn vị.'
      throw new Error('Delete unit failed')
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
