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

export type Order = {
  id: number
  order_code: string
  customer_id: number | null
  customer_name: string | null
  customer_phone: string | null
  order_date: string
  total_amount: number
  paid_amount: number
  total_cost: number
  status: 'paid' | 'debt'
  order_status: 'pending' | 'completed' | 'cancelled'
  note: string | null
  items_count: number
}

export type OrderItem = {
  id: number
  product_id: number
  product_unit_id: number
  product_name: string
  unit_name: string
  qty: number
  price_sell: number
  amount: number
}

export type OrderDetail = {
  order: Order & {
    customer_address?: string | null
    discount_type?: 'none' | 'fixed' | 'percent'
    discount_value?: number
    discount_amount?: number
    surcharge_amount?: number
  }
  summary: {
    total: number
    paid: number
    debt: number
    cost: number
    profit: number
  }
  items: OrderItem[]
  manual_items: Array<{
    id: number
    item_name: string
    unit_name: string | null
    qty: number
    price_sell: number
    amount_sell: number
  }>
  payments: Array<{
    id: number
    amount: number
    note: string | null
    paid_at: string
  }>
}

export type PosUnit = {
  id: number
  unit_id: number
  unit_name: string
  factor: number
  price_sell: number
  price_cost: number
  allow_fraction: number
  min_step: number
}

export type PosProduct = {
  id: number
  name: string
  code: string
  units: PosUnit[]
}

export type PosCustomer = {
  id: number
  name: string
  phone: string | null
  address: string | null
}

export type CreateOrderPayload = {
  order_date: string
  customer_id?: number
  customer_name?: string
  customer_phone?: string
  customer_address?: string
  note?: string
  payment_status: 'pay' | 'debt'
  payment_method?: 'cash' | 'bank'
  payment_amount?: number
  discount_type?: 'none' | 'fixed' | 'percent'
  discount_value?: number
  surcharge_amount?: number
  items: Array<{
    product_unit_id: number
    qty: number
    price_sell: number
  }>
}

export const orderService = {
  async getList(params: {
    page: number
    per_page: number
    q?: string
    status?: string
    order_status?: string
  }): Promise<ApiPaginate<Order>> {
    const response = await api.get<ApiPaginate<Order>>('/orders', { params })
    return response.data
  },

  async getById(id: number): Promise<OrderDetail> {
    const response = await api.get<ApiSuccess<OrderDetail>>(`/orders/${id}`)
    return response.data.data
  },

  async create(payload: CreateOrderPayload): Promise<{ id: number; order_code: string }> {
    const response = await api.post<ApiSuccess<{ id: number; order_code: string }>>('/orders', payload)
    return response.data.data
  },

  async updateStatus(id: number, payload: { order_status: 'pending' | 'completed' | 'cancelled'; note?: string }): Promise<void> {
    await api.put(`/orders/${id}`, payload)
  },

  async remove(id: number): Promise<void> {
    await api.delete(`/orders/${id}`)
  },

  async restore(id: number): Promise<void> {
    await api.post(`/orders/${id}/restore`)
  },

  async addPayment(id: number, payload: { amount: number; note?: string; payment_method?: 'cash' | 'bank' }): Promise<void> {
    await api.post(`/orders/${id}/payment`, payload)
  },

  async addReturn(id: number, payload: {
    note?: string
    return_all?: boolean
    items?: Array<{ order_item_id: number; qty: number }>
  }): Promise<void> {
    await api.post(`/orders/${id}/return`, payload)
  },

  async getCreateData(): Promise<{ products: PosProduct[]; customers: PosCustomer[] }> {
    const response = await api.get<ApiSuccess<{ products: PosProduct[]; customers: PosCustomer[] }>>('/orders/create-data')
    return response.data.data
  },
}
