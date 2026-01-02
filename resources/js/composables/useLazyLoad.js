import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

/**
 * Composable for lazy loading page data via API
 *
 * Usage:
 * ```js
 * const { isLoading, error, data, fetchData, loadedProps } = useLazyLoad(props, '/api/endpoint', ['field1', 'field2']);
 *
 * // Access loaded data with fallback to props
 * const stats = loadedProps('stats', { total: 0 });
 * ```
 */
export function useLazyLoad(props, apiUrl, fields = [], options = {}) {
    const isLoading = ref(false);
    const error = ref(null);
    const data = ref({});
    const hasLoaded = ref(false);

    const {
        autoLoad = true,
        onSuccess = null,
        onError = null,
        transform = null,
        params = {},
    } = options;

    /**
     * Fetch data from API
     */
    const fetchData = async (customParams = {}) => {
        if (!props.lazyLoad && hasLoaded.value) {
            return;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const response = await axios.get(apiUrl, {
                params: { ...params, ...customParams }
            });

            let responseData = response.data;

            // Apply transform if provided
            if (transform && typeof transform === 'function') {
                responseData = transform(responseData);
            }

            data.value = responseData;
            hasLoaded.value = true;

            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(responseData);
            }
        } catch (err) {
            console.error('LazyLoad error:', err);
            error.value = err.response?.data?.message || err.message || 'Ma\'lumotlarni yuklashda xatolik';

            if (onError && typeof onError === 'function') {
                onError(err);
            }
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Refresh data (force reload)
     */
    const refresh = async (customParams = {}) => {
        hasLoaded.value = false;
        await fetchData(customParams);
    };

    /**
     * Get loaded prop value with fallback to original prop
     * @param {string} key - Property key
     * @param {any} defaultValue - Default value if not found
     */
    const loadedProp = (key, defaultValue = null) => {
        return computed(() => {
            // If data is loaded from API, use that
            if (hasLoaded.value && data.value[key] !== undefined) {
                return data.value[key];
            }
            // Otherwise fall back to prop value
            if (props[key] !== undefined && props[key] !== null) {
                return props[key];
            }
            return defaultValue;
        });
    };

    /**
     * Get multiple loaded props at once
     * @param {Object} defaults - Object with keys and default values
     */
    const loadedProps = (defaults) => {
        const result = {};
        for (const [key, defaultValue] of Object.entries(defaults)) {
            result[key] = loadedProp(key, defaultValue);
        }
        return result;
    };

    /**
     * Check if specific field has data
     */
    const hasData = (key) => {
        return computed(() => {
            return (hasLoaded.value && data.value[key] !== undefined) ||
                   (props[key] !== undefined && props[key] !== null);
        });
    };

    // Auto load on mount if lazyLoad flag is true
    onMounted(() => {
        if (autoLoad && props.lazyLoad) {
            fetchData();
        }
    });

    return {
        isLoading,
        error,
        data,
        hasLoaded,
        fetchData,
        refresh,
        loadedProp,
        loadedProps,
        hasData,
    };
}

/**
 * Composable specifically for paginated data
 */
export function usePaginatedData(apiUrl, options = {}) {
    const isLoading = ref(false);
    const error = ref(null);
    const items = ref([]);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });
    const filters = ref({});

    const {
        perPage = 25,
        onSuccess = null,
        onError = null,
    } = options;

    const fetchPage = async (page = 1, newFilters = {}) => {
        isLoading.value = true;
        error.value = null;
        filters.value = { ...filters.value, ...newFilters };

        try {
            const response = await axios.get(apiUrl, {
                params: {
                    page,
                    per_page: perPage,
                    ...filters.value,
                }
            });

            const data = response.data;

            items.value = data.data || [];
            pagination.value = {
                current_page: data.current_page,
                last_page: data.last_page,
                per_page: data.per_page,
                total: data.total,
            };

            if (onSuccess && typeof onSuccess === 'function') {
                onSuccess(data);
            }
        } catch (err) {
            console.error('Pagination error:', err);
            error.value = err.response?.data?.message || err.message || 'Ma\'lumotlarni yuklashda xatolik';

            if (onError && typeof onError === 'function') {
                onError(err);
            }
        } finally {
            isLoading.value = false;
        }
    };

    const nextPage = () => {
        if (pagination.value.current_page < pagination.value.last_page) {
            fetchPage(pagination.value.current_page + 1);
        }
    };

    const prevPage = () => {
        if (pagination.value.current_page > 1) {
            fetchPage(pagination.value.current_page - 1);
        }
    };

    const goToPage = (page) => {
        fetchPage(page);
    };

    const applyFilters = (newFilters) => {
        fetchPage(1, newFilters);
    };

    const refresh = () => {
        fetchPage(pagination.value.current_page);
    };

    return {
        isLoading,
        error,
        items,
        pagination,
        filters,
        fetchPage,
        nextPage,
        prevPage,
        goToPage,
        applyFilters,
        refresh,
    };
}

export default useLazyLoad;
