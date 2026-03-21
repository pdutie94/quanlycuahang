import api from '../lib/api'

type ApiSuccess<T> = {
  success: boolean
  data: T
  message: string
  error: unknown
}

export type DashboardMetrics = {
  orders_today: {
    total_amount: number
    total_cost: number
    profit: number
    paid_amount: number
    debt_amount: number
  }
  orders_month: {
    total_amount: number
    total_cost: number
    profit: number
    paid_amount: number
    debt_amount: number
  }
  purchases_month: {
    total_amount: number
    paid_amount: number
    debt_amount: number
  }
  customer_debt: number
  supplier_debt: number
  recent_orders: Array<{
    id: number
    order_date: string
    total_amount: number
    paid_amount: number
    order_status: string | null
    customer_name: string | null
    items_count: number
  }>
}

export const dashboardService = {
  async getMetrics(): Promise<DashboardMetrics> {
    const response = await api.get<ApiSuccess<DashboardMetrics>>('/dashboard/metrics')
    return response.data.data
  },
}
