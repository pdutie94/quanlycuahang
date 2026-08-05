<script setup lang="ts">
import InfiniteListStatus from "../../../shared/components/InfiniteListStatus.vue";
import CustomerItemCard from "../../../shared/components/CustomerItemCard.vue";
import { useInfiniteList } from "../../../shared/composables/useInfiniteList";
import ReportNavButtons from "../components/ReportNavButtons.vue";
import ReportGroupTabs from "../components/ReportGroupTabs.vue";
import { useFormat } from "../../../shared/composables/useFormat";
const { formatMoney } = useFormat();
import { computed, reactive, ref } from "vue";
import { useToast } from "../../../shared/composables/useToast";
import { useCustomerDebtReport } from "../composables/useCustomerDebtReport";

const toast = useToast();
const { items, summary, meta, loading, error, load } = useCustomerDebtReport();
const form = reactive({ start_date: "", end_date: "", q: "", show_all: false });
const hasLoadedOnce = ref(false);
const isInitialLoading = computed(() => loading.value && !hasLoadedOnce.value);

const { hasMore, loadingMore } = useInfiniteList({
    itemsRef: items,
    metaRef: meta,
    loadingRef: loading,
    fetchPage: (page: number) =>
        load({
            start_date: form.start_date,
            end_date: form.end_date,
            q: form.q,
            show_all: form.show_all ? "1" : "0",
            page,
        }),
    onError: () => {
        toast.error(error.value || "Không thể tải báo cáo công nợ khách hàng.");
    },
});

// Đã thay thế bằng useFormat

const loadPage = async () => {
    try {
        await load({
            start_date: form.start_date,
            end_date: form.end_date,
            q: form.q,
            show_all: form.show_all ? "1" : "0",
        });
        hasLoadedOnce.value = true;
    } catch (_err: unknown) {
        toast.error(error.value || "Không thể tải báo cáo công nợ khách hàng.");
    }
};

const resetFilter = async () => {
    form.start_date = "";
    form.end_date = "";
    form.q = "";
    form.show_all = false;
    await loadPage();
};
</script>

<template>
    <section class="space-y-4">
        <header>
            <h1 class="text-lg font-semibold text-slate-900">
                Công nợ khách hàng
            </h1>
            <ReportNavButtons />
            <ReportGroupTabs group="debts" />

            <form
                class="app-card mt-3 flex flex-col gap-3"
                @submit.prevent="loadPage"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-1 flex-col min-w-[130px]">
                        <label class="app-label">Từ ngày</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="app-input"
                        />
                    </div>
                    <div class="flex flex-1 flex-col min-w-[130px]">
                        <label class="app-label">Đến ngày</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="app-input"
                        />
                    </div>
                    <div class="flex flex-1 flex-col min-w-[180px]">
                        <label class="app-label">Từ khóa</label>
                        <input
                            v-model="form.q"
                            type="text"
                            placeholder="Tên, SĐT, địa chỉ"
                            class="app-input"
                        />
                    </div>
                    <label
                        class="inline-flex h-10 cursor-pointer select-none items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm text-slate-700"
                    >
                        <input
                            v-model="form.show_all"
                            type="checkbox"
                            class="h-4 w-4 accent-brand-600"
                        />
                        Hiển thị tất cả
                    </label>
                </div>
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="app-btn-primary"
                        :disabled="loading"
                    >
                        Lọc dữ liệu
                    </button>
                    <button
                        type="button"
                        class="app-btn-secondary"
                        :disabled="loading"
                        @click="resetFilter"
                    >
                        Đặt lại
                    </button>
                </div>
            </form>
        </header>

        <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-3">
                <div class="text-sm text-slate-500">Tổng doanh số</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">
                    {{ formatMoney(summary.total_amount) }}
                </div>
            </article>
            <article
                class="rounded-2xl border border-brand-100 bg-brand-50 p-3"
            >
                <div class="text-sm text-brand-700">Đã thu</div>
                <div class="mt-1 text-lg font-semibold text-brand-800">
                    {{ formatMoney(summary.paid_amount) }}
                </div>
            </article>
            <article class="rounded-2xl border border-rose-100 bg-rose-50 p-3">
                <div class="text-sm text-rose-700">Còn nợ</div>
                <div class="mt-1 text-lg font-semibold text-rose-800">
                    {{ formatMoney(summary.debt_amount) }}
                </div>
            </article>
        </section>

        <div class="space-y-3">
            <div
                v-if="isInitialLoading"
                class="app-card text-center text-sm text-slate-500"
            >
                Đang tải...
            </div>

            <div v-else-if="!items.length" class="app-empty-state">
                Chưa có khách hàng nào.
            </div>

            <template v-else>
                <transition-group
                    name="app-list-fade"
                    tag="div"
                    class="space-y-3"
                    appear
                >
                    <CustomerItemCard
                        v-for="item in items"
                        :key="item.id"
                        :customer="item"
                        :to="{
                            name: 'customers.detail',
                            params: { id: item.id },
                        }"
                    />
                </transition-group>
            </template>
        </div>

        <InfiniteListStatus
            :visible="items.length > 0"
            :loading-more="loadingMore"
            :has-more="hasMore"
        />
        <div
            v-if="items.length && hasMore"
            ref="infiniteSentinel"
            class="h-1 w-full"
        ></div>
    </section>
</template>
