export function useToast() {
  const notify = (type, message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(type, message);
      return;
    }
    if (message) {
      // Fallback for environments where legacy toast is unavailable.
      window.alert(message);
    }
  };

  return {
    success: (message) => notify('success', message),
    error: (message) => notify('error', message),
    info: (message) => notify('info', message)
  };
}
