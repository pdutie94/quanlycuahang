<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    open: boolean
    title?: string
    subtitle?: string
  }>(),
  {},
)

const emit = defineEmits<{ close: [] }>()

// ── Swipe gesture state ──────────────────────────────────────────────
const dragY      = ref(0)   // downward translation (px, ≥ 0)
const panelH     = ref(0)   // explicit height during up-drag (0 = natural/auto)
const isDragging = ref(false)
const isExpanded = ref(false)

let startY      = 0
let startHeight = 0   // panel.offsetHeight captured at touchstart
let wasExpanded = false
let lastY       = 0
let lastTime    = 0
let velocityY   = 0
let bodyEl: HTMLElement | null = null

const CLOSE_THRESHOLD    = 120  // px down → close (from normal)
const COLLAPSE_THRESHOLD = 120  // px shrunk → collapse to normal (from expanded)
const CLOSE_VELOCITY     = 0.5  // px/ms flick down
const EXPAND_THRESHOLD   = 80   // px grown → expand to full

const EASE = 'cubic-bezier(0.32, 0.72, 0, 1)'

function rubberBand(excess: number, dimension: number): number {
  if (excess <= 0) return 0
  const c = 0.55
  return (excess * c * dimension) / (dimension + c * excess)
}

const panelStyle = computed(() => {
  const tr = isDragging.value
    ? 'none'
    : `transform 0.32s ${EASE}, height 0.32s ${EASE}, border-radius 0.32s ${EASE}`

  // Stay in expanded branch while dragging down from full to avoid max-height clamp jump.
  if (isExpanded.value) {
    if (panelH.value > 0) {
      return { transition: tr, height: `${panelH.value}px` }
    }
    return { transition: tr, height: '100dvh' }
  }

  const style: Record<string, string> = { transition: tr, maxHeight: '90dvh' }
  if (panelH.value > 0)  style.height    = `${panelH.value}px`
  if (dragY.value > 0)   style.transform = `translateY(${dragY.value}px)`
  return style
})

const panelClass = computed(() => {
  // Keep full-screen corner style through collapse drag, then switch after collapse.
  const full = isExpanded.value
  return full
    ? 'modal-sheet-panel flex w-full flex-col rounded-none bg-white'
    : 'modal-sheet-panel flex w-full flex-col rounded-t-3xl bg-white'
})

// Reset all state when modal is closed (by parent or by button)
watch(() => props.open, (v) => {
  if (!v) {
    isExpanded.value = false
    dragY.value      = 0
    panelH.value     = 0
  }
})

function handleClose() {
  emit('close')
}

// ── Touch handlers ────────────────────────────────────────────────────
function onTouchStart(e: TouchEvent) {
  const panel  = e.currentTarget as HTMLElement
  bodyEl       = panel.querySelector('.modal-body') as HTMLElement | null
  wasExpanded  = isExpanded.value
  startHeight  = panel.offsetHeight   // always real rendered px — never window.innerHeight
  startY       = e.touches[0].clientY
  lastY        = startY
  lastTime     = Date.now()
  velocityY    = 0
  isDragging.value = false
}

function onTouchMove(e: TouchEvent) {
  const currentY = e.touches[0].clientY
  const dy       = currentY - startY
  const now      = Date.now()
  const dt       = now - lastTime || 1
  velocityY = (currentY - lastY) / dt
  lastY     = currentY
  lastTime  = now

  const bodyAtTop   = (bodyEl?.scrollTop ?? 0) <= 0
  const pullingDown = dy > 0

  if (!isDragging.value) {
    if (pullingDown && bodyAtTop) {
      isDragging.value = true
    } else if (!pullingDown && !isExpanded.value) {
      isDragging.value = true
    } else {
      return  // let body scroll naturally
    }
  }

  e.preventDefault()

  if (wasExpanded && pullingDown) {
    // shrink from full screen, with rubber-band if pulled below minimum height
    dragY.value  = 0
    const minHeight = window.innerHeight * 0.3
    const rawHeight = startHeight - dy
    if (rawHeight >= minHeight) {
      panelH.value = rawHeight
    } else {
      const extra = minHeight - rawHeight
      panelH.value = Math.max(minHeight - rubberBand(extra, minHeight), 56)
    }
  } else if (pullingDown) {
    // normal state: translate panel to dismiss, then rubber-band past close threshold
    const raw = Math.max(0, dy)
    if (raw <= CLOSE_THRESHOLD) {
      dragY.value = raw
    } else {
      const extra = raw - CLOSE_THRESHOLD
      dragY.value = CLOSE_THRESHOLD + rubberBand(extra, window.innerHeight) * 0.45
    }
    panelH.value = 0
  } else {
    // swipe up: grow height 1:1 until full, then rubber-band
    dragY.value  = 0
    const maxHeight = window.innerHeight
    const rawHeight = startHeight + Math.abs(dy)
    if (rawHeight <= maxHeight) {
      panelH.value = rawHeight
    } else {
      const extra = rawHeight - maxHeight
      panelH.value = maxHeight + rubberBand(extra, maxHeight) * 0.35
    }
  }
}

function onTouchEnd() {
  isDragging.value = false

  if (wasExpanded) {
    // expanded → shrink drag: decide collapse or snap back
    const shrunk = startHeight - panelH.value
    if (shrunk > COLLAPSE_THRESHOLD || velocityY > CLOSE_VELOCITY) {
      isExpanded.value = false
      panelH.value     = 0   // CSS transition animates back to max-height: 90dvh
    } else {
      // not far enough → snap back to full
      panelH.value = 0       // CSS transition returns to height: 100dvh
    }
    dragY.value = 0
    return
  }

  if (dragY.value > CLOSE_THRESHOLD || velocityY > CLOSE_VELOCITY) {
    dragY.value = window.innerHeight
    setTimeout(handleClose, 320)
    return
  }

  const grew = panelH.value - startHeight
  if (grew > EXPAND_THRESHOLD || velocityY < -CLOSE_VELOCITY) {
    isExpanded.value = true
    panelH.value     = 0
  } else {
    panelH.value = 0
  }
  dragY.value = 0
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal-sheet">
      <div
        v-if="open"
        class="fixed inset-0 z-[80] flex flex-col justify-end bg-black/35"
        @click.self="handleClose"
      >
        <div
          :class="panelClass"
          :style="panelStyle"
          @touchstart.passive="onTouchStart"
          @touchmove="onTouchMove"
          @touchend="onTouchEnd"
          @touchcancel="onTouchEnd"
        >
          <!-- drag handle -->
          <div class="mx-auto mt-3 h-1.5 w-14 flex-none cursor-grab rounded-full bg-black/10 active:cursor-grabbing" />

          <!-- header -->
          <header class="flex flex-none items-start gap-3 border-b border-slate-200 px-4 py-3">
            <div class="flex-1">
              <slot name="header">
                <h3 v-if="title" class="text-xl font-semibold text-slate-800">{{ title }}</h3>
                <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
              </slot>
            </div>
            <button
              type="button"
              class="mt-0.5 flex-none rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50"
              @click="handleClose"
            >
              <X :size="16" />
            </button>
          </header>

          <!-- body (scrollable) -->
          <div class="modal-body flex-1 overflow-y-auto px-4 py-3">
            <slot />
          </div>

          <!-- footer (optional) -->
          <footer v-if="$slots.footer" class="flex-none border-t border-slate-200 px-4 py-3">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
