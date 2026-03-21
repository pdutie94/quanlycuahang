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

export type Purchase = {
  id: number
  purchase_code: string
  supplier_id: number
  supplier_name: string
  supplier_phone: string | null
  purchase_date: string
  total_amount: number
  paid_amount: number
  status: 'paid' | 'debt'
  note: string | null
}

export type PurchaseItem = {
  id: number
  product_id: number
  product_unit_id: number
  product_name: string
  unit_name: string
  qty: number
  qty_base: number
  price_cost: number
  amount: number
}

export type PurchaseDetail = {
  purchase: Purchase & {
    supplier_address?: string | null
  }
  items: PurchaseItem[]
  payments: Array<{
    id: number
    amount: number
    note: string | null
    paid_at: string
  }>
}

export type PurchaseCreateData = {
  suppliers: Array<{
    id: number
    name: string
    phone: string | null
    address: string | null
  }>
  product_units: Array<{
    product_unit_id: number
    product_id: number
    product_name: string
    product_code: string
    unit_id: number
    unit_name: string
    factor: number
    price_cost: number
    allow_fraction: number
    min_step: number
  }>
}

export type PurchasePayload = {
  supplier_id: number
  purchase_date?: string
  paid_amount?: number
  payment_method?: 'cash' | 'bank'
  note?: string
  items: Array<{
    product_unit_id: number
    qty: number
    price_cost: number
  }>
}

export const purchaseService = {
  async getList(params: {
    page: number
    per_page: number
    q?: string
    supplier_id?: number
  }): Promise<ApiPaginate<Purchase>> {
    const response = await api.get<ApiPaginate<Purchase>>('/purchases', { params })
    return response.data
  },

  async getById(id: number): Promise<PurchaseDetail> {
    const response = await api.get<ApiSuccess<PurchaseDetail>>(`/purchases/${id}`)
    return response.data.data
  },

  async getCreateData(): Promise<PurchaseCreateData> {
    const response = await api.get<ApiSuccess<PurchaseCreateData>>('/purchases/create-data')
    return response.data.data
  },

  async create(payload: PurchasePayload): Promise<{ id: number; purchase_code: string }> {
    const response = await api.post<ApiSuccess<{ id: number; purchase_code: string }>>('/purchases', payload)
    return response.data.data
  },

  async update(id: number, payload: PurchasePayload): Promise<void> {
    await api.put(`/purchases/${id}`, payload)
  },

  async addPayment(id: number, payload: { amount: number; payment_method?: 'cash' | 'bank'; note?: string }): Promise<void> {
    await api.post(`/purchases/${id}/payment`, payload)
  },
}
