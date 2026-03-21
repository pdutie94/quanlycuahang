import { ref } from 'vue'

export type ToastTone = 'default' | 'success' | 'warning' | 'error'

export type ToastItem = {
  id: number
  message: string
  tone: ToastTone
}

const toasts = ref<ToastItem[]>([])
let toastSeed = 0

export function useToasts() {
  return toasts
}

export function dismissToast(id: number): void {
  toasts.value = toasts.value.filter((item) => item.id !== id)
}

export function showToast(message: string, tone: ToastTone = 'default', duration = 2600): number {
  const duplicate = toasts.value.find((item) => item.message === message && item.tone === tone)
  if (duplicate) {
    return duplicate.id
  }

  const id = ++toastSeed
  toasts.value = [...toasts.value, { id, message, tone }]

  window.setTimeout(() => {
    dismissToast(id)
  }, duration)

  return id
}
