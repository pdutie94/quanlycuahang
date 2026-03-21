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
    last_page: number
  }
  message?: string
}

export type Supplier = {
  id: number
  name: string
  phone: string | null
  email: string | null
  address: string | null
  created_at: string
  total_purchases?: number
  total_debt?: number
}

export type SupplierDetail = {
  supplier: Supplier
  summary: {
    total_purchases: number
    total_debt: number
  }
  latest_purchases: Array<{
    id: number
    reference_code: string
    total_amount: number
    paid_amount: number
    debt_amount: number
    purchase_date: string
  }>
}

export type SupplierPayload = {
  name: string
  phone?: string
  email?: string
  address?: string
}

export const supplierService = {
  async getList(params: { page: number; per_page: number; search?: string }): Promise<ApiPaginate<Supplier>> {
    const response = await api.get<ApiPaginate<Supplier>>('/suppliers', { params })
    return response.data
  },

  async getById(id: number): Promise<SupplierDetail> {
    const response = await api.get<ApiSuccess<SupplierDetail>>(`/suppliers/${id}`)
    return response.data.data
  },

  async create(payload: SupplierPayload): Promise<number> {
    const response = await api.post<ApiSuccess<{ id: number }>>('/suppliers', payload)
    return response.data.data.id
  },

  async update(id: number, payload: SupplierPayload): Promise<void> {
    await api.put(`/suppliers/${id}`, payload)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/suppliers/${id}`)
  },
}
