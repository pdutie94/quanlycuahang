import { ref } from 'vue';
import { fetchReportOverview } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { ReportOverview } from '../types';

export function useReportOverview() {
  const overview = ref<ReportOverview>({
    orders_today: { total_amount: 0, order_count: 0 },
    orders_month: { total_amount: 0, order_count: 0 },
    purchases_month: { total_amount: 0, order_count: 0 },
    customer_debt: 0,
    supplier_debt: 0,
    delta: {
      orders_today_total: { amount: 0, percent: 0 },
      orders_month_total: { amount: 0, percent: 0 },
      orders_today_profit: { amount: 0, percent: 0 },
      orders_month_profit: { amount: 0, percent: 0 },
      purchases_month_total: { amount: 0, percent: 0 },
      customer_debt: { amount: 0, percent: 0 },
      supplier_debt: { amount: 0, percent: 0 }
    },
    updated_at_text: '',
    recent_orders: [],
    low_stock_items: []
  });

  const request = useFetch(fetchReportOverview);

  const load = async () => {
    const payload = await request.execute();
    if (payload?.data) {
      overview.value = payload.data;
    }
    return payload;
  };

  return {
    overview,
    loading: request.loading,
    error: request.error,
    load
  };
}
