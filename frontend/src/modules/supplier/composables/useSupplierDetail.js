import { ref } from 'vue';
import { deleteSupplier, fetchSupplierDetail } from '../services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useSupplierDetail() {
  const supplier = ref(null);
  const purchases = ref([]);
  const totalDebt = ref(0);

  const request = useFetch(fetchSupplierDetail);
  const deleteRequest = useFetch(deleteSupplier);

  const load = async (id) => {
    const payload = await request.execute(id);
    supplier.value = payload?.data?.supplier || null;
    purchases.value = payload?.data?.purchases || [];
    totalDebt.value = payload?.data?.total_debt || 0;
    return payload;
  };

  const remove = async (id) => deleteRequest.execute(id);

  return {
    supplier,
    purchases,
    totalDebt,
    loading: request.loading,
    error: request.error,
    load,
    remove,
    deleteLoading: deleteRequest.loading,
    deleteError: deleteRequest.error
  };
}
