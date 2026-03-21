<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import { RouterView } from 'vue-router'
import ToastViewport from './components/ToastViewport.vue'
import { showToast } from './lib/toast'

function handleOffline(): void {
  showToast('Mất kết nối. Thử lại sau.', 'error')
}

function handleOnline(): void {
  showToast('Đã kết nối lại.', 'success')
}

onMounted(() => {
  window.addEventListener('offline', handleOffline)
  window.addEventListener('online', handleOnline)
})

onBeforeUnmount(() => {
  window.removeEventListener('offline', handleOffline)
  window.removeEventListener('online', handleOnline)
})
</script>

<template>
  <RouterView />
  <ToastViewport />
</template>

