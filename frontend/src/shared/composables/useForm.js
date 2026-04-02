import { reactive, ref } from 'vue';

export function useForm(initialValues = {}) {
  const values = reactive({ ...initialValues });
  const errors = ref({});

  const setErrors = (nextErrors = {}) => {
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
