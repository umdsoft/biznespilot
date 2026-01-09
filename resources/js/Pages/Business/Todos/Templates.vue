<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    PlusIcon,
    DocumentDuplicateIcon,
    PencilSquareIcon,
    TrashIcon,
    PlayIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';
import TemplateModal from '@/components/todos/TemplateModal.vue';

const props = defineProps({
    templates: Array,
    categories: Object,
    categoryIcons: Object,
    currentCategory: String,
});

// State
const showTemplateModal = ref(false);
const editingTemplate = ref(null);
const activeCategory = ref(props.currentCategory || 'all');
const applyingTemplate = ref(null);

// Methods
const openTemplateModal = (template = null) => {
    editingTemplate.value = template;
    showTemplateModal.value = true;
};

const closeTemplateModal = () => {
    showTemplateModal.value = false;
    editingTemplate.value = null;
};

const changeCategory = (category) => {
    activeCategory.value = category;
    router.get(route('business.todo-templates.index'), {
        category: category,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteTemplate = async (template) => {
    if (!confirm('Shablonni o\'chirmoqchimisiz?')) return;

    try {
        const response = await fetch(route('business.todo-templates.destroy', template.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload();
        }
    } catch (error) {
        console.error('Failed to delete template:', error);
    }
};

const duplicateTemplate = async (template) => {
    try {
        const response = await fetch(route('business.todo-templates.duplicate', template.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.reload();
        }
    } catch (error) {
        console.error('Failed to duplicate template:', error);
    }
};

const applyTemplate = async (template) => {
    applyingTemplate.value = template.id;
    try {
        const response = await fetch(route('business.todo-templates.apply', template.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({}),
        });
        if (response.ok) {
            const data = await response.json();
            alert(data.message);
            router.visit(route('business.todos.index'));
        }
    } catch (error) {
        console.error('Failed to apply template:', error);
    } finally {
        applyingTemplate.value = null;
    }
};

const onTemplateSaved = () => {
    router.reload();
};

// Category colors
const getCategoryColor = (category) => {
    const colors = {
        onboarding: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        sales: 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        operations: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400',
        marketing: 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-400',
        custom: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[category] || colors.custom;
};
</script>

<template>
    <BusinessLayout title="Shablon boshqaruvi">
        <Head title="Shablon boshqaruvi" />

        <div class="h-full flex flex-col -m-4 sm:-m-6 lg:-m-8">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('business.todos.index')"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shablonlar</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Tayyor shablonlardan foydalaning
                            </p>
                        </div>
                    </div>
                    <button
                        @click="openTemplateModal()"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-blue-500/25"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi shablon
                    </button>
                </div>

                <!-- Category filters -->
                <div class="flex items-center gap-2 mt-6 overflow-x-auto pb-2">
                    <button
                        @click="changeCategory('all')"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap',
                            activeCategory === 'all'
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                    >
                        Barchasi
                    </button>
                    <button
                        v-for="(label, key) in categories"
                        :key="key"
                        @click="changeCategory(key)"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-lg transition-colors whitespace-nowrap flex items-center gap-2',
                            activeCategory === key
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                        ]"
                    >
                        <span>{{ categoryIcons[key] }}</span>
                        {{ label }}
                    </button>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Template cards -->
                    <div
                        v-for="template in templates"
                        :key="template.id"
                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all overflow-hidden"
                    >
                        <!-- Card header -->
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ template.icon }}</span>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ template.name }}</h3>
                                        <span :class="['text-xs px-2 py-0.5 rounded', getCategoryColor(template.category)]">
                                            {{ template.category_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <p v-if="template.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">
                                {{ template.description }}
                            </p>

                            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1">
                                    <DocumentDuplicateIcon class="w-4 h-4" />
                                    {{ template.items_count }} ta vazifa
                                </span>
                                <span class="flex items-center gap-1">
                                    <PlayIcon class="w-4 h-4" />
                                    {{ template.usage_count }} marta ishlatilgan
                                </span>
                            </div>
                        </div>

                        <!-- Card actions -->
                        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-3 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                            <button
                                @click="applyTemplate(template)"
                                :disabled="applyingTemplate === template.id"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
                            >
                                <PlayIcon class="w-4 h-4" />
                                {{ applyingTemplate === template.id ? 'Yaratilmoqda...' : 'Qo\'llash' }}
                            </button>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="duplicateTemplate(template)"
                                    class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="Nusxa olish"
                                >
                                    <DocumentDuplicateIcon class="w-5 h-5" />
                                </button>
                                <button
                                    @click="openTemplateModal(template)"
                                    class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="Tahrirlash"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                </button>
                                <button
                                    @click="deleteTemplate(template)"
                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                    title="O'chirish"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="templates.length === 0"
                        class="col-span-full text-center py-16"
                    >
                        <DocumentDuplicateIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Shablonlar yo'q</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-6">Yangi shablon yarating</p>
                        <button
                            @click="openTemplateModal()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors"
                        >
                            <PlusIcon class="w-5 h-5" />
                            Yangi shablon
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Template Modal -->
        <TemplateModal
            :show="showTemplateModal"
            :template="editingTemplate"
            :categories="categories"
            :category-icons="categoryIcons"
            @close="closeTemplateModal"
            @saved="onTemplateSaved"
        />
    </BusinessLayout>
</template>
