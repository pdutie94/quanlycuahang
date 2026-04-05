import { ref } from 'vue';
import { fetchInventoryReport, submitInventoryAdjust } from '../services/report.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useInventoryReport() {
  const items = ref([]);

  const listRequest = useFetch(fetchInventoryReport);
  const adjustRequest = useFetch(submitInventoryAdjust);

  const applyListData = (data) => {
    items.value = data?.items || [];
  };

  const load = async () => {
    const payload = await listRequest.execute();
    applyListData(payload?.data || {});
    return payload;
  };

  const refresh = async () => {
    const payload = await fetchInventoryReport();
    applyListData(payload?.data || {});
    return payload;
  };

  const adjust = async (payload) => adjustRequest.execute(payload);

  return {
    items,
    loading: listRequest.loading,
    error: listRequest.error,
    load,
    refresh,
    adjust,
    adjustLoading: adjustRequest.loading,
    adjustError: adjustRequest.error
  };
}
