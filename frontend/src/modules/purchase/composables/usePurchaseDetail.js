import { ref } from 'vue';
import { fetchPurchaseDetail, recordPurchasePayment } from '../services/purchase.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function usePurchaseDetail() {
  const purchase = ref(null);
  const items = ref([]);
  const payments = ref([]);
  const logs = ref([]);

  const detailRequest = useFetch(fetchPurchaseDetail);
  const paymentRequest = useFetch(recordPurchasePayment);

  const load = async (id) => {
    const payload = await detailRequest.execute(id);
    purchase.value = payload?.data?.purchase || null;
    items.value = payload?.data?.items || [];
    payments.value = payload?.data?.payments || [];
    logs.value = payload?.data?.logs || [];
    return payload;
  };

  const submitPayment = async (id, payload) => paymentRequest.execute(id, payload);

  return {
    purchase,
    items,
    payments,
    logs,
    loading: detailRequest.loading,
    error: detailRequest.error,
    load,
    submitPayment,
    paymentLoading: paymentRequest.loading,
    paymentError: paymentRequest.error
  };
}