import { computed, ref } from 'vue';
import { fetchPosBootstrap, createOrder } from '../services/pos.api';
import { useFetch } from '../../../shared/composables/useFetch';

function createEmptyManualItem(): Record<string, any> {
  return {
    item_name: '',
    unit_name: '',
    qty: 1,
    price_buy: '',
    price_sell: ''
  };
}

function getUnitStep(unit: Record<string, any>) {
  const allowFraction = Number(unit?.allow_fraction || 0) === 1;
  const minStep = Number(unit?.min_step || 1);

  if (!allowFraction) {
    return 1;
  }

  if (!Number.isFinite(minStep) || minStep <= 0) {
    return 1;
  }

  return minStep;
}

export function usePos() {
  const products = ref<Record<string, any>[]>([]);
  const productUnitsByProduct = ref<Record<string, any>>({});
  const customers = ref<Record<string, any>[]>([]);
  const cartItems = ref<Record<string, any>[]>([]);
  const manualItems = ref<Record<string, any>[]>([]);

  const bootstrapRequest = useFetch(fetchPosBootstrap);
  const createRequest = useFetch(createOrder);

  const loadBootstrap = async () => {
    const payload = await bootstrapRequest.execute();
    products.value = payload?.data?.products || [];
    productUnitsByProduct.value = payload?.data?.product_units_by_product || {};
    customers.value = payload?.data?.customers || [];
    return payload;
  };

  const addProduct = (product: Record<string, any>) => {
    const units = productUnitsByProduct.value[String(product.id)] || productUnitsByProduct.value[product.id] || [];
    if (!units.length) {
      return false;
    }

    const defaultUnit = units[0];
    const quantityStep = getUnitStep(defaultUnit);

    const existing = cartItems.value.find((item: Record<string, any>) => item.product_id === product.id && item.unit_id === defaultUnit.unit_id);
    if (existing) {
      existing.quantity = Number(existing.quantity || 0) + quantityStep;
      return true;
    }

    cartItems.value.push({
      id: `${product.id}-${defaultUnit.unit_id}-${Date.now()}`,
      product_id: product.id,
      product_name: product.name,
      unit_id: defaultUnit.unit_id,
      quantity: quantityStep,
      price: Number(defaultUnit.price_sell || 0),
      base_price: Number(defaultUnit.price_sell || 0)
    });
    return true;
  };

  const updateCartUnit = (item: Record<string, any>, unitId: number | string) => {
    const units = productUnitsByProduct.value[String(item.product_id)] || productUnitsByProduct.value[item.product_id] || [];
    const nextUnit = units.find((unit: Record<string, any>) => Number(unit.unit_id) === Number(unitId));
    if (!nextUnit) {
      return;
    }

    item.unit_id = nextUnit.unit_id;
    item.price = Number(nextUnit.price_sell || 0);
    item.base_price = Number(nextUnit.price_sell || 0);
    item.quantity = getUnitStep(nextUnit);
  };

  const removeCartItem = (id: string | number) => {
    cartItems.value = cartItems.value.filter((item: Record<string, any>) => item.id !== id);
  };

  const addManualItem = () => {
    manualItems.value.push(createEmptyManualItem());
  };

  const removeManualItem = (index: number) => {
    manualItems.value.splice(index, 1);
  };

  const subtotal = computed(() => {
    const cartTotal = cartItems.value.reduce((sum: number, item: Record<string, any>) => sum + (Number(item.quantity || 0) * Number(item.price || 0)), 0);
    const manualTotal = manualItems.value.reduce((sum: number, item: Record<string, any>) => sum + (Number(item.qty || 0) * Number(item.price_sell || 0)), 0);
    return cartTotal + manualTotal;
  });

  const submitOrder = async (payload: Record<string, any>) => createRequest.execute(payload);

  return {
    products,
    productUnitsByProduct,
    customers,
    cartItems,
    manualItems,
    loading: bootstrapRequest.loading,
    error: bootstrapRequest.error,
    loadBootstrap,
    addProduct,
    updateCartUnit,
    removeCartItem,
    addManualItem,
    removeManualItem,
    subtotal,
    submitOrder,
    submitting: createRequest.loading,
    submitError: createRequest.error
  };
}
