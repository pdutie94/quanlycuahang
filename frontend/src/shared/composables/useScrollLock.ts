import { onMounted, onUnmounted } from 'vue';

let observer: MutationObserver | null = null;
let lockCount = 0;

function updateBodyScroll() {
  const hasOpenModal = document.querySelector('.app-modal-overlay.app-modal-open, .app-modal-open') !== null;
  if (hasOpenModal) {
    document.body.classList.add('overflow-hidden');
  } else {
    document.body.classList.remove('overflow-hidden');
  }
}

export function initGlobalScrollLock() {
  if (observer) return;
  
  updateBodyScroll();
  
  observer = new MutationObserver((mutations) => {
    // Check if any mutation involves modal classes
    const shouldCheck = mutations.some(m => {
      if (m.type === 'attributes' && m.attributeName === 'class') {
        const target = m.target as HTMLElement;
        return target.classList?.contains('app-modal-overlay') || 
               target.classList?.contains('app-modal-open');
      }
      // Check added/removed nodes
      for (const node of Array.from(m.addedNodes).concat(Array.from(m.removedNodes))) {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const el = node as HTMLElement;
          if (el.classList?.contains('app-modal-overlay') || 
              el.classList?.contains('app-modal-open') ||
              el.querySelector?.('.app-modal-overlay.app-modal-open, .app-modal-open')) {
            return true;
          }
        }
      }
      return false;
    });
    
    if (shouldCheck) {
      updateBodyScroll();
    }
  });
  
  observer.observe(document.body, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['class']
  });
}

export function destroyGlobalScrollLock() {
  if (observer) {
    observer.disconnect();
    observer = null;
  }
  document.body.classList.remove('overflow-hidden');
}

// Legacy API for individual modal usage
export function useScrollLock(isLocked?: import('vue').Ref<boolean>) {
  onMounted(() => {
    initGlobalScrollLock();
  });
  
  onUnmounted(() => {
    // Don't destroy if other components still using
    setTimeout(updateBodyScroll, 0);
  });
}
