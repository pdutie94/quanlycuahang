import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { createPinia } from 'pinia'
import router from './router'
import { setNavigateHandler } from './lib/navigation'

const app = createApp(App)
app.use(createPinia())
app.use(router)
setNavigateHandler((to) => router.push(to))

if (import.meta.env.PROD && 'serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		void navigator.serviceWorker.register('/sw.js')
	})
}

app.mount('#app')
