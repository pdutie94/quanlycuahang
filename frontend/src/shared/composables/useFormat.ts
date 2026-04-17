// Composable: useFormat.js
// Dùng chung cho format tiền, ngày, parseAmount

export const numberFormatter = new Intl.NumberFormat('vi-VN');
const dateFormatter = new Intl.DateTimeFormat('vi-VN', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
});
const dateOnlyFormatter = new Intl.DateTimeFormat('vi-VN', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric'
});

export function useFormat() {
  const roundToThousand = (amount: number): number => {
    const remainder = amount % 1000;
    if (remainder < 500) {
      return amount - remainder; // làm tròn xuống
    } else {
      return amount + (1000 - remainder); // làm tròn lên
    }
  };
  function formatMoney(value: string | number | null | undefined): string {
    return `${numberFormatter.format(Number(value || 0))} đ`;
  }

  function formatDateTime(value: string | Date | null | undefined): string {
    if (!value) return '--';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '--';
    return dateFormatter.format(date);
  }

  function formatDate(value: string | Date | null | undefined): string {
    if (!value) return '--';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '--';
    return dateOnlyFormatter.format(date);
  }

  function formatHistoryDateTime(value: string | Date | null | undefined): string {
    const text = formatDateTime(value);
    if (text === '--') {
      return '';
    }
    return text.replace(/^([^,]+),\s*/, '$1, ');
  }

  function parseAmount(value: string | number | null | undefined): number {
    const normalized = String(value || '').replace(/[^\d]/g, '');
    return Number(normalized || 0);
  }

  const formatNumber = (value: string | number | null | undefined): string => {
    const nextValue = Number(value || 0);
    if (Number.isInteger(nextValue)) {
      return numberFormatter.format(nextValue);
    }

    return nextValue.toLocaleString('vi-VN', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    });
  };

  const formatMoneyInput = (value: string | number | null | undefined, allowEmpty = true): string => {
    const amount = parseAmount(value);
    if (amount <= 0) {
      return allowEmpty ? '' : '0';
    }

    return numberFormatter.format(amount);
  };

  const formatMoneyInputValue = (rawValue: string | number | null | undefined): string => {
    const digits = String(rawValue ?? '').replace(/[^0-9]/g, '');
    if (!digits) {
      return '';
    }

    return numberFormatter.format(Number(digits));
  };
  
  const parsePriceShorthand = (raw: string | number | null | undefined): number => {
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
  
  const formatPriceInput = (value: string | number | null | undefined, allowEmpty = true): string => {
    const amount = parsePriceShorthand(value);
    if (amount <= 0) {
      return allowEmpty ? '' : '0';
    }

    return numberFormatter.format(amount);
  };


  return {
    formatMoney,
    formatDate,
    formatDateTime,
    formatHistoryDateTime,
    parseAmount,
    formatNumber,
    formatMoneyInput,
    formatMoneyInputValue,
    formatPriceInput,
    roundToThousand
  };
}
