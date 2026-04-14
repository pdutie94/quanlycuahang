// Composable: useDebtAllocation.js
// Dùng cho cả customer và supplier (truyền vào danh sách đơn/purchase, số tiền, trường nợ)
import { computed, type Ref } from 'vue';
import { useFormat } from './useFormat';

interface DebtAllocationProps {
  items: Ref<Record<string, any>[]>;
  amount: Ref<string | number>;
  debtField?: string;
  idField?: string;
  codeField?: string;
  dateField?: string;
}

export function useDebtAllocation({ items, amount, debtField = 'debt_amount', idField = 'id', codeField = 'order_code', dateField = 'order_date' }: DebtAllocationProps) {
  const { parseAmount } = useFormat();
  const amountNumber = computed(() => parseAmount(amount.value));

  // Tính preview phân bổ vào các đơn còn nợ (theo thứ tự cũ đến mới)
  const preview = computed(() => {
    let remainingAmount = amountNumber.value;
    return (items.value || [])
      .filter(item => Number(item[debtField] || 0) > 0)
      .sort((a, b) => new Date(a[dateField]).getTime() - new Date(b[dateField]).getTime())
      .map((item) => {
        const debt = Math.max(Number(item[debtField] || 0), 0);
        const allocatedAmount = remainingAmount > 0 ? Math.min(remainingAmount, debt) : 0;
        remainingAmount -= allocatedAmount;
        return {
          id: Number(item[idField] || 0),
          code: item[codeField] || `#${item[idField]}`,
          date: item[dateField] || '',
          debtBefore: debt,
          allocatedAmount,
          debtAfter: Math.max(debt - allocatedAmount, 0)
        };
      })
      .filter(item => item.allocatedAmount > 0);
  });

  const previewTotal = computed(() => preview.value.reduce((sum, item) => sum + item.allocatedAmount, 0));
  const unappliedAmount = computed(() => {
    const remaining = amountNumber.value - previewTotal.value;
    return remaining > 0 ? remaining : 0;
  });

  return { amountNumber, preview, previewTotal, unappliedAmount };
}
