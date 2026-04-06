import { ref } from 'vue';
import { fetchCustomerDebtPaymentInfo, submitCustomerDebtPayment } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomerDebtPayment() {
  const customer = ref(null);
  const orders = ref([]);
  const totalDebt = ref(0);

  const infoRequest = useFetch(fetchCustomerDebtPaymentInfo);
  const paymentRequest = useFetch(submitCustomerDebtPayment);

  const load = async (customerId) => {
    const payload = await infoRequest.execute(customerId);
    customer.value = payload?.data?.customer || null;
    orders.value = payload?.data?.orders || [];
    totalDebt.value = Number(payload?.data?.total_debt || 0);
    return payload;
  };

  const submit = async (customerId, payload) => paymentRequest.execute(customerId, payload);

  return {
    customer,
    orders,
    totalDebt,
    loading: infoRequest.loading,
    error: infoRequest.error,
    load,
    submit,
    submitLoading: paymentRequest.loading,
    submitError: paymentRequest.error
  };
}