<script setup lang="ts">
import { useFormat } from "../../../shared/composables/useFormat";
const {
    formatMoney,
    parseAmount,
    formatNumber,
    formatMoneyInput,
    formatPriceInput,
} = useFormat();
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { Package, Users, X, Plus } from "@lucide/vue";
import { useRoute, useRouter } from "vue-router";
import { usePurchaseForm } from "../composables/usePurchaseForm";
import type { PurchaseItem, ManualPurchaseItem } from "../types";
import { useToast } from "../../../shared/composables/useToast";
import DetailHeaderBar from "../../../shared/components/DetailHeaderBar.vue";
import ProductSelectorModal, {
    type SelectorItem,
} from "../../../shared/components/ProductSelectorModal.vue";
import ManualItemModal from "../../../shared/components/ManualItemModal.vue";

const route = useRoute();
const router = useRouter();
const toast = useToast();

const {
    suppliers,
    productUnits,
    baseUnits,
    form,
    rows,
    manualItems,
    rowDisplayMap,
    summary,
    bootstrapLoading,
    bootstrapError,
    loadBootstrap,
    loadEdit,
    addRow,
    removeRow,
    addManualItem,
    removeManualItem,
    createInlineSupplier,
    createInlineProduct,
    createSupplierLoading,
    createSupplierError,
    createProductError,
    resetState,
    submitCreate,
    createLoading,
    createError,
    submitUpdate,
    updateLoading,
    updateError,
} = usePurchaseForm();

const formatter = new Intl.NumberFormat("vi-VN");
const isEdit = computed(() => Number(route.params.id || 0) > 0);
const pageTitle = computed(() =>
    isEdit.value ? "Chỉnh sửa phiếu nhập" : "Tạo phiếu nhập hàng",
);
const loading = computed(() => bootstrapLoading.value);
const saving = computed(() => createLoading.value || updateLoading.value);
const isPayNow = computed(() => form.value.payment_status === "pay");

const showSupplierModal = ref(false);
const showProductSelector = ref(false);
const showManualItemModal = ref(false);
const supplierMode = ref("existing");
const supplierKeyword = ref("");

const pendingSupplierId = ref("");
const selectedProductIds = ref<number[]>([]);
const selectedGiftProductIds = ref<number[]>([]);
const activeRowIndex = ref<number | null>(null);
const editingManualIndex = ref<number | null>(null);
const supplierNameInput = ref<HTMLInputElement | null>(null);
const customerNameInput = ref<HTMLInputElement | null>(null);
const supplierDraft = ref({
    name: "",
    phone: "",
    address: "",
});
const currentManualDraft = ref<any>({
    item_name: "",
    unit_name: "",
    qty: "1",
    price_cost: "",
    amount: "",
});

// --- normalizeRowQty logic từ OrderFormPage ---
const getRowStep = (row: PurchaseItem) => {
    const unit = getUnitDisplay(row);
    const allowFraction = Number(unit?.allow_fraction || 0) === 1;
    const minStep = Number(unit?.min_step || 1);
    if (!allowFraction) {
        return 1;
    }
    if (!Number.isFinite(minStep) || minStep <= 0) {
        return 1;
    }
    return minStep;
};

const formatQtyValue = (value: string | number) =>
    Number(value || 0)
        .toFixed(4)
        .replace(/\.?0+$/, "");

const normalizeRowQty = (row: PurchaseItem) => {
    const step = getRowStep(row);
    const currentQty = Number(row.qty || 0);
    if (!Number.isFinite(currentQty) || currentQty <= 0) {
        row.qty = formatQtyValue(step);
        return;
    }
    if (step === 1) {
        row.qty = formatQtyValue(Math.max(1, Math.round(currentQty)));
    } else {
        const normalizedQty = Math.max(
            step,
            Math.round(currentQty / step) * step,
        );
        row.qty = formatQtyValue(normalizedQty);
    }
};

function onRowQtyBlur(row: PurchaseItem) {
    normalizeRowQty(row);
    onRowQtyInput(row);
}

const filteredSuppliers = computed(() => {
    const keyword = String(supplierKeyword.value || "")
        .trim()
        .toLowerCase();
    if (!keyword) {
        return suppliers.value;
    }

    return suppliers.value.filter((supplier) => {
        const haystack = [supplier.name, supplier.phone, supplier.address]
            .map((value) => String(value || "").toLowerCase())
            .join(" ");

        return haystack.includes(keyword);
    });
});

const selectedSupplier = computed(
    () =>
        suppliers.value.find(
            (supplier) =>
                Number(supplier.id) === Number(form.value.supplier_id || 0),
        ) || null,
);

