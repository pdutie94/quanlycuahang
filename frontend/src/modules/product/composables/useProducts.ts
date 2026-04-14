import { ref } from 'vue';
import { fetchProducts } from '../services/product.api';
import { useFetch } from '../../../shared/composables/useFetch';

function toNumber(value: any): number {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
}

function resolvePrimaryPrice(item: Record<string, any>, units: Record<string, any>[]) {
  const baseUnitId = toNumber(item?.base_unit_id);
  let primary: Record<string, any> | null = null;

  if (baseUnitId > 0) {
    primary = (units || []).find((unit) => toNumber(unit?.unit_id) === baseUnitId && toNumber(unit?.price_sell) > 0) || null;
  }

  if (!primary) {
    primary = (units || []).find((unit) => toNumber(unit?.price_sell) > 0) || null;
  }

  return {
    priceSell: primary ? toNumber(primary.price_sell) : null,
    priceCost: primary ? toNumber(primary.price_cost) : null,
    priceUnitName: primary?.unit_name || ''
  };
}

export function useProducts() {
  const items = ref<Record<string, any>[]>([]);
  const meta = ref({ page: 1, total_pages: 1 });
  const filters = ref<Record<string, any>>({ q: '', stock: 'all', category_id: null });
  const categories = ref<Record<string, any>[]>([]);

  const { loading, error, execute } = useFetch(fetchProducts);

  const load = async (params: Record<string, any> = {}) => {
    const payload = await execute(params);

    const rows = payload?.data?.items || [];
    const unitsByProduct = payload?.data?.product_units_by_product || {};

    const transformedItems = rows.map((item: Record<string, any>) => {
      const productId = Number(item?.id || 0);
      const units =
        unitsByProduct[String(productId)] ||
        unitsByProduct[productId] ||
        [];

      const primaryPrice = resolvePrimaryPrice(item, units);

      return {
        ...item,
        id: Number(item.id),
        display_price_sell: primaryPrice.priceSell,
        display_price_cost: primaryPrice.priceCost,
        display_price_unit_name: primaryPrice.priceUnitName
      };
    });

    if (params.page === 1 || !params.page) {
      items.value = transformedItems;
    }

    meta.value = payload?.data?.meta || { page: 1, total_pages: 1 };
    filters.value = payload?.data?.filters || {};
    categories.value = payload?.data?.categories || [];

    return {
      ...payload,
      data: {
        ...payload.data,
        items: transformedItems
      }
    };
  };

  return {
    items,
    meta,
    filters,
    categories,
    loading,
    error,
    load
  };
}
