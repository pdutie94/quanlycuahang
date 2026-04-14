<script setup lang="ts">
import { X } from '@lucide/vue';

defineProps({
  active: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: 'Xóa bộ lọc'
  }
});

defineEmits(['clear']);
</script>

<template>
  <button
    v-if="active"
    type="button"
    class="shrink-0 border inline-flex h-[30px] w-[30px] items-center justify-center rounded-lg border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
    :aria-label="label"
    @click="$emit('clear')"
  >
    <X class="h-4 w-4" />
  </button>
</template>