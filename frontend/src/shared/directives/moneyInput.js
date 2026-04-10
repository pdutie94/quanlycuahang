// Vue 3 directive: v-money-input
// Tự động format tiền khi blur, giữ nguyên khi nhập
import { numberFormatter } from '../composables/useFormat';

export default {
  mounted(el) {
    el.inputHandler = (e) => {
      // Chỉ cho phép số và dấu chấm
      let val = e.target.value.replace(/[^\d.]/g, '');
      // Không cho nhiều dấu chấm
      const parts = val.split('.');
      if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
      e.target.value = val;
      // Sync v-model
      el.dispatchEvent(new Event('input', { bubbles: true }));
    };
    el.blurHandler = (e) => {
      let val = e.target.value;
      if (!val) return;
      // Format số
      const num = Number(val.replace(/[^\d.]/g, ''));
      if (!isNaN(num) && num > 0) {
        e.target.value = numberFormatter.format(Number(val.replace(/[^\d.]/g, '')));
        el.dispatchEvent(new Event('input', { bubbles: true }));
      }
    };
    el.addEventListener('input', el.inputHandler);
    el.addEventListener('blur', el.blurHandler);
  },
  unmounted(el) {
    el.removeEventListener('input', el.inputHandler);
    el.removeEventListener('blur', el.blurHandler);
  }
};
