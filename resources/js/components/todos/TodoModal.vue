<script setup>
import { ref, watch, computed } from 'vue';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';
import { XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    todo: Object,
    teamMembers: Array,
    templates: Array,
    types: Object,
    priorities: Object,
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
    title: '',
    description: '',
    type: 'personal',
    priority: 'medium',
    due_date: '',
    assigned_to: '',
    subtasks: [],
});

const newSubtask = ref('');
const loading = ref(false);
const errors = ref({});

// Reset form - defined before watch to avoid hoisting issues
const resetForm = () => {
    form.value = {
        title: '',
        description: '',
        type: 'personal',
        priority: 'medium',
        due_date: '',
        assigned_to: '',
        subtasks: [],
    };
    newSubtask.value = '';
    errors.value = {};
};

// Watch for todo changes
watch(() => props.todo, (newTodo) => {
    if (newTodo) {
        form.value = {
            title: newTodo.title || '',
            description: newTodo.description || '',
            type: newTodo.type || 'personal',
            priority: newTodo.priority || 'medium',
            due_date: newTodo.due_date ? newTodo.due_date.slice(0, 16) : '',
            assigned_to: newTodo.assignee?.id || '',
            subtasks: newTodo.subtasks?.map(s => ({ title: s.title })) || [],
        };
    } else {
        resetForm();
    }
}, { immediate: true });

// Add subtask
const addSubtask = () => {
    if (newSubtask.value.trim()) {
        form.value.subtasks.push({ title: newSubtask.value.trim() });
        newSubtask.value = '';
    }
};

// Remove subtask
const removeSubtask = (index) => {
    form.value.subtasks.splice(index, 1);
};

// Submit form
const submit = async () => {
    loading.value = true;
    errors.value = {};

    const url = props.todo
        ? route(`${routePrefix.value}.todos.update`, props.todo.id)
        : route(`${routePrefix.value}.todos.store`);

    try {
        // Refresh CSRF token before request
        await refreshCsrfToken();

        const payload = {
            title: form.value.title,
            description: form.value.description || null,
            type: form.value.type,
            priority: form.value.priority,
            due_date: form.value.due_date || null,
            assigned_to: form.value.assigned_to || null,
            subtasks: form.value.subtasks,
        };

        const response = props.todo
            ? await window.axios.put(url, payload)
            : await window.axios.post(url, payload);

        if (response.data.success !== false) {
            emit('saved');
            emit('close');
            resetForm();
        } else {
            errors.value = response.data.errors || { general: response.data.error || 'Xatolik yuz berdi' };
        }
    } catch (error) {
        console.error('Failed to save todo:', error);

        // Handle 419 CSRF error
        if (isCsrfError(error)) {
            errors.value = { general: 'Sessiya muddati tugadi. Qayta urinib ko\'ring.' };
            await refreshCsrfToken();
            return;
        }

        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else if (error.response?.data?.error) {
            errors.value = { general: error.response.data.error };
        } else {
            errors.value = { general: 'Tarmoq xatosi' };
        }
    } finally {
        loading.value = false;
    }
};

// Close modal
const close = () => {
    emit('close');
    resetForm();
};

// Computed
const isEditing = computed(() => !!props.todo);
const modalTitle = computed(() => isEditing.value ? 'Vazifani tahrirlash' : 'Yangi vazifa');
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
                            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full"
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

                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sarlavha *
                                    </label>
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Vazifa nomi"
                                    />
                                    <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tavsif
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Vazifa haqida..."
                                    ></textarea>
                                </div>

                                <!-- Type and Priority -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Turi
                                        </label>
                                        <select
                                            v-model="form.type"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="(label, value) in types" :key="value" :value="value">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Muhimlik
                                        </label>
                                        <select
                                            v-model="form.priority"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="(label, value) in priorities" :key="value" :value="value">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Due Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Muddat
                                    </label>
                                    <input
                                        v-model="form.due_date"
                                        type="datetime-local"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <!-- Assignee -->
                                <div v-if="teamMembers && teamMembers.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tayinlash
                                    </label>
                                    <select
                                        v-model="form.assigned_to"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">O'zim</option>
                                        <option v-for="member in teamMembers" :key="member.id" :value="member.id">
                                            {{ member.name }} ({{ member.role }})
                                        </option>
                                    </select>
                                </div>

                                <!-- Subtasks -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Sub-vazifalar
                                    </label>
                                    <div class="space-y-2 mb-2">
                                        <div
                                            v-for="(subtask, index) in form.subtasks"
                                            :key="index"
                                            class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                        >
                                            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ subtask.title }}</span>
                                            <button
                                                type="button"
                                                @click="removeSubtask(index)"
                                                class="p-1 text-gray-400 hover:text-red-500"
                                            >
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <input
                                            v-model="newSubtask"
                                            type="text"
                                            @keyup.enter="addSubtask"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            placeholder="Yangi sub-vazifa..."
                                        />
                                        <button
                                            type="button"
                                            @click="addSubtask"
                                            class="px-3 py-2 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500"
                                        >
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                                <button
                                    type="button"
                                    @click="close"
                                    class="px-4 py-2.5 text-gray-700 dark:text-gray-300 font-medium rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    @click="submit"
                                    :disabled="loading"
                                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                                >
                                    {{ loading ? 'Saqlanmoqda...' : 'Saqlash' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
