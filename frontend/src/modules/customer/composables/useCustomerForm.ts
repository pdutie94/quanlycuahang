import { ref } from 'vue';
import { createCustomer, fetchCustomerDetail, updateCustomer } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomerForm() {
  const form = ref({ name: '', phone: '', address: '' });
  const customer = ref<Record<string, any> | null>(null);

  const detailRequest = useFetch(fetchCustomerDetail);
  const createRequest = useFetch(createCustomer);
  const updateRequest = useFetch(updateCustomer);

  const loadEdit = async (id: number | string) => {
    const payload = await detailRequest.execute(id);
    customer.value = payload?.data?.customer || null;
    if (customer.value) {
      form.value = {
        name: customer.value.name || '',
        phone: customer.value.phone || '',
        address: customer.value.address || ''
      };
    }
    return payload;
  };

  const submitCreate = async () => createRequest.execute({ ...form.value });
  const submitUpdate = async (id: number | string) => updateRequest.execute(id, { ...form.value });

  return {
    form, customer,
    loading: detailRequest.loading, error: detailRequest.error,
    loadEdit, submitCreate,
    createLoading: createRequest.loading, createError: createRequest.error,
    submitUpdate,
    updateLoading: updateRequest.loading, updateError: updateRequest.error
  };
}
