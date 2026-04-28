import { ref } from 'vue';
import { deletePurchase, fetchPurchaseDetail, recordPurchasePayment, resetPurchasePayment } from '../services/purchase.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function usePurchaseDetail() {
  const purchase = ref<Record<string, any> | null>(null);
  const items = ref<Record<string, any>[]>([]);
  const manualItems = ref<Record<string, any>[]>([]);
  const payments = ref<Record<string, any>[]>([]);
  const logs = ref<Record<string, any>[]>([]);

  const detailRequest = useFetch(fetchPurchaseDetail);
  const paymentRequest = useFetch(recordPurchasePayment);
  const resetRequest = useFetch(resetPurchasePayment);
  const deleteRequest = useFetch(deletePurchase);

  const applyDetailData = (data: Record<string, any>) => {
    purchase.value = data?.purchase || null;
    items.value = data?.items || [];
    manualItems.value = data?.manual_items || [];
    payments.value = data?.payments || [];
    logs.value = data?.logs || [];
  };

  const load = async (id: number | string) => {
    const payload = await detailRequest.execute(id);
    applyDetailData(payload?.data || {});
    return payload;
  };

  const refresh = async (id: number | string) => {
    const payload = await fetchPurchaseDetail(id);
    applyDetailData(payload?.data || {});
    return payload;
  };

  const submitPayment = async (id: number | string, payload: Record<string, any>) => paymentRequest.execute(id, payload);
  const resetPayment = async (id: number | string) => resetRequest.execute(id);
  const remove = async (id: number | string) => deleteRequest.execute(id);

  return {
    purchase, items, manualItems, payments, logs,
    loading: detailRequest.loading, error: detailRequest.error,
    load, refresh, submitPayment, resetPayment, remove,
    paymentLoading: paymentRequest.loading, paymentError: paymentRequest.error,
    resetLoading: resetRequest.loading, resetError: resetRequest.error,
    deleteLoading: deleteRequest.loading, deleteError: deleteRequest.error
  };
}
