import { ref } from 'vue';
import { fetchReportOverview } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useReportOverview() {
  const overview = ref({
    orders_today: {},
    orders_month: {},
    purchases_month: {},
    customer_debt: 0,
    supplier_debt: 0,
    delta: {},
    updated_at_text: '',
    recent_orders: [],
    low_stock_items: []
  });

  const request = useFetch(fetchReportOverview);

  const load = async () => {
    const payload = await request.execute();
    overview.value = payload?.data || overview.value;
    return payload;
  };

  return {
    overview,
    loading: request.loading,
    error: request.error,
    load
  };
}
