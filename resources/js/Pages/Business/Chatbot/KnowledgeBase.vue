<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    knowledge_base: Object,
    categories: Array,
    filters: Object,
});

const filters = ref({
    search: props.filters.search || '',
    category: props.filters.category || 'all',
});

const showModal = ref(false);
const editingKnowledge = ref(null);

const form = useForm({
    question: '',
    answer: '',
    category: '',
    keywords: [],
    priority: 50,
    is_active: true,
});

const applyFilters = () => {
    router.get(route('business.customer-bot.knowledge-base'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(() => filters.value.search, (value) => {
    if (value.length === 0 || value.length >= 3) {
        applyFilters();
    }
});

const openModal = (knowledge = null) => {
    if (knowledge) {
        editingKnowledge.value = knowledge;
        form.question = knowledge.question;
        form.answer = knowledge.answer;
        form.category = knowledge.category || '';
        form.keywords = knowledge.keywords || [];
        form.priority = knowledge.priority || 50;
        form.is_active = knowledge.is_active;
    } else {
        editingKnowledge.value = null;
        form.reset();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingKnowledge.value = null;
    form.reset();
};

const submit = () => {
    if (editingKnowledge.value) {
        form.put(route('business.customer-bot.knowledge-base.update', editingKnowledge.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('business.customer-bot.knowledge-base.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteKnowledge = (knowledge) => {
    if (confirm('Bu ma\'lumotni o\'chirmoqchimisiz?')) {
        router.delete(route('business.customer-bot.knowledge-base.destroy', knowledge.id));
    }
};

const addKeyword = () => {
    const keyword = prompt('Kalit so\'z kiriting:');
    if (keyword && !form.keywords.includes(keyword)) {
        form.keywords.push(keyword);
    }
};

const removeKeyword = (index) => {
    form.keywords.splice(index, 1);
};
</script>

<template>
    <Head title="Bilim Bazasi" />

    <BusinessLayout>
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Bilim Bazasi</h1>
                        <p class="mt-2 text-gray-600">Tez-tez so'raladigan savollar va javoblar</p>
                    </div>
                    <button
                        @click="openModal()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        + Yangi Q&A
                    </button>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qidirish</label>
                            <input
                                type="text"
                                v-model="filters.search"
                                placeholder="Savol, javob yoki kategoriya..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategoriya</label>
                            <select
                                v-model="filters.category"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="all">Barchasi</option>
                                <option v-for="category in categories" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Base List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="item in knowledge_base.data"
                            :key="item.id"
                            class="p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Question -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ item.question }}</h3>
                                        <span v-if="item.category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ item.category }}
                                        </span>
                                        <span v-if="!item.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            O'chirilgan
                                        </span>
                                    </div>

                                    <!-- Answer -->
                                    <p class="text-gray-600 mb-3">{{ item.answer }}</p>

                                    <!-- Keywords -->
                                    <div v-if="item.keywords && item.keywords.length > 0" class="flex flex-wrap gap-2 mb-2">
                                        <span
                                            v-for="(keyword, index) in item.keywords"
                                            :key="index"
                                            class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700"
                                        >
                                            {{ keyword }}
                                        </span>
                                    </div>

                                    <!-- Meta -->
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span>Prioritet: {{ item.priority }}</span>
                                        <span>{{ new Date(item.created_at).toLocaleDateString('uz-UZ') }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 ml-4">
                                    <button
                                        @click="openModal(item)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-md"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteKnowledge(item)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-md"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </li>

                        <li v-if="knowledge_base.data.length === 0" class="p-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="mt-4">Hech qanday ma'lumot topilmadi</p>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <div v-if="knowledge_base.data.length > 0" class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">{{ knowledge_base.from }}</span>
                                -
                                <span class="font-medium">{{ knowledge_base.to }}</span>
                                dan
                                <span class="font-medium">{{ knowledge_base.total }}</span>
                                ta
                            </div>

                            <div class="flex gap-2">
                                <Link
                                    v-if="knowledge_base.prev_page_url"
                                    :href="knowledge_base.prev_page_url"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    preserve-state
                                >
                                    Oldingi
                                </Link>

                                <Link
                                    v-if="knowledge_base.next_page_url"
                                    :href="knowledge_base.next_page_url"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    preserve-state
                                >
                                    Keyingi
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
            @click.self="closeModal"
        >
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ editingKnowledge ? 'Tahrirlash' : 'Yangi Q&A' }}
                    </h2>
                    <button @click="closeModal" class="p-2 hover:bg-gray-100 rounded-md">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Question -->
                    <div>
                        <label for="question" class="block text-sm font-medium text-gray-700 mb-1">Savol *</label>
                        <input
                            type="text"
                            id="question"
                            v-model="form.question"
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Masalan: Yetkazib berish narxi qancha?"
                        />
                        <p v-if="form.errors.question" class="mt-1 text-sm text-red-600">{{ form.errors.question }}</p>
                    </div>

                    <!-- Answer -->
                    <div>
                        <label for="answer" class="block text-sm font-medium text-gray-700 mb-1">Javob *</label>
                        <textarea
                            id="answer"
                            v-model="form.answer"
                            required
                            rows="4"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Bu savolga javob..."
                        ></textarea>
                        <p v-if="form.errors.answer" class="mt-1 text-sm text-red-600">{{ form.errors.answer }}</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Kategoriya</label>
                        <input
                            type="text"
                            id="category"
                            v-model="form.category"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Masalan: Yetkazib berish"
                            list="category-suggestions"
                        />
                        <datalist id="category-suggestions">
                            <option v-for="category in categories" :key="category" :value="category" />
                        </datalist>
                    </div>

                    <!-- Keywords -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kalit so'zlar</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <span
                                v-for="(keyword, index) in form.keywords"
                                :key="index"
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                            >
                                {{ keyword }}
                                <button
                                    type="button"
                                    @click="removeKeyword(index)"
                                    class="ml-2 hover:text-blue-600"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="addKeyword"
                            class="text-sm text-blue-600 hover:text-blue-700"
                        >
                            + Kalit so'z qo'shish
                        </button>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">
                            Prioritet: {{ form.priority }}
                        </label>
                        <input
                            type="range"
                            id="priority"
                            v-model="form.priority"
                            min="0"
                            max="100"
                            class="w-full"
                        />
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>Past</span>
                            <span>Yuqori</span>
                        </div>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Faol
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saqlanmoqda...</span>
                            <span v-else>{{ editingKnowledge ? 'Yangilash' : 'Qo\'shish' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </BusinessLayout>
</template>
