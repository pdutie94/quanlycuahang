<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'
import { House, ShoppingCart, Package, PieChart, Menu, X, UserRound, LogOut } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useUiStore } from '../stores/ui'

const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()
const isMenuOpen = ref(false)
const transitionName = computed(() => ui.transitionName)

// ── Menu sheet swipe ─────────────────────────────────────────────────
const menuDragY      = ref(0)
const menuPanelH     = ref(0)   // explicit px height during up-drag (0 = natural)
const menuIsDragging = ref(false)
const menuIsExpanded = ref(false)

let menuStartY      = 0
let menuStartHeight = 0
let menuWasExpanded = false
let menuLastY       = 0
let menuLastTime    = 0
let menuVelocity    = 0

const CLOSE_THRESHOLD    = 120
const COLLAPSE_THRESHOLD = 120
const CLOSE_VELOCITY     = 0.5
const EXPAND_THRESHOLD   = 80

const EASE = 'cubic-bezier(0.32, 0.72, 0, 1)'

function rubberBand(excess: number, dimension: number): number {
  if (excess <= 0) return 0
  const c = 0.55
  return (excess * c * dimension) / (dimension + c * excess)
}

const menuPanelStyle = computed(() => {
  const tr = menuIsDragging.value
    ? 'none'
    : `transform 0.32s ${EASE}, height 0.32s ${EASE}, border-radius 0.32s ${EASE}`

  // Stay in expanded branch while dragging down from full to avoid clamp jump.
  if (menuIsExpanded.value) {
    if (menuPanelH.value > 0) {
      return { transition: tr, height: `${menuPanelH.value}px` }
    }
    return { transition: tr, height: '100dvh' }
  }

  const style: Record<string, string> = { transition: tr, maxHeight: '90dvh' }
  if (menuPanelH.value > 0)  style.height    = `${menuPanelH.value}px`
  if (menuDragY.value  > 0)  style.transform = `translateY(${menuDragY.value}px)`
  return style
})

const menuPanelClass = computed(() => {
  const full = menuIsExpanded.value
  return full
    ? 'modal-sheet-panel flex w-full flex-col rounded-none bg-white'
    : 'modal-sheet-panel flex w-full flex-col rounded-t-3xl bg-white'
})

const navItems = [
  { to: '/', label: 'Home', icon: House },
  { to: '/pos', label: 'Tạo đơn', icon: ShoppingCart },
  { to: '/products', label: 'Sản phẩm', icon: Package },
  { to: '/reports', label: 'Báo cáo', icon: PieChart },
]

async function handleLogout(): Promise<void> {
  await auth.logout()
  await router.push('/login')
}

function closeMenu(): void {
  menuIsExpanded.value = false
  menuDragY.value      = 0
  menuPanelH.value     = 0
  isMenuOpen.value     = false
}

let menuBodyEl: HTMLElement | null = null

function handleMenuTouchStart(event: TouchEvent): void {
  const panel     = event.currentTarget as HTMLElement
  menuBodyEl      = panel.querySelector('.menu-body') as HTMLElement | null
  menuWasExpanded = menuIsExpanded.value
  menuStartHeight = panel.offsetHeight   // always real rendered px — never window.innerHeight
  menuStartY      = event.touches[0].clientY
  menuLastY       = menuStartY
  menuLastTime    = Date.now()
  menuVelocity    = 0
  menuIsDragging.value = false
}

function handleMenuTouchMove(event: TouchEvent): void {
  const currentY = event.touches[0].clientY
  const dy = currentY - menuStartY
  const now = Date.now()
  const dt = now - menuLastTime || 1
  menuVelocity = (currentY - menuLastY) / dt
  menuLastY = currentY
  menuLastTime = now

  const bodyAtTop = (menuBodyEl?.scrollTop ?? 0) <= 0
  const pullingDown = dy > 0

  if (!menuIsDragging.value) {
    if (pullingDown && bodyAtTop) {
      menuIsDragging.value = true
    } else if (!pullingDown && !menuIsExpanded.value) {
      menuIsDragging.value = true
    } else {
      return
    }
  }

  event.preventDefault()
  if (menuWasExpanded && pullingDown) {
    // shrink from full screen, with rubber-band below minimum height
    menuDragY.value  = 0
    const minHeight = window.innerHeight * 0.3
    const rawHeight = menuStartHeight - dy
    if (rawHeight >= minHeight) {
      menuPanelH.value = rawHeight
    } else {
      const extra = minHeight - rawHeight
      menuPanelH.value = Math.max(minHeight - rubberBand(extra, minHeight), 56)
    }
  } else if (pullingDown) {
    const raw = Math.max(0, dy)
    if (raw <= CLOSE_THRESHOLD) {
      menuDragY.value = raw
    } else {
      const extra = raw - CLOSE_THRESHOLD
      menuDragY.value = CLOSE_THRESHOLD + rubberBand(extra, window.innerHeight) * 0.45
    }
    menuPanelH.value = 0
  } else {
    menuDragY.value  = 0
    const maxHeight = window.innerHeight
    const rawHeight = menuStartHeight + Math.abs(dy)
    if (rawHeight <= maxHeight) {
      menuPanelH.value = rawHeight
    } else {
      const extra = rawHeight - maxHeight
      menuPanelH.value = maxHeight + rubberBand(extra, maxHeight) * 0.35
    }
  }
}

