import { ref } from 'vue';
import { fetchSupplierDebtReport } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { DebtReportItem, DebtReportSummary } from '../types';

export function useSupplierDebtReport() {
  const rows = ref<DebtReportItem[]>([]);
  const summary = ref<DebtReportSummary>({ total_amount: 0, paid_amount: 0, debt_amount: 0 });
  const filters = ref({ start_date: '', end_date: '', q: '', show_all: '0' });
  const meta = ref({ page: 1, total_pages: 1, total_count: 0, per_page: 30 });

  const request = useFetch(fetchSupplierDebtReport);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
    rows.value = payload?.data?.items || [];
    summary.value = payload?.data?.summary || { total_amount: 0, paid_amount: 0, debt_amount: 0 };
    filters.value = payload?.data?.filters || { start_date: '', end_date: '', q: '', show_all: '0' };
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1, total_count: 0, per_page: 30 };
    return payload;
  };

  return {
    rows,
    summary,
    filters,
    meta,
    loading: request.loading,
    error: request.error,
    load
  };
}
