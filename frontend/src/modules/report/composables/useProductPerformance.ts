import { ref } from "vue";
import { fetchProductPerformance } from "../services/report.api";
import { useFetch } from "../../../shared/composables/useFetch";

export function useProductPerformance() {
  const data = ref<any>(null);

  const request = useFetch(fetchProductPerformance);

  const load = async (params: Record<string, any> = {}, sortBy = "revenue", page = 1, perPage = 50) => {
    const payload = await request.execute(params, sortBy, page, perPage);
    data.value = payload?.data || null;
    return payload;
  };

  return {
    data,
    loading: request.loading,
    error: request.error,
    load,
  };
}