const selectedSupplierSummary = computed(() => {
    if (!selectedSupplier.value) {
        return {
            title: "Chưa chọn nhà cung cấp",
            meta: "Nhấn để chọn nhà cung cấp cũ hoặc thêm mới",
        };
    }

    return {
        title:
            [selectedSupplier.value.name, selectedSupplier.value.phone]
                .filter(Boolean)
                .join(" - ") ||
            selectedSupplier.value.name ||
            "Chưa chọn nhà cung cấp",
        meta: selectedSupplier.value.address || "",
    };
});

const productCatalog = computed(() => {
    const map = new Map();

    for (const unit of productUnits.value) {
        const productId = Number(unit.product_id || 0);
        if (productId <= 0 || map.has(productId)) {
            continue;
        }

        map.set(productId, {
            id: productId,
            name: unit.product_name || "",
            code: unit.product_code || "",
            unitName: unit.unit_name || "",
            priceCost: Number(unit.price_cost || 0),
            defaultUnitId: Number(unit.id || 0),
        });
    }

    return Array.from(map.values());
});

// --- Đồng bộ giá nhập, tổng tiền, số lượng cho từng dòng sản phẩm ---
function parseMoneyInput(val: string | number) {
    return Number(String(val).replace(/\D/g, "")) || 0;
}

function onRowQtyInput(row: PurchaseItem) {
    // Khi sửa số lượng: giữ tổng, tính lại giá nhập
    const qty = Number(row.qty) || 0;
    const amount = parseMoneyInput(row.amount);
    if (qty > 0) {
        row.price_cost =
            amount > 0 ? formatter.format(Math.round(amount / qty)) : "";
    } else {
        row.price_cost = "";
    }
}

function onRowPriceInput(row: PurchaseItem) {
    // Khi sửa giá nhập: tính lại tổng
    const qty = Number(row.qty) || 0;
    const price = parseMoneyInput(row.price_cost);
    row.amount = qty > 0 && price > 0 ? formatter.format(qty * price) : "";
}

function onRowAmountInput(row: PurchaseItem) {
    // Khi sửa tổng: tính lại giá nhập
    const qty = Number(row.qty) || 0;
    const amount = parseMoneyInput(row.amount);
    if (qty > 0) {
        row.price_cost =
            amount > 0 ? formatter.format(Math.round(amount / qty)) : "";
    } else {
        row.price_cost = "";
    }
}

function syncAmount(row: PurchaseItem) {
    const qty = Number(row.qty) || 0;
    const price = parseMoneyInput(row.price_cost);
    row.amount =
        qty > 0 && price > 0 ? formatter.format(Math.round(qty * price)) : "";
}

function onManualPriceInput(item: ManualPurchaseItem) {
    const qty = Number(item.qty) || 0;
    const price = parseMoneyInput(item.price_cost);
    item.amount = qty > 0 && price > 0 ? formatter.format(qty * price) : "";
}

function onManualAmountInput(item: ManualPurchaseItem) {
    const qty = Number(item.qty) || 0;
    const amount = parseMoneyInput(item.amount);
    if (qty > 0) {
        item.price_cost =
            amount > 0 ? formatter.format(Math.round(amount / qty)) : "";
    } else {
        item.price_cost = "";
    }
}

const selectorItems = computed<SelectorItem[]>(() =>
    productCatalog.value.map((p) => ({
        id: Number(p.id),
        name: p.name || "",
        unitName: p.unitName || "",
        subLabel: p.code || "",
        price: Number(p.priceCost || 0),
    })),
);

const isProductSelected = (productId: number) =>
    selectedProductIds.value.includes(Number(productId));
const isPendingSupplier = (supplierId: string | number) =>
    String(pendingSupplierId.value) === String(supplierId);
const getDefaultUnit = (productId: number) =>
    productUnits.value.find(
        (unit: any) => Number(unit.product_id) === Number(productId),
    ) || null;
const getUnitDisplay = (row: PurchaseItem) =>
    rowDisplayMap.value.get(String(row.product_unit_id)) || null;

const manualModalHeading = computed(() => {
    if (editingManualIndex.value === null) {
        return "Thêm sản phẩm khác";
    }

    const fallbackName =
        manualItems.value[editingManualIndex.value]?.item_name || "";
    const displayName =
        String(currentManualDraft.value.item_name || fallbackName).trim() ||
        "Sản phẩm khác";

    return displayName;
});

const syncingPaidAmount = ref(false);

const normalizeManualDraft = () => ({
    item_name: "",
    unit_name: "",
    qty: "1",
    price_cost: "",
    amount: "",
});

const normalizeUnitName = (value: string | null | undefined) =>
    String(value || "")
        .trim()
        .toLowerCase();

const syncPaidAmountFromSummary = () => {
    if (isEdit.value) {
        return;
    }

    syncingPaidAmount.value = true;
    form.value.paid_amount =
        summary.value.grandTotal > 0
            ? formatter.format(summary.value.grandTotal)
            : "";
    nextTick(() => {
        syncingPaidAmount.value = false;
    });
};

const resetSupplierDraft = () => {
    supplierDraft.value = {
        name: "",
        phone: "",
        address: "",
    };
};

const openSupplierModal = () => {
    supplierKeyword.value = "";
    pendingSupplierId.value = form.value.supplier_id
        ? String(form.value.supplier_id)
        : "";
    supplierMode.value = "existing";
    resetSupplierDraft();
    showSupplierModal.value = true;
};

const closeSupplierModal = () => {
    showSupplierModal.value = false;
};

const applySelectedSupplier = () => {
    if (!pendingSupplierId.value) {
        closeSupplierModal();
        return;
    }

    form.value.supplier_id = String(pendingSupplierId.value);
    closeSupplierModal();
};

const saveNewSupplier = async () => {
    if (!String(supplierDraft.value.name || "").trim()) {
        toast.error("Vui lòng nhập tên nhà cung cấp mới.");
        supplierNameInput.value?.focus();
        return;
    }

    try {
        const payload = await createInlineSupplier({ ...supplierDraft.value });
        toast.success(payload?.message || "Đã thêm nhà cung cấp.");
        closeSupplierModal();
    } catch (_err: unknown) {
        toast.error(
            createSupplierError.value || "Không thể thêm nhà cung cấp.",
        );
    }
};

const openProductSelector = (rowIndex: number | null = null) => {
    const normalizedRowIndex =
        typeof rowIndex === "number" && Number.isFinite(rowIndex)
            ? rowIndex
            : null;

    activeRowIndex.value = normalizedRowIndex;
    if (
        normalizedRowIndex === null ||
        normalizedRowIndex < 0 ||
        normalizedRowIndex >= rows.value.length
    ) {
        selectedProductIds.value = [];
    } else {
        const currentUnit = getUnitDisplay(rows.value[normalizedRowIndex]);
        selectedProductIds.value = currentUnit?.product_id
            ? [Number(currentUnit.product_id)]
            : [];
    }
    showProductSelector.value = true;
};

const closeProductSelector = () => {
    showProductSelector.value = false;
    selectedProductIds.value = [];
    activeRowIndex.value = null;
};

const toggleProductSelection = (productId: number) => {
    const nextId = Number(productId);
    if (isProductSelected(nextId)) {
        selectedProductIds.value = selectedProductIds.value.filter(
            (id) => id !== nextId,
        );
        return;
    }

    selectedProductIds.value = [...selectedProductIds.value, nextId];
};

const applySelectedProducts = () => {
    if (!selectedProductIds.value.length) {
        return;
    }

    if (
        activeRowIndex.value !== null &&
        activeRowIndex.value >= 0 &&
        activeRowIndex.value < rows.value.length
    ) {
        const productId = Number(selectedProductIds.value[0] || 0);
        const unit = getDefaultUnit(productId);
        if (!unit?.id) {
            toast.error("Sản phẩm chưa có đơn vị nhập phù hợp.");
            return;
        }

        const row = rows.value[activeRowIndex.value];
        const qty = Number(row.qty || 0) > 0 ? formatNumber(row.qty) : "1";
        row.product_unit_id = String(unit.id);
        row.qty = qty;
        row.price_cost = formatPriceInput(unit.price_cost || 0, false);
        syncAmount(row);
        closeProductSelector();
        return;
    }

    let addedCount = 0;
    let invalidCount = 0;

    selectedProductIds.value.forEach((productId: number) => {
        const unit = getDefaultUnit(productId);
        if (!unit?.id) {
            invalidCount += 1;
            return;
        }

        const amount = Math.round(Number(unit.price_cost || 0));
        addRow({
            product_unit_id: String(unit.id),
            qty: "1",
            price_cost: amount > 0 ? formatPriceInput(amount) : "",
            amount: amount > 0 ? formatPriceInput(amount) : "",
            update_cost: false,
        });
        addedCount += 1;
    });

    if (invalidCount > 0) {
        toast.error(`${invalidCount} sản phẩm chưa có đơn vị nhập phù hợp.`);
    }

    if (addedCount > 0) {
        closeProductSelector();
    }
};

