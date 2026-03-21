<script setup lang="ts">
import { computed, ref } from 'vue'
import { X } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    description?: string
    closeOnOverlay?: boolean
    showClose?: boolean
  }>(),
  {
    title: '',
    description: '',
    closeOnOverlay: true,
    showClose: true,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const touchStartY = ref(0)
const dragOffset = ref(0)
const sheetStyle = computed(() => ({
  transform: `translateY(${dragOffset.value}px)`,
}))

function close(): void {
  dragOffset.value = 0
  emit('update:modelValue', false)
}

function handleOverlayClick(): void {
  if (props.closeOnOverlay) {
    close()
  }
}

function handleTouchStart(event: TouchEvent): void {
  touchStartY.value = event.touches[0]?.clientY ?? 0
}

function handleTouchMove(event: TouchEvent): void {
  const currentY = event.touches[0]?.clientY ?? 0
  dragOffset.value = Math.max(0, Math.min(220, currentY - touchStartY.value))
}

function handleTouchEnd(): void {
  if (dragOffset.value > 96) {
    close()
    return
  }

  dragOffset.value = 0
}
</script>

<template>
  <transition name="bottom-sheet">
    <div v-if="modelValue" class="fixed inset-0 z-[70] bg-black/35" @click.self="handleOverlayClick">
      <div
        class="absolute bottom-0 left-0 right-0 rounded-t-[28px] bg-white p-5 shadow-2xl"
        :style="sheetStyle"
        @touchend="handleTouchEnd"
        @touchmove="handleTouchMove"
        @touchstart="handleTouchStart"
      >
        <div class="mx-auto mb-4 h-1.5 w-12 rounded-full bg-black/10" />

        <div v-if="title || showClose" class="mb-4 flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <h2 v-if="title" class="text-base font-semibold">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-ink/60">{{ description }}</p>
          </div>
          <button v-if="showClose" class="rounded-full border border-black/10 p-2 text-ink/80" type="button" @click="close">
            <X :size="18" />
          </button>
        </div>

        <slot :close="close" />
      </div>
    </div>
  </transition>
</template>

<style scoped>
.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
  transition: opacity 0.22s ease;
}

.bottom-sheet-enter-from,
.bottom-sheet-leave-to {
  opacity: 0;
}

.bottom-sheet-enter-from > div,
.bottom-sheet-leave-to > div {
  transform: translateY(100%);
}
</style>
