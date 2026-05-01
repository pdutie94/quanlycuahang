import { ref, watch, type Ref } from 'vue';

export interface UseLocalStorageOptions<T> {
  serializer?: {
    read: (value: string) => T;
    write: (value: T) => string;
  };
  syncAcrossTabs?: boolean;
}

export function useLocalStorage<T>(
  key: string,
  defaultValue: T,
  options: UseLocalStorageOptions<T> = {}
): Ref<T> {
  const { syncAcrossTabs = true } = options;

  const defaultSerializer = {
    read: (value: string): T => {
      try {
        return JSON.parse(value) as T;
      } catch {
        return defaultValue;
      }
    },
    write: (value: T): string => JSON.stringify(value),
  };

  const serializer = options.serializer || defaultSerializer;

  const readValue = (): T => {
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
      return defaultValue;
    }

    try {
      const item = localStorage.getItem(key);
      if (item === null) {
        localStorage.setItem(key, serializer.write(defaultValue));
        return defaultValue;
      }
      return serializer.read(item);
    } catch (error) {
      console.warn(`Error reading localStorage key "${key}":`, error);
      return defaultValue;
    }
  };

  const storedValue = ref(readValue()) as Ref<T>;

  const writeValue = (value: T) => {
    if (typeof window === 'undefined' || typeof localStorage === 'undefined') {
      return;
    }

    try {
      localStorage.setItem(key, serializer.write(value));
    } catch (error) {
      console.warn(`Error writing localStorage key "${key}":`, error);
    }
  };

  // Watch for changes and sync to localStorage
  watch(
    storedValue,
    (newValue) => {
      writeValue(newValue);
    },
    { deep: true }
  );

  // Sync across tabs
  if (syncAcrossTabs && typeof window !== 'undefined') {
    const handleStorage = (event: StorageEvent) => {
      if (event.key === key && event.newValue !== null) {
        storedValue.value = serializer.read(event.newValue);
      }
    };

    window.addEventListener('storage', handleStorage);
  }

  // Helper methods
  const remove = () => {
    if (typeof window !== 'undefined' && typeof localStorage !== 'undefined') {
      localStorage.removeItem(key);
    }
    storedValue.value = defaultValue;
  };

  return storedValue;
}

export function useSessionStorage<T>(
  key: string,
  defaultValue: T,
  options: UseLocalStorageOptions<T> = {}
): Ref<T> {
  const { syncAcrossTabs = true } = options;

  const defaultSerializer = {
    read: (value: string): T => {
      try {
        return JSON.parse(value) as T;
      } catch {
        return defaultValue;
      }
    },
    write: (value: T): string => JSON.stringify(value),
  };

  const serializer = options.serializer || defaultSerializer;

  const readValue = (): T => {
    if (typeof window === 'undefined' || typeof sessionStorage === 'undefined') {
      return defaultValue;
    }

    try {
      const item = sessionStorage.getItem(key);
      if (item === null) {
        sessionStorage.setItem(key, serializer.write(defaultValue));
        return defaultValue;
      }
      return serializer.read(item);
    } catch (error) {
      console.warn(`Error reading sessionStorage key "${key}":`, error);
      return defaultValue;
    }
  };

  const storedValue = ref(readValue()) as Ref<T>;

  const writeValue = (value: T) => {
    if (typeof window === 'undefined' || typeof sessionStorage === 'undefined') {
      return;
    }

    try {
      sessionStorage.setItem(key, serializer.write(value));
    } catch (error) {
      console.warn(`Error writing sessionStorage key "${key}":`, error);
    }
  };

  watch(
    storedValue,
    (newValue) => {
      writeValue(newValue);
    },
    { deep: true }
  );

  if (syncAcrossTabs && typeof window !== 'undefined') {
    const handleStorage = (event: StorageEvent) => {
      if (event.key === key && event.newValue !== null) {
        storedValue.value = serializer.read(event.newValue);
      }
    };

    window.addEventListener('storage', handleStorage);
  }

  return storedValue;
}