const openManualItemModal = (index: number | null = null) => {
    editingManualIndex.value = index;

    if (index === null || index < 0 || index >= manualItems.value.length) {
        currentManualDraft.value = {
            item_name: "",
            unit_name: "",
            qty: "1",
            price_cost: "",
            amount: "",
        };
    } else {
        const source = manualItems.value[index];
        currentManualDraft.value = {
            ...{
                item_name: "",
                unit_name: "",
                qty: "1",
                price_cost: "",
                amount: "",
            },
            item_name: source.item_name || "",
            unit_name: source.unit_name || "",
            qty: String(source.qty ?? "1"),
            price_cost: String(source.price_cost ?? ""),
            amount: String(source.amount ?? ""),
        };
    }

    showManualItemModal.value = true;
};

const closeManualItemModal = () => {
    showManualItemModal.value = false;
    editingManualIndex.value = null;
    currentManualDraft.value = {
        item_name: "",
        unit_name: "",
        qty: "1",
        price_cost: "",
        amount: "",
    };
};

const onManualItemSave = async (draft: any) => {
    const normalizedItem = {
        item_name: String(draft.item_name || "").trim(),
        unit_name: String(draft.unit_name || "").trim(),
        qty: String(draft.qty || "").trim(),
        price_cost: formatPriceInput(draft.price_cost, true),
        amount: formatPriceInput(draft.amount, true),
    };

    if (!normalizedItem.item_name) {
        toast.error("Vui lòng nhập tên sản phẩm khác.");
        return;
    }

    if (Number(normalizedItem.qty || 0) <= 0) {
        toast.error("Số lượng sản phẩm khác phải lớn hơn 0.");
        return;
    }

    if (!normalizedItem.amount && normalizedItem.price_cost) {
        const qty = Number(normalizedItem.qty || 0);
        const price = parseAmount(normalizedItem.price_cost || 0);
        const amount = qty * price;
        normalizedItem.amount =
            amount > 0 ? formatter.format(Math.round(amount)) : "";
    }

    if (!normalizedItem.price_cost && normalizedItem.amount) {
        const qty = Number(normalizedItem.qty || 0);
        const amount = parseAmount(normalizedItem.amount || 0);
        const price = qty > 0 ? Math.round(amount / qty) : 0;
        normalizedItem.price_cost = price > 0 ? formatter.format(price) : "";
    }

    if (
        editingManualIndex.value === null ||
        editingManualIndex.value < 0 ||
        editingManualIndex.value >= manualItems.value.length
    ) {
        addManualItem(normalizedItem);
    } else {
        manualItems.value[editingManualIndex.value as number] = {
            ...manualItems.value[editingManualIndex.value as number],
            ...normalizedItem,
        };
    }

    closeManualItemModal();
    toast.success("Đã lưu sản phẩm khác.");
};

const validateBeforeSubmit = (): boolean => {
    if (!Number(form.value.supplier_id || 0)) {
        toast.error("Vui lòng chọn nhà cung cấp.");
        openSupplierModal();
        return false;
    }

    if (!rows.value.length && !manualItems.value.length) {
        toast.error(
            "Vui lòng chọn ít nhất một sản phẩm hoặc thêm sản phẩm khác.",
        );
        openProductSelector();
        return false;
    }

    const hasInvalidRow = rows.value.some(
        (row) =>
            !String(row.product_unit_id || "").trim() ||
            Number(row.qty || 0) <= 0,
    );
    if (hasInvalidRow) {
        toast.error("Danh sách sản phẩm còn dòng chưa hợp lệ.");
        return false;
    }

    const hasInvalidManualItem = manualItems.value.some(
        (item) =>
            !String(item.item_name || "").trim() || Number(item.qty || 0) <= 0,
    );
    if (hasInvalidManualItem) {
        toast.error("Sản phẩm khác còn dữ liệu chưa hợp lệ.");
        return false;
    }

    return true;
};

const submit = async () => {
    if (!validateBeforeSubmit()) {
        return;
    }

    try {
        const payload = isEdit.value
            ? await submitUpdate(Number(route.params.id || 0))
            : await submitCreate();

        toast.success(
            payload?.message ||
                (isEdit.value
                    ? "Đã cập nhật phiếu nhập hàng."
                    : "Đã tạo phiếu nhập hàng."),
        );
        // Không chuyển hướng, giữ nguyên trang hiện tại sau khi cập nhật/tạo phiếu
    } catch (_err: unknown) {
        toast.error(
            (isEdit.value ? updateError.value : createError.value) ||
                "Không thể lưu phiếu nhập.",
        );
    }
};

