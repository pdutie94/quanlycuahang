<script setup lang="ts">
import { ref, watch } from "vue";
import { X } from "@lucide/vue";

type ManualMode = "order" | "purchase";

export interface BaseUnit {
    id: number | string;
    name: string;
}

export interface OrderManualDraft {
    item_name: string;
    unit_name: string;
    qty: string | number;
    price_buy?: string;
    price_sell?: string;
}

export interface PurchaseManualDraft {
    item_name: string;
    unit_name: string;
    qty: string | number;
    qty_precision?: number;
    price_cost?: string;
    amount?: string;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        mode?: ManualMode;
        initialDraft?: OrderManualDraft | PurchaseManualDraft;
        baseUnits?: BaseUnit[];
    }>(),
    {
        mode: "order",
        initialDraft: () => ({ item_name: "", unit_name: "", qty: "1" }),
        baseUnits: () => [],
    },
);

const emit = defineEmits<{
    save: [draft: OrderManualDraft | PurchaseManualDraft];
    close: [];
}>();

const numberFormatter = new Intl.NumberFormat("vi-VN");

const itemName = ref("");
const unitName = ref("");
const qty = ref("1");
const qtyPrecision = ref(0);
// order fields
const priceBuy = ref("");
const priceSell = ref("");
// purchase fields
const priceCost = ref("");
const amount = ref("");

const initFromDraft = (draft: OrderManualDraft | PurchaseManualDraft) => {
    itemName.value = String(draft.item_name || "");
    unitName.value = String(draft.unit_name || "");
    qty.value = String(draft.qty || "1");
    qtyPrecision.value = Number(
        (draft as PurchaseManualDraft).qty_precision || 0,
    );
    if (props.mode === "purchase") {
        const d = draft as PurchaseManualDraft;
        priceCost.value = String(d.price_cost || "");
        amount.value = String(d.amount || "");
    } else {
        const d = draft as OrderManualDraft;
        priceBuy.value = String(d.price_buy || "");
        priceSell.value = String(d.price_sell || "");
    }
};

watch(
    () => props.open,
    (val) => {
        if (val)
            initFromDraft(
                props.initialDraft ?? {
                    item_name: "",
                    unit_name: "",
                    qty: "1",
                },
            );
    },
);

const parseMoneyVal = (val: string) =>
    Number(String(val || "").replace(/\D/g, "")) || 0;

const detectPrecision = (value: string | number) => {
    const raw = String(value ?? "").trim();
    if (!raw || !raw.includes(".")) return 0;
    const frac = raw
        .split(".")[1]
        .replace(/[^0-9]/g, "")
        .replace(/0+$/, "");
    return frac.length;
};

const onQtyInput = () => {
    if (props.mode !== "purchase") return;
    const q = Number(qty.value) || 0;
    const a = parseMoneyVal(amount.value);
    priceCost.value =
        q > 0 && a > 0 ? numberFormatter.format(Math.round(a / q)) : "";
};

const onQtyBlur = () => {
    if (props.mode !== "purchase") return;
    const q = Number(qty.value) || 0;
    if (q <= 0) {
        qty.value = "1";
    }
    const precision = detectPrecision(qty.value);
    qty.value =
        Number(qty.value || 0)
            .toFixed(precision)
            .replace(/\.?0+$/, "") || "1";
    onQtyInput();
};

const onPriceCostInput = () => {
    if (props.mode !== "purchase") return;
    const q = Number(qty.value) || 0;
    const p = parseMoneyVal(priceCost.value);
    amount.value = q > 0 && p > 0 ? numberFormatter.format(q * p) : "";
};

const onAmountInput = () => {
    if (props.mode !== "purchase") return;
    const q = Number(qty.value) || 0;
    const a = parseMoneyVal(amount.value);
    priceCost.value =
        q > 0 && a > 0 ? numberFormatter.format(Math.round(a / q)) : "";
};

