// Composable: useFormat.js
// Dùng chung cho format tiền, ngày, parseAmount
import { ref } from 'vue';

const numberFormatter = new Intl.NumberFormat('vi-VN');
const dateFormatter = new Intl.DateTimeFormat('vi-VN', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
});

export function useFormat() {
  function formatMoney(value) {
    return `${numberFormatter.format(Number(value || 0))} đ`;
  }

  function formatDateTime(value) {
    if (!value) return '--';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '--';
    return dateFormatter.format(date);
  }

  function parseAmount(value) {
    const normalized = String(value || '').replace(/[^\d]/g, '');
    return Number(normalized || 0);
  }

  const formatNumber = (value) => {
    const nextValue = Number(value || 0);
    if (Number.isInteger(nextValue)) {
      return numberFormatter.format(nextValue);
    }

    return nextValue.toLocaleString('vi-VN', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    });
  };

  const formatMoneyInput = (value, allowEmpty = true) => {
    const amount = parseAmount(value);
    if (amount <= 0) {
      return allowEmpty ? '' : '0';
    }

    return numberFormatter.format(amount);
  };

  const formatMoneyInputValue = (rawValue) => {
    const digits = String(rawValue ?? '').replace(/[^0-9]/g, '');
    if (!digits) {
      return '';
    }

    return numberFormatter.format(Number(digits));
  };
  
  const parsePriceShorthand = (raw) => {
    const str = String(raw ?? '').trim();
    if (!str) return 0;

    const dotIdx = str.indexOf('.');
    if (dotIdx !== -1 && (str.match(/\./g) || []).length === 1) {
      const afterDot = str.slice(dotIdx + 1).replace(/[^0-9]/g, '');
      if (afterDot.length < 3) {
        const num = parseFloat(str.replace(/[^0-9.]/g, ''));
        if (!Number.isNaN(num) && num > 0) {
          return num < 1000 ? Math.round(num * 1000) : Math.round(num);
        }
        return 0;
      }
    }

    const digits = str.replace(/[^0-9]/g, '');
    if (!digits) {
      return 0;
    }

    const num = Number(digits);
    return num > 0 && num < 1000 ? num * 1000 : num;
  };
  
  const formatPriceInput = (value, allowEmpty = true) => {
    const amount = parsePriceShorthand(value);
    if (amount <= 0) {
      return allowEmpty ? '' : '0';
    }

    return numberFormatter.format(amount);
  };


  return { formatMoney, formatDateTime, parseAmount, formatNumber, formatMoneyInput, formatMoneyInputValue, formatPriceInput };
}
