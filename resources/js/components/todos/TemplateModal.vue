<script setup>
import { ref, watch, computed } from 'vue';
import { XMarkIcon, PlusIcon, TrashIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    template: Object,
    categories: Object,
    categoryIcons: Object,
});

const emit = defineEmits(['close', 'saved']);

// Form data
const form = ref({
    name: '',
    description: '',
    category: 'custom',
    items: [],
});

const newItemTitle = ref('');
const loading = ref(false);
const errors = ref({});

// Reset form - defined before watch to avoid hoisting issues
const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        category: 'custom',
        items: [],
    };
    newItemTitle.value = '';
    errors.value = {};
};

// Watch for template changes
watch(() => props.template, async (newTemplate) => {
    if (newTemplate) {
        // Load template with items
        try {
            const response = await fetch(route('business.todo-templates.show', newTemplate.id), {
                headers: {
                    'Accept': 'application/json',
                },
            });
            if (response.ok) {
                const data = await response.json();
                form.value = {
                    name: data.template.name || '',
                    description: data.template.description || '',
                    category: data.template.category || 'custom',
                    items: data.items || [],
                };
            }
        } catch (error) {
            console.error('Failed to load template:', error);
            form.value = {
                name: newTemplate.name || '',
                description: newTemplate.description || '',
                category: newTemplate.category || 'custom',
                items: [],
            };
        }
    } else {
        resetForm();
    }
}, { immediate: true });

// Add item
const addItem = () => {
    if (newItemTitle.value.trim()) {
        form.value.items.push({
            title: newItemTitle.value.trim(),
            description: null,
            default_assignee_role: null,
            due_days_offset: null,
            children: [],
        });
        newItemTitle.value = '';
    }
};

// Remove item
const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

// Add child item
const addChildItem = (parentIndex, title) => {
    if (title.trim()) {
        form.value.items[parentIndex].children.push({
            title: title.trim(),
            description: null,
            default_assignee_role: null,
            due_days_offset: null,
            children: [],
        });
    }
};

// Remove child item
const removeChildItem = (parentIndex, childIndex) => {
    form.value.items[parentIndex].children.splice(childIndex, 1);
};

// Submit form
const submit = async () => {
    loading.value = true;
    errors.value = {};

    const url = props.template
        ? route('business.todo-templates.update', props.template.id)
        : route('business.todo-templates.store');

    const method = props.template ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: form.value.name,
                description: form.value.description || null,
                category: form.value.category,
                items: form.value.items,
            }),
        });

        const data = await response.json();

        if (response.ok) {
            emit('saved');
            emit('close');
            resetForm();
        } else {
            errors.value = data.errors || { general: data.error || 'Xatolik yuz berdi' };
        }
    } catch (error) {
        console.error('Failed to save template:', error);
        errors.value = { general: 'Tarmoq xatosi' };
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
const isEditing = computed(() => !!props.template);
const modalTitle = computed(() => isEditing.value ? 'Shablonni tahrirlash' : 'Yangi shablon');
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
                            class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col"
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
                            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                                <!-- Error message -->
                                <div v-if="errors.general" class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm rounded-lg">
                                    {{ errors.general }}
                                </div>

                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Shablon nomi *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Masalan: Yangi xodim onboarding"
                                    />
                                    <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tavsif
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="2"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Shablon haqida qisqacha..."
                                    ></textarea>
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Kategoriya
                                    </label>
                                    <div class="grid grid-cols-5 gap-2">
                                        <button
                                            v-for="(label, value) in categories"
                                            :key="value"
                                            type="button"
                                            @click="form.category = value"
                                            :class="[
                                                'px-3 py-2 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-1',
                                                form.category === value
                                                    ? 'bg-blue-600 text-white'
                                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            ]"
                                        >
                                            <span>{{ categoryIcons[value] }}</span>
                                            <span class="hidden sm:inline">{{ label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Items -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Vazifalar
                                    </label>
                                    <div class="space-y-2 mb-3">
                                        <div
                                            v-for="(item, index) in form.items"
                                            :key="index"
                                            class="bg-gray-50 dark:bg-gray-700 rounded-lg overflow-hidden"
                                        >
                                            <!-- Parent item -->
                                            <div class="flex items-center gap-2 p-3">
                                                <ChevronRightIcon v-if="item.children?.length" class="w-4 h-4 text-gray-400" />
                                                <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">{{ item.title }}</span>
                                                <button
                                                    type="button"
                                                    @click="removeItem(index)"
                                                    class="p-1 text-gray-400 hover:text-red-500"
                                                >
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </div>

                                            <!-- Children -->
                                            <div v-if="item.children?.length" class="border-t border-gray-200 dark:border-gray-600 pl-6 pr-3 py-2 space-y-1">
                                                <div
                                                    v-for="(child, childIndex) in item.children"
                                                    :key="childIndex"
                                                    class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400"
                                                >
                                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                                    <span class="flex-1">{{ child.title }}</span>
                                                    <button
                                                        type="button"
                                                        @click="removeChildItem(index, childIndex)"
                                                        class="p-1 text-gray-400 hover:text-red-500"
                                                    >
                                                        <TrashIcon class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Add child input -->
                                            <div class="border-t border-gray-200 dark:border-gray-600 pl-6 pr-3 py-2">
                                                <input
                                                    type="text"
                                                    @keyup.enter="(e) => { addChildItem(index, e.target.value); e.target.value = ''; }"
                                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-600 text-gray-900 dark:text-white"
                                                    placeholder="Sub-vazifa qo'shish (Enter)"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Add item input -->
                                    <div class="flex gap-2">
                                        <input
                                            v-model="newItemTitle"
                                            type="text"
                                            @keyup.enter="addItem"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                                            placeholder="Yangi vazifa..."
                                        />
                                        <button
                                            type="button"
                                            @click="addItem"
                                            class="px-3 py-2 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500"
                                        >
                                            <PlusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

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
