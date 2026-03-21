import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardService, type DashboardMetrics } from '../services/dashboardService'

const emptyMetrics: DashboardMetrics = {
  orders_today: { total_amount: 0, total_cost: 0, profit: 0, paid_amount: 0, debt_amount: 0 },
  orders_month: { total_amount: 0, total_cost: 0, profit: 0, paid_amount: 0, debt_amount: 0 },
  purchases_month: { total_amount: 0, paid_amount: 0, debt_amount: 0 },
  customer_debt: 0,
  supplier_debt: 0,
  recent_orders: [],
}

export const useDashboardStore = defineStore('dashboard', () => {
  const loading = ref(false)
  const error = ref('')
  const metrics = ref<DashboardMetrics>({ ...emptyMetrics })

  async function fetchMetrics(): Promise<void> {
    loading.value = true
    error.value = ''
    try {
      metrics.value = await dashboardService.getMetrics()
    } catch {
      error.value = 'Không tải được số liệu dashboard. Vui lòng thử lại.'
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    error,
    metrics,
    fetchMetrics,
  }
})
