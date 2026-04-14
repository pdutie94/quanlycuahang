import { ref } from 'vue';
import { fetchCustomers } from '../services/customer.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useCustomers() {
  const items = ref<Record<string, any>[]>([]);
  const meta = ref({ page: 1, total_pages: 1 });
  const filters = ref<Record<string, any>>({ q: '', debt_status: '' });

  const { loading, error, execute } = useFetch(fetchCustomers);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await execute(params);
    items.value = payload?.data?.items || [];
    meta.value = payload?.data?.meta || { page: 1, total_pages: 1 };
    filters.value = payload?.data?.filters || { q: '', debt_status: '' };
    return payload;
  };

  return { items, meta, filters, loading, error, load };
}
