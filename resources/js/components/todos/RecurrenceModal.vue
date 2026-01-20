<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from '@/i18n';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    show: Boolean,
    todo: Object,
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead'].includes(value),
    },
});

// Route prefix based on panel type
const routePrefix = computed(() => props.panelType === 'saleshead' ? 'sales-head' : 'business');

const emit = defineEmits(['close', 'saved']);

// Form data
const form = ref({
    frequency: 'daily',
    interval: 1,
    days_of_week: [],
    day_of_month: null,
    start_date: '',
    end_date: '',
    generation_mode: 'on_time',
});

const loading = ref(false);
const errors = ref({});

// Constants - now using computed to support reactivity with i18n
const frequencies = computed(() => ({
    daily: t('todos.recurrence.daily'),
    weekly: t('todos.recurrence.weekly'),
    monthly: t('todos.recurrence.monthly'),
    yearly: t('todos.recurrence.yearly'),
}));

const daysOfWeek = computed(() => [
    { value: 1, label: t('todos.recurrence.day_mon') },
    { value: 2, label: t('todos.recurrence.day_tue') },
    { value: 3, label: t('todos.recurrence.day_wed') },
    { value: 4, label: t('todos.recurrence.day_thu') },
    { value: 5, label: t('todos.recurrence.day_fri') },
    { value: 6, label: t('todos.recurrence.day_sat') },
    { value: 7, label: t('todos.recurrence.day_sun') },
]);

const generationModes = computed(() => ({
    on_time: t('todos.recurrence.on_time'),
    advance: t('todos.recurrence.advance'),
}));

// Get today's date - defined before resetForm and watch
const getTodayDate = () => {
    return new Date().toISOString().split('T')[0];
};

// Reset form - defined before watch to avoid hoisting issues
const resetForm = () => {
    form.value = {
        frequency: 'daily',
        interval: 1,
        days_of_week: [],
        day_of_month: null,
        start_date: getTodayDate(),
        end_date: '',
        generation_mode: 'on_time',
    };
    errors.value = {};
};

// Watch for todo changes
watch(() => props.todo, (newTodo) => {
    if (newTodo?.recurrence) {
        const rec = newTodo.recurrence;
        form.value = {
            frequency: rec.frequency || 'daily',
            interval: rec.interval || 1,
            days_of_week: rec.days_of_week || [],
            day_of_month: rec.day_of_month || null,
            start_date: rec.start_date || getTodayDate(),
            end_date: rec.end_date || '',
            generation_mode: rec.generation_mode || 'on_time',
        };
    } else {
        resetForm();
    }
}, { immediate: true });

// Toggle day of week
const toggleDayOfWeek = (day) => {
    const index = form.value.days_of_week.indexOf(day);
    if (index > -1) {
        form.value.days_of_week.splice(index, 1);
    } else {
        form.value.days_of_week.push(day);
        form.value.days_of_week.sort((a, b) => a - b);
    }
};

// Submit form
const submit = async () => {
    loading.value = true;
    errors.value = {};

    const url = props.todo?.recurrence
        ? route(`${routePrefix.value}.todo-recurrences.update`, props.todo.recurrence.id)
        : route(`${routePrefix.value}.todos.recurrence.store`, props.todo.id);

    try {
        // Refresh CSRF token before request
        await refreshCsrfToken();

        const payload = {
            frequency: form.value.frequency,
            interval: form.value.interval,
            days_of_week: form.value.frequency === 'weekly' ? form.value.days_of_week : null,
            day_of_month: form.value.frequency === 'monthly' ? form.value.day_of_month : null,
            start_date: form.value.start_date,
            end_date: form.value.end_date || null,
            generation_mode: form.value.generation_mode,
        };

        const response = props.todo?.recurrence
            ? await window.axios.put(url, payload)
            : await window.axios.post(url, payload);

        if (response.data.success !== false) {
            emit('saved');
            emit('close');
            resetForm();
        } else {
            errors.value = response.data.errors || { general: response.data.error || t('common.error') };
        }
    } catch (error) {
        console.error('Failed to save recurrence:', error);

        // Handle 419 CSRF error
        if (isCsrfError(error)) {
            errors.value = { general: t('todos.recurrence.session_expired') };
            await refreshCsrfToken();
            return;
        }

        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else if (error.response?.data?.error) {
            errors.value = { general: error.response.data.error };
        } else {
            errors.value = { general: t('todos.recurrence.network_error') };
        }
    } finally {
        loading.value = false;
    }
};