// --- normalizeManualQty logic từ OrderFormPage ---
const detectManualQtyPrecision = (
    value: string | number | null | undefined,
) => {
    const rawValue = String(value ?? "").trim();
    if (!rawValue || !rawValue.includes(".")) {
        return 0;
    }
    const fractionalPart = rawValue
        .split(".")[1]
        .replace(/[^0-9]/g, "")
        .replace(/0+$/, "");
    return fractionalPart.length;
};

const getManualQtyPrecision = (item: ManualPurchaseItem): number => {
    const typed = detectManualQtyPrecision(item?.qty);

    if (typed !== null && typed !== undefined) {
        return typed; // 👈 ưu tiên input mới
    }

    const storedPrecision = Number(item?.qty_precision);
    if (Number.isInteger(storedPrecision) && storedPrecision >= 0) {
        return storedPrecision;
    }

    return 0;
};

const getManualQtyStep = (item: ManualPurchaseItem) =>
    1 / 10 ** getManualQtyPrecision(item);
const getManualQtyMin = (item: ManualPurchaseItem) => getManualQtyStep(item);

const roundManualQtyByPrecision = (
    value: string | number,
    precision: number,
) => {
    const factor = 10 ** precision;
    return Math.round(Number(value || 0) * factor) / factor;
};

const formatManualQtyValue = (value: string | number, precision = 4) =>
    Number(value).toFixed(precision);

const normalizeManualQty = (item: ManualPurchaseItem) => {
    const currentQty = Number(item.qty || 0);
    const precision = getManualQtyPrecision(item);
    const step = getManualQtyStep(item);
    const minQty = getManualQtyMin(item);

    if (!Number.isFinite(currentQty) || currentQty <= 0) {
        item.qty = formatManualQtyValue(1, precision);
        return;
    }

    const normalizedQty = Math.max(
        minQty,
        Math.round(currentQty / step) * step,
    );

    item.qty = formatManualQtyValue(normalizedQty, precision);
};

function onManualQtyInput(item: ManualPurchaseItem) {
    // Khi sửa số lượng: tính lại giá nhập = tổng tiền / số lượng
    const qty = Number(item.qty) || 0;
    const amount = parseMoneyInput(item.amount);
    if (qty > 0 && amount > 0) {
        item.price_cost = formatter.format(Math.round(amount / qty));
    }
}

function onManualQtyBlur(item: ManualPurchaseItem) {
    normalizeManualQty(item);
    onManualQtyInput(item);
}

const initializePage = async () => {
    resetState();
    await loadBootstrap();

    if (isEdit.value) {
        await loadEdit(Number(route.params.id || 0));
        // Normalize số lượng cho từng row khi load form edit
        rows.value.forEach((row: PurchaseItem) => {
            normalizeRowQty(row);
            row.price_cost = formatMoneyInput(row.price_cost, false);
            row.amount = formatMoneyInput(row.amount, false);
        });
        // Normalize số lượng cho từng sản phẩm khác khi load form edit
        manualItems.value.forEach((item: ManualPurchaseItem) => {
            normalizeManualQty(item);
            item.price_cost = formatMoneyInput(item.price_cost, false);
            item.amount = formatMoneyInput(item.amount, false);
        });
    }
};

watch(supplierMode, async (mode) => {
    if (mode !== "new") {
        return;
    }

    await nextTick();
    supplierNameInput.value?.focus();
});

watch(
    () => summary.value.grandTotal,
    () => {
        if (syncingPaidAmount.value) {
            return;
        }

        syncPaidAmountFromSummary();
    },
);

watch(
    () => form.value.payment_status,
    (status) => {
        if (isEdit.value) {
            return;
        }

        if (status === "pay" || !String(form.value.paid_amount || "").trim()) {
            syncPaidAmountFromSummary();
        }
    },
);

watch(
    () => route.params.id,
    async () => {
        try {
            await initializePage();
        } catch (_err: unknown) {
            toast.error(
                bootstrapError.value ||
                    "Không thể tải dữ liệu form phiếu nhập.",
            );
        }
    },
);

onMounted(async () => {
    try {
        await initializePage();
        syncPaidAmountFromSummary();
    } catch (_err: unknown) {
        toast.error(
            bootstrapError.value || "Không thể tải dữ liệu form phiếu nhập.",
        );
    }
});
</script>

