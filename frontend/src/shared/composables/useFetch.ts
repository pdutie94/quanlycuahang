import { ref } from 'vue';

export function useFetch(requestFn: (...args: any[]) => Promise<any>) {
  const loading = ref(false);
  const error = ref('');

  const execute = async (...args: any[]) => {
    loading.value = true;
    error.value = '';
    try {
      return await requestFn(...args);
    } catch (err: any) {
      const message = err?.response?.data?.message || err?.message || 'Request failed';
      error.value = message;
      throw err;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    execute
  };
}
