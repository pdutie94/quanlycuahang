import { ref } from 'vue';
import { fetchCustomerDebtReport } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { DebtReportItem, DebtReportSummary } from '../types';

export function useCustomerDebtReport() {
  const rows = ref<DebtReportItem[]>([]);
  const summary = ref<DebtReportSummary>({ total_amount: 0, paid_amount: 0, debt_amount: 0 });
  const filters = ref({ start_date: '', end_date: '', q: '', show_all: '0' });

  const request = useFetch(fetchCustomerDebtReport);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
    rows.value = payload?.data?.rows || [];
    summary.value = payload?.data?.summary || { total_amount: 0, paid_amount: 0, debt_amount: 0 };
    filters.value = payload?.data?.filters || { start_date: '', end_date: '', q: '', show_all: '0' };
    return payload;
  };

  return {
    rows,
    summary,
    filters,
    loading: request.loading,
    error: request.error,
    load
  };
}
