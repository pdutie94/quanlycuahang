<script setup lang="ts">
import { computed } from 'vue'
import { dismissToast, useToasts } from '../lib/toast'

const toasts = useToasts()

const toneClass = computed<Record<string, string>>(() => ({
  default: 'border-black/10 bg-white text-ink shadow-card',
  success: 'border-emerald-200 bg-emerald-50 text-emerald-800 shadow-[0_12px_30px_rgba(5,150,105,0.18)]',
  warning: 'border-amber-200 bg-amber-50 text-amber-800 shadow-[0_12px_30px_rgba(245,158,11,0.18)]',
  error: 'border-red-200 bg-red-50 text-red-700 shadow-[0_12px_30px_rgba(220,38,38,0.16)]',
}))
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 bottom-20 z-[80] px-4 sm:bottom-6">
    <transition-group name="toast-stack" tag="div" class="mx-auto flex w-full max-w-md flex-col gap-2">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto rounded-2xl border px-4 py-3 backdrop-blur"
        :class="toneClass[toast.tone]"
        role="status"
        aria-live="polite"
        @click="dismissToast(toast.id)"
      >
        <p class="text-sm font-medium">{{ toast.message }}</p>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-stack-enter-active,
.toast-stack-leave-active {
  transition: all 0.22s ease;
}

.toast-stack-enter-from,
.toast-stack-leave-to {
  opacity: 0;
  transform: translateY(12px) scale(0.98);
}
</style>
