export function useToast() {
  const notify = (type: string, message: string) => {
    if (!message) {
      return;
    }

    const g = window as any;
    if (typeof g.showToast === 'function') {
      g.showToast(type, message);
      return;
    }

    // Keep notification non-blocking when legacy toast bridge is unavailable.
    window.dispatchEvent(new CustomEvent('spa:toast', { detail: { type, message } }));
    if (type === 'error') {
      console.error(`[${type}] ${message}`);
    } else {
      console.log(`[${type}] ${message}`);
    }
  };

  return {
    success: (message: string) => notify('success', message),
    error: (message: string) => notify('error', message),
    info: (message: string) => notify('info', message)
  };
}
