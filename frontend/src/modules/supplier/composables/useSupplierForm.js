import { ref } from 'vue';
import { createSupplier, fetchSupplierFormEditData, updateSupplier } from '../services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useSupplierForm() {
  const form = ref({ name: '', phone: '', address: '' });
  const supplier = ref(null);

  const detailRequest = useFetch(fetchSupplierFormEditData);
  const createRequest = useFetch(createSupplier);
  const updateRequest = useFetch(updateSupplier);

  const loadEdit = async (id) => {
    const payload = await detailRequest.execute(id);
    supplier.value = payload?.data?.supplier || null;
    if (supplier.value) {
      form.value = {
        name: supplier.value.name || '',
        phone: supplier.value.phone || '',
        address: supplier.value.address || ''
      };
    }
    return payload;
  };

  const submitCreate = async () => createRequest.execute({ ...form.value });
  const submitUpdate = async (id) => updateRequest.execute(id, { ...form.value });

  return {
    form,
    supplier,
    loading: detailRequest.loading,
    error: detailRequest.error,
    loadEdit,
    submitCreate,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    submitUpdate,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}
