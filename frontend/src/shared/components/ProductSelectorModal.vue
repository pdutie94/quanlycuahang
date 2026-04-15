<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { X } from "@lucide/vue";
import { useFormat } from "../composables/useFormat";

export interface SelectorItem {
    id: number;
    name: string;
    unitName?: string;
    subLabel?: string;
    price: number;
}

const props = withDefaults(
    defineProps<{
        open: boolean;
        items: SelectorItem[];
        selectedIds: number[];
        title?: string;
        searchPlaceholder?: string;
        applyLabel?: string;
    }>(),
    {
        title: "Chọn sản phẩm",
        searchPlaceholder: "Tìm sản phẩm...",
        applyLabel: "Áp dụng",
    },
);

const emit = defineEmits<{
    toggle: [id: number];
    apply: [];
    close: [];
}>();

const { formatMoney } = useFormat();
const keyword = ref("");

watch(
    () => props.open,
    (val) => {
        if (val) keyword.value = "";
    },
);

const filtered = computed(() => {
    const kw = keyword.value.trim().toLowerCase();
    const source = props.items;
    if (!kw) return source.slice(0, 80);
    return source
        .filter((item) => {
            const hay = [item.name, item.unitName, item.subLabel]
                .map((v) => String(v || "").toLowerCase())
                .join(" ");
            return hay.includes(kw);
        })
        .slice(0, 80);
});

const isSelected = (id: number) => props.selectedIds.includes(id);
</script>

<template>
    <Teleport to="body">
        <transition name="app-modal-fade-up">
            <div
                v-if="open"
                class="app-modal-overlay app-modal-open"
                @click.self="$emit('close')"
            >
                <div class="app-modal-sheet app-modal-sheet-fill">
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
                    <div class="app-modal-body app-modal-body-fill pt-2 pb-3">
                        <div class="mb-2">
                            <input
                                v-model="keyword"
                                type="search"
                                :placeholder="searchPlaceholder"
                                class="app-input"
                            />
                        </div>
                        <div
                            class="app-modal-body-scroll rounded-lg border border-slate-200"
                        >
                            <button
                                v-for="item in filtered"
                                :key="item.id"
                                type="button"
                                class="flex w-full items-center justify-between gap-2 border-b border-slate-100 px-3 py-2 text-left last:border-b-0"
                                @click="$emit('toggle', item.id)"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-slate-900">
                                        {{ item.name
                                        }}<span
                                            v-if="item.unitName"
                                            class="text-slate-500"
                                        >
                                            - {{ item.unitName }}</span
                                        >
                                    </div>
                                    <!-- <div v-if="item.subLabel" class="mt-0.5 text-sm text-slate-500">{{ item.subLabel }}</div> -->
                                    <div
                                        class="mt-0.5 text-sm font-medium text-brand-700"
                                    >
                                        {{ formatMoney(item.price) }}
                                    </div>
                                </div>
                                <span
                                    class="inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-md border text-xs font-semibold"
                                    :class="
                                        isSelected(item.id)
                                            ? 'border-brand-600 bg-brand-600 text-white'
                                            : 'border-slate-300 bg-white text-transparent'
                                    "
                                    >✓</span
                                >
                            </button>
                            <div
                                v-if="!filtered.length"
                                class="p-3 text-center text-sm text-slate-500"
                            >
                                Không tìm thấy sản phẩm phù hợp.
                            </div>
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
                            :disabled="!selectedIds.length"
                            @click="$emit('apply')"
                        >
                            {{ applyLabel
                            }}{{
                                selectedIds.length
                                    ? ` (${selectedIds.length})`
                                    : ""
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
