<script setup lang="ts">
import { watch } from "vue";
import { useFormat } from "../composables/useFormat";

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        statTotal: number;
        statPaid: number;
        statDebt: number;
        statPaidLabel?: string;
        submitLabel?: string;
        loading?: boolean;
    }>(),
    {
        statPaidLabel: "Đã thu",
        submitLabel: "Xác nhận",
        loading: false,
    },
);

const amount = defineModel<string>("amount", { default: "" });
const paymentMethod = defineModel<string>("paymentMethod", { default: "cash" });
const note = defineModel<string>("note", { default: "" });

defineEmits<{
    submit: [];
    close: [];
}>();

const { formatMoney, formatMoneyInput } = useFormat();

watch(
    () => props.open,
    (val) => {
        if (val && amount.value) {
            const formatted = formatMoneyInput(amount.value);
            if (formatted !== amount.value) {
                amount.value = formatted;
            }
        }
    },
);
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
                            <svg
                                class="h-4 w-4"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="m6 6 8 8M14 6l-8 8"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </button>
                    </div>
                    <form
                        class="app-modal-body space-y-4"
                        @submit.prevent="$emit('submit')"
                    >
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <div class="rounded-md bg-slate-50 px-3 py-2">
                                <div class="text-sm uppercase text-slate-500">
                                    Tổng tiền
                                </div>
                                <div class="mt-1 font-medium text-slate-900">
                                    {{ formatMoney(statTotal) }}
                                </div>
                            </div>
                            <div class="rounded-md bg-brand-50 px-3 py-2">
                                <div class="text-sm uppercase text-brand-600">
                                    {{ statPaidLabel }}
                                </div>
                                <div class="mt-1 font-medium text-brand-700">
                                    {{ formatMoney(statPaid) }}
                                </div>
                            </div>
                            <div class="rounded-md bg-slate-50 px-3 py-2">
                                <div class="text-sm uppercase text-slate-500">
                                    Còn nợ
                                </div>
                                <div class="mt-1 font-medium text-red-600">
                                    {{ formatMoney(statDebt) }}
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-sm text-slate-700"
                                >Hình thức thanh toán</label
                            >
                            <div class="app-segment">
                                <button
                                    type="button"
                                    class="app-segment-item"
                                    :class="
                                        paymentMethod === 'cash'
                                            ? 'app-segment-item-active'
                                            : ''
                                    "
                                    @click="paymentMethod = 'cash'"
                                >
                                    Tiền mặt
                                </button>
                                <button
                                    type="button"
                                    class="app-segment-item"
                                    :class="
                                        paymentMethod === 'bank'
                                            ? 'app-segment-item-active'
                                            : ''
                                    "
                                    @click="paymentMethod = 'bank'"
                                >
                                    Chuyển khoản
                                </button>
                            </div>
                        </div>
                        <label class="block space-y-1 text-sm text-slate-700">
                            <span class="app-label">Số tiền</span>
                            <div class="relative">
                                <input
                                    v-model="amount"
                                    type="text"
                                    v-money-input
                                    class="app-input pr-9 text-right"
                                    placeholder="Nhập số tiền"
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500"
                                    >đ</span
                                >
                            </div>
                        </label>
                        <label class="block space-y-1 text-sm text-slate-700">
                            <span class="app-label">Ghi chú</span>
                            <textarea
                                v-model="note"
                                rows="2"
                                class="form-field block w-full rounded-xl border border-slate-300 bg-white px-3.5 text-sm outline-none transition focus:border-brand-500"
                            ></textarea>
                        </label>
                        <div
                            class="app-modal-footer mt-2 border-t border-slate-100 px-0 py-0 pt-2"
                        >
                            <button
                                type="button"
                                class="app-btn-secondary"
                                @click="$emit('close')"
                            >
                                Hủy
                            </button>
                            <button
                                type="submit"
                                class="app-btn-primary"
                                :disabled="loading"
                            >
                                {{ submitLabel }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
