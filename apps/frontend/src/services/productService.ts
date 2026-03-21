import api from '../lib/api'

type ApiSuccess<T> = {
  success: boolean
  data: T
  message: string
  error: unknown
}

type ApiPaginate<T> = {
  data: T[]
  meta: {
    page: number
    per_page: number
    total: number
  }
  message?: string
}

export type Product = {
  id: number
  name: string
  code: string
  category_id: number | null
  category_name: string | null
  base_unit_id: number
  base_unit_name: string
  min_stock_qty: number | null
  created_at: string
  updated_at: string | null
  inventory_qty_base: number
}

export type ProductPayload = {
  name: string
  code?: string
  category_id?: number | null
  base_unit_id: number
  min_stock_qty?: number | null
}

export const productService = {
  async getList(params: { page: number; per_page: number; q?: string }): Promise<ApiPaginate<Product>> {
    const response = await api.get<ApiPaginate<Product>>('/products', { params })
    return response.data
  },

  async getById(id: number): Promise<Product> {
    const response = await api.get<ApiSuccess<Product>>(`/products/${id}`)
    return response.data.data
  },

  async create(payload: ProductPayload): Promise<number> {
    const response = await api.post<ApiSuccess<{ id: number }>>('/products', payload)
    return response.data.data.id
  },

  async update(id: number, payload: ProductPayload): Promise<void> {
    await api.put(`/products/${id}`, payload)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/products/${id}`)
  },
}
