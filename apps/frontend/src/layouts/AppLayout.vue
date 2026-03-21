<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { House, ShoppingCart, Boxes, ClipboardList, Menu } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { usePullToRefreshState } from '../lib/pullToRefresh'
import BottomSheet from '../components/BottomSheet.vue'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const isMenuOpen = ref(false)
const previousDepth = ref(Number(route.meta.depth ?? 0))
const transitionName = ref('slide-left')
const scrollContainer = ref<HTMLElement | null>(null)
const touchStartY = ref(0)
const pullDistance = ref(0)
const isPulling = ref(false)
const isRefreshing = ref(false)
const { activeRefreshHandler, activeRefreshLabel } = usePullToRefreshState()

const navItems = [
  { to: '/', label: 'Home', icon: House },
  { to: '/pos', label: 'POS', icon: ShoppingCart },
  { to: '/products', label: 'Sản phẩm', icon: Boxes },
  { to: '/reports', label: 'Báo cáo', icon: ClipboardList },
]

const menuItems = [
  { to: '/', label: 'Dashboard' },
  { to: '/products', label: 'Sản phẩm' },
  { to: '/pos', label: 'POS bán hàng' },
  { to: '/orders', label: 'Đơn hàng' },
  { to: '/purchases', label: 'Phiếu nhập' },
  { to: '/reports', label: 'Báo cáo' },
  { to: '/categories', label: 'Danh mục' },
  { to: '/units', label: 'Đơn vị tính' },
  { to: '/suppliers', label: 'Nhà cung cấp' },
  { to: '/customers', label: 'Khách hàng' },
]

const canPullToRefresh = computed(() => Boolean(route.meta.pullToRefresh && activeRefreshHandler.value))
const contentTransform = computed(() => ({
  transform: `translateY(${isRefreshing.value ? 52 : pullDistance.value}px)`,
}))
watch(
  () => route.fullPath,
  async () => {
    const nextDepth = Number(route.meta.depth ?? 0)
    transitionName.value = nextDepth < previousDepth.value ? 'slide-right' : 'slide-left'
    previousDepth.value = nextDepth
    isMenuOpen.value = false
    pullDistance.value = 0
    isPulling.value = false
    await nextTick()
    scrollContainer.value?.scrollTo({ top: 0, behavior: 'auto' })
  },
)

function closeMenu(): void {
  isMenuOpen.value = false
}

async function handleLogout(): Promise<void> {
  await auth.logout()
  await router.push('/login')
}

function handleTouchStart(event: TouchEvent): void {
  if (!canPullToRefresh.value || isRefreshing.value || (scrollContainer.value?.scrollTop ?? 0) > 0) {
    return
  }

  touchStartY.value = event.touches[0]?.clientY ?? 0
  isPulling.value = true
}

function handleTouchMove(event: TouchEvent): void {
  if (!isPulling.value) {
    return
  }

  const currentY = event.touches[0]?.clientY ?? 0
  const delta = currentY - touchStartY.value
  if (delta <= 0) {
    pullDistance.value = 0
    return
  }

  pullDistance.value = Math.min(88, delta * 0.42)
}

async function handleTouchEnd(): Promise<void> {
  if (!isPulling.value) {
    return
  }

  isPulling.value = false
  const shouldRefresh = pullDistance.value >= 56 && activeRefreshHandler.value

  if (shouldRefresh) {
    isRefreshing.value = true
    pullDistance.value = 52
    try {
      await activeRefreshHandler.value?.()
    } finally {
      isRefreshing.value = false
    }
  }

  pullDistance.value = 0
}

</script>

