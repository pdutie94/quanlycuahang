<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { usePagination } from '../../../shared/composables/usePagination';
import { useToast } from '../../../shared/composables/useToast';
import { useCategoryList } from '../composables/useCategoryList';

const { items, meta, loading, error, load } = useCategoryList();
const { page, totalPages, canPrev, canNext, setMeta, next, prev } = usePagination(1);
const toast = useToast();
const hasLoadedOnce = ref(false);

const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);
const isRefreshing = computed(() => loading.value && hasLoadedOnce.value);

const loadPage = async () => {
  try {
    await load(page.value);
    setMeta(meta.value);
    hasLoadedOnce.value = true;
  } catch (_err) {
    toast.error(error.value || 'Không thể tải danh sách danh mục.');
  }
};

watch(page, async () => {
  await loadPage();
});

onMounted(async () => {
  await loadPage();
});
</script>

<template>
  <section class="space-y-3">
    <header class="app-card">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-semibold text-slate-900">Danh mục sản phẩm</h1>
        </div>
        <RouterLink to="/categories/create" class="inline-flex h-10 items-center rounded-xl bg-brand-600 px-4 text-sm font-medium text-white">Thêm danh mục</RouterLink>
      </div>
    </header>

    <div class="space-y-3">
      <div v-if="isRefreshing" class="px-1 text-xs font-medium text-slate-500">Đang cập nhật danh mục...</div>
      <div v-if="isInitialLoading" class="app-card text-center text-sm text-slate-500">Đang tải...</div>
      <div v-else-if="!items.length" class="app-empty-state">Chưa có danh mục nào.</div>
      <div v-else class="space-y-3" :class="isRefreshing ? 'opacity-70 transition-opacity' : 'transition-opacity'">
        <RouterLink
          v-for="item in items"
          :key="item.id"
          :to="{ name: 'categories.edit', params: { id: item.id } }"
          class="app-list-card"
        >
          <div class="text-sm font-medium text-slate-900">{{ item.name }}</div>
        </RouterLink>
      </div>
    </div>

    <footer class="app-card flex items-center justify-between px-4 py-3">
      <span class="text-sm text-slate-600">Trang {{ page }} / {{ totalPages }}</span>
      <div class="flex gap-2">
        <button class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50" :disabled="!canPrev || loading" @click="prev">Trước</button>
        <button class="h-9 rounded-lg border border-slate-300 px-3 text-sm disabled:opacity-50" :disabled="!canNext || loading" @click="next">Sau</button>
      </div>
    </footer>
  </section>
</template>
