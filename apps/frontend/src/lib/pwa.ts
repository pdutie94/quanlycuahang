export async function registerServiceWorker(): Promise<void> {
  if (!('serviceWorker' in navigator)) {
    return
  }

  window.addEventListener('load', async () => {
    try {
      await navigator.serviceWorker.register('/admin/sw.js', { scope: '/admin/' })
    } catch {
      // Swallow registration errors in local dev to avoid noisy UX.
    }
  })
}
