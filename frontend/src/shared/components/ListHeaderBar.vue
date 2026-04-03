<script setup>
import { computed, onMounted, onUnmounted, ref, useSlots } from 'vue';
import { RouterLink } from 'vue-router';
import { CirclePlus, LayoutGrid, Search, SlidersHorizontal } from '@lucide/vue';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  modelValue: {
    type: String,
    default: ''
  },
  searchPlaceholder: {
    type: String,
    default: ''
  },
  createTo: {
    type: [String, Object],
    default: null
  },
  createLabel: {
    type: String,
    default: 'Tạo mới'
  },
  filterType: {
    type: String,
    default: '',
    validator: (value) => ['', 'filter', 'grid'].includes(value)
  },
  chipsClass: {
    type: String,
    default: 'mt-2 flex items-center gap-2 overflow-x-auto whitespace-nowrap text-sm'
  }
});

const emit = defineEmits(['update:modelValue', 'search', 'filter-click']);
const slots = useSlots();

const formRef = ref(null);
const isStuck = ref(false);
const hasChips = computed(() => Boolean(slots.chips));
const FORM_HOST_ID = 'app-list-header-form-host';

const checkStickState = () => {
  const formHost = document.getElementById(FORM_HOST_ID);
  if (!formRef.value || !formHost) return;
  const rect = formRef.value.getBoundingClientRect();
  const nextState = rect.top <= 0;
  isStuck.value = nextState;
  formHost.classList.toggle('is-stuck', nextState);
};

onMounted(() => {
  window.addEventListener('scroll', checkStickState, { passive: true });
  window.addEventListener('resize', checkStickState, { passive: true });
  checkStickState();
});

onUnmounted(() => {
  window.removeEventListener('scroll', checkStickState);
  window.removeEventListener('resize', checkStickState);
  const formHost = document.getElementById(FORM_HOST_ID);
  formHost?.classList.remove('is-stuck');
});

const onSubmit = () => {
  emit('search');
};

const onInput = (event) => {
  emit('update:modelValue', event?.target?.value || '');
};
</script>

<template>
  <Teleport to="#app-list-header-title-host">
    <div class="app-list-header-title">
      <div class="app-content-wrap py-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h1 class="font-display text-xl font-bold text-slate-900 md:text-2xl">{{ title }}</h1>
            <p v-if="subtitle" class="mt-1 text-sm leading-6 text-slate-500">{{ subtitle }}</p>
          </div>
          <RouterLink
            v-if="createTo"
            :to="createTo"
            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-2 text-white transition hover:bg-brand-700"
          >
            <CirclePlus class="h-5 w-5" />
            <span>{{ createLabel }}</span>
          </RouterLink>
        </div>
      </div>
    </div>
  </Teleport>

  <Teleport to="#app-list-header-form-host">
    <form ref="formRef" :class="['app-list-header-form', { 'is-stuck': isStuck }]" @submit.prevent="onSubmit">
      <div class="app-content-wrap py-3">
        <div class="flex items-center gap-2">
          <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
              <Search class="h-4 w-4" />
            </span>
            <input
              :value="modelValue"
              type="search"
              :placeholder="searchPlaceholder"
              class="h-10 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:border-brand-500"
              @input="onInput"
            />
          </div>
          <button
            v-if="filterType"
            type="button"
            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
            @click="$emit('filter-click')"
          >
            <SlidersHorizontal v-if="filterType === 'filter'" class="h-4 w-4" />
            <LayoutGrid v-else class="h-5 w-5" />
          </button>
        </div>

        <div v-if="hasChips" :class="chipsClass">
          <slot name="chips" />
        </div>
      </div>
    </form>
  </Teleport>
</template>
