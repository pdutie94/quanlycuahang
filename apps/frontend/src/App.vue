<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import AppToastHost from './components/AppToastHost.vue'
import { useUiStore } from './stores/ui'

const ui = useUiStore()

function handleOffline(): void {
  ui.setOfflineState(true)
}

function handleOnline(): void {
  ui.setOfflineState(false)
}

function handleApiOffline(): void {
  ui.pushToast('API tạm thời không phản hồi. Thử lại sau.', 'error', 3000)
}

onMounted(() => {
  ui.setOfflineState(!window.navigator.onLine)
  window.addEventListener('offline', handleOffline)
  window.addEventListener('online', handleOnline)
  window.addEventListener('app:network-error', handleApiOffline)
})

onBeforeUnmount(() => {
  window.removeEventListener('offline', handleOffline)
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('app:network-error', handleApiOffline)
})
</script>

<template>
  <RouterView />
  <AppToastHost />
</template>
