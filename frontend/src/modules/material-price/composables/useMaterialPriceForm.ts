import { ref } from 'vue';
import { createMaterialPrice, updateMaterialPrice } from '../services/material-price.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useMaterialPriceForm() {
  const materialName = ref('');
  const unitPrice = ref('');
  const unitId = ref('');
  const description = ref('');

  const createRequest = useFetch(createMaterialPrice);
  const updateRequest = useFetch(updateMaterialPrice);

  const resetForm = () => {
    materialName.value = '';
    unitPrice.value = '';
    unitId.value = '';
    description.value = '';
  };

  const submitCreate = async () => {
    const payload = {
      material_name: materialName.value,
      unit_price: unitPrice.value,
      unit_id: unitId.value,
      description: description.value
    };
    return await createRequest.execute(payload);
  };

  const submitUpdate = async (id: number | string) => {
    const payload = {
      material_name: materialName.value,
      unit_price: unitPrice.value,
      unit_id: unitId.value,
      description: description.value
    };
    return await updateRequest.execute(id, payload);
  };

  const setFormData = (data: Record<string, any>) => {
    materialName.value = data.material_name || '';
    unitPrice.value = data.unit_price || '';
    unitId.value = data.unit_id || '';
    description.value = data.description || '';
  };

  return {
    materialName,
    unitPrice,
    unitId,
    description,
    resetForm,
    submitCreate,
    submitUpdate,
    setFormData,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}
