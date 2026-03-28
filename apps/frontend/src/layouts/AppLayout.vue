<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRouter, useRoute } from 'vue-router'
import { House, ShoppingCart, Package, PieChart, Menu, X, UserRound, LogOut, Users, ClipboardList, FileBarChart2, Layers, Tag, Ruler, Truck, User2 } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'
import { useUiStore } from '../stores/ui'
import AppModal from '../components/AppModal.vue'


// Danh sách các route cần show app-title-host
const showTitleHostRoutes = [
  '/products',
  '/customers',
  '/purchases',
  '/orders',
  '/suppliers',
]
const hideStickyHostRoutes = [
  '/',
  '/login',
  '/dashboard',
]

const shouldHideStickyHost = computed(() => hideStickyHostRoutes.includes(route.path))
const shouldShowTitleHost = computed(() => showTitleHostRoutes.includes(route.path))

const auth = useAuthStore()
const ui = useUiStore()
const router = useRouter()
const route = useRoute()
const isMenuOpen = ref(false)
const transitionName = computed(() => ui.transitionName)


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
  isMenuOpen.value = false
}
</script>

<template>
  <div class="app-shell min-h-screen bg-slate-50 text-slate-900">
    <header class="app-topbar border-b border-slate-200 bg-white py-2">
      <div class="app-content-wrap flex items-center justify-between gap-3 px-4">
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
      <main class="relative flex-1 pb-24">
        <div v-if="shouldShowTitleHost" :class="'pt-4'" id="app-title-host"></div>
        <div v-if="!shouldHideStickyHost" id="app-sticky-host" class="sticky top-0 z-20 mb-3"></div>
        <div class="app-content-wrap route-stage px-4">
          <RouterView v-slot="{ Component, route }">
            <transition :name="transitionName" mode="out-in">
              <component :is="Component" :key="route.fullPath" class="route-page" />
            </transition>
          </RouterView>
        </div>
      </main>

      <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white">
        <div class="app-content-wrap grid w-full grid-cols-5 p-1.5">
          <RouterLink
            v-for="item in navItems"
            :key="item.label"
            :to="item.to"
            :class="route.path === item.to ? 'fancy-box tone-mint text-teal-700' : 'text-slate-500'"
            class="flex h-full flex-col items-center justify-center rounded-xl p-1.5 text-xs font-medium transition duration-200 hover:bg-slate-50 active:scale-[0.98]"
          >
            <component :is="item.icon" :size="18" />
              <span class="block w-full whitespace-nowrap overflow-hidden text-ellipsis text-center">{{ item.label }}</span>
          </RouterLink>
          <button
            class="flex h-full flex-col items-center justify-center rounded-xl p-1.5 text-xs font-medium text-slate-500 transition duration-200 hover:bg-slate-50"
            type="button"
            @click="isMenuOpen = true"
          >
            <Menu :size="18" />
              <span class="block w-full whitespace-nowrap overflow-hidden text-ellipsis text-center">Menu</span>
          </button>
        </div>
      </nav>

      <AppModal :open="isMenuOpen" title="Menu" @close="closeMenu">
        <div class="menu-body flex-1 overflow-y-auto">
          <div class="grid grid-cols-2 gap-2">
            <RouterLink to="/" class="fancy-box tone-sky rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <House :size="18" class="text-sky-500" />
              <span class="flex items-center leading-none">Dashboard</span>
            </RouterLink>
            <RouterLink to="/products" class="fancy-box tone-lime rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Package :size="18" class="text-lime-500" />
              <span class="flex items-center leading-none">Sản phẩm</span>
            </RouterLink>
            <RouterLink to="/pos" class="fancy-box tone-mint rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <ShoppingCart :size="18" class="text-emerald-500" />
              <span class="flex items-center leading-none">POS bán hàng</span>
            </RouterLink>
            <RouterLink to="/orders" class="fancy-box tone-amber rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <ClipboardList :size="18" class="text-amber-500" />
              <span class="flex items-center leading-none">Đơn hàng</span>
            </RouterLink>
            <RouterLink to="/purchases" class="fancy-box tone-violet rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Layers :size="18" class="text-violet-500" />
              <span class="flex items-center leading-none">Phiếu nhập</span>
            </RouterLink>
            <RouterLink to="/reports" class="fancy-box tone-sky rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <FileBarChart2 :size="18" class="text-sky-500" />
              <span class="flex items-center leading-none">Báo cáo</span>
            </RouterLink>
            <RouterLink to="/categories" class="fancy-box tone-mint rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Tag :size="18" class="text-emerald-500" />
              <span class="flex items-center leading-none">Danh mục</span>
            </RouterLink>
            <RouterLink to="/units" class="fancy-box tone-amber rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Ruler :size="18" class="text-amber-500" />
              <span class="flex items-center leading-none">Đơn vị tính</span>
            </RouterLink>
            <RouterLink to="/suppliers" class="fancy-box tone-violet rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Truck :size="18" class="text-violet-500" />
              <span class="flex items-center leading-none">Nhà cung cấp</span>
            </RouterLink>
            <RouterLink to="/customers" class="fancy-box tone-rose rounded-xl p-3 flex items-center gap-2" @click="closeMenu">
              <Users :size="18" class="text-rose-500" />
              <span class="flex items-center leading-none">Khách hàng</span>
            </RouterLink>
          </div>
        </div>
      </AppModal>
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
