import { ref } from 'vue';
import { fetchOrderReturnInfo, submitOrderReturn } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useOrderReturn() {
  const order = ref<Record<string, any> | null>(null);
  const items = ref<Record<string, any>[]>([]);

  const infoRequest = useFetch(fetchOrderReturnInfo);
  const submitRequest = useFetch(submitOrderReturn);

  const load = async (id: number | string) => {
    const payload = await infoRequest.execute(id);
    order.value = payload?.data?.order || null;
    items.value = payload?.data?.items || [];
    return payload;
  };

  const submit = async (id: number | string, payload: Record<string, any>) => submitRequest.execute(id, payload);

  return {
    order, items,
    loading: infoRequest.loading, error: infoRequest.error,
    load, submit,
    submitting: submitRequest.loading, submitError: submitRequest.error
  };
}
