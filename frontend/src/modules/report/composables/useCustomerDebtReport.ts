import { ref } from 'vue';
import { fetchCustomerDebtReport } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { DebtReportItem, DebtReportSummary } from '../types';

export function useCustomerDebtReport() {
  const items = ref<DebtReportItem[]>([]);
  const summary = ref<DebtReportSummary>({ total_amount: 0, paid_amount: 0, debt_amount: 0 });
  const meta = ref({ page: 1, total_pages: 1, total_count: 0, per_page: 30 });
  const filters = ref({ start_date: '', end_date: '', q: '', show_all: '0' });

  const request = useFetch(fetchCustomerDebtReport);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
    items.value = payload?.data?.items || [];
    summary.value = payload?.data?.summary || { total_amount: 0, paid_amount: 0, debt_amount: 0 };
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1, total_count: 0, per_page: 30 };
    filters.value = payload?.data?.filters || { start_date: '', end_date: '', q: '', show_all: '0' };
    return payload;
  };

  return {
    items,
    summary,
    meta,
    filters,
    loading: request.loading,
    error: request.error,
    load
  };
}
