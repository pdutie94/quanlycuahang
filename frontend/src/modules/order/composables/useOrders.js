import { ref } from 'vue';
import { fetchOrders } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useOrders() {
  const items = ref([]);
  const meta = ref({ page: 1, total_pages: 1, total_count: 0, per_page: 20 });
  const filters = ref({
    q: '',
    status: '',
    order_status: '',
    from_date: '',
    to_date: ''
  });

  const { loading, error, execute } = useFetch(fetchOrders);

  const load = async (params = {}) => {
    const payload = await execute(params);
    items.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1, total_count: 0, per_page: 20 };
    filters.value = payload?.data?.filters || {
      q: '',
      status: '',
      order_status: '',
      from_date: '',
      to_date: ''
    };
    return payload;
  };

  return {
    items,
    meta,
    filters,
    loading,
    error,
    load
  };
}