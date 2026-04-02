import { ref } from 'vue';

export function useFetch(requestFn) {
  const loading = ref(false);
  const error = ref('');

  const execute = async (...args) => {
    loading.value = true;
    error.value = '';
    try {
      return await requestFn(...args);
    } catch (err) {
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
