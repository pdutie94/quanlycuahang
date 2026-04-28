// Vue 3 directive: v-money-input
// Tự động format tiền khi blur, giữ nguyên khi nhập
import { numberFormatter } from '../composables/useFormat';

function formatMoneyInput(val: string | number): string {
  const raw = String(val).replace(/\D/g, '');
  if (!raw) return '';
  const num = Number(raw);
  return !isNaN(num) && num > 0 ? numberFormatter.format(num) : '';
}

export default {
  mounted(el: HTMLInputElement & { inputHandler?: EventListener }) {
    // Always show numeric keyboard on mobile
    el.setAttribute('inputmode', 'numeric');

    // Format on mount
    if (el.value) {
      el.value = formatMoneyInput(el.value);
    }

    el.inputHandler = ((e: Event) => {
      const target = e.target as HTMLInputElement;
      if (!target) return;
      // Only allow digits
      let raw = target.value.replace(/\D/g, '');
      // Format with thousands separator
      let formatted = raw ? numberFormatter.format(Number(raw)) : '';
      if (target.value !== formatted) {
        target.value = formatted;
        el.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }) as EventListener;

    el.addEventListener('input', el.inputHandler);
  },
  unmounted(el: HTMLInputElement & { inputHandler?: EventListener }) {
    if (el.inputHandler) {
      el.removeEventListener('input', el.inputHandler);
    }
  }
};
