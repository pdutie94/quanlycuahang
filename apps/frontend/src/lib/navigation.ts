type NavigateHandler = (to: string) => unknown | Promise<unknown>

let navigateHandler: NavigateHandler | null = null

export function setNavigateHandler(handler: NavigateHandler): void {
  navigateHandler = handler
}

export function navigateTo(to: string): void {
  if (navigateHandler) {
    void navigateHandler(to)
    return
  }

  window.location.assign(to)
}
