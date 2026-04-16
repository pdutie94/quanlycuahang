<script setup lang="ts">
import ReportNavButtons from "../components/ReportNavButtons.vue";
import { useFormat } from "../../../shared/composables/useFormat";
const { formatMoney } = useFormat();
import { computed, onMounted, reactive, ref } from "vue";
import { useToast } from "../../../shared/composables/useToast";
import { useMissingCostReport } from "../composables/useMissingCostReport";
import type { MissingCostItem } from "../types";

const toast = useToast();
const {
    items,
    summary,
    loading,
    error,
    load,
    refresh,
    submit,
    submitLoading,
    submitError,
} = useMissingCostReport();
const form = reactive({ q: "", start_date: "", end_date: "" });
const selectedIds = ref<number[]>([]);
const hasLoadedOnce = ref(false);

// Đã thay thế bằng useFormat
const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);
const isRefreshing = computed(() => loading.value && hasLoadedOnce.value);

const allSelected = computed({
    get() {
        return (
            items.value.length > 0 &&
            selectedIds.value.length === items.value.length
        );
    },
    set(val: boolean) {
        selectedIds.value = val
            ? items.value.map((row: MissingCostItem) => Number(row.item_id))
            : [];
    },
});

const loadPage = async () => {
    try {
        await load({
            q: form.q,
            start_date: form.start_date,
            end_date: form.end_date,
        });
        selectedIds.value = [];
        hasLoadedOnce.value = true;
    } catch (_err: unknown) {
        toast.error(error.value || "Không thể tải báo cáo giá vốn thiếu.");
    }
};

const refreshPage = async () => {
    try {
        await refresh({
            q: form.q,
            start_date: form.start_date,
            end_date: form.end_date,
        });
        selectedIds.value = [];
    } catch (_err: unknown) {
        toast.error(error.value || "Không thể tải báo cáo giá vốn thiếu.");
    }
};

const updateSelected = async () => {
    try {
        const result = await submit({
            mode: "selected",
            item_ids: selectedIds.value,
        });
        if (result?.success) {
            toast.success(result?.message || "Đã cập nhật giá vốn.");
            await refreshPage();
            return;
        }
        toast.error(
            result?.message ||
                submitError.value ||
                "Không thể cập nhật giá vốn.",
        );
    } catch (_err: unknown) {
        toast.error(submitError.value || "Không thể cập nhật giá vốn.");
    }
};

const updateAll = async () => {
    try {
        const result = await submit({ mode: "all" });
        if (result?.success) {
            toast.success(result?.message || "Đã cập nhật giá vốn.");
            await refreshPage();
            return;
        }
        toast.error(
            result?.message ||
                submitError.value ||
                "Không thể cập nhật giá vốn.",
        );
    } catch (_err: unknown) {
        toast.error(submitError.value || "Không thể cập nhật giá vốn.");
    }
};

onMounted(async () => {
    await loadPage();
});
</script>

<template>
    <section class="space-y-4">
        <header>
            <h1 class="text-lg font-semibold text-slate-900">
                Báo cáo giá vốn thiếu
            </h1>
            <ReportNavButtons />
            <form
                class="app-card mt-3 flex flex-col gap-3"
                @submit.prevent="loadPage"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-1 flex-col gap-1 min-w-[180px]">
                        <label class="app-label">Từ khóa</label>
                        <input
                            v-model="form.q"
                            type="text"
                            placeholder="Mã đơn, sản phẩm, khách hàng"
                            class="app-input"
                        />
                    </div>
                    <div class="flex flex-1 flex-col gap-1 min-w-[130px]">
                        <label class="app-label">Từ ngày</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="app-input"
                        />
                    </div>
                    <div class="flex flex-1 flex-col gap-1 min-w-[130px]">
                        <label class="app-label">Đến ngày</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="app-input"
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="app-btn-primary"
                        :disabled="loading"
                    >
                        Lọc dữ liệu
                    </button>
                </div>
            </form>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    class="app-btn-secondary"
                    :disabled="submitLoading || !selectedIds.length"
                    @click="updateSelected"
                >
                    Cập nhật dòng đã chọn
                </button>
                <button
                    type="button"
                    class="app-btn-primary"
                    :disabled="submitLoading"
                    @click="updateAll"
                >
                    Cập nhật tất cả
                </button>
            </div>
        </header>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div
                class="rounded-xl border border-slate-200 bg-white p-3 text-sm"
            >
                <div class="text-slate-500">Số dòng</div>
                <div class="mt-1 font-semibold">
                    {{ Number(summary.item_count || 0) }}
                </div>
            </div>
            <div
                class="rounded-xl border border-slate-200 bg-white p-3 text-sm"
            >
                <div class="text-slate-500">Số đơn</div>
                <div class="mt-1 font-semibold">
                    {{ Number(summary.order_count || 0) }}
                </div>
            </div>
            <div
                class="rounded-xl border border-slate-200 bg-white p-3 text-sm"
            >
                <div class="text-slate-500">Tăng giá vốn dự kiến</div>
                <div class="mt-1 font-semibold">
                    {{ formatMoney(summary.total_delta_cost) }}
                </div>
            </div>
        </section>

        <div
            v-if="isRefreshing"
            class="px-1 text-xs font-medium text-slate-500"
        >
            Đang cập nhật báo cáo giá vốn...
        </div>
        <div
            v-if="isInitialLoading"
            class="rounded-xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500"
        >
            Đang tải...
        </div>
        <div
            v-else-if="!items.length"
            class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500"
        >
            Không có dòng thiếu giá vốn.
        </div>

        <section
            v-else
            class="space-y-2"
            :class="
                isRefreshing
                    ? 'opacity-70 transition-opacity'
                    : 'transition-opacity'
            "
        >
            <label
                class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-3 text-sm"
            >
                <input v-model="allSelected" type="checkbox" class="h-4 w-4" />
                <span>Chọn tất cả</span>
            </label>
            <article
                v-for="item in items"
                :key="item.item_id"
                class="rounded-xl border border-slate-200 bg-white p-3 text-sm"
            >
                <label class="flex items-start gap-3">
                    <input
                        v-model="selectedIds"
                        :value="Number(item.item_id)"
                        type="checkbox"
                        class="mt-1 h-4 w-4"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-slate-900">
                            #{{ item.order_code }} - {{ item.product_name }}
                        </div>
                        <div class="mt-1 text-slate-500">
                            {{ item.order_date }} |
                            {{ item.customer_name || "Khách lẻ" }}
                        </div>
                        <div class="mt-1 flex flex-wrap gap-3">
                            <span>SL: {{ item.qty }} {{ item.unit_name }}</span>
                            <span
                                >Giá vốn hiện tại:
                                {{ formatMoney(item.item_price_cost) }}</span
                            >
                            <span
                                >Giá vốn đề xuất:
                                {{ formatMoney(item.unit_price_cost) }}</span
                            >
                        </div>
                    </div>
                </label>
            </article>
        </section>
    </section>
</template>
