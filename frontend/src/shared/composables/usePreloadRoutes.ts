import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

interface PreloadOptions {
  routes: string[] // Route names to preload
  delay?: number // Delay before starting preload (ms)
  concurrency?: number // Number of routes to preload concurrently
  priority?: 'high' | 'low' // Preload priority
}

interface PreloadState {
  isPreloading: boolean
  progress: number
  loaded: string[]
  failed: string[]
}

/**
 * Composable for preloading critical route components after login
 * Improves perceived performance by loading components before user navigates
 *
 * @example
 * // In App.vue or after login
 * const { preload, state } = usePreloadRoutes()
 *
 * onMounted(() => {
 *   // Preload critical routes 2 seconds after login
 *   setTimeout(() => {
 *     preload({
 *       routes: ['products', 'orders', 'pos', 'customers'],
 *       delay: 0,
 *       concurrency: 2
 *     })
 *   }, 2000)
 * })
 */
export function usePreloadRoutes() {
  const router = useRouter()
  const state = ref<PreloadState>({
    isPreloading: false,
    progress: 0,
    loaded: [],
    failed: []
  })

  /**
   * Preload a single route component
   */
  const preloadRoute = async (routeName: string): Promise<boolean> => {
    try {
      const route = router.resolve({ name: routeName })

      if (!route.matched.length) {
        console.warn(`[usePreloadRoutes] Route "${routeName}" not found`)
        return false
      }

      // Get the component from the matched route
      const component = route.matched[0]?.components?.default

      if (!component) {
        console.warn(`[usePreloadRoutes] No component for route "${routeName}"`)
        return false
      }

      // If it's an async component (lazy loaded), execute it
      if (typeof component === 'function') {
        await (component as () => Promise<any>)()
      }

      return true
    } catch (error) {
      console.error(`[usePreloadRoutes] Failed to preload "${routeName}":`, error)
      return false
    }
  }

  /**
   * Preload multiple routes with concurrency control
   */
  const preload = async (options: PreloadOptions): Promise<void> => {
    const { routes, delay = 0, concurrency = 2 } = options

    if (!routes.length) return

    // Wait for initial delay (e.g., after login animation)
    if (delay > 0) {
      await new Promise(resolve => setTimeout(resolve, delay))
    }

    // Only preload if connection is good (save data on slow connections)
    const connection = (navigator as any).connection
    if (connection) {
      const saveData = connection.saveData
      const effectiveType = connection.effectiveType

      if (saveData || effectiveType === '2g' || effectiveType === 'slow-2g') {
        console.log('[usePreloadRoutes] Skipping preload due to slow connection')
        return
      }
    }

    state.value.isPreloading = true
    state.value.progress = 0
    state.value.loaded = []
    state.value.failed = []

    // Process routes in chunks based on concurrency
    const queue = [...routes]

    while (queue.length > 0) {
      const batch = queue.splice(0, concurrency)

      const results = await Promise.allSettled(
        batch.map(async (routeName) => {
          const success = await preloadRoute(routeName)
          return { routeName, success }
        })
      )

      results.forEach((result) => {
        if (result.status === 'fulfilled') {
          const { routeName, success } = result.value
          if (success) {
            state.value.loaded.push(routeName)
          } else {
            state.value.failed.push(routeName)
          }
        } else {
          state.value.failed.push(batch[results.indexOf(result)])
        }
      })

      // Update progress
      state.value.progress = Math.round(
        ((state.value.loaded.length + state.value.failed.length) / routes.length) * 100
      )

      // Small delay between batches to not block main thread
      await new Promise(resolve => setTimeout(resolve, 100))
    }

    state.value.isPreloading = false

    console.log('[usePreloadRoutes] Preload complete:', {
      loaded: state.value.loaded.length,
      failed: state.value.failed.length
    })
  }

  /**
   * Preload critical routes immediately after login
   * This is the main entry point for post-login preloading
   */
  const preloadCriticalRoutes = async (): Promise<void> => {
    const criticalRoutes = [
      'products', // Product list - most visited
      'orders',   // Order list - frequently accessed
      'pos',      // POS - needs to be fast
      'customers' // Customer list - often needed
    ]

    await preload({
      routes: criticalRoutes,
      delay: 2000, // Wait 2s after login
      concurrency: 2,
      priority: 'high'
    })
  }

  /**
   * Preload routes on hover (for faster navigation on user intent)
   */
  const preloadOnHover = (routeName: string, delay = 150): (() => void) => {
    let timeoutId: number | null = null

    const handleMouseEnter = () => {
      timeoutId = window.setTimeout(() => {
        preloadRoute(routeName).catch(() => {
          // Silent fail on hover preload
        })
      }, delay)
    }

    const handleMouseLeave = () => {
      if (timeoutId) {
        clearTimeout(timeoutId)
        timeoutId = null
      }
    }

    // Return cleanup function
    return () => {
      if (timeoutId) {
        clearTimeout(timeoutId)
      }
    }
  }

  return {
    preload,
    preloadCriticalRoutes,
    preloadOnHover,
    preloadRoute,
    state
  }
}

export default usePreloadRoutes
