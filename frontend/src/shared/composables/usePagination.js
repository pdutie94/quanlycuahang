import { computed, ref } from 'vue';

export function usePagination(defaultPage = 1) {
  const page = ref(defaultPage);
  const totalPages = ref(1);

  const canPrev = computed(() => page.value > 1);
  const canNext = computed(() => page.value < totalPages.value);

  const setMeta = (meta = {}) => {
    const nextPage = Number(meta.page || 1);
    const nextTotal = Number(meta.total_pages || 1);

    page.value = nextPage > 0 ? nextPage : 1;
    totalPages.value = nextTotal > 0 ? nextTotal : 1;
  };

  const next = () => {
    if (canNext.value) {
      page.value += 1;
    }
  };

  const prev = () => {
    if (canPrev.value) {
      page.value -= 1;
    }
  };

  return {
    page,
    totalPages,
    canPrev,
    canNext,
    setMeta,
    next,
    prev
  };
}
