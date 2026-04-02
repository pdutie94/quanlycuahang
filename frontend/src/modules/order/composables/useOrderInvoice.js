import { ref } from 'vue';
import { fetchOrderInvoice } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useOrderInvoice() {
  const order = ref(null);
  const items = ref([]);

  const request = useFetch(fetchOrderInvoice);

  const load = async (id) => {
    const payload = await request.execute(id);
    order.value = payload?.data?.order || null;
    items.value = payload?.data?.items || [];
    return payload;
  };

  return {
    order,
    items,
    loading: request.loading,
    error: request.error,
    load
  };
}
