import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import moneyInput from './shared/directives/moneyInput';
import { initGlobalScrollLock } from './shared/composables/useScrollLock';

const root = document.getElementById('spa-root');

if (root) {
  const app = createApp(App);
  app.use(router);
  app.directive('money-input', moneyInput);
  app.mount(root);
  (window as any).__SPA_ROUTER__ = router;
  
  // Initialize global scroll lock for all modals
  initGlobalScrollLock();
}
