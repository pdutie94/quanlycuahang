<script setup>
import { computed, onMounted, onUnmounted, ref, useSlots } from 'vue';
import { RouterLink } from 'vue-router';
import { Plus, CirclePlus, LayoutGrid, Search, SlidersHorizontal, X } from '@lucide/vue';

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
const inputRef = ref(null);
const isStuck = ref(false);
const hasChips = computed(() => Boolean(slots.chips));
const FORM_HOST_ID = 'app-list-header-form-host';
let debounceTimer = null;

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
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
});

const onSubmit = () => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  emit('search');
};

const onInput = (event) => {
  emit('update:modelValue', event?.target?.value || '');
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  debounceTimer = setTimeout(() => {
    emit('search');
  }, 200);
};

const onClearKeyword = () => {
  if (debounceTimer) {
    clearTimeout(debounceTimer);
  }
  emit('update:modelValue', '');
  emit('search');
  inputRef.value?.focus();
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
            class="inline-flex w-10 h-10 items-center justify-center gap-2 rounded-xl bg-brand-600 text-white transition hover:bg-brand-700"
          >
            <Plus class="h-5 w-5" />
            <!-- <span>{{ createLabel }}</span> -->
          </RouterLink>
        </div>
      </div>
    </div>
  </Teleport>

  <Teleport to="#app-list-header-form-host">
    <form ref="formRef" :class="['app-list-header-form', { 'is-stuck': isStuck }]" @submit.prevent="onSubmit">
      <div class="app-content-wrap py-2">
        <div class="flex items-center gap-2">
          <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
              <Search class="h-4 w-4" />
            </span>
            <input
              ref="inputRef"
              :value="modelValue"
              type="search"
              :placeholder="searchPlaceholder"
              class="app-search-input h-10 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:border-brand-500"
              @input="onInput"
            />
            <button
              v-if="modelValue"
              type="button"
              class="absolute inset-y-0 right-3 inline-flex items-center justify-center text-slate-400 hover:text-slate-600"
              aria-label="Xóa từ khóa"
              @click="onClearKeyword"
            >
              <X class="h-4 w-4" />
            </button>
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

<style scoped>
.app-search-input::-webkit-search-cancel-button,
.app-search-input::-webkit-search-decoration,
.app-search-input::-webkit-search-results-button,
.app-search-input::-webkit-search-results-decoration {
  -webkit-appearance: none;
  appearance: none;
}

.app-search-input::-ms-clear,
.app-search-input::-ms-reveal {
  display: none;
  width: 0;
  height: 0;
}
</style>
