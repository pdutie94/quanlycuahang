import { ref, watch, type Ref } from 'vue';
import { useRoute, useRouter, type LocationQuery } from 'vue-router';

export interface FilterSchema {
    [key: string]: {
        default: any;
        parse?: (val: any) => any;
    };
}

export function useUrlFilters<T extends Record<string, any>>(schema: FilterSchema) {
    const route = useRoute();
    const router = useRouter();
    const filters = ref<any>({});

    // Initialize filters from schema defaults
    Object.keys(schema).forEach(key => {
        filters.value[key] = schema[key].default;
    });

    const parseQuery = (query: LocationQuery) => {
        const result: any = {};
        Object.keys(schema).forEach(key => {
            const rawValue = query[key];
            const parser = schema[key].parse;
            if (rawValue !== undefined && rawValue !== null) {
                result[key] = parser ? parser(rawValue) : rawValue;
            } else {
                result[key] = schema[key].default;
            }
        });
        return result;
    };

    // Watch for route changes to sync back to local state
    watch(
        () => route.query,
        (newQuery) => {
            const parsed = parseQuery(newQuery);
            Object.keys(parsed).forEach(key => {
                filters.value[key] = parsed[key];
            });
        },
        { immediate: true }
    );

    const applyFilters = (updates: Partial<T>) => {
        const nextQuery = { ...route.query, ...updates, page: undefined };
        
        // Remove empty filters from query
        Object.keys(nextQuery).forEach(key => {
            if (nextQuery[key] === '' || nextQuery[key] === undefined || nextQuery[key] === null) {
                delete nextQuery[key];
            }
        });

        router.push({ query: nextQuery });
    };

    const applyFilter = (keyOrUpdates: keyof T | Partial<T>, value?: any) => {
        if (typeof keyOrUpdates === 'string' || typeof keyOrUpdates === 'number' || typeof keyOrUpdates === 'symbol') {
            applyFilters({ [keyOrUpdates as keyof T]: value } as Partial<T>);
            return;
        }
        applyFilters(keyOrUpdates as Partial<T>);
    };

    const clearFilters = () => {
        router.push({ query: {} });
    };

    return {
        filters,
        applyFilter,
        applyFilters,
        clearFilters
    };
}
