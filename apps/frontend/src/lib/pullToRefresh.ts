import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'

type RefreshHandler = () => void | Promise<void>

const activeRefreshHandler = shallowRef<RefreshHandler | null>(null)
const activeRefreshLabel = ref('Kéo xuống để làm mới')

export function usePullToRefreshState() {
  return {
    activeRefreshHandler,
    activeRefreshLabel,
  }
}

export function setPullToRefreshHandler(handler: RefreshHandler, label = 'Kéo xuống để làm mới'): void {
  activeRefreshHandler.value = handler
  activeRefreshLabel.value = label
}

export function clearPullToRefreshHandler(handler?: RefreshHandler): void {
  if (!handler || activeRefreshHandler.value === handler) {
    activeRefreshHandler.value = null
    activeRefreshLabel.value = 'Kéo xuống để làm mới'
  }
}

export function usePullToRefresh(handler: RefreshHandler, label?: string): void {
  onMounted(() => {
    setPullToRefreshHandler(handler, label)
  })

  onBeforeUnmount(() => {
    clearPullToRefreshHandler(handler)
  })
}
