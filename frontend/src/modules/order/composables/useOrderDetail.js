import { ref } from 'vue';
import { deleteOrder, fetchOrderDetail, recordOrderPayment, resetOrderPayment } from '../services/order.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useOrderDetail() {
  const order = ref(null);
  const items = ref([]);
  const manualItems = ref([]);
  const payments = ref([]);
  const logs = ref([]);

  const { loading, error, execute } = useFetch(fetchOrderDetail);
  const paymentRequest = useFetch(recordOrderPayment);
  const resetRequest = useFetch(resetOrderPayment);
  const deleteRequest = useFetch(deleteOrder);

  const load = async (id) => {
    const payload = await execute(id);
    order.value = payload?.data?.order || null;
    items.value = payload?.data?.items || [];
    manualItems.value = payload?.data?.manual_items || [];
    payments.value = payload?.data?.payments || [];
    logs.value = payload?.data?.logs || [];
    return payload;
  };

  const submitPayment = async (id, payload) => paymentRequest.execute(id, payload);

  const resetPayment = async (id) => resetRequest.execute(id);

  const remove = async (id) => deleteRequest.execute(id);

  return {
    order,
    items,
    manualItems,
    payments,
    logs,
    loading,
    error,
    load,
    submitPayment,
    paymentLoading: paymentRequest.loading,
    paymentError: paymentRequest.error,
    resetPayment,
    resetLoading: resetRequest.loading,
    resetError: resetRequest.error,
    remove,
    deleteLoading: deleteRequest.loading,
    deleteError: deleteRequest.error
  };
}