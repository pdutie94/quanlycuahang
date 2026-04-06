// Composable: useFormat.js
// Dùng chung cho format tiền, ngày, parseAmount
import { ref } from 'vue';

const currencyFormatter = new Intl.NumberFormat('vi-VN');
const dateFormatter = new Intl.DateTimeFormat('vi-VN', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit'
});

export function useFormat() {
  function formatMoney(value) {
    return `${currencyFormatter.format(Number(value || 0))} đ`;
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

  return { formatMoney, formatDateTime, parseAmount };
}
