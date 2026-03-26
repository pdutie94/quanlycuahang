<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { LayoutGrid, Search, X } from 'lucide-vue-next'

type FilterOption = {
  key: string
  label: string
  count: number
}

const props = defineProps<{
  modelValue: string
  filterOptions: FilterOption[]
  activeFilter: string
  hasActiveFilter?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  submit: []
  'update:activeFilter': [value: string]
  openCategoryFilter: []
  clearAllFilters: []
  clearSearch: []
}>()

const isScrolled = ref(false)
const titleTeleportTarget = ref<string | null>(null)
const stickyTeleportTarget = ref<string | null>(null)
let stickyHostEl: HTMLElement | null = null

function updateScrolledState(): void {
  if (stickyHostEl) {
    isScrolled.value = stickyHostEl.getBoundingClientRect().top <= 0
    return
  }

  isScrolled.value = window.scrollY > 0
}

onMounted(() => {
  titleTeleportTarget.value = document.getElementById('app-title-host') ? '#app-title-host' : null
  stickyTeleportTarget.value = document.getElementById('app-sticky-host') ? '#app-sticky-host' : null
  stickyHostEl = document.getElementById('app-sticky-host')
  updateScrolledState()
  window.addEventListener('scroll', updateScrolledState, { passive: true })
  window.addEventListener('resize', updateScrolledState, { passive: true })
})

onUnmounted(() => {
  window.removeEventListener('scroll', updateScrolledState)
  window.removeEventListener('resize', updateScrolledState)
})

function onInput(event: Event): void {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

function onSubmit(): void {
  emit('submit')
}

function pickFilter(key: string): void {
  emit('update:activeFilter', key)
}

function openCategoryFilter(): void {
  emit('openCategoryFilter')
}

function clearSearch(): void {
  emit('clearSearch')
}

function clearAllFilters(): void {
  emit('clearAllFilters')
}
</script>

<template>
  <Teleport v-if="titleTeleportTarget" :to="titleTeleportTarget">
    <header class="app-content-wrap mb-3 px-4">
      <h2 class="text-2xl font-semibold leading-none text-slate-900">Sản phẩm</h2>
      <p class="mt-2 text-sm text-slate-500">Quản lý danh sách sản phẩm đang bán.</p>
    </header>
  </Teleport>

  <Teleport v-if="stickyTeleportTarget" :to="stickyTeleportTarget">
    <div
      class="py-2 transition-colors duration-200"
      :class="isScrolled ? 'border-b border-slate-200 bg-white/95 backdrop-blur-sm' : 'border-b border-transparent bg-transparent'"
    >
      <div class="app-content-wrap px-4">
        <form class="flex items-center gap-2" @submit.prevent="onSubmit">
            <label class="relative block min-w-0 flex-1">
              <Search :size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                :value="props.modelValue"
                type="text"
                class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-14 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100"
                placeholder="Tìm kiếm theo tên, SKU..."
                @input="onInput"
              />

              <button
                v-if="props.modelValue"
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500"
                @click="clearSearch"
              >
                Xóa
              </button>
            </label>

            <button
              type="button"
              class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600"
              aria-label="Lọc danh mục"
              @click="openCategoryFilter"
            >
              <LayoutGrid :size="18" />
            </button>
        </form>

        <div class="mt-2 flex gap-2 overflow-x-auto pb-0.5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
          <button
            v-if="props.hasActiveFilter"
            type="button"
            class="inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-xl border border-red-200 bg-red-100 text-red-600"
            aria-label="Xóa bộ lọc"
            @click="clearAllFilters"
          >
            <X :size="14" />
          </button>

          <button
            v-for="filter in props.filterOptions"
            :key="filter.key"
            type="button"
            class="shrink-0 rounded-xl border px-3 py-1 text-sm font-medium transition"
            :class="props.activeFilter === filter.key ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
            @click="pickFilter(filter.key)"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <div v-if="!titleTeleportTarget || !stickyTeleportTarget">
    <header class="mb-3 flex items-start justify-between gap-3">
      <div>
        <h2 class="text-2xl font-semibold leading-none text-slate-900">Sản phẩm</h2>
        <p class="mt-2 text-sm text-slate-500">Quản lý danh sách sản phẩm đang bán.</p>
      </div>
    </header>

    <div
      class="sticky top-0 z-20 py-2 transition-colors duration-200"
      :class="isScrolled ? 'border-b border-slate-200 bg-white/95 backdrop-blur-sm' : 'border-b border-transparent bg-transparent'"
    >
      <div>
        <form class="flex items-center gap-2" @submit.prevent="onSubmit">
          <label class="relative block min-w-0 flex-1">
            <Search :size="18" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              :value="props.modelValue"
              type="text"
              class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-14 text-sm text-slate-700 outline-none transition focus:border-teal-300 focus:ring-2 focus:ring-teal-100"
              placeholder="Tìm kiếm theo tên, SKU..."
              @input="onInput"
            />

            <button
              v-if="props.modelValue"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-slate-500"
              @click="clearSearch"
            >
              Xóa
            </button>
          </label>

          <button
            type="button"
            class="inline-flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600"
            aria-label="Lọc danh mục"
            @click="openCategoryFilter"
          >
            <LayoutGrid :size="18" />
          </button>
        </form>

        <div class="mt-2 flex gap-2 overflow-x-auto pb-0.5 [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
          <button
            v-if="props.hasActiveFilter"
            type="button"
            class="inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600"
            aria-label="Xóa bộ lọc"
            @click="clearAllFilters"
          >
            <X :size="14" />
          </button>

          <button
            v-for="filter in props.filterOptions"
            :key="filter.key"
            type="button"
            class="shrink-0 rounded-xl border px-3 py-1 text-sm font-medium transition"
            :class="props.activeFilter === filter.key ? 'border-teal-600 bg-teal-600 text-white' : 'border-slate-200 bg-white text-slate-700'"
            @click="pickFilter(filter.key)"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