<template>
  <div class="min-h-screen overflow-x-hidden bg-surface text-ink">
    <div class="mx-auto flex min-h-screen w-full max-w-5xl flex-col overflow-x-hidden bg-white/70 shadow-card backdrop-blur">
      <header class="sticky top-0 z-40 border-b border-black/5 bg-white/90 px-4 py-3 backdrop-blur">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-pine/70">Admin Panel</p>
            <h1 class="text-lg font-semibold">Đại lý Đức Nam</h1>
          </div>
          <button
            class="rounded-full border border-black/10 p-2 text-ink transition hover:bg-black/5"
            type="button"
            @click="isMenuOpen = true"
          >
            <Menu :size="20" />
          </button>
        </div>
      </header>

      <main class="relative flex-1 overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-3 z-30 flex justify-center px-4">
          <div
            class="rounded-full border border-black/10 bg-white/90 px-3 py-1 text-xs font-medium text-ink/65 shadow-sm backdrop-blur transition"
            :class="pullDistance > 0 || isRefreshing ? 'opacity-100' : 'opacity-0'"
          >
            {{ isRefreshing ? 'Đang làm mới...' : activeRefreshLabel }}
          </div>
        </div>

        <div
          ref="scrollContainer"
          class="h-full overflow-x-hidden overflow-y-auto px-4 py-5 pb-24"
          @touchend="handleTouchEnd"
          @touchmove="handleTouchMove"
          @touchstart="handleTouchStart"
        >
          <div class="relative min-h-full overflow-x-hidden transition-transform duration-200 ease-out" :style="contentTransform">
            <RouterView v-slot="{ Component, route: currentRoute }">
              <transition :name="transitionName" mode="out-in">
                <component :is="Component" :key="currentRoute.fullPath" class="route-panel" />
              </transition>
            </RouterView>
          </div>
        </div>
      </main>

      <nav class="fixed inset-x-0 bottom-0 z-50 min-h-14 border-t border-black/10 bg-white/95 backdrop-blur">
        <div class="mx-auto grid w-full max-w-5xl grid-cols-5 gap-1 px-2 py-2">
          <RouterLink
            v-for="item in navItems"
            :key="item.label"
            :to="item.to"
            class="flex flex-col items-center justify-center rounded-xl py-2 text-[11px] font-medium text-ink/70 transition hover:bg-black/5"
            active-class="bg-pine/10 text-pine"
          >
            <component :is="item.icon" :size="18" />
            <span>{{ item.label }}</span>
          </RouterLink>
          <button
            class="flex flex-col items-center justify-center rounded-xl py-2 text-[11px] font-medium text-ink/70 transition hover:bg-black/5"
            type="button"
            @click="isMenuOpen = true"
          >
            <Menu :size="18" />
            <span>Menu</span>
          </button>
        </div>
      </nav>

      <BottomSheet v-model="isMenuOpen" title="Menu nhanh">
        <div class="grid grid-cols-2 gap-2">
          <RouterLink v-for="item in menuItems" :key="item.to" :to="item.to" class="rounded-xl border border-black/10 p-3" @click="closeMenu">
            {{ item.label }}
          </RouterLink>
          <button class="col-span-2 rounded-xl bg-clay px-4 py-3 text-left font-medium text-white" type="button" @click="handleLogout">
            Đăng xuất
          </button>
        </div>
      </BottomSheet>
    </div>
  </div>
</template>

<style scoped>
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active,
:deep(.slide-left-enter-active),
:deep(.slide-left-leave-active),
:deep(.slide-right-enter-active),
:deep(.slide-right-leave-active) {
  transition: transform 0.25s ease, opacity 0.25s ease;
  width: 100%;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
  position: absolute;
  inset: 0;
}

.slide-left-enter-from,
.slide-right-leave-to,
:deep(.slide-left-enter-from),
:deep(.slide-right-leave-to) {
  opacity: 0.35;
  transform: translateX(100%);
}

.slide-left-leave-to,
.slide-right-enter-from,
:deep(.slide-left-leave-to),
:deep(.slide-right-enter-from) {
  opacity: 0.35;
  transform: translateX(-28%);
}

:deep(.route-panel) {
  width: 100%;
  min-height: 100%;
}
</style>
