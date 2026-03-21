import api from '../lib/api'

type ApiSuccess<T> = {
  success: boolean
  data: T
  message: string
  error: unknown
}

export type Unit = {
  id: number
  name: string
}

export const unitService = {
  async getList(): Promise<Unit[]> {
    const response = await api.get<ApiSuccess<Unit[]>>('/units')
    return response.data.data
  },

  async create(name: string): Promise<number> {
    const response = await api.post<ApiSuccess<{ id: number }>>('/units', { name })
    return response.data.data.id
  },

  async update(id: number, name: string): Promise<void> {
    await api.put(`/units/${id}`, { name })
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/units/${id}`)
  },
}
