import { ref, watch, type Ref } from 'vue';

export interface UseDebounceOptions<T> {
  delay?: number;
  immediate?: boolean;
}

export function useDebounce<T>(
  valueRef: Ref<T>,
  options: UseDebounceOptions<T> = {}
): Ref<T> {
  const { delay = 300, immediate = false } = options;
  const debouncedValue = ref(valueRef.value) as Ref<T>;
  let timeoutId: ReturnType<typeof setTimeout> | null = null;

  const clearExistingTimeout = () => {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
  };

  watch(
    valueRef,
    (newValue) => {
      clearExistingTimeout();

      if (immediate && debouncedValue.value === newValue) {
        debouncedValue.value = newValue;
        return;
      }

      timeoutId = setTimeout(() => {
        debouncedValue.value = newValue;
        timeoutId = null;
      }, delay);
    },
    { immediate }
  );

  return debouncedValue;
}

export function useDebouncedCallback<T extends (...args: any[]) => any>(
  callback: T,
  delay = 300
): (...args: Parameters<T>) => void {
  let timeoutId: ReturnType<typeof setTimeout> | null = null;

  return (...args: Parameters<T>) => {
    if (timeoutId) {
      clearTimeout(timeoutId);
    }

    timeoutId = setTimeout(() => {
      callback(...args);
    }, delay);
  };
}

export function useDebounceFn<T extends (...args: any[]) => any>(
  fn: T,
  delay = 300,
  options: { leading?: boolean; trailing?: boolean } = {}
): { run: (...args: Parameters<T>) => void; cancel: () => void } {
  const { leading = false, trailing = true } = options;
  let timeoutId: ReturnType<typeof setTimeout> | null = null;
  let lastArgs: Parameters<T> | null = null;
  let isLeadingCall = false;

  const cancel = () => {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
    lastArgs = null;
    isLeadingCall = false;
  };

  const run = (...args: Parameters<T>) => {
    lastArgs = args;

    if (timeoutId) {
      clearTimeout(timeoutId);
    } else if (leading) {
      isLeadingCall = true;
      fn(...args);
    }

    timeoutId = setTimeout(() => {
      if (trailing && (!leading || !isLeadingCall)) {
        fn(...lastArgs!);
      }
      timeoutId = null;
      lastArgs = null;
      isLeadingCall = false;
    }, delay);
  };

  return { run, cancel };
}