function handleMenuTouchEnd(): void {
  menuIsDragging.value = false

  if (menuWasExpanded) {
    const shrunk = menuStartHeight - menuPanelH.value
    if (shrunk > COLLAPSE_THRESHOLD || menuVelocity > CLOSE_VELOCITY) {
      menuIsExpanded.value = false
      menuPanelH.value     = 0
    } else {
      menuPanelH.value = 0  // snap back to full
    }
    menuDragY.value = 0
    return
  }

  if (menuDragY.value > CLOSE_THRESHOLD || menuVelocity > CLOSE_VELOCITY) {
    menuDragY.value = window.innerHeight
    setTimeout(closeMenu, 320)
    return
  }

  const grew = menuPanelH.value - menuStartHeight
  if (grew > EXPAND_THRESHOLD || menuVelocity < -CLOSE_VELOCITY) {
    menuIsExpanded.value = true
    menuPanelH.value     = 0
  } else {
    menuPanelH.value = 0
  }
  menuDragY.value = 0
}
</script>

<template>
  <div class="app-shell min-h-screen bg-slate-50 text-slate-900">
    <header class="app-topbar border-b border-slate-200 bg-white px-4 py-2">
      <div class="app-content-wrap flex items-center justify-between gap-3 px-0">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500">
            <House :size="14" />
          </span>
          <h1 class="text-base font-semibold">Đại lý Đức Nam</h1>
        </div>
        <div class="flex items-center gap-2">
          <button class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100" type="button">
            <UserRound :size="14" />
          </button>
          <button class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100" type="button" @click="handleLogout">
            <LogOut :size="14" />
          </button>
        </div>
      </div>
    </header>

    <div class="flex min-h-[calc(100vh-49px)] w-full flex-col">
      <main class="relative flex-1 overflow-hidden px-4 py-5 pb-24">
        <div class="app-content-wrap route-stage px-0">
          <RouterView v-slot="{ Component, route }">
            <transition :name="transitionName" mode="out-in">
              <component :is="Component" :key="route.fullPath" class="route-page" />
            </transition>
          </RouterView>
        </div>
      </main>

      <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white">
        <div class="app-content-wrap grid h-16 w-full grid-cols-5 gap-1 px-1 py-1">
          <RouterLink
            v-for="item in navItems"
            :key="item.label"
            :to="item.to"
            :class="route.path === item.to ? 'fancy-box tone-mint text-teal-700' : 'text-slate-500'"
            class="flex h-full flex-col items-center justify-center rounded-xl p-1 text-sm font-medium transition duration-200 hover:bg-slate-50 active:scale-[0.98]"
          >
            <component :is="item.icon" :size="18" />
              <span class="block w-full whitespace-nowrap overflow-hidden text-ellipsis text-center">{{ item.label }}</span>
          </RouterLink>
          <button
            class="flex h-full flex-col items-center justify-center rounded-xl p-1 text-sm font-medium text-slate-500 transition duration-200 hover:bg-slate-50"
            type="button"
            @click="isMenuOpen = true"
          >
            <Menu :size="18" />
              <span class="block w-full whitespace-nowrap overflow-hidden text-ellipsis text-center">Menu</span>
          </button>
        </div>
      </nav>

      <transition name="modal-sheet">
        <div v-if="isMenuOpen" class="fixed inset-0 z-50 flex flex-col justify-end bg-black/35" @click.self="closeMenu">
          <div
            :class="menuPanelClass"
            :style="menuPanelStyle"
            @touchstart.passive="handleMenuTouchStart"
            @touchmove="handleMenuTouchMove"
            @touchend="handleMenuTouchEnd"
            @touchcancel="handleMenuTouchEnd"
          >
            <!-- drag handle -->
            <div class="mx-auto mt-3 h-1.5 w-14 flex-none cursor-grab rounded-full bg-black/10 active:cursor-grabbing" />

            <!-- header -->
            <header class="flex flex-none items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
              <h2 class="text-base font-semibold">Menu nhanh</h2>
              <button class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50" type="button" @click="closeMenu">
                <X :size="16" />
              </button>
            </header>

            <!-- body (scrollable) -->
            <div class="menu-body flex-1 overflow-y-auto px-4 py-3">
              <div class="grid grid-cols-2 gap-2">
                <RouterLink to="/" class="fancy-box tone-sky rounded-xl p-3" @click="closeMenu">Dashboard</RouterLink>
                <RouterLink to="/products" class="fancy-box tone-lime rounded-xl p-3" @click="closeMenu">Sản phẩm</RouterLink>
                <RouterLink to="/pos" class="fancy-box tone-mint rounded-xl p-3" @click="closeMenu">POS bán hàng</RouterLink>
                <RouterLink to="/orders" class="fancy-box tone-amber rounded-xl p-3" @click="closeMenu">Đơn hàng</RouterLink>
                <RouterLink to="/purchases" class="fancy-box tone-violet rounded-xl p-3" @click="closeMenu">Phiếu nhập</RouterLink>
                <RouterLink to="/reports" class="fancy-box tone-sky rounded-xl p-3" @click="closeMenu">Báo cáo</RouterLink>
                <RouterLink to="/categories" class="fancy-box tone-mint rounded-xl p-3" @click="closeMenu">Danh mục</RouterLink>
                <RouterLink to="/units" class="fancy-box tone-amber rounded-xl p-3" @click="closeMenu">Đơn vị tính</RouterLink>
                <RouterLink to="/suppliers" class="fancy-box tone-violet rounded-xl p-3" @click="closeMenu">Nhà cung cấp</RouterLink>
                <RouterLink to="/customers" class="fancy-box tone-rose rounded-xl p-3" @click="closeMenu">Khách hàng</RouterLink>
                <button class="col-span-2 rounded-xl bg-teal-600 px-4 py-3 text-left font-medium text-white" type="button" @click="handleLogout">Đăng xuất</button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.route-stage {
  position: relative;
  min-height: 100%;
}

.route-page {
  width: 100%;
}
</style>
