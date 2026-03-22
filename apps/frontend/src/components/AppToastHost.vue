<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { useUiStore } from '../stores/ui'

const ui = useUiStore()
const { toasts } = storeToRefs(ui)
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 top-4 z-[80] flex flex-col items-center gap-2 px-4">
    <transition-group name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto w-full max-w-sm rounded-2xl border px-4 py-3 text-sm font-medium shadow-lg backdrop-blur"
        :class="toast.tone === 'error'
          ? 'border-red-200 bg-red-50/95 text-red-700'
          : 'border-pine/20 bg-white/95 text-ink'"
      >
        {{ toast.message }}
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: transform 0.26s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.22s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-18px) scale(0.96);
}

.toast-move {
  transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}
</style>
