import { ref } from 'vue';
import { fetchCostUpdateList, submitCostUpdate } from '../services/cost-update.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCostUpdate() {
  const items = ref([]);
  const listRequest = useFetch(fetchCostUpdateList);
  const updateRequest = useFetch(submitCostUpdate);

  const applyListData = (data) => {
    items.value = data?.items || [];
  };

  const load = async () => {
    const payload = await listRequest.execute();
    applyListData(payload?.data || {});
    return payload;
  };

  const refresh = async () => {
    const payload = await fetchCostUpdateList();
    applyListData(payload?.data || {});
    return payload;
  };

  const update = async (payload) => updateRequest.execute(payload);

  return {
    items,
    loading: listRequest.loading,
    error: listRequest.error,
    load,
    refresh,
    update,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}
