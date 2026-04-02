import { ref } from 'vue';
import { createCategory, fetchCategoryDetail, updateCategory, deleteCategory } from '../services/category.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCategoryForm() {
  const form = ref({ name: '' });
  const category = ref(null);

  const detailRequest = useFetch(fetchCategoryDetail);
  const createRequest = useFetch(createCategory);
  const updateRequest = useFetch(updateCategory);
  const deleteRequest = useFetch(deleteCategory);

  const loadEdit = async (id) => {
    const payload = await detailRequest.execute(id);
    category.value = payload?.data?.category || null;
    if (category.value) {
      form.value = { name: category.value.name || '' };
    }
    return payload;
  };

  const submitCreate = async () => createRequest.execute({ ...form.value });
  const submitUpdate = async (id) => updateRequest.execute(id, { ...form.value });
  const submitDelete = async (id) => deleteRequest.execute(id);

  return {
    form,
    category,
    loading: detailRequest.loading,
    error: detailRequest.error,
    loadEdit,
    submitCreate,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    submitUpdate,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error,
    submitDelete,
    deleteLoading: deleteRequest.loading,
    deleteError: deleteRequest.error
  };
}
