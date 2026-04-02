import { createApp } from 'vue';
import App from './App.vue';
import { router } from './router';

const root = document.getElementById('spa-root');

if (root) {
  const app = createApp(App);
  app.use(router);
  app.mount(root);

  window.__SPA_ROUTER__ = router;
}
