import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch, type Ref } from 'vue';

function toNumber(value: any, fallback = 0): number {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : fallback;
}

function mergeUniqueById<T>(previousItems: T[], nextItems: T[], getItemId: (item: T) => number): T[] {
  const merged = [...previousItems];
  const seen = new Set(previousItems.map((item) => getItemId(item)).filter((id) => id > 0));

  nextItems.forEach((item) => {
    const id = getItemId(item);
    if (id > 0 && seen.has(id)) {
      return;
    }

    if (id > 0) {
      seen.add(id);
    }
    merged.push(item);
  });

  return merged;
}

export interface UseInfiniteListOptions<T> {
  itemsRef: Ref<T[]>;
  metaRef?: Ref<any>;
  loadingRef: Ref<boolean>;
  fetchPage: (page: number) => Promise<any>;
  onError?: (error: unknown) => void;
  getItemId?: (item: T) => number;
  rootMargin?: string;
  autoLoad?: boolean;
}

export function useInfiniteList<T>(options: UseInfiniteListOptions<T>) {
  const {
    itemsRef,
    metaRef,
    loadingRef,
    fetchPage,
    onError,
    getItemId = (item: T) => toNumber((item as any)?.id),
    rootMargin = '240px 0px',
    autoLoad = true
  } = options;

  const page = ref(1);
  const totalPages = ref(1);
  const loadingMore = ref(false);
  const hasLoadedOnce = ref(false);
  const infiniteSentinel = ref<HTMLElement | null>(null);
  let infiniteObserver: IntersectionObserver | null = null;

  const hasMore = computed(() => page.value < totalPages.value);
  const isInitialLoading = computed(() => loadingRef.value && !loadingMore.value && !hasLoadedOnce.value);

  const readTotalPages = (payload: any) => {
    return toNumber(payload?.data?.meta?.total_pages ?? metaRef?.value?.total_pages, 1) || 1;
  };

  const loadPage = async (append = false) => {
    try {
      const previousItems = append ? [...itemsRef.value] : [];
      const payload = await fetchPage(page.value);
      totalPages.value = readTotalPages(payload);

      if (append) {
        const nextItems = (payload?.data?.items as T[]) || [];
        itemsRef.value = mergeUniqueById(previousItems, nextItems, getItemId);
      } else {
        itemsRef.value = (payload?.data?.items as T[]) || [];
      }

      hasLoadedOnce.value = true;

      return payload;
    } catch (error) {
      if (append) {
        page.value = Math.max(1, page.value - 1);
      }
      if (typeof onError === 'function') {
        onError(error);
      }
      return null;
    }
  };

  const refresh = async () => {
    page.value = 1;
    await loadPage(false);
  };

  const loadMore = async () => {
    if (loadingRef.value || loadingMore.value || !hasMore.value) {
      return;
    }

    loadingMore.value = true;
    page.value += 1;
    await loadPage(true);
    loadingMore.value = false;
  };

  const setupInfiniteObserver = async () => {
    if (infiniteObserver) {
      infiniteObserver.disconnect();
      infiniteObserver = null;
    }

    await nextTick();
    if (!infiniteSentinel.value || typeof window === 'undefined' || !('IntersectionObserver' in window)) {
      return;
    }

    infiniteObserver = new IntersectionObserver(
      async (entries) => {
        const [entry] = entries;
        if (!entry?.isIntersecting) {
          return;
        }
        await loadMore();
      },
      { rootMargin }
    );

    infiniteObserver.observe(infiniteSentinel.value);
  };

  onMounted(async () => {
    if (!autoLoad) return;
    
    await loadPage(false);
    await setupInfiniteObserver();
  });

  onBeforeUnmount(() => {
    if (infiniteObserver) {
      infiniteObserver.disconnect();
      infiniteObserver = null;
    }
  });

  watch(hasMore, async () => {
    await setupInfiniteObserver();
  });

  return {
    page,
    totalPages,
    hasMore,
    loadingMore,
    isInitialLoading,
    infiniteSentinel,
    loadPage,
    refresh,
    loadMore,
    setupInfiniteObserver
  };
}
