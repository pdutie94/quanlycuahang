<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  confirmLabel: {
    type: String,
    default: 'Xác nhận'
  },
  cancelLabel: {
    type: String,
    default: 'Hủy'
  },
  loading: {
    type: Boolean,
    default: false
  },
  tone: {
    type: String,
    default: 'danger',
    validator: (value: any) => ['danger', 'warning'].includes(value)
  }
});

const emit = defineEmits(['cancel', 'confirm']);
const popupRef = ref<HTMLElement | null>(null);
const ignoreOutsideClick = ref(false);

watch(
  () => props.open,
  (open) => {
    if (!open) {
      ignoreOutsideClick.value = false;
      return;
    }

    ignoreOutsideClick.value = true;
    window.requestAnimationFrame(() => {
      ignoreOutsideClick.value = false;
    });
  }
);

const onDocumentClick = (event: Event) => {
  if (!props.open || !popupRef.value || ignoreOutsideClick.value) {
    return;
  }

  if (!popupRef.value.contains(event.target as Node)) {
    emit('cancel');
  }
};

const onDocumentKeydown = (event: KeyboardEvent) => {
  if (!props.open) {
    return;
  }

  if (event.key === 'Escape') {
    emit('cancel');
  }
};

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  document.addEventListener('keydown', onDocumentKeydown);
});

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick);
  document.removeEventListener('keydown', onDocumentKeydown);
});
</script>

<template>
  <Teleport to="body">
    <transition name="app-modal-fade-up">
      <div v-if="open" class="pointer-events-none fixed inset-x-0 bottom-20 z-[120] flex justify-center px-4 sm:justify-end sm:px-6">
        <div ref="popupRef" class="pointer-events-auto w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h2 class="text-sm font-semibold text-slate-900">{{ title }}</h2>
              <p v-if="description" class="mt-1 text-sm leading-5 text-slate-600">{{ description }}</p>
            </div>
            <button type="button" class="inline-flex items-center justify-center text-slate-400 hover:text-slate-600" :disabled="loading" @click="$emit('cancel')">
              <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 6l8 8M14 6l-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
            </button>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button type="button" class="inline-flex h-9 items-center rounded-xl border border-slate-300 px-3.5 text-sm font-medium text-slate-700" :disabled="loading" @click="$emit('cancel')">{{ cancelLabel }}</button>
            <button
              type="button"
              class="inline-flex h-9 items-center rounded-xl px-3.5 text-sm font-medium text-white disabled:opacity-50"
              :class="tone === 'warning' ? 'border border-amber-600 bg-amber-600' : 'border border-rose-600 bg-rose-600'"
              :disabled="loading"
              @click="$emit('confirm')"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>