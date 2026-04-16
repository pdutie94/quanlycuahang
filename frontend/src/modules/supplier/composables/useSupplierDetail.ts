import { ref } from 'vue';
import { deleteSupplier, fetchSupplierDetail } from '../services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useSupplierDetail() {
  const supplier = ref<Record<string, any> | null>(null);
  const purchases = ref<Record<string, any>[]>([]);
  const paymentHistory = ref<Record<string, any>[]>([]);
  const totalDebt = ref(0);

  const request = useFetch(fetchSupplierDetail);
  const deleteRequest = useFetch(deleteSupplier);

  const load = async (id: number | string) => {
    const payload = await request.execute(id);
    supplier.value = payload?.data?.supplier || null;
    purchases.value = payload?.data?.purchases || [];
    paymentHistory.value = payload?.data?.payment_history || [];
    totalDebt.value = payload?.data?.total_debt || 0;
    return payload;
  };

  const remove = async (id: number | string) => deleteRequest.execute(id);

  return {
    supplier, purchases, totalDebt, paymentHistory, loading: request.loading, error: request.error,
    load, remove,
    deleteLoading: deleteRequest.loading, deleteError: deleteRequest.error
  };
}
