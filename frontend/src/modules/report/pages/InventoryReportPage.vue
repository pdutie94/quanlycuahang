<script setup lang="ts">
import ReportNavButtons from "../components/ReportNavButtons.vue";
import { ref, computed, onMounted, reactive, nextTick } from "vue";
import { useToast } from "../../../shared/composables/useToast";
import { useInventoryReport } from "../composables/useInventoryReport";
import type { InventoryReportItem } from "../types";

const toast = useToast();
const { items, loading, error, load, refresh, adjust, adjustError } =
    useInventoryReport();

const qtyMap = reactive<Record<string, any>>({});
const rowLoading = reactive<Record<string, boolean>>({});

const keyword = ref("");
const search = ref("");
const searchTimeout = ref<any>(null);

const inputRefsMap = reactive<Record<string, HTMLInputElement | null>>({});

const setInputRef = (el: any, id: string | number) => {
    inputRefsMap[id] = el ?? null;
};

const focusInput = (id: string | number) => {
    nextTick(() => {
        const input = inputRefsMap[id];
        if (input && typeof input.focus === "function") {
            input.focus();
        }
    });
};

function formatQty(val: string | number, minStep: string | number): string {
    const step = Number(minStep) || 1;
    if (step >= 1) return String(Math.round(Number(val)));
    const decimals = step.toString().split(".")[1]?.length || 0;
    return Number(val).toFixed(decimals).replace(/\.0+$/, "");
}

const filteredItems = computed(() => {
    if (!search.value) return items.value;
    const kw = search.value.trim().toLowerCase();
    return items.value.filter(
        (item: InventoryReportItem) =>
            (item.name && item.name.toLowerCase().includes(kw)) ||
            (item.product_code && item.product_code.toLowerCase().includes(kw)),
    );
});

const syncQtyMap = () => {
    for (const item of items.value) {
        qtyMap[item.id] = formatQty(item.qty_base ?? 0, item.min_step);
    }
};

const loadPage = async () => {
    try {
        await load();
        syncQtyMap();
    } catch (_err: unknown) {
        toast.error(error.value || "Không thể tải báo cáo tồn kho.");
    }
};

const refreshPage = async () => {
    try {
        await refresh();
        syncQtyMap();
    } catch (_err: unknown) {
        toast.error(error.value || "Không thể tải báo cáo tồn kho.");
    }
};

const submitAdjust = async (item: InventoryReportItem) => {
    try {
        rowLoading[item.id] = true;
        const payload = {
            product_id: item.id,
            qty_base: qtyMap[item.id] ?? "",
        };
        const result = await adjust(payload);
        if (result?.success) {
            toast.success(result?.message || "Đã cập nhật tồn kho.");
            await refreshPage();
            return;
        }
        toast.error(
            result?.message ||
                adjustError.value ||
                "Không thể cập nhật tồn kho.",
        );
    } catch (_err: unknown) {
        toast.error(adjustError.value || "Không thể cập nhật tồn kho.");
    } finally {
        rowLoading[item.id] = false;
    }
};

const onSearchInput = () => {
    if (searchTimeout.value) clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => {
        search.value = keyword.value;
    }, 300);
};

onMounted(async () => {
    await loadPage();
});
</script>

<template>
    <section class="space-y-4">
        <header>
            <div>
                <h1 class="text-lg font-semibold text-slate-900">
                    Báo cáo tồn kho
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Xem và điều chỉnh số lượng tồn kho theo đơn vị cơ bản.
                </p>
            </div>
            <ReportNavButtons />
        </header>

        <div class="flex items-center gap-2">
            <input
                v-model="keyword"
                type="text"
                placeholder="Tìm kiếm tên, mã sản phẩm..."
                class="h-10 w-full rounded-lg border border-slate-300 px-3 text-sm outline-none focus:border-brand-500"
                @input="onSearchInput"
            />
        </div>

        <div
            v-if="loading"
            class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500"
        >
            Đang tải...
        </div>
        <div
            v-else-if="!filteredItems.length"
            class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500"
        >
            Không có dữ liệu tồn kho.
        </div>

        <div v-else class="w-full overflow-x-auto overflow-y-hidden">
            <div
                class="min-w-[340px] overflow-hidden rounded-xl border border-slate-300 bg-white"
            >
                <table class="min-w-full table-auto bg-white">
                    <thead>
                        <tr class="bg-slate-50 text-sm text-slate-600">
                            <th class="border-b border-slate-300 p-2 text-left">
                                Sản phẩm
                            </th>
                            <th
                                class="border-b border-slate-300 p-2 text-right"
                            >
                                Điều chỉnh
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, idx) in filteredItems"
                            :key="item.id"
                            class="cursor-pointer transition hover:bg-slate-50"
                            @click="focusInput(item.id)"
                        >
                            <!-- Sản phẩm -->
                            <td
                                class="p-2 align-middle"
                                :class="
                                    idx !== filteredItems.length - 1
                                        ? 'border-b border-slate-200'
                                        : ''
                                "
                            >
                                <div class="flex items-center gap-2">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-slate-900"
                                        >
                                            {{ item.name }}
                                            <span class="text-slate-500"
                                                >-
                                                {{ item.base_unit_name }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="item.product_code"
                                            class="font-mono text-xs text-slate-400"
                                        >
                                            {{ item.product_code }}
                                        </div>
                                    </div>
                                    <span
                                        v-if="item.status === 'low'"
                                        class="inline-flex shrink-0 items-center rounded-md border border-amber-300 bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-700"
                                    >
                                        Sắp hết
                                    </span>
                                </div>
                            </td>

                            <!-- Ô điều chỉnh -->
                            <td
                                class="p-2 text-right align-middle"
                                :class="
                                    idx !== filteredItems.length - 1
                                        ? 'border-b border-slate-200'
                                        : ''
                                "
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <input
                                        :ref="(el) => setInputRef(el, item.id)"
                                        v-model="qtyMap[item.id]"
                                        type="number"
                                        :step="item.min_step || 1"
                                        min="0"
                                        :inputmode="
                                            item.min_step &&
                                            Number(item.min_step) < 1
                                                ? 'decimal'
                                                : 'numeric'
                                        "
                                        class="h-9 w-24 rounded-md border border-slate-400 px-2 text-right text-sm outline-none focus:border-brand-500"
                                        :aria-label="`Điều chỉnh tồn kho ${item.name}`"
                                        @click.stop
                                        @keyup.enter="submitAdjust(item)"
                                    />
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-600 text-white shadow-sm hover:bg-brand-700 disabled:opacity-60"
                                        :disabled="rowLoading[item.id]"
                                        :aria-label="`Cập nhật tồn kho ${item.name}`"
                                        @click.stop="submitAdjust(item)"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
