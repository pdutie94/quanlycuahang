import { ref } from 'vue';
import { deleteCustomer, fetchCustomerDetail } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomerDetail() {
  const customer = ref(null);
  const orders = ref([]);
  const summary = ref({ total_amount: 0, total_paid: 0, total_debt: 0 });

  const { loading, error, execute } = useFetch(fetchCustomerDetail);
  const deleteRequest = useFetch(deleteCustomer);

  const load = async (id) => {
    const payload = await execute(id);
    customer.value = payload?.data?.customer || null;
    orders.value = payload?.data?.orders || [];
    summary.value = payload?.data?.summary || { total_amount: 0, total_paid: 0, total_debt: 0 };
    return payload;
  };

  const remove = async (id) => deleteRequest.execute(id);

  return {
    customer,
    orders,
    summary,
    loading,
    error,
    load,
    remove,
    deleteLoading: deleteRequest.loading,
    deleteError: deleteRequest.error
  };
}