<script setup lang="ts">
import { computed, ref } from 'vue'

const props = withDefaults(
  defineProps<{
    disabled?: boolean
  }>(),
  {
    disabled: false,
  },
)

const emit = defineEmits<{
  refresh: []
}>()

const startY = ref<number | null>(null)
const distance = ref(0)
const refreshing = ref(false)
const threshold = 76
const progress = computed(() => Math.min(distance.value / threshold, 1))
const arcDash = computed(() => 20 + progress.value * 56)
const arcRotation = computed(() => 60 + progress.value * 220)

const indicatorStyle = computed(() => ({
  transform: `translateY(${Math.min(distance.value, 92)}px)`,
  opacity: distance.value > 6 || refreshing.value ? 1 : 0,
  transition: refreshing.value ? 'transform 180ms ease-out, opacity 180ms ease-out' : 'none',
}))

function handleTouchStart(event: TouchEvent): void {
  if (props.disabled || window.scrollY > 0 || refreshing.value) {
    return
  }

  startY.value = event.touches[0]?.clientY ?? null
}

function handleTouchMove(event: TouchEvent): void {
  if (startY.value === null || props.disabled || refreshing.value) {
    return
  }

  const currentY = event.touches[0]?.clientY ?? startY.value
  const nextDistance = Math.max(0, currentY - startY.value)
  distance.value = Math.min(110, nextDistance * 0.55)
}

async function handleTouchEnd(): Promise<void> {
  if (startY.value === null) {
    return
  }

  const shouldRefresh = distance.value >= threshold && !props.disabled && !refreshing.value
  startY.value = null

  if (!shouldRefresh) {
    distance.value = 0
    return
  }

  refreshing.value = true
  distance.value = 56

  try {
    emit('refresh')
  } finally {
    window.setTimeout(() => {
      refreshing.value = false
      distance.value = 0
    }, 480)
  }
}
</script>

<template>
  <div
    class="relative"
    @touchstart.passive="handleTouchStart"
    @touchmove.passive="handleTouchMove"
    @touchend="handleTouchEnd"
    @touchcancel="handleTouchEnd"
  >
    <div class="pointer-events-none absolute inset-x-0 top-0 z-20 flex justify-center">
      <div class="flex items-center gap-2 rounded-full border border-black/10 bg-white/92 px-3 py-1 text-xs font-semibold text-ink/70 shadow-sm backdrop-blur" :style="indicatorStyle">
        <svg class="h-4 w-4" viewBox="0 0 28 28" aria-hidden="true">
          <circle cx="14" cy="14" r="10" fill="none" stroke="rgba(31,36,33,0.14)" stroke-width="3" />
          <circle
            cx="14"
            cy="14"
            r="10"
            fill="none"
            stroke="rgba(24,121,99,0.9)"
            stroke-width="3"
            stroke-linecap="round"
            :stroke-dasharray="`${arcDash} 100`"
            :style="{ transform: `rotate(${refreshing ? 340 : arcRotation}deg)`, transformOrigin: '50% 50%' }"
          />
        </svg>
        <span>{{ refreshing ? 'Đang làm mới' : 'Kéo xuống để làm mới' }}</span>
      </div>
    </div>
    <slot />
  </div>
</template>
