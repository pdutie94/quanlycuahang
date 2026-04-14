import { ref } from 'vue';
import { fetchSalesReport } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { Order } from '../../order/types';
import type { SalesReportSummary, SalesReportItem } from '../types';

export function useSalesReport() {
  const rows = ref<Order[]>([]);
  const summary = ref<SalesReportSummary>({ order_count: 0, total_amount: 0, total_cost: 0, profit: 0, paid_amount: 0, debt_amount: 0 });
  const meta = ref({ page: 1, total_pages: 1 });
  const filters = ref({ start_date: '', end_date: '', range_mode: 'day' });
  const dailyStats = ref<SalesReportItem[]>([]);

  const request = useFetch(fetchSalesReport);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
    rows.value = payload?.data?.rows || [];
    summary.value = payload?.data?.summary || { order_count: 0, total_amount: 0, total_cost: 0, profit: 0, paid_amount: 0, debt_amount: 0 };
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1 };
    filters.value = payload?.data?.filters || { start_date: '', end_date: '', range_mode: 'day' };
    dailyStats.value = payload?.data?.daily_stats || [];
    return payload;
  };

  return {
    rows,
    summary,
    meta,
    filters,
    dailyStats,
    loading: request.loading,
    error: request.error,
    load
  };
}
