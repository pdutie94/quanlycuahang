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

  const applyDetailData = (data) => {
    purchase.value = data?.purchase || null;
    items.value = data?.items || [];
    payments.value = data?.payments || [];
    logs.value = data?.logs || [];
  };

  const load = async (id) => {
    const payload = await detailRequest.execute(id);
    applyDetailData(payload?.data || {});
    return payload;
  };

  const refresh = async (id) => {
    const payload = await fetchPurchaseDetail(id);
    applyDetailData(payload?.data || {});
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
    refresh,
    submitPayment,
    paymentLoading: paymentRequest.loading,
    paymentError: paymentRequest.error
  };
}