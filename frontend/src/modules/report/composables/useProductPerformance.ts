import { ref } from "vue";
import { fetchProductPerformance } from "../services/report.api";
import { useFetch } from "../../../shared/composables/useFetch";

export function useProductPerformance() {
  const data = ref<any>(null);

  const request = useFetch(fetchProductPerformance);

  const load = async (period = "30d", sortBy = "revenue", page = 1, perPage = 50) => {
    const payload = await request.execute(period, sortBy, page, perPage);
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
