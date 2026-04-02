import { computed, ref } from 'vue';
import { createOrder, fetchOrderDetail, fetchOrderFormBootstrap, updateOrder } from '../services/order.api';
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

function createEmptyManualItem() {
  return {
    item_name: '',
    unit_name: '',
    qty: '1',
    price_buy: '',
    price_sell: ''
  };
}

export function useOrderForm() {
  const products = ref([]);
  const productUnitsByProduct = ref({});
  const productUnits = ref([]);
  const customers = ref([]);
  const order = ref(null);

  const form = ref({
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    customer_address: '',
    note: '',
    payment_status: 'pay',
    payment_method: 'cash',
    payment_amount: '',
    discount_type: 'none',
    discount_value: '',
    surcharge_amount: ''
  });

  const rows = ref([]);
  const manualItems = ref([]);
  const existingItemIds = ref([]);

  const bootstrapRequest = useFetch(fetchOrderFormBootstrap);
  const detailRequest = useFetch(fetchOrderDetail);
  const createRequest = useFetch(createOrder);
  const updateRequest = useFetch(updateOrder);

  const rowDisplayMap = computed(() => {
    const map = new Map();
    for (const unit of productUnits.value) {
      map.set(String(unit.id), unit);
    }
    return map;
  });

  const summary = computed(() => {
    let subtotal = 0;
    for (const row of rows.value) {
      subtotal += Number(row.qty || 0) * Number(row.price || 0);
    }

    let manualSellTotal = 0;
    for (const row of manualItems.value) {
      manualSellTotal += Number(row.qty || 0) * Number(row.price_sell || 0);
    }

    return {
      subtotal,
      manualSellTotal,
      gross: subtotal + manualSellTotal
    };
  });

  const loadBootstrap = async () => {
    const payload = await bootstrapRequest.execute();
    const data = payload?.data || {};

    products.value = data.products || [];
    productUnitsByProduct.value = data.product_units_by_product || {};
    customers.value = data.customers || [];

    productUnits.value = products.value.flatMap((product) => {
      const units = productUnitsByProduct.value[String(product.id)] || productUnitsByProduct.value[product.id] || [];
      return units.map((unit) => ({
        id: Number(unit.id || 0),
        product_id: Number(product.id || 0),
        product_name: product.name || '',
        unit_id: Number(unit.unit_id || 0),
        unit_name: unit.unit_name || '',
        price_sell: Number(unit.price_sell || 0),
        price_cost: Number(unit.price_cost || 0)
      }));
    });

    return payload;
  };

  const loadEdit = async (id) => {
    const payload = await detailRequest.execute(id);
    const data = payload?.data || {};
    const orderData = data.order || null;
    order.value = orderData;

    if (!orderData) {
      return payload;
    }

    const noteMeta = parseMethodAndNote(orderData.note || '');

    form.value = {
      customer_id: orderData.customer_id ? String(orderData.customer_id) : '',
      customer_name: orderData.customer_name || '',
      customer_phone: orderData.customer_phone || '',
      customer_address: orderData.customer_address || '',
      note: noteMeta.note,
      payment_status: orderData.status === 'debt' ? 'debt' : 'pay',
      payment_method: noteMeta.paymentMethod,
      payment_amount: orderData.paid_amount ? String(orderData.paid_amount) : '',
      discount_type: orderData.discount_type || 'none',
      discount_value: orderData.discount_value ? String(orderData.discount_value) : '',
      surcharge_amount: orderData.surcharge_amount ? String(orderData.surcharge_amount) : ''
    };

    rows.value = (data.items || []).map((item) => ({
      source_id: Number(item.id || 0),
      product_unit_id: String(item.product_unit_id || ''),
      qty: String(item.qty || ''),
      price: String(item.price_sell || '')
    }));

    existingItemIds.value = (data.items || []).map((item) => Number(item.id || 0)).filter((idValue) => idValue > 0);

    manualItems.value = (data.manual_items || []).map((item) => ({
      item_name: item.item_name || '',
      unit_name: item.unit_name || '',
      qty: String(item.qty || ''),
      price_buy: String(item.price_buy || ''),
      price_sell: String(item.price_sell || '')
    }));

    return payload;
  };

  const addRow = (initial = null) => {
    rows.value.push({
      source_id: 0,
      product_unit_id: initial?.product_unit_id || '',
      qty: initial?.qty || '1',
      price: initial?.price || ''
    });
  };

  const removeRow = (index) => {
    rows.value.splice(index, 1);
  };

  const addManualItem = (initial = null) => {
    manualItems.value.push({
      ...createEmptyManualItem(),
      ...(initial || {})
    });
  };

  const removeManualItem = (index) => {
    manualItems.value.splice(index, 1);
  };

  const syncCustomerFields = () => {
    const selected = customers.value.find((customer) => Number(customer.id) === Number(form.value.customer_id));
    if (!selected) {
      return;
    }

    form.value.customer_name = selected.name || '';
    form.value.customer_phone = selected.phone || '';
    form.value.customer_address = selected.address || '';
  };

  const buildCreatePayload = () => ({
    items: rows.value.map((row) => {
      const unit = rowDisplayMap.value.get(String(row.product_unit_id));
      return {
        product_id: Number(unit?.product_id || 0),
        unit_id: Number(unit?.unit_id || 0),
        quantity: Number(row.qty || 0),
        price: Number(row.price || 0)
      };
    }),
    customer_id: form.value.customer_id ? Number(form.value.customer_id) : 0,
    customer_name: form.value.customer_name,
    customer_phone: form.value.customer_phone,
    customer_address: form.value.customer_address,
    note: form.value.note,
    payment_status: form.value.payment_status,
    payment_method: form.value.payment_method,
    payment_amount: form.value.payment_amount,
    discount_type: form.value.discount_type,
    discount_value: form.value.discount_value,
    surcharge_amount: form.value.surcharge_amount,
    manual_item_name: manualItems.value.map((item) => item.item_name),
    manual_unit_name: manualItems.value.map((item) => item.unit_name),
    manual_qty: manualItems.value.map((item) => item.qty),
    manual_price_buy: manualItems.value.map((item) => item.price_buy),
    manual_price_sell: manualItems.value.map((item) => item.price_sell)
  });

  const buildUpdatePayload = () => ({
    customer_id: form.value.customer_id ? Number(form.value.customer_id) : '',
    customer_name: form.value.customer_name,
    customer_phone: form.value.customer_phone,
    customer_address: form.value.customer_address,
    note: form.value.note,
    discount_type: form.value.discount_type,
    discount_value: form.value.discount_value,
    surcharge_amount: form.value.surcharge_amount,
    remove_existing: existingItemIds.value,
    product_unit_id: rows.value.map((row) => row.product_unit_id),
    qty: rows.value.map((row) => row.qty),
    price: rows.value.map((row) => row.price),
    mode: rows.value.map(() => 'new'),
    manual_item_name: manualItems.value.map((item) => item.item_name),
    manual_unit_name: manualItems.value.map((item) => item.unit_name),
    manual_qty: manualItems.value.map((item) => item.qty),
    manual_price_buy: manualItems.value.map((item) => item.price_buy),
    manual_price_sell: manualItems.value.map((item) => item.price_sell)
  });

  const submitCreate = async () => createRequest.execute(buildCreatePayload());
  const submitUpdate = async (id) => updateRequest.execute(id, buildUpdatePayload());

  return {
    form,
    order,
    products,
    productUnits,
    rows,
    manualItems,
    summary,
    rowDisplayMap,
    loadBootstrap,
    loadEdit,
    addRow,
    removeRow,
    addManualItem,
    removeManualItem,
    syncCustomerFields,
    customers,
    submitCreate,
    submitUpdate,
    bootstrapLoading: bootstrapRequest.loading,
    bootstrapError: bootstrapRequest.error,
    detailLoading: detailRequest.loading,
    detailError: detailRequest.error,
    createLoading: createRequest.loading,
    createError: createRequest.error,
    updateLoading: updateRequest.loading,
    updateError: updateRequest.error
  };
}
