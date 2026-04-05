<script setup>
import { computed, onMounted, onUnmounted, ref, useSlots } from 'vue';
import { RouterLink } from 'vue-router';
import { ChevronLeft } from '@lucide/vue';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  backTo: {
    type: [String, Object],
    required: true
  },
  backLabel: {
    type: String,
    default: 'Quay lại'
  }
});

const slots = useSlots();
const formRef = ref(null);
const menuRef = ref(null);
const showMenu = ref(false);
const isStuck = ref(false);
const FORM_HOST_ID = 'app-list-header-form-host';
const hasActions = computed(() => Boolean(slots.actions));

const closeMenu = () => {
  showMenu.value = false;
};

const toggleMenu = () => {
  showMenu.value = !showMenu.value;
};

const checkStickState = () => {
  const formHost = document.getElementById(FORM_HOST_ID);
  if (!formRef.value || !formHost) {
    return;
  }

  const rect = formRef.value.getBoundingClientRect();
  const nextState = rect.top <= 0;
  isStuck.value = nextState;
  formHost.classList.toggle('is-stuck', nextState);
};

const onDocumentClick = (event) => {
  if (!showMenu.value || !menuRef.value) {
    return;
  }

  if (!menuRef.value.contains(event.target)) {
    closeMenu();
  }
};

const onDocumentKeydown = (event) => {
  if (event.key === 'Escape') {
    closeMenu();
  }
};

onMounted(() => {
  window.addEventListener('scroll', checkStickState, { passive: true });
  window.addEventListener('resize', checkStickState, { passive: true });
  document.addEventListener('click', onDocumentClick);
  document.addEventListener('keydown', onDocumentKeydown);
  checkStickState();
});

onUnmounted(() => {
  window.removeEventListener('scroll', checkStickState);
  window.removeEventListener('resize', checkStickState);
  document.removeEventListener('click', onDocumentClick);
  document.removeEventListener('keydown', onDocumentKeydown);
  document.getElementById(FORM_HOST_ID)?.classList.remove('is-stuck');
});
</script>

<template>
  <Teleport to="#app-list-header-form-host">
    <div ref="formRef" :class="['app-list-header-form', { 'is-stuck': isStuck }]">
      <div class="app-content-wrap py-2">
        <div class="flex items-center justify-between gap-3">
          <div class="flex min-w-0 items-center gap-1">
            <RouterLink
              :to="backTo"
              class="inline-flex shrink-0 items-center justify-center text-slate-600 transition hover:text-slate-900"
              :aria-label="backLabel"
            >
              <ChevronLeft class="h-5.5 w-5.5" />
            </RouterLink>
            <div class="min-w-0">
              <h1 class="truncate font-display text-lg font-semibold text-slate-900 md:text-xl">{{ title }}</h1>
              <p v-if="subtitle" class="truncate text-sm text-slate-500">{{ subtitle }}</p>
            </div>
          </div>

          <div v-if="hasActions" ref="menuRef" class="relative shrink-0 flex items-center">
            <button
              type="button"
              class="inline-flex items-center justify-center text-slate-600 transition hover:text-slate-900"
              aria-label="Tùy chọn"
              @click.stop="toggleMenu"
            >
              <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <circle cx="10" cy="4.5" r="1.5" />
                <circle cx="10" cy="10" r="1.5" />
                <circle cx="10" cy="15.5" r="1.5" />
              </svg>
            </button>

            <div
              v-if="showMenu"
              class="absolute right-0 top-full z-40 mt-3 w-52 rounded-2xl border border-slate-200 bg-white p-1.5"
            >
              <div class="flex flex-col gap-1">
                <slot name="actions" :closeMenu="closeMenu" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
:slotted(.detail-header-menu-item) {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
  border-radius: 0.75rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1;
  color: rgb(51 65 85);
  transition: background-color 0.2s, color 0.2s;
}

:slotted(.detail-header-menu-item:hover) {
  background-color: rgb(248 250 252);
}

:slotted(.detail-header-menu-item-amber) {
  color: rgb(180 83 9);
}

:slotted(.detail-header-menu-item-amber:hover) {
  background-color: rgb(255 251 235);
}

:slotted(.detail-header-menu-item-rose) {
  color: rgb(225 29 72);
}

:slotted(.detail-header-menu-item-rose:hover) {
  background-color: rgb(255 241 242);
}
</style>