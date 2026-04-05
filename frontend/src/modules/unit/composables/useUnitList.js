import { ref } from 'vue';
import { createUnit, deleteUnit, fetchUnits, updateUnit } from '../services/unit.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useUnitList() {
  const items = ref([]);

  const listRequest = useFetch(fetchUnits);
  const createRequest = useFetch(createUnit);
  const updateRequest = useFetch(updateUnit);
  const deleteRequest = useFetch(deleteUnit);

  const applyListData = (data) => {
    items.value = data?.items || [];
  };

  const load = async () => {
    const payload = await listRequest.execute();
    applyListData(payload?.data || {});
    return payload;
  };

  const refresh = async () => {
    const payload = await fetchUnits();
    applyListData(payload?.data || {});
    return payload;
  };

  const submitCreate = async (payload) => createRequest.execute(payload);
  const submitUpdate = async (id, payload) => updateRequest.execute(id, payload);
  const submitDelete = async (id) => deleteRequest.execute(id);

  return {
    items,
    load,
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
