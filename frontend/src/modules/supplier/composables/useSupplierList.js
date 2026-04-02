import { ref, computed } from 'vue';
import { fetchSuppliers } from '../services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useSupplierList() {
  const suppliers = ref([]);
  const keyword = ref('');
  const currentPage = ref(1);
  const meta = ref({ page: 1, per_page: 20, total_pages: 1 });

  const request = useFetch(fetchSuppliers);

  const load = async (page = 1, query = '') => {
    currentPage.value = page;
    keyword.value = query;
    const payload = await request.execute({ q: query, page });
    
    suppliers.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, per_page: 20, total_pages: 1 };
    
    return payload;
  };

  const searchSuppliers = async (q = '') => {
    return load(1, q);
  };

  return {
    suppliers,
    keyword,
    currentPage,
    meta,
    totalPages: computed(() => meta.value?.total_pages || 1),
    loading: request.loading,
    error: request.error,
    load,
    searchSuppliers
  };
}
