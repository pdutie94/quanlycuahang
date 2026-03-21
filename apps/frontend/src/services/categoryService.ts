import api from '../lib/api'

type ApiSuccess<T> = {
  success: boolean
  data: T
  message: string
  error: unknown
}

export type Category = {
  id: number
  name: string
  created_at: string | null
}

export const categoryService = {
  async getList(): Promise<Category[]> {
    const response = await api.get<ApiSuccess<Category[]>>('/categories')
    return response.data.data
  },

  async create(name: string): Promise<number> {
    const response = await api.post<ApiSuccess<{ id: number }>>('/categories', { name })
    return response.data.data.id
  },

  async update(id: number, name: string): Promise<void> {
    await api.put(`/categories/${id}`, { name })
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/categories/${id}`)
  },
}
