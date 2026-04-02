import { ref } from 'vue';
import { fetchMissingCostReport, submitMissingCostUpdate } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useMissingCostReport() {
  const items = ref([]);
  const summary = ref({ item_count: 0, order_count: 0, total_delta_cost: 0 });
  const filters = ref({ q: '', start_date: '', end_date: '' });

  const listRequest = useFetch(fetchMissingCostReport);
  const updateRequest = useFetch(submitMissingCostUpdate);

  const load = async (params = {}) => {
    const payload = await listRequest.execute(params);
    items.value = payload?.data?.items || [];
    summary.value = payload?.data?.summary || { item_count: 0, order_count: 0, total_delta_cost: 0 };
    filters.value = payload?.data?.filters || { q: '', start_date: '', end_date: '' };
    return payload;
  };

  const submit = async (payload) => updateRequest.execute(payload);

  return {
    items,
    summary,
    filters,
    loading: listRequest.loading,
    error: listRequest.error,
    load,
    submit,
    submitLoading: updateRequest.loading,
    submitError: updateRequest.error
  };
}
