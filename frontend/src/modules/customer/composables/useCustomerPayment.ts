import { ref } from 'vue';
import { fetchCustomerPaymentInfo, submitCustomerPayment } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomerPayment() {
  const order = ref<Record<string, any> | null>(null);
  const remaining = ref(0);

  const infoRequest = useFetch(fetchCustomerPaymentInfo);
  const paymentRequest = useFetch(submitCustomerPayment);

  const load = async (orderId: number | string) => {
    const payload = await infoRequest.execute(orderId);
    order.value = payload?.data?.order || null;
    remaining.value = Number(payload?.data?.remaining || 0);
    return payload;
  };

  const submit = async (orderId: number | string, payload: Record<string, any>) => paymentRequest.execute(orderId, payload);

  return {
    order, remaining,
    loading: infoRequest.loading, error: infoRequest.error,
    load, submit,
    submitLoading: paymentRequest.loading, submitError: paymentRequest.error
  };
}
