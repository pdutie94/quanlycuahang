import { createApp } from 'vue';
import App from './App.vue';
import { router } from './router';

const moneyFormatter = new Intl.NumberFormat('vi-VN');

const moneyInputSelector = [
  'input[inputmode="numeric"][type="text"]:not([data-money-format="off"])',
  'input[inputmode="numeric"]:not([type]):not([data-money-format="off"])'
].join(', ');

function formatMoneyInputValue(rawValue) {
  const digits = String(rawValue ?? '').replace(/[^0-9]/g, '');
  if (!digits) {
    return '';
  }

  return moneyFormatter.format(Number(digits));
}

function configureMoneyInput(target) {
  target.setAttribute('inputmode', 'numeric');
  target.setAttribute('pattern', '[0-9.,]*');
  target.setAttribute('enterkeyhint', 'done');
  target.setAttribute('autocomplete', 'off');
}

// Returns true when value looks like a decimal being typed (e.g. "24.5", "24.").
// A value with exactly one dot where the digits after are fewer than 3
// is treated as a decimal shorthand, not a thousands separator.
function isDecimalShorthand(value) {
  const str = String(value ?? '').trim();
  const dotIdx = str.indexOf('.');
  if (dotIdx === -1) return false;
  if ((str.match(/\./g) || []).length !== 1) return false;
  const afterDot = str.slice(dotIdx + 1).replace(/[^0-9]/g, '');
  return afterDot.length < 3;
}

// Strips everything except digits and the first dot.
function sanitizeDecimalInput(value) {
  let result = '';
  let hasDot = false;
  for (const ch of String(value ?? '')) {
    if (ch >= '0' && ch <= '9') {
      result += ch;
    } else if (ch === '.' && !hasDot) {
      result += ch;
      hasDot = true;
    }
  }
  return result;
}

function bindGlobalMoneyFormatter(container) {
  const configureKnownMoneyInputs = () => {
    const inputs = container.querySelectorAll(moneyInputSelector);
    inputs.forEach((input) => {
      if (input instanceof HTMLInputElement) {
        configureMoneyInput(input);
      }
    });
  };

  configureKnownMoneyInputs();

  const observer = new MutationObserver(() => {
    configureKnownMoneyInputs();
  });
  observer.observe(container, { childList: true, subtree: true });

  const onFocusIn = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }

    if (!target.matches(moneyInputSelector)) {
      return;
    }

    configureMoneyInput(target);
  };

  const onInput = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }

    if (!target.matches(moneyInputSelector)) {
      return;
    }

    configureMoneyInput(target);

    // While user is typing a decimal (e.g. "24.5"), preserve the dot.
    if (isDecimalShorthand(target.value)) {
      const sanitized = sanitizeDecimalInput(target.value);
      if (sanitized !== target.value) {
        target.value = sanitized;
        target.dispatchEvent(new Event('input', { bubbles: true }));
      }
      return;
    }

    const formatted = formatMoneyInputValue(target.value);
    if (formatted !== target.value) {
      target.value = formatted;
      target.dispatchEvent(new Event('input', { bubbles: true }));
    }
  };

  const onFocusOut = (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }

    if (!target.matches(moneyInputSelector)) {
      return;
    }

    if (target.getAttribute('data-money-x1000') === 'off') {
      return;
    }

    const raw = String(target.value ?? '').trim();
    if (!raw) {
      return;
    }

    let finalNum;

    if (isDecimalShorthand(raw)) {
      // e.g. "24.5" → 24.5 × 1000 = 24500
      const num = parseFloat(sanitizeDecimalInput(raw));
      if (!isNaN(num) && num > 0) {
        finalNum = num < 1000 ? num * 1000 : num;
      }
    } else {
      const digits = raw.replace(/[^0-9]/g, '');
      if (digits) {
        const num = Number(digits);
        if (num > 0 && num < 1000) {
          finalNum = num * 1000;
        }
      }
    }

    if (finalNum !== undefined) {
      target.value = moneyFormatter.format(Math.round(finalNum));
      target.dispatchEvent(new Event('input', { bubbles: true }));
    }
  };

  container.addEventListener('focusin', onFocusIn);
  container.addEventListener('input', onInput);
  container.addEventListener('focusout', onFocusOut);
}

const root = document.getElementById('spa-root');

if (root) {
  const app = createApp(App);
  app.use(router);
  app.mount(root);
  bindGlobalMoneyFormatter(root);

  window.__SPA_ROUTER__ = router;
}