const onSave = () => {
    if (props.mode === "purchase") {
        emit("save", {
            item_name: itemName.value,
            unit_name: unitName.value,
            qty: qty.value,
            qty_precision: detectPrecision(qty.value),
            price_cost: priceCost.value,
            amount: amount.value,
        } as PurchaseManualDraft);
    } else {
        emit("save", {
            item_name: itemName.value,
            unit_name: unitName.value,
            qty: qty.value,
            price_buy: priceBuy.value,
            price_sell: priceSell.value,
        } as OrderManualDraft);
    }
};
</script>

<template>
    <Teleport to="body">
        <transition name="app-modal-fade-up">
            <div
                v-if="open"
                class="app-modal-overlay app-modal-open"
                @click.self="$emit('close')"
            >
                <div class="app-modal-sheet-sm">
                    <div class="app-modal-header">
                        <h2 class="app-modal-title">{{ title }}</h2>
                        <button
                            type="button"
                            class="app-modal-close"
                            @click="$emit('close')"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="app-modal-body space-y-4">
                        <label class="space-y-1">
                            <span class="app-label">Tên hàng</span>
                            <input
                                v-model="itemName"
                                type="text"
                                class="app-input"
                            />
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="space-y-1">
                                <span class="app-label">Đơn vị</span>
                                <template
                                    v-if="baseUnits && baseUnits.length > 0"
                                >
                                    <div class="grid">
                                        <select
                                            v-model="unitName"
                                            class="col-start-1 row-start-1 h-10 w-full appearance-none cursor-pointer rounded-xl border border-slate-300 bg-white px-3 pr-9 text-sm outline-none focus:border-brand-500"
                                        >
                                            <option value="">
                                                Chọn đơn vị
                                            </option>
                                            <option
                                                v-for="u in baseUnits"
                                                :key="u.id"
                                                :value="u.name"
                                            >
                                                {{ u.name }}
                                            </option>
                                        </select>
                                        <span
                                            class="pointer-events-none col-start-1 row-start-1 ml-auto mr-3 self-center text-slate-400"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 20 20"
                                                fill="none"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="m6 8 4 4 4-4"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                        </span>
                                    </div>
                                </template>
                                <template v-else>
                                    <input
                                        v-model="unitName"
                                        type="text"
                                        class="app-input"
                                    />
                                </template>
                            </label>
                            <label class="space-y-1">
                                <span class="app-label">Số lượng</span>
                                <input
                                    v-model="qty"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="app-input text-right"
                                    @input="onQtyInput"
                                    @blur="onQtyBlur"
                                />
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <template v-if="mode === 'purchase'">
                                <label class="space-y-1">
                                    <span class="app-label">Giá nhập</span>
                                    <div class="relative">
                                        <input
                                            v-model="priceCost"
                                            type="text"
                                            v-money-input
                                            class="app-input pr-8 text-right"
                                            @input="onPriceCostInput"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                            >đ</span
                                        >
                                    </div>
                                </label>
                                <label class="space-y-1">
                                    <span class="app-label">Thành tiền</span>
                                    <div class="relative">
                                        <input
                                            v-model="amount"
                                            type="text"
                                            v-money-input
                                            class="app-input pr-8 text-right"
                                            @input="onAmountInput"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                            >đ</span
                                        >
                                    </div>
                                </label>
                            </template>
                            <template v-else>
                                <label class="space-y-1">
                                    <span class="app-label">Giá vốn</span>
                                    <div class="relative">
                                        <input
                                            v-model="priceBuy"
                                            type="text"
                                            v-money-input
                                            class="app-input pr-8 text-right"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                            >đ</span
                                        >
                                    </div>
                                </label>
                                <label class="space-y-1">
                                    <span class="app-label">Giá bán</span>
                                    <div class="relative">
                                        <input
                                            v-model="priceSell"
                                            type="text"
                                            v-money-input
                                            class="app-input pr-8 text-right"
                                        />
                                        <span
                                            class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                            >đ</span
                                        >
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                    <div class="app-modal-footer">
                        <button
                            type="button"
                            class="app-btn-secondary"
                            @click="$emit('close')"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            class="app-btn-primary"
                            @click="onSave"
                        >
                            Lưu
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