<template>
    <section class="space-y-4">
        <DetailHeaderBar
            :title="pageTitle"
            :back-to="
                isEdit
                    ? {
                          name: 'purchases.detail',
                          params: { id: route.params.id },
                      }
                    : '/purchases'
            "
        />

        <div
            v-if="loading"
            class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500"
        >
            Đang tải dữ liệu form...
        </div>

        <template v-else>
            <section class="space-y-4">
                <section class="rounded-lg border border-slate-200 bg-white">
                    <div
                        class="flex items-center gap-2 border-b border-slate-100 px-4 py-2 text-sm font-medium text-slate-800"
                    >
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
                            ><Users class="h-4 w-4"
                        /></span>
                        <span>Nhà cung cấp</span>
                    </div>

                    <div class="px-4 py-3">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-left text-sm text-amber-800 hover:border-amber-400 hover:bg-amber-100"
                            @click="openSupplierModal"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-100 text-amber-700"
                                    ><Users class="h-4 w-4"
                                /></span>
                                <span class="flex flex-col">
                                    <span class="font-medium">{{
                                        selectedSupplierSummary.title
                                    }}</span>
                                    <span class="text-xs text-amber-700">{{
                                        selectedSupplierSummary.meta
                                    }}</span>
                                </span>
                            </div>
                            <span
                                class="ml-2 inline-flex h-6 w-6 items-center justify-center text-amber-500"
                            >
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m8 6 4 4-4 4"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                        </button>
                    </div>
                </section>

                <section>
                    <div
                        class="flex items-center justify-between text-sm font-medium text-slate-800"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"
                                ><Package class="h-4 w-4"
                            /></span>
                            <span>Sản phẩm</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="app-btn-primary !min-h-0 gap-1.5 px-3 py-1 text-sm"
                                @click="openProductSelector()"
                            >
                                <Plus class="w-4 h-4" />Thêm
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 mt-3">
                        <div
                            v-if="!rows.length"
                            class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500"
                        >
                            Chưa có sản phẩm nào.
                        </div>

                        <div
                            v-for="(row, index) in rows"
                            :key="`row-${index}`"
                            class="rounded-xl border border-slate-200 bg-white px-3 pt-2 pb-1.5"
                        >
                            <div
                                class="flex flex-row items-center gap-2 sm:gap-3"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm truncate">
                                        {{
                                            getUnitDisplay(row)?.product_name ||
                                            ""
                                        }}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-rose-600 hover:text-rose-700"
                                    @click="removeRow(index)"
                                >
                                    Xóa
                                </button>
                            </div>
                            <div
                                class="mt-1 grid grid-cols-2 md:grid-cols-3 gap-2"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Số lượng</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            v-model="row.qty"
                                            min="0"
                                            :step="
                                                row.allow_fraction == 1
                                                    ? row.min_step || 1
                                                    : getUnitDisplay(row)
                                                            ?.allow_fraction ==
                                                        1
                                                      ? getUnitDisplay(row)
                                                            ?.min_step || 1
                                                      : 1
                                            "
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-10"
                                            @input="onRowQtyInput(row)"
                                            @blur="onRowQtyBlur(row)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >{{
                                                getUnitDisplay(row)
                                                    ?.unit_name || ""
                                            }}</span
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Giá nhập</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-money-input
                                            min="0"
                                            v-model="row.price_cost"
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7"
                                            @input="onRowPriceInput(row)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >đ</span
                                        >
                                    </div>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Thành tiền</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-money-input
                                            min="0"
                                            v-model="row.amount"
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7"
                                            @input="onRowAmountInput(row)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >đ</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <label
                                class="mt-2 inline-flex items-center gap-2 text-sm text-slate-700"
                            >
                                <input
                                    v-model="row.update_cost"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-brand-600"
                                />
                                <span>Cập nhật giá vốn</span>
                            </label>
                        </div>
                    </div>
                </section>

                <section>
                    <div
                        class="flex items-center justify-between text-sm font-medium text-slate-800"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-50 text-brand-700"
                                ><Package class="h-4 w-4"
                            /></span>
                            <span>Sản phẩm khác</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="app-btn-primary !min-h-0 gap-1.5 px-3 py-1 text-sm"
                                @click="openManualItemModal()"
                            >
                                <Plus class="w-4 h-4" /> Thêm
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 mt-3">
                        <div
                            v-if="!manualItems.length"
                            class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-center text-sm text-slate-500"
                        >
                            Chưa có sản phẩm nào.
                        </div>

                        <div
                            v-for="(item, index) in manualItems"
                            :key="`manual-${index}`"
                            class="rounded-xl border border-slate-200 bg-white px-3 pt-2 pb-2.5"
                        >
                            <div
                                class="flex flex-row items-center gap-2 sm:gap-3"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm truncate">
                                        {{ item.item_name }}
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-brand-700 hover:text-brand-800"
                                    @click="openManualItemModal(index)"
                                >
                                    Sửa
                                </button>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-rose-600 hover:text-rose-700"
                                    @click="removeManualItem(index)"
                                >
                                    Xóa
                                </button>
                            </div>
                            <div
                                class="mt-1 grid grid-cols-2 md:grid-cols-3 gap-2"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Số lượng</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-model="item.qty"
                                            inputmode="decimal"
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-10"
                                            @input="onManualQtyInput(item)"
                                            @blur="onManualQtyBlur(item)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >{{ item.unit_name }}</span
                                        >
                                    </div>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Giá nhập</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-money-input
                                            min="0"
                                            v-model="item.price_cost"
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7"
                                            @input="onManualPriceInput(item)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >đ</span
                                        >
                                    </div>
                                </div>
                                <div class="col-span-2 md:col-span-1">
                                    <label
                                        class="block text-xs font-medium text-slate-600 mb-0.5"
                                        >Thành tiền</label
                                    >
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-money-input
                                            min="0"
                                            v-model="item.amount"
                                            class="text-sm rounded-md border border-slate-300 px-2 py-1 w-full pr-7"
                                            @input="onManualAmountInput(item)"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-sm text-slate-400"
                                            >đ</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-slate-700"
                                >Tổng nhập</span
                            ><span
                                class="text-lg font-semibold text-slate-900"
                                >{{ formatMoney(summary.grandTotal) }}</span
                            >
                        </div>
                    </div>

                    <div
                        class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4"
                    >
                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >Ngày giờ phiếu nhập</label
                            >
                            <input
                                v-model="form.purchase_date"
                                type="datetime-local"
                                class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
                            />
                        </div>

                        <template v-if="!isEdit">
                            <div>
                                <label
                                    class="mb-1 block text-sm font-medium text-slate-700"
                                    >Trạng thái thanh toán</label
                                >
                                <div class="app-segment">
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            form.payment_status === 'pay'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="form.payment_status = 'pay'"
                                    >
                                        Thanh toán
                                    </button>
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            form.payment_status === 'debt'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="form.payment_status = 'debt'"
                                    >
                                        Ghi nợ
                                    </button>
                                </div>
                            </div>

                            <div v-if="isPayNow">
                                <label
                                    class="mb-1 block text-sm font-medium text-slate-700"
                                    >Hình thức thanh toán</label
                                >
                                <div class="app-segment">
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            form.payment_method === 'cash'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="form.payment_method = 'cash'"
                                    >
                                        Tiền mặt
                                    </button>
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            form.payment_method === 'bank'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="form.payment_method = 'bank'"
                                    >
                                        Chuyển khoản
                                    </button>
                                </div>
                            </div>

                            <div v-if="isPayNow">
                                <label
                                    class="mb-1 block text-sm font-medium text-slate-700"
                                    >Số tiền thanh toán</label
                                >
                                <div class="relative">
                                    <input
                                        v-model="form.paid_amount"
                                        type="text"
                                        v-money-input
                                        class="h-10 w-full rounded-xl border border-slate-300 px-3 pr-8 text-sm outline-none focus:border-brand-500"
                                    />
                                    <span
                                        class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                        >đ</span
                                    >
                                </div>
                            </div>
                        </template>

                        <div>
                            <label
                                class="mb-1 block text-sm font-medium text-slate-700"
                                >Ghi chú</label
                            >
                            <textarea
                                v-model="form.note"
                                rows="3"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-brand-500"
                                placeholder="Nhập ghi chú cho phiếu nhập này..."
                            ></textarea>
                        </div>
                    </div>
                </section>

                <button
                    type="button"
                    class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-brand-600 bg-brand-600 px-4 text-sm font-medium text-white disabled:opacity-50"
                    :disabled="saving"
                    @click="submit"
                >
                    {{ isEdit ? "Cập nhật phiếu" : "Lưu phiếu" }}
                </button>
            </section>

            <Teleport to="body">
                <transition name="app-modal-fade-up">
                    <div
                        v-if="showSupplierModal"
                        class="app-modal-overlay app-modal-open"
                        @click.self="closeSupplierModal"
                    >
                        <div class="app-modal-sheet app-modal-sheet-fill">
                            <div class="app-modal-header">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
                                        ><Users class="h-4 w-4"
                                    /></span>
                                    <h2 class="app-modal-title">
                                        Chọn nhà cung cấp
                                    </h2>
                                </div>
                                <button
                                    type="button"
                                    class="app-modal-close"
                                    @click="closeSupplierModal"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <div
                                class="app-modal-body app-modal-body-fill pt-2 pb-3 space-y-3"
                            >
                                <div class="app-segment">
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            supplierMode === 'existing'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="supplierMode = 'existing'"
                                    >
                                        Nhà cung cấp cũ
                                    </button>
                                    <button
                                        type="button"
                                        class="app-segment-item"
                                        :class="
                                            supplierMode === 'new'
                                                ? 'app-segment-item-active'
                                                : ''
                                        "
                                        @click="supplierMode = 'new'"
                                    >
                                        Nhà cung cấp mới
                                    </button>
                                </div>

                                <template v-if="supplierMode === 'existing'">
                                    <div>
                                        <input
                                            v-model="supplierKeyword"
                                            type="search"
                                            placeholder="Tìm theo tên, SĐT, địa chỉ..."
                                            class="app-input"
                                        />
                                    </div>
                                    <div
                                        class="app-modal-body-scroll rounded-lg border border-slate-200"
                                    >
                                        <button
                                            v-for="supplier in filteredSuppliers"
                                            :key="supplier.id"
                                            type="button"
                                            class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                                            @click="
                                                pendingSupplierId = String(
                                                    supplier.id,
                                                )
                                            "
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-2"
                                            >
                                                <span
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700"
                                                    ><Users class="h-4 w-4"
                                                /></span>
                                                <div class="min-w-0">
                                                    <div
                                                        class="truncate font-medium text-slate-900"
                                                    >
                                                        {{ supplier.name
                                                        }}<span
                                                            v-if="
                                                                supplier.phone
                                                            "
                                                        >
                                                            -
                                                            {{
                                                                supplier.phone
                                                            }}</span
                                                        >
                                                    </div>
                                                    <div
                                                        v-if="supplier.address"
                                                        class="mt-0.5 line-clamp-2 text-xs text-slate-500"
                                                    >
                                                        {{ supplier.address }}
                                                    </div>
                                                </div>
                                            </div>
                                            <span
                                                v-if="
                                                    isPendingSupplier(
                                                        supplier.id,
                                                    )
                                                "
                                                class="inline-flex items-center gap-1 text-xs font-medium text-brand-700"
                                            >
                                                <span
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-lg bg-brand-100 text-brand-700"
                                                    >✓</span
                                                >
                                                <span>Đã chọn</span>
                                            </span>
                                        </button>
                                        <div
                                            v-if="!filteredSuppliers.length"
                                            class="px-3 py-4 text-center text-sm text-slate-500"
                                        >
                                            Chưa có nhà cung cấp phù hợp.
                                        </div>
                                    </div>
                                </template>

                                <template v-else>
                                    <label class="space-y-1">
                                        <span class="app-label"
                                            >Tên nhà cung cấp</span
                                        >
                                        <input
                                            ref="supplierNameInput"
                                            v-model="supplierDraft.name"
                                            type="text"
                                            class="app-input"
                                        />
                                    </label>
                                    <label class="space-y-1">
                                        <span class="app-label"
                                            >Số điện thoại</span
                                        >
                                        <input
                                            v-model="supplierDraft.phone"
                                            type="text"
                                            class="app-input"
                                        />
                                    </label>
                                    <label class="space-y-1">
                                        <span class="app-label">Địa chỉ</span>
                                        <input
                                            v-model="supplierDraft.address"
                                            type="text"
                                            class="app-input"
                                        />
                                    </label>
                                </template>
                            </div>

                            <div class="app-modal-footer">
                                <button
                                    type="button"
                                    class="app-btn-secondary"
                                    @click="closeSupplierModal"
                                >
                                    Hủy
                                </button>
                                <button
                                    v-if="supplierMode === 'existing'"
                                    type="button"
                                    class="app-btn-primary"
                                    @click="applySelectedSupplier"
                                >
                                    Chọn
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="app-btn-primary"
                                    :disabled="createSupplierLoading"
                                    @click="saveNewSupplier"
                                >
                                    Lưu nhà cung cấp
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>

                <ProductSelectorModal
                    :open="showProductSelector"
                    :items="selectorItems"
                    :selected-ids="selectedProductIds"
                    search-placeholder="Tìm sản phẩm, mã hàng..."
                    @toggle="toggleProductSelection"
                    @apply="applySelectedProducts"
                    @close="closeProductSelector"
                />

                <ManualItemModal
                    :open="showManualItemModal"
                    :title="manualModalHeading"
                    mode="purchase"
                    :initial-draft="currentManualDraft"
                    :base-units="baseUnits"
                    @save="onManualItemSave"
                    @close="closeManualItemModal"
                />
            </Teleport>
        </template>
    </section>
</template>
