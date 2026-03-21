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

export type Customer = {
  id: number
  name: string
  phone: string | null
  email: string | null
  address: string | null
  created_at: string
  total_spent?: number
  total_debt?: number
}

export type CustomerDetail = {
  customer: Customer
  summary: {
    total_spent: number
    total_debt: number
  }
  latest_orders: Array<{
    id: number
    order_code: string
    final_amount: number
    debt_amount: number
    order_date: string
    order_status: string
  }>
  recent_payments: Array<{
    id: number
    amount: number
    payment_method: string
    notes: string | null
    payment_date: string
  }>
}

export type CustomerPayload = {
  name: string
  phone?: string
  email?: string
  address?: string
}

export const customerService = {
  async getList(params: { page: number; per_page: number; search?: string }): Promise<ApiPaginate<Customer>> {
    const response = await api.get<ApiPaginate<Customer>>('/customers', { params })
    return response.data
  },

  async getById(id: number): Promise<CustomerDetail> {
    const response = await api.get<ApiSuccess<CustomerDetail>>(`/customers/${id}`)
    return response.data.data
  },

  async create(payload: CustomerPayload): Promise<number> {
    const response = await api.post<ApiSuccess<{ id: number }>>('/customers', payload)
    return response.data.data.id
  },

  async update(id: number, payload: CustomerPayload): Promise<void> {
    await api.put(`/customers/${id}`, payload)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/customers/${id}`)
  },

  async payDebt(id: number, payload: { amount: number; payment_method?: string; notes?: string }): Promise<void> {
    await api.post(`/customers/${id}/payment`, payload)
  },
}
