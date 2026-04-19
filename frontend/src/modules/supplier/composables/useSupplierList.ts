import { computed, ref } from 'vue';
import { fetchSuppliers } from '../services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useSupplierList() {
  const suppliers = ref<Record<string, any>[]>([]);
  const keyword = ref('');
  const currentPage = ref(1);
  const meta = ref({ page: 1, per_page: 30, total_pages: 1 });
  const filters = ref<Record<string, any>>({ q: '', debt_status: '' });

  const request = useFetch(fetchSuppliers);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
    suppliers.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, per_page: 30, total_pages: 1 };
    filters.value = payload?.data?.filters || { q: '', debt_status: '' };
    return payload;
  };

  const searchSuppliers = async (q: string = '') => load({ q });

  return {
    suppliers, keyword, currentPage, meta, filters,
    totalPages: computed(() => meta.value?.total_pages || 1),
    loading: request.loading, error: request.error,
    load, searchSuppliers
  };
}
