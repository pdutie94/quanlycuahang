import { computed, ref } from 'vue';
import { createProduct, fetchProductFormData, fetchProductFormEditData, updateProduct } from '../services/product.api';
import { useFetch } from '../../../shared/composables/useFetch';

export function useProductForm() {
  const units = ref([]);
  const categories = ref([]);
  const product = ref(null);

  const form = ref({
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
    redirect: 'stay'
  });

  const productLogs = ref([]);

  const bootstrapRequest = useFetch(fetchProductFormData);
  const editRequest = useFetch(fetchProductFormEditData);
  const createRequest = useFetch(createProduct);
  const updateRequest = useFetch(updateProduct);

  const loadBootstrap = async () => {
    const payload = await bootstrapRequest.execute();
    units.value = payload?.data?.units || [];
    categories.value = payload?.data?.categories || [];
    return payload;
  };

  const loadEdit = async (id) => {
    const payload = await editRequest.execute(id);
    const data = payload?.data || {};
    product.value = data.product || null;
    productLogs.value = data.product_logs || [];

    const firstUnit = Array.isArray(data.product_units) && data.product_units.length ? data.product_units[0] : null;

    form.value = {
      name: data.product?.name || '',
      code: data.product?.code || '',
      base_unit_id: data.product?.base_unit_id ? String(data.product.base_unit_id) : '',
      category_id: data.product?.category_id ? String(data.product.category_id) : '',
      price_sell_single: firstUnit?.price_sell ? String(firstUnit.price_sell) : '',
      price_cost_single: firstUnit?.price_cost ? String(firstUnit.price_cost) : '',
      allow_fraction: Number(firstUnit?.allow_fraction || 0) === 1,
      min_step: firstUnit?.min_step ? String(firstUnit.min_step) : '1',
      inventory_qty_base: data.inventory_qty_base !== null && data.inventory_qty_base !== undefined ? String(data.inventory_qty_base) : '',
      min_stock_qty: data.product?.min_stock_qty !== null && data.product?.min_stock_qty !== undefined ? String(data.product.min_stock_qty) : '',
      redirect: 'stay'
    };

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
    redirect: form.value.redirect
  });

  const submitCreate = async () => createRequest.execute(buildPayload());
  const submitUpdate = async (id) => updateRequest.execute(id, buildPayload());

  return {
    units,
    categories,
    product,
    productLogs,
    form,
    baseUnitName,
    loadBootstrap,
    loadEdit,
    submitCreate,
    submitUpdate,
    bootstrapLoading: bootstrapRequest.loading,
    bootstrapError: bootstrapRequest.error,
    editLoading: editRequest.loading,
    editError: editRequest.error,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}
