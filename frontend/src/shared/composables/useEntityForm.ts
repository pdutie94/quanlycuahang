import { computed, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useToast } from './useToast';

export interface UseEntityFormOptions<T, P> {
    loadData?: (id: number) => Promise<any>;
    loadBootstrap?: () => Promise<any>;
    submitCreate: (data: P) => Promise<any>;
    submitUpdate: (id: number, data: P) => Promise<any>;
    onSuccess?: (payload: any) => void;
    onError?: (error: any) => void;
    redirectPath: string;
    entityName: string;
}

export function useEntityForm<T extends Record<string, any>, P>(options: UseEntityFormOptions<T, P>) {
    const route = useRoute();
    const router = useRouter();
    const toast = useToast();

    const id = computed(() => Number(route.params.id || 0));
    const isEdit = computed(() => id.value > 0);
    
    const loading = ref(false);
    const saving = ref(false);

    const load = async () => {
        loading.value = true;
        try {
            if (options.loadBootstrap) {
                await options.loadBootstrap();
            }
            if (isEdit.value && options.loadData) {
                await options.loadData(id.value);
            }
        } catch (error) {
            toast.error(`Không thể tải dữ liệu ${options.entityName}.`);
            if (options.onError) options.onError(error);
        } finally {
            loading.value = false;
        }
    };

    const submit = async (data: P, redirectMode: 'stay' | 'exit' = 'stay') => {
        saving.value = true;
        try {
            const payload = isEdit.value
                ? await options.submitUpdate(id.value, data)
                : await options.submitCreate(data);

            toast.success(payload?.message || `Đã lưu ${options.entityName}.`);

            if (options.onSuccess) {
                options.onSuccess(payload);
            }

            if (redirectMode === 'exit') {
                router.push(options.redirectPath);
                return;
            }

            if (!isEdit.value && payload?.data?.id) {
                router.push({ params: { id: payload.data.id } });
            }
        } catch (error: any) {
            toast.error(error?.message || `Không thể lưu ${options.entityName}.`);
            if (options.onError) options.onError(error);
        } finally {
            saving.value = false;
        }
    };

    onMounted(load);

    return {
        id,
        isEdit,
        loading,
        saving,
        submit,
        refresh: load
    };
}
