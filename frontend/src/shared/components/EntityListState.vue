<script setup lang="ts">
defineProps<{
  loading: boolean;
  hasItems: boolean;
  loadingText?: string;
  emptyText?: string;
}>();
</script>

<template>
  <div class="space-y-3">
    <div v-if="loading" class="app-card text-center text-sm text-slate-500">{{ loadingText || 'Đang tải...' }}</div>
    <div v-else-if="!hasItems" class="app-empty-state">{{ emptyText || 'Không có dữ liệu.' }}</div>
    <template v-else>
      <slot />
    </template>
  </div>
</template>
