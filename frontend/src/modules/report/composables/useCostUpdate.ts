import { ref } from 'vue';
import { fetchCostUpdateList, submitCostUpdate } from '../services/cost-update.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { CostUpdateItem } from '../types';

export function useCostUpdate() {
  const items = ref<CostUpdateItem[]>([]);
  const listRequest = useFetch(fetchCostUpdateList);
  const updateRequest = useFetch(submitCostUpdate);

  const applyListData = (data: any) => {
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

  const update = async (payload: { product_id: number | string; price_cost: number | string }) => updateRequest.execute(payload);

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
