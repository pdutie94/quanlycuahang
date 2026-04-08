import { computed, ref } from 'vue';
import { fetchCategories } from '../services/category.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCategoryList() {
  const items = ref([]);
  const meta = ref({ page: 1, per_page: 30, total_pages: 1 });
  const request = useFetch(fetchCategories);

  const load = async (page = 1) => {
    const payload = await request.execute({ page });
    items.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, per_page: 30, total_pages: 1 };
    return payload;
  };

  return {
    items,
    meta,
    totalPages: computed(() => meta.value?.total_pages || 1),
    loading: request.loading,
    error: request.error,
    load
  };
}
