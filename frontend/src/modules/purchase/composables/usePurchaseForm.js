import { computed, ref } from 'vue';
import { createPurchase, fetchPurchaseDetail, fetchPurchaseFormData, updatePurchase } from '../services/purchase.api';
import { useFetch } from '../../../shared/composables/useFetch';

function parseMethodAndNote(note) {
  const raw = String(note || '').trim();
  if (raw.endsWith('[TT:cash]')) {
    return { note: raw.slice(0, -9).trim(), paymentMethod: 'cash' };
  }
  if (raw.endsWith('[TT:bank]')) {
    return { note: raw.slice(0, -9).trim(), paymentMethod: 'bank' };
  }
  return { note: raw, paymentMethod: 'cash' };
}

export function usePurchaseForm() {
  const suppliers = ref([]);
  const productUnits = ref([]);
  const form = ref({
    supplier_id: '',
    payment_status: 'pay',
    payment_method: 'cash',
    paid_amount: '',
    note: ''
  });
  const rows = ref([]);
  const purchase = ref(null);

  const bootstrapRequest = useFetch(fetchPurchaseFormData);
  const detailRequest = useFetch(fetchPurchaseDetail);
  const createRequest = useFetch(createPurchase);
  const updateRequest = useFetch(updatePurchase);

  const loadBootstrap = async () => {
    const payload = await bootstrapRequest.execute();
    suppliers.value = payload?.data?.suppliers || [];
    productUnits.value = payload?.data?.product_units || [];
    return payload;
  };

  const loadEdit = async (id) => {
    const payload = await detailRequest.execute(id);
    const purchaseData = payload?.data?.purchase || null;
    purchase.value = purchaseData;
    if (!purchaseData) {
      return payload;
    }

    const noteMeta = parseMethodAndNote(purchaseData.note || '');
    form.value = {
      supplier_id: purchaseData.supplier_id ? String(purchaseData.supplier_id) : '',
      payment_status: purchaseData.status === 'debt' ? 'debt' : 'pay',
      payment_method: noteMeta.paymentMethod,
      paid_amount: purchaseData.paid_amount ? String(purchaseData.paid_amount) : '',
      note: noteMeta.note
    };
    rows.value = (payload?.data?.items || []).map((item) => ({
      product_unit_id: String(item.product_unit_id || ''),
      qty: String(item.qty || ''),
      price_cost: String(item.price_cost || ''),
      amount: String(item.amount || ''),
      update_cost: false
    }));
    return payload;
  };

  const addRow = (initialRow = null) => {
    rows.value.push({
      product_unit_id: initialRow?.product_unit_id || '',
      qty: initialRow?.qty || '',
      price_cost: initialRow?.price_cost || '',
      amount: initialRow?.amount || '',
      update_cost: Boolean(initialRow?.update_cost)
    });
  };

  const removeRow = (index) => {
    rows.value.splice(index, 1);
  };

  const rowDisplayMap = computed(() => {
    const map = new Map();
    for (const unit of productUnits.value) {
      map.set(String(unit.id), unit);
    }
    return map;
  });

  const summary = computed(() => {
    let totalQty = 0;
    let totalAmount = 0;
    for (const row of rows.value) {
      totalQty += Number(row.qty || 0);
      totalAmount += Number(row.amount || 0) || Number(row.qty || 0) * Number(row.price_cost || 0);
    }
    return { totalQty, totalAmount };
  });

  const buildPayload = (isEdit = false) => ({
    supplier_id: Number(form.value.supplier_id || 0),
    payment_status: form.value.payment_status,
    payment_method: form.value.payment_method,
    paid_amount: isEdit ? undefined : form.value.paid_amount,
    note: form.value.note,
    product_unit_id: rows.value.map((row) => row.product_unit_id),
    qty: rows.value.map((row) => row.qty),
    price_cost: rows.value.map((row) => row.price_cost),
    amount: rows.value.map((row) => row.amount),
    update_cost: rows.value.map((row) => (row.update_cost ? '1' : ''))
  });

  const submitCreate = async () => createRequest.execute(buildPayload(false));
  const submitUpdate = async (id) => updateRequest.execute(id, buildPayload(true));

  return {
    suppliers,
    productUnits,
    form,
    rows,
    purchase,
    rowDisplayMap,
    summary,
    bootstrapLoading: bootstrapRequest.loading,
    bootstrapError: bootstrapRequest.error,
    loadBootstrap,
    loadEdit,
    addRow,
    removeRow,
    submitCreate,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    submitUpdate,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}