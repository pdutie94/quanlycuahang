import { computed, ref } from 'vue';
import { createProduct, deleteProduct, fetchProductFormData, fetchProductFormEditData, updateProduct } from '../services/product.api';
import { useFetch } from '../../../shared/composables/useFetch';
import type { Unit, Product } from '../../../shared/types';
import type { Category, ProductLog, ProductFormState, ProductEditData } from '../types';

function formatMoneyField(value: any): string {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  const numericValue = Number(value);
  if (!Number.isFinite(numericValue)) {
    return '';
  }

  return new Intl.NumberFormat('vi-VN').format(Math.round(numericValue));
}

function formatStepValue(value: any): string {
  if (value === null || value === undefined || value === '') {
    return '1';
  }

  const normalized = String(value).replace(',', '.');
  const numericValue = Number(normalized);
  if (!Number.isFinite(numericValue) || numericValue <= 0) {
    return '1';
  }

  return numericValue.toFixed(4).replace(/\.?0+$/, '');
}

export function useProductForm() {
  const units = ref<Unit[]>([]);
  const categories = ref<Category[]>([]);
  const product = ref<Product | null>(null);

  const form = ref<ProductFormState>({
    name: '',
    code: '',
    base_unit_id: '',
    category_id: '',
    price_sell_single: '',
    price_cost_single: '',
    allow_fraction: false,
    min_step: '1',
    inventory_qty_base: '',
    min_stock_qty: '',
    redirect: 'stay',
    auto_price_enabled: 0,
    auto_price_value: ''
  });

  const productLogs = ref<ProductLog[]>([]);

  const bootstrapRequest = useFetch(fetchProductFormData);
  const editRequest = useFetch(fetchProductFormEditData);
  const createRequest = useFetch(createProduct);
  const updateRequest = useFetch(updateProduct);
  const deleteRequest = useFetch(deleteProduct);

  const applyEditData = (data: Partial<ProductEditData>) => {
    product.value = data.product || null;
    productLogs.value = data.product_logs || [];

    const firstUnit = Array.isArray(data.product_units) && data.product_units.length ? data.product_units[0] : null;

    form.value = {
      name: data.product?.name || '',
      code: data.product?.code || '',
      base_unit_id: data.product?.base_unit_id ? String(data.product.base_unit_id) : '',
      category_id: data.product?.category_id ? String(data.product.category_id) : '',
      price_sell_single: formatMoneyField(firstUnit?.price_sell),
      price_cost_single: formatMoneyField(firstUnit?.price_cost),
      allow_fraction: Number(firstUnit?.allow_fraction || 0) === 1,
      min_step: formatStepValue(firstUnit?.min_step),
      inventory_qty_base: data.inventory_qty_base !== null && data.inventory_qty_base !== undefined ? String(data.inventory_qty_base) : '',
      min_stock_qty: data.product?.min_stock_qty !== null && data.product?.min_stock_qty !== undefined ? String(data.product.min_stock_qty) : '',
      redirect: form.value.redirect || 'stay',
      auto_price_enabled: data.product?.auto_price_enabled ? 1 : 0,
      auto_price_value: String(data.product?.auto_price_value ?? '')
    };
  };

  const loadBootstrap = async () => {
    const payload = await bootstrapRequest.execute();
    units.value = payload?.data?.units || [];
    categories.value = payload?.data?.categories || [];
    return payload;
  };

  const loadEdit = async (id: number | string) => {
    const payload = await editRequest.execute(id);
    applyEditData(payload?.data || {});
    return payload;
  };

  const refreshEdit = async (id: number | string) => {
    const payload = await fetchProductFormEditData(id);
    applyEditData(payload?.data || {});
    return payload;
  };

  const baseUnitName = computed(() => {
    const selected = units.value.find((unit) => Number(unit.id) === Number(form.value.base_unit_id));
    return selected?.name || '';
  });

  const buildPayload = () => ({
    name: form.value.name,
    code: form.value.code,
    base_unit_id: form.value.base_unit_id,
    category_id: form.value.category_id,
    price_sell_single: form.value.price_sell_single,
    price_cost_single: form.value.price_cost_single,
    allow_fraction: form.value.allow_fraction ? '1' : '0',
    min_step: form.value.min_step,
    inventory_qty_base: form.value.inventory_qty_base,
    min_stock_qty: form.value.min_stock_qty,
    redirect: form.value.redirect,
    auto_price_enabled: form.value.auto_price_enabled,
    auto_price_value: form.value.auto_price_value
  });

  const submitCreate = async () => createRequest.execute(buildPayload());
  const submitUpdate = async (id: number | string) => updateRequest.execute(id, buildPayload());
  const remove = async (id: number | string) => deleteRequest.execute(id);

  return {
    units,
    categories,
    product,
    productLogs,
    form,
    baseUnitName,
    loadBootstrap,
    loadEdit,
    refreshEdit,
    submitCreate,
    submitUpdate,
    bootstrapLoading: bootstrapRequest.loading,
    bootstrapError: bootstrapRequest.error,
    editLoading: editRequest.loading,
    editError: editRequest.error,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error,
    remove,
    deleteLoading: deleteRequest.loading,
    deleteError: deleteRequest.error
  };
}
