import { computed, ref } from 'vue';
import { createPurchase, fetchPurchaseDetail, fetchPurchaseFormData, updatePurchase } from '../services/purchase.api';
import { createProduct, fetchProductFormData } from '../../product/services/product.api';
import { createSupplier } from '../../supplier/services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';

function parseMoneyValue(value) {
  if (value === null || value === undefined) {
    return 0;
  }

  const digits = String(value).replace(/[^0-9-]/g, '');
  if (!digits || digits === '-') {
    return 0;
  }

  return Number(digits);
}

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

function createEmptyManualItem() {
  return {
    item_name: '',
    unit_name: '',
    qty: '1',
    price_cost: '',
    amount: '',
    save_as_product: false,
    saved_product_id: '',
    product_base_unit_id: '',
    product_category_id: ''
  };
}

function createDefaultFormState() {
  const now = new Date();
  const localNow = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);

  return {
    supplier_id: '',
    purchase_date: localNow,
    payment_status: 'pay',
    payment_method: 'cash',
    paid_amount: '',
    note: ''
  };
}

function formatDateTimeLocalValue(value) {
  const raw = String(value || '').trim();
  if (!raw) {
    return createDefaultFormState().purchase_date;
  }

  const normalized = raw.replace(' ', 'T');
  return normalized.slice(0, 16);
}

export function usePurchaseForm() {
  const suppliers = ref([]);
  const productUnits = ref([]);
  const baseUnits = ref([]);
  const productCategories = ref([]);
  const form = ref(createDefaultFormState());
  const rows = ref([]);
  const manualItems = ref([]);
  const purchase = ref(null);

  const bootstrapRequest = useFetch(fetchPurchaseFormData);
  const productFormRequest = useFetch(fetchProductFormData);
  const detailRequest = useFetch(fetchPurchaseDetail);
  const createRequest = useFetch(createPurchase);
  const updateRequest = useFetch(updatePurchase);
  const createSupplierRequest = useFetch(createSupplier);
  const createProductRequest = useFetch(createProduct);

  const loadBootstrap = async () => {
    const [purchasePayload, productPayload] = await Promise.all([
      bootstrapRequest.execute(),
      productFormRequest.execute()
    ]);

    suppliers.value = purchasePayload?.data?.suppliers || [];
    productUnits.value = purchasePayload?.data?.product_units || [];
    baseUnits.value = productPayload?.data?.units || [];
    productCategories.value = productPayload?.data?.categories || [];

    return purchasePayload;
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
      purchase_date: formatDateTimeLocalValue(purchaseData.purchase_date),
      payment_status: purchaseData.status === 'debt' ? 'debt' : 'pay',
      payment_method: noteMeta.paymentMethod,
      paid_amount: purchaseData.paid_amount ? String(purchaseData.paid_amount) : '',
      note: noteMeta.note
    };
    rows.value = (payload?.data?.items || []).map((item) => ({
      product_unit_id: String(item.product_unit_id || ''),
      qty: String(item.qty ?? ''),
      price_cost: String(item.price_cost ?? ''),
      amount: String(item.amount ?? ''),
      update_cost: false
    }));
    manualItems.value = (payload?.data?.manual_items || []).map((item) => ({
      ...createEmptyManualItem(),
      item_name: item.item_name || '',
      unit_name: item.unit_name || '',
      qty: String(item.qty ?? ''),
      price_cost: String(item.price_cost ?? ''),
      amount: String(item.amount ?? '')
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

  const addManualItem = (initialItem = null) => {
    manualItems.value.push({
      ...createEmptyManualItem(),
      ...(initialItem || {})
    });
  };

  const removeManualItem = (index) => {
    manualItems.value.splice(index, 1);
  };

  const upsertSupplier = (supplier) => {
    if (!supplier?.id) {
      return;
    }

    const nextId = Number(supplier.id);
    const nextList = suppliers.value.filter((entry) => Number(entry.id) !== nextId);
    nextList.push(supplier);
    nextList.sort((left, right) => String(left.name || '').localeCompare(String(right.name || ''), 'vi'));
    suppliers.value = nextList;
  };

  const createInlineSupplier = async (payload) => {
    const response = await createSupplierRequest.execute(payload);
    const supplier = response?.data?.supplier || null;
    if (supplier?.id) {
      upsertSupplier(supplier);
      form.value.supplier_id = String(supplier.id);
    }
    return response;
  };

  const createInlineProduct = async (payload) => createProductRequest.execute(payload);

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
      totalAmount += parseMoneyValue(row.amount || 0) || Number(row.qty || 0) * parseMoneyValue(row.price_cost || 0);
    }

    let manualQty = 0;
    let manualAmount = 0;
    for (const row of manualItems.value) {
      manualQty += Number(row.qty || 0);
      manualAmount += parseMoneyValue(row.amount || 0) || Number(row.qty || 0) * parseMoneyValue(row.price_cost || 0);
    }

    return {
      totalQty,
      manualQty,
      totalAmount,
      manualAmount,
      grandTotal: totalAmount + manualAmount
    };
  });

  const buildPayload = (isEdit = false) => ({
    supplier_id: Number(form.value.supplier_id || 0),
    purchase_date: form.value.purchase_date,
    payment_status: form.value.payment_status,
    payment_method: form.value.payment_method,
    paid_amount: isEdit ? undefined : form.value.paid_amount,
    note: form.value.note,
    product_unit_id: rows.value.map((row) => row.product_unit_id),
    qty: rows.value.map((row) => row.qty),
    price_cost: rows.value.map((row) => parseMoneyValue(row.price_cost)),
    amount: rows.value.map((row) => parseMoneyValue(row.amount)),
    update_cost: rows.value.map((row) => (row.update_cost ? '1' : '')),
    manual_item_name: manualItems.value.map((row) => row.item_name),
    manual_unit_name: manualItems.value.map((row) => row.unit_name),
    manual_qty: manualItems.value.map((row) => row.qty),
    manual_price_cost: manualItems.value.map((row) => parseMoneyValue(row.price_cost)),
    manual_amount: manualItems.value.map((row) => parseMoneyValue(row.amount))
  });

  const submitCreate = async () => createRequest.execute(buildPayload(false));
  const submitUpdate = async (id) => updateRequest.execute(id, buildPayload(true));

  const resetState = () => {
    purchase.value = null;
    form.value = createDefaultFormState();
    rows.value = [];
    manualItems.value = [];
  };

  return {
    suppliers,
    productUnits,
    baseUnits,
    productCategories,
    form,
    rows,
    manualItems,
    purchase,
    rowDisplayMap,
    summary,
    loadBootstrap,
    loadEdit,
    addRow,
    removeRow,
    addManualItem,
    removeManualItem,
    createInlineSupplier,
    createInlineProduct,
    createSupplierLoading: createSupplierRequest.loading,
    createSupplierError: createSupplierRequest.error,
    createProductLoading: createProductRequest.loading,
    createProductError: createProductRequest.error,
    resetState,
    submitCreate,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    submitUpdate,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error,
    bootstrapLoading: computed(() => bootstrapRequest.loading.value || productFormRequest.loading.value),
    bootstrapError: computed(() => bootstrapRequest.error.value || productFormRequest.error.value)
  };
}