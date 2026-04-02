import { ref } from 'vue';
import { fetchProducts } from '../services/product.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useProducts() {
  const items = ref([]);
  const meta = ref({ page: 1, total_pages: 1 });
  const filters = ref({ q: '' });

  const { loading, error, execute } = useFetch(fetchProducts);

  const load = async (params = {}) => {
    const payload = await execute(params);
    items.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1 };
    filters.value = payload?.data?.filters || { q: '' };
    return payload;
  };

  return {
    items,
    meta,
    filters,
    loading,
    error,
    load
  };
}
