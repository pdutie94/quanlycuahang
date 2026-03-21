import api from '../lib/api'

type ApiPaginate<T> = {
  data: T[]
  meta: {
    page: number
    per_page: number
    total: number
    last_page: number
    summary?: Record<string, number>
  }
  message?: string
}

export type ReportSalesRow = {
  id: number
  order_code: string
  order_date: string
  total_amount: number
  total_cost: number
  paid_amount: number
  status: string
  order_status: string
  customer_name: string | null
  customer_phone: string | null
}

export type ReportDebtRow = {
  id: number
  name: string
  phone: string | null
  address: string | null
  total_amount: number
  paid_amount: number
  debt_amount: number
}

export type ReportMissingCostRow = {
  id: number
  order_id: number
  order_code: string
  order_date: string
  product_name: string
  unit_name: string
  qty: number
  price_cost: number
  current_unit_price_cost: number
}

export type ReportInventoryRow = {
  id: number
  name: string
  code: string
  min_stock_qty: number | null
  base_unit_name: string
  qty_base: number
  is_low_stock: number
}

export type ReportInventoryAdjustRow = {
  id: number
  product_id: number
  product_name: string
  action: string
  detail: string
  created_at: string
}

export const reportService = {
  async getSales(params: { page: number; per_page: number; start_date?: string; end_date?: string }): Promise<ApiPaginate<ReportSalesRow>> {
    const response = await api.get<ApiPaginate<ReportSalesRow>>('/reports/sales', { params })
    return response.data
  },

  async getCustomerDebt(params: { page: number; per_page: number; q?: string; show_all?: boolean }): Promise<ApiPaginate<ReportDebtRow>> {
    const response = await api.get<ApiPaginate<ReportDebtRow>>('/reports/customer-debt', { params })
    return response.data
  },

  async getSupplierDebt(params: { page: number; per_page: number; q?: string; show_all?: boolean }): Promise<ApiPaginate<ReportDebtRow>> {
    const response = await api.get<ApiPaginate<ReportDebtRow>>('/reports/supplier-debt', { params })
    return response.data
  },

  async getMissingCost(params: { page: number; per_page: number }): Promise<ApiPaginate<ReportMissingCostRow>> {
    const response = await api.get<ApiPaginate<ReportMissingCostRow>>('/reports/missing-cost', { params })
    return response.data
  },

  async getInventory(params: { page: number; per_page: number; q?: string }): Promise<ApiPaginate<ReportInventoryRow>> {
    const response = await api.get<ApiPaginate<ReportInventoryRow>>('/reports/inventory', { params })
    return response.data
  },

  async getInventoryAdjust(params: { page: number; per_page: number }): Promise<ApiPaginate<ReportInventoryAdjustRow>> {
    const response = await api.get<ApiPaginate<ReportInventoryAdjustRow>>('/reports/inventory-adjust', { params })
    return response.data
  },
}
