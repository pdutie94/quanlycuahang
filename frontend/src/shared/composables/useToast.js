export function useToast() {
  const notify = (type, message) => {
    if (!message) {
      return;
    }

    if (typeof window.showToast === 'function') {
      window.showToast(type, message);
      return;
    }

    // Keep notification non-blocking when legacy toast bridge is unavailable.
    window.dispatchEvent(new CustomEvent('spa:toast', { detail: { type, message } }));
    console[type === 'error' ? 'error' : 'log'](`[${type}] ${message}`);
  };

  return {
    success: (message) => notify('success', message),
    error: (message) => notify('error', message),
    info: (message) => notify('info', message)
  };
}
