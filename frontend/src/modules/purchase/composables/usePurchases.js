import { ref } from 'vue';
import { fetchPurchases } from '../services/purchase.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function usePurchases() {
  const items = ref([]);
  const suppliers = ref([]);
  const meta = ref({ page: 1, total_pages: 1, total_count: 0, per_page: 30 });
  const filters = ref({ q: '', from_date: '', to_date: '', supplier_id: 0 });

  const { loading, error, execute } = useFetch(fetchPurchases);

  const load = async (params = {}) => {
    const payload = await execute(params);
    items.value = payload?.data?.items || [];
    suppliers.value = payload?.data?.suppliers || [];
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1, total_count: 0, per_page: 30 };
    filters.value = payload?.data?.filters || { q: '', from_date: '', to_date: '', supplier_id: 0 };
    return payload;
  };

  return {
    items,
    suppliers,
    meta,
    filters,
    loading,
    error,
    load
  };
}