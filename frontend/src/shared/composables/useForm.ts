import { reactive, ref } from 'vue';

export function useForm(initialValues: Record<string, any> = {}) {
  const values = reactive({ ...initialValues });
  const errors = ref<Record<string, any>>({});

  const setErrors = (nextErrors: Record<string, any> = {}) => {
    errors.value = { ...nextErrors };
  };

  const reset = () => {
    Object.keys(initialValues).forEach((key) => {
      values[key] = initialValues[key];
    });
    errors.value = {};
  };

  return {
    values,
    errors,
    setErrors,
    reset
  };
}
