import { ref } from 'vue';
import { createUnit, deleteUnit, fetchUnits, updateUnit } from '../services/unit.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useUnitList() {
  const items = ref<Record<string, any>[]>([]);
  const meta = ref({ page: 1, per_page: 30, total_pages: 1 });
  const page = ref(1);

  const listRequest = useFetch(fetchUnits);
  const createRequest = useFetch(createUnit);
  const updateRequest = useFetch(updateUnit);
  const deleteRequest = useFetch(deleteUnit);

  const applyListData = (data: Record<string, any>, append: boolean = false) => {
    if (append) {
      items.value.push(...(data?.items || []));
    } else {
      items.value = data?.items || [];
    }
    meta.value = data?.meta || { page: 1, per_page: 30, total_pages: 1 };
  };

  const load = async (p: number = 1, append: boolean = false) => {
    const payload = await listRequest.execute({ page: p });
    applyListData(payload?.data || {}, append);
    page.value = p;
    return payload;
  };

  const loadMore = async () => {
    if (meta.value.page >= meta.value.total_pages) return;
    const nextPage = meta.value.page + 1;
    await load(nextPage, true);
  };

  const refresh = async () => {
    const payload = await fetchUnits();
    applyListData(payload?.data || {});
    return payload;
  };

  const submitCreate = async (payload: Record<string, any>) => createRequest.execute(payload);
  const submitUpdate = async (id: number | string, payload: Record<string, any>) => updateRequest.execute(id, payload);
  const submitDelete = async (id: number | string) => deleteRequest.execute(id);

  return {
    items,
    meta,
    page,
    load,
    loadMore,
    refresh,
    loading: listRequest.loading,
    error: listRequest.error,
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
