<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { House, ShoppingCart, Boxes, ClipboardList, Menu, X } from 'lucide-vue-next'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const isMenuOpen = ref(false)

const navItems = [
  { to: '/', label: 'Home', icon: House },
  { to: '/pos', label: 'POS', icon: ShoppingCart },
  { to: '/products', label: 'Sản phẩm', icon: Boxes },
  { to: '/orders', label: 'Đơn hàng', icon: ClipboardList },
]

async function handleLogout(): Promise<void> {
  await auth.logout()
  window.location.href = '/login'
}
</script>

<template>
  <div class="min-h-screen bg-surface text-ink">
    <div class="mx-auto flex min-h-screen w-full max-w-5xl flex-col bg-white/70 shadow-card backdrop-blur">
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

      <main class="flex-1 px-4 py-5 pb-24">
        <RouterView />
      </main>

      <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-black/10 bg-white/95 backdrop-blur">
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

      <transition name="sheet">
        <div v-if="isMenuOpen" class="fixed inset-0 z-50 bg-black/35" @click.self="isMenuOpen = false">
          <div class="absolute bottom-0 left-0 right-0 rounded-t-3xl bg-white p-5 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-base font-semibold">Menu nhanh</h2>
              <button
                class="rounded-full border border-black/10 p-2 text-ink/80"
                type="button"
                @click="isMenuOpen = false"
              >
                <X :size="18" />
              </button>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <RouterLink to="/" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Dashboard</RouterLink>
              <RouterLink to="/products" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Sản phẩm</RouterLink>
              <RouterLink to="/pos" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">POS bán hàng</RouterLink>
              <RouterLink to="/orders" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Đơn hàng</RouterLink>
              <RouterLink to="/categories" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Danh mục</RouterLink>
              <RouterLink to="/units" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Đơn vị tính</RouterLink>
              <RouterLink to="/suppliers" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Nhà cung cấp</RouterLink>
              <RouterLink to="/customers" class="rounded-xl border border-black/10 p-3" @click="isMenuOpen = false">Khách hàng</RouterLink>
              <button class="col-span-2 rounded-xl bg-clay px-4 py-3 text-left font-medium text-white" type="button" @click="handleLogout">
                Đăng xuất
              </button>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
  transition: all 0.22s ease;
}

.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}

.sheet-enter-from .absolute,
.sheet-leave-to .absolute {
  transform: translateY(24px);
}
</style>
