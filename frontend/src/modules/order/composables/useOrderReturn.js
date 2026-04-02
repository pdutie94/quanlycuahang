import { ref } from 'vue';
import { fetchOrderReturnInfo, submitOrderReturn } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useOrderReturn() {
  const order = ref(null);
  const items = ref([]);

  const infoRequest = useFetch(fetchOrderReturnInfo);
  const submitRequest = useFetch(submitOrderReturn);

  const load = async (id) => {
    const payload = await infoRequest.execute(id);
    order.value = payload?.data?.order || null;
    items.value = payload?.data?.items || [];
    return payload;
  };

  const submit = async (id, payload) => submitRequest.execute(id, payload);

  return {
    order,
    items,
    loading: infoRequest.loading,
    error: infoRequest.error,
    load,
    submit,
    submitting: submitRequest.loading,
    submitError: submitRequest.error
  };
}
