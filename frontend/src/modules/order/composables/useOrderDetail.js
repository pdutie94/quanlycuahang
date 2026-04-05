import { ref } from 'vue';
import { deleteOrder, fetchOrderDetail, recordOrderPayment, resetOrderPayment, updateOrderStatus } from '../services/order.api';
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
  const statusRequest = useFetch(updateOrderStatus);

  const applyDetailData = (data) => {
    order.value = data?.order || null;
    items.value = data?.items || [];
    manualItems.value = data?.manual_items || [];
    payments.value = data?.payments || [];
    logs.value = data?.logs || [];
  };

  const load = async (id) => {
    const payload = await execute(id);
    applyDetailData(payload?.data || {});
    return payload;
  };

  const refresh = async (id) => {
    const payload = await fetchOrderDetail(id);
    applyDetailData(payload?.data || {});
    return payload;
  };

  const submitPayment = async (id, payload) => paymentRequest.execute(id, payload);

  const submitStatus = async (id, payload) => statusRequest.execute(id, payload);

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
    refresh,
    submitStatus,
    submitPayment,
    statusLoading: statusRequest.loading,
    statusError: statusRequest.error,
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