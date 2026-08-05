import { ref } from "vue";
import { fetchAnalytics } from "../services/report.api";
import { useFetch } from "../../../shared/composables/useFetch";

export function useAnalytics() {
  const data = ref<any>(null);

  const request = useFetch(fetchAnalytics);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await request.execute(params);
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
