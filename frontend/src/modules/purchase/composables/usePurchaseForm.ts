import { computed, ref } from 'vue';
import { createPurchase, fetchPurchaseDetail, fetchPurchaseFormData, updatePurchase } from '../services/purchase.api';
import { createProduct, fetchProductFormData } from '../../product/services/product.api';
import { createSupplier } from '../../supplier/services/supplier.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { Product, ProductUnit, Unit } from '../../../shared/types';
import type { Category } from '../../product/types';
import type { Purchase, PurchaseItem, ManualPurchaseItem, PurchaseFormState } from '../types';

function parseMoneyValue(value: any): number {
  if (value === null || value === undefined) return 0;
  const digits = String(value).replace(/[^0-9-]/g, '');
  if (!digits || digits === '-') return 0;
  return Number(digits);
}

function parseMethodAndNote(note: string) {
  const raw = String(note || '').trim();
  if (raw.endsWith('[TT:cash]')) return { note: raw.slice(0, -9).trim(), paymentMethod: 'cash' as const };
  if (raw.endsWith('[TT:bank]')) return { note: raw.slice(0, -9).trim(), paymentMethod: 'bank' as const };
  return { note: raw, paymentMethod: 'cash' as const };
}

function createEmptyManualItem(): ManualPurchaseItem {
  return { item_name: '', unit_name: '', qty: '1', price_cost: '', amount: '' };
}

function createDefaultFormState(): PurchaseFormState {
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

function formatDateTimeLocalValue(value: any): string {
  const raw = String(value || '').trim();
  if (!raw) return createDefaultFormState().purchase_date;
  return raw.replace(' ', 'T').slice(0, 16);
}

export function usePurchaseForm() {
  const suppliers = ref<any[]>([]);
  const productUnits = ref<ProductUnit[]>([]);
  const baseUnits = ref<Unit[]>([]);
  const productCategories = ref<Category[]>([]);
  const form = ref<PurchaseFormState>(createDefaultFormState());
  const rows = ref<PurchaseItem[]>([]);
  const manualItems = ref<ManualPurchaseItem[]>([]);
  const purchase = ref<Purchase | null>(null);

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

  const loadEdit = async (id: number | string) => {
    const payload = await detailRequest.execute(id);
    const purchaseData = payload?.data?.purchase || null;
    purchase.value = purchaseData;
    if (!purchaseData) return payload;
    const noteMeta = parseMethodAndNote(purchaseData.note || '');
    form.value = {
      supplier_id: purchaseData.supplier_id ? String(purchaseData.supplier_id) : '',
      purchase_date: formatDateTimeLocalValue(purchaseData.purchase_date),
      payment_status: purchaseData.status === 'debt' ? 'debt' : 'pay',
      payment_method: noteMeta.paymentMethod,
      paid_amount: purchaseData.paid_amount ? String(purchaseData.paid_amount) : '',
      note: noteMeta.note
    };
    rows.value = (payload?.data?.items || []).map((item: any) => ({
      product_unit_id: String(item.product_unit_id || ''),
      qty: String(item.qty ?? ''),
      price_cost: String(item.price_cost ?? ''),
      amount: String(item.amount ?? ''),
      allow_fraction: typeof item.allow_fraction !== 'undefined' ? item.allow_fraction : undefined,
      min_step: typeof item.min_step !== 'undefined' ? item.min_step : undefined,
      update_cost: false
    }));
    manualItems.value = (payload?.data?.manual_items || []).map((item: any) => ({
      ...createEmptyManualItem(),
      item_name: item.item_name || '',
      unit_name: item.unit_name || '',
      qty: String(item.qty ?? ''),
      price_cost: String(item.price_cost ?? ''),
      amount: String(item.amount ?? '')
    }));
    return payload;
  };

  const addRow = (initialRow: Partial<PurchaseItem> | null = null) => {
    rows.value.push({
      product_unit_id: initialRow?.product_unit_id || '',
      qty: initialRow?.qty || '',
      price_cost: initialRow?.price_cost || '',
      amount: initialRow?.amount || '',
      update_cost: Boolean(initialRow?.update_cost)
    });
  };

  const removeRow = (index: number) => rows.value.splice(index, 1);
  const addManualItem = (initialItem: Partial<ManualPurchaseItem> | null = null) => manualItems.value.push({ ...createEmptyManualItem(), ...(initialItem || {}) });
  const removeManualItem = (index: number) => manualItems.value.splice(index, 1);

  const upsertSupplier = (supplier: any) => {
    if (!supplier?.id) return;
    const nextId = Number(supplier.id);
    const nextList = suppliers.value.filter((entry: any) => Number(entry.id) !== nextId);
    nextList.push(supplier);
    nextList.sort((left: any, right: any) => String(left.name || '').localeCompare(String(right.name || ''), 'vi'));
    suppliers.value = nextList;
  };

  const createInlineSupplier = async (payload: Record<string, any>) => {
    const response = await createSupplierRequest.execute(payload);
    const supplier = response?.data?.supplier || null;
    if (supplier?.id) {
      upsertSupplier(supplier);
      form.value.supplier_id = String(supplier.id);
    }
    return response;
  };

  const createInlineProduct = async (payload: Record<string, any>) => createProductRequest.execute(payload);

  const rowDisplayMap = computed(() => {
    const map = new Map<string, ProductUnit>();
    for (const unit of productUnits.value) map.set(String(unit.id), unit);
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
    return { totalQty, manualQty, totalAmount, manualAmount, grandTotal: totalAmount + manualAmount };
  });

  const buildPayload = (isUpdateMode: boolean = false) => ({
    supplier_id: Number(form.value.supplier_id || 0),
    purchase_date: form.value.purchase_date,
    payment_status: form.value.payment_status,
    payment_method: form.value.payment_method,
    paid_amount: isUpdateMode ? undefined : (form.value.payment_status === 'debt' ? 0 : form.value.paid_amount),
    note: form.value.note,
    product_unit_id: rows.value.map((row: PurchaseItem) => row.product_unit_id),
    qty: rows.value.map((row: PurchaseItem) => row.qty),
    price_cost: rows.value.map((row: PurchaseItem) => parseMoneyValue(row.price_cost)),
    amount: rows.value.map((row: PurchaseItem) => parseMoneyValue(row.amount)),
    update_cost: rows.value.map((row: PurchaseItem) => (row.update_cost ? '1' : '')),
    manual_item_name: manualItems.value.map((row: ManualPurchaseItem) => row.item_name),
    manual_unit_name: manualItems.value.map((row: ManualPurchaseItem) => row.unit_name),
    manual_qty: manualItems.value.map((row: ManualPurchaseItem) => row.qty),
    manual_price_cost: manualItems.value.map((row: ManualPurchaseItem) => parseMoneyValue(row.price_cost)),
    manual_amount: manualItems.value.map((row: ManualPurchaseItem) => parseMoneyValue(row.amount))
  });

  const submitCreate = async () => createRequest.execute(buildPayload(false));
  const submitUpdate = async (id: number | string) => updateRequest.execute(id, buildPayload(true));
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
    createSupplierLoading: createSupplierRequest.loading,
    createSupplierError: createSupplierRequest.error,
    createInlineProduct,
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
