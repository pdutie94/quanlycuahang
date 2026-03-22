import { defineStore } from 'pinia'
import { ref } from 'vue'

type ToastTone = 'neutral' | 'error'

export interface ToastItem {
  id: number
  message: string
  tone: ToastTone
}

let toastSeed = 1

export const useUiStore = defineStore('ui', () => {
  const toasts = ref<ToastItem[]>([])
  const isOffline = ref(false)
  const transitionName = ref<'slide-left' | 'slide-right'>('slide-left')

  function pushToast(message: string, tone: ToastTone = 'neutral', ttl = 2600): void {
    const id = toastSeed++
    toasts.value.push({ id, message, tone })

    window.setTimeout(() => {
      dismissToast(id)
    }, ttl)
  }

  function dismissToast(id: number): void {
    toasts.value = toasts.value.filter((item) => item.id !== id)
  }

  function setOfflineState(value: boolean): void {
    if (isOffline.value === value) {
      return
    }

    isOffline.value = value
    if (value) {
      pushToast('Mất kết nối. Thử lại sau.', 'error', 3000)
    } else {
      pushToast('Đã kết nối lại.', 'neutral', 2200)
    }
  }

  function setTransitionName(value: 'slide-left' | 'slide-right'): void {
    transitionName.value = value
  }

  return {
    toasts,
    isOffline,
    transitionName,
    pushToast,
    dismissToast,
    setOfflineState,
    setTransitionName,
  }
})
