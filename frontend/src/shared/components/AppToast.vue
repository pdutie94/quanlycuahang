<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

interface ToastMessage {
  id: number;
  type: string;
  message: string;
}

const toasts = ref<ToastMessage[]>([]);
let nextId = 0;

const DURATION = 3500;

function addToast(type: string, message: string) {
  const id = ++nextId;
  toasts.value.push({ id, type, message });
  window.setTimeout(() => {
    removeToast(id);
  }, DURATION);
}

function removeToast(id: number) {
  const idx = toasts.value.findIndex((t: ToastMessage) => t.id === id);
  if (idx !== -1) {
    toasts.value.splice(idx, 1);
  }
}

onMounted(() => {
  (window as any).showToast = addToast;
});

onBeforeUnmount(() => {
  if ((window as any).showToast === addToast) {
    (window as any).showToast = undefined;
  }
});
</script>

<template>
  <Teleport to="body">
    <div class="pointer-events-none fixed inset-x-0 top-4 z-[9999] flex flex-col items-center gap-2 px-4">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-md"
          :class="{
            'border-green-200 bg-green-50 text-green-800': toast.type === 'success',
            'border-rose-200 bg-rose-50 text-rose-800': toast.type === 'error',
            'border-sky-200 bg-sky-50 text-sky-800': toast.type === 'info',
          }"
          @click="removeToast(toast.id)"
        >
          <span class="flex-1 leading-snug">{{ toast.message }}</span>
          <button type="button" class="ml-auto shrink-0 opacity-50 hover:opacity-100" aria-label="Đóng">&times;</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-enter-active {
  transition: all 220ms ease;
}
.toast-leave-active {
  transition: all 180ms ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-8px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