// Delete recurrence
const deleteRecurrence = async () => {
    if (!props.todo?.recurrence) return;
    if (!confirm(t('todos.recurrence.delete_confirm'))) return;

    try {
        // Refresh CSRF token before request
        await refreshCsrfToken();

        const response = await window.axios.delete(
            route(`${routePrefix.value}.todo-recurrences.destroy`, props.todo.recurrence.id)
        );

        if (response.data.success !== false) {
            emit('saved');
            emit('close');
        }
    } catch (error) {
        console.error('Failed to delete recurrence:', error);

        // Handle 419 CSRF error
        if (isCsrfError(error)) {
            errors.value = { general: t('todos.recurrence.session_expired') };
            await refreshCsrfToken();
        }
    }
};

// Close modal
const close = () => {
    emit('close');
    resetForm();
};

// Computed
const hasRecurrence = computed(() => !!props.todo?.recurrence);
const modalTitle = computed(() => hasRecurrence.value ? t('todos.recurrence.edit_title') : t('todos.recurrence.title'));

// Interval label
const intervalLabel = computed(() => {
    const labels = {
        daily: t('todos.recurrence.interval_day'),
        weekly: t('todos.recurrence.interval_week'),
        monthly: t('todos.recurrence.interval_month'),
        yearly: t('todos.recurrence.interval_year'),
    };
    return labels[form.value.frequency] || '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75" @click="close"></div>

                <!-- Modal -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show"
                            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full"
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ modalTitle }}</h3>
                                <button
                                    @click="close"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                >
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submit" class="p-6 space-y-4">
                                <!-- Error message -->
                                <div v-if="errors.general" class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm rounded-lg">
                                    {{ errors.general }}
                                </div>

                                <!-- Frequency -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ t('todos.recurrence.frequency') }}
                                    </label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <button
                                            v-for="(label, value) in frequencies"
                                            :key="value"
                                            type="button"
                                            @click="form.frequency = value"
                                            :class="[
                                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors',
                                                form.frequency === value
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Interval -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('todos.recurrence.every') }} {{ intervalLabel }}
                                    </label>
                                    <input
                                        v-model.number="form.interval"
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <!-- Days of week (for weekly) -->
                                <div v-if="form.frequency === 'weekly'">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ t('todos.recurrence.days') }}
                                    </label>
                                    <div class="flex gap-2">
                                        <button
                                            v-for="day in daysOfWeek"
                                            :key="day.value"
                                            type="button"
                                            @click="toggleDayOfWeek(day.value)"
                                            :class="[
                                                'w-10 h-10 text-sm font-medium rounded-lg transition-colors',
                                                form.days_of_week.includes(day.value)
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            {{ day.label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Day of month (for monthly) -->
                                <div v-if="form.frequency === 'monthly'">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('todos.recurrence.day_of_month') }}
                                    </label>
                                    <input
                                        v-model.number="form.day_of_month"
                                        type="number"
                                        min="1"
                                        max="31"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        :placeholder="t('todos.recurrence.day_of_month_placeholder')"
                                    />
                                </div>

                                <!-- Start date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('todos.recurrence.start_date') }}
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        required
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <!-- End date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ t('todos.recurrence.end_date') }}
                                    </label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <!-- Generation mode -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ t('todos.recurrence.generation_time') }}
                                    </label>
                                    <div class="space-y-2">
                                        <label
                                            v-for="(label, value) in generationModes"
                                            :key="value"
                                            class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.generation_mode"
                                                :value="value"
                                                class="text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ label }}</span>
                                        </label>
                                    </div>
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                <div>
                                    <button
                                        v-if="hasRecurrence"
                                        type="button"
                                        @click="deleteRecurrence"
                                        class="text-sm text-red-600 hover:text-red-700 font-medium"
                                    >
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="close"
                                        class="px-4 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button
                                        @click="submit"
                                        :disabled="loading"
                                        class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                                    >
                                        {{ loading ? t('common.saving') + '...' : t('common.save') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
