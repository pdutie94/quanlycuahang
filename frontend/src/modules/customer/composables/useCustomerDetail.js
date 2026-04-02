import { ref } from 'vue';
import { fetchCustomerDetail } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomerDetail() {
  const customer = ref(null);
  const orders = ref([]);
  const summary = ref({ total_amount: 0, total_paid: 0, total_debt: 0 });

  const { loading, error, execute } = useFetch(fetchCustomerDetail);

  const load = async (id) => {
    const payload = await execute(id);
    customer.value = payload?.data?.customer || null;
    orders.value = payload?.data?.orders || [];
    summary.value = payload?.data?.summary || { total_amount: 0, total_paid: 0, total_debt: 0 };
    return payload;
  };

  return {
    customer,
    orders,
    summary,
    loading,
    error,
    load
  };
}