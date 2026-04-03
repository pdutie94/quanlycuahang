<script setup>
defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  loadingMore: {
    type: Boolean,
    default: false
  },
  hasMore: {
    type: Boolean,
    default: false
  }
});
</script>

<template>
  <div v-if="visible" class="text-center text-sm text-slate-500">
    <span v-if="loadingMore">Đang tải thêm...</span>
    <span v-else-if="!hasMore">Đã hiển thị hết danh sách.</span>
    <span v-else>Cuộn xuống để tải thêm</span>
  </div>
</template>