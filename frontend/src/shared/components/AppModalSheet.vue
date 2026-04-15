<script setup lang="ts">
import { X } from '@lucide/vue';

defineProps<{
  open: boolean;
  title: string;
}>();

defineEmits<{
  (e: 'close'): void;
}>();
</script>

<template>
  <Teleport to="body">
    <transition name="app-modal-fade-up">
      <div v-if="open" class="app-modal-overlay app-modal-open" @click.self="$emit('close')">
        <div class="app-modal-sheet-sm">
          <div class="app-modal-header">
            <h2 class="app-modal-title">{{ title }}</h2>
            <button type="button" class="app-modal-close" @click="$emit('close')">
              <X class="h-4 w-4" />
            </button>
          </div>
          <div class="app-modal-body">
            <slot />
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
/* Ensure the transitions are globally available or defined here if needed */
</style>
