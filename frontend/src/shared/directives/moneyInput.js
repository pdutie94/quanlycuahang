// Vue 3 directive: v-money-input
// Tự động format tiền khi blur, giữ nguyên khi nhập
import { numberFormatter } from '../composables/useFormat';

function formatMoneyInput(val) {
  const raw = String(val).replace(/\D/g, '');
  if (!raw) return '';
  const num = Number(raw);
  return !isNaN(num) && num > 0 ? numberFormatter.format(num) : '';
}

export default {
  mounted(el) {
    // Always show numeric keyboard on mobile
    el.setAttribute('inputmode', 'numeric');
    el.setAttribute('pattern', '[0-9]*(\\.[0-9]+)?');

    // Format on mount
    if (el.value) {
      el.value = formatMoneyInput(el.value);
    }

    el.inputHandler = (e) => {
      // Only allow digits
      let raw = e.target.value.replace(/\D/g, '');
      // Format with thousands separator
      let formatted = raw ? numberFormatter.format(Number(raw)) : '';
      if (e.target.value !== formatted) {
        e.target.value = formatted;
        el.dispatchEvent(new Event('input', { bubbles: true }));
      }
    };

    el.addEventListener('input', el.inputHandler);
  },
  unmounted(el) {
    el.removeEventListener('input', el.inputHandler);
  }
};
