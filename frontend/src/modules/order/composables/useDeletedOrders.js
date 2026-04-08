import { ref } from 'vue';
import { fetchDeletedOrders, purgeDeletedOrders, restoreOrder } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

const defaultMeta = { page: 1, total_pages: 1, total_count: 0, per_page: 30 };
const defaultFilters = {
  q: '',
  status: '',
  order_status: '',
  from_date: '',
  to_date: ''
};

export function useDeletedOrders() {
  const items = ref([]);
  const meta = ref(defaultMeta);
  const filters = ref(defaultFilters);

  const listRequest = useFetch(fetchDeletedOrders);
  const restoreRequest = useFetch(restoreOrder);
  const purgeRequest = useFetch(purgeDeletedOrders);

  const load = async (params = {}) => {
    const payload = await listRequest.execute(params);
    items.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || defaultMeta;
    filters.value = payload?.data?.filters || defaultFilters;
    return payload;
  };

  return {
    items,
    meta,
    filters,
    load,
    loading: listRequest.loading,
    error: listRequest.error,
    restore: restoreRequest.execute,
    restoreLoading: restoreRequest.loading,
    restoreError: restoreRequest.error,
    purgeSelected: purgeRequest.execute,
    purgeLoading: purgeRequest.loading,
    purgeError: purgeRequest.error
  };
}