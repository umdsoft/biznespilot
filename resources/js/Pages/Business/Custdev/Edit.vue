<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
    survey: Object,
    dreamBuyers: Array,
});

const form = useForm({
    title: props.survey.title,
    description: props.survey.description || '',
    dream_buyer_id: props.survey.dream_buyer_id || '',
    collect_contact: props.survey.collect_contact,
    welcome_message: props.survey.welcome_message || '',
    thank_you_message: props.survey.thank_you_message || '',
    theme_color: props.survey.theme_color || '#10B981',
    response_limit: props.survey.response_limit,
    expires_at: props.survey.expires_at ? props.survey.expires_at.split('T')[0] : null,
    questions: props.survey.questions?.map((q, index) => ({
        id: q.id,
        question: q.question,
        type: q.type,
        options: q.options || [],
        category: q.category,
        is_required: q.is_required,
        order: index,
    })) || [],
});

const themeColors = [
    { name: 'Emerald', value: '#10B981' },
    { name: 'Blue', value: '#3B82F6' },
    { name: 'Purple', value: '#8B5CF6' },
    { name: 'Pink', value: '#EC4899' },
    { name: 'Orange', value: '#F97316' },
    { name: 'Teal', value: '#14B8A6' },
];

const questionTypes = [
    { value: 'text', label: 'Qisqa matn' },
    { value: 'textarea', label: 'Uzun matn' },
    { value: 'select', label: 'Bir tanlov' },
    { value: 'multiselect', label: 'Ko\'p tanlov' },
    { value: 'rating', label: 'Reyting (1-5)' },
    { value: 'scale', label: 'Shkala (1-10)' },
];

const showAddQuestion = ref(false);
const newQuestion = ref({
    question: '',
    type: 'textarea',
    options: [],
    category: 'custom',
    is_required: true,
});
const newOption = ref('');

const getCategoryColor = (category) => {
    const colors = {
        where_spend_time: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
        info_sources: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
        frustrations: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
        dreams: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
        fears: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
        satisfaction: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
        custom: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    };
    return colors[category] || colors.custom;
};

const getCategoryLabel = (category) => {
    const labels = {
        where_spend_time: 'Vaqt o\'tkazish',
        info_sources: 'Ma\'lumot manbalari',
        frustrations: 'Muammolar',
        dreams: 'Orzular',
        fears: 'Qo\'rquvlar',
        satisfaction: 'Qoniqish',
        custom: 'Maxsus',
    };
    return labels[category] || category;
};

const addOption = () => {
    if (newOption.value.trim()) {
        newQuestion.value.options.push(newOption.value.trim());
        newOption.value = '';
    }
};

const removeOption = (index) => {
    newQuestion.value.options.splice(index, 1);
};

const addQuestion = () => {
    if (newQuestion.value.question.trim()) {
        form.questions.push({
            ...newQuestion.value,
            order: form.questions.length,
        });
        newQuestion.value = {
            question: '',
            type: 'textarea',
            options: [],
            category: 'custom',
            is_required: true,
        };
        showAddQuestion.value = false;
    }
};

const removeQuestion = (index) => {
    form.questions.splice(index, 1);
    form.questions.forEach((q, i) => {
        q.order = i;
    });
};

const moveQuestion = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex >= 0 && newIndex < form.questions.length) {
        const temp = form.questions[index];
        form.questions[index] = form.questions[newIndex];
        form.questions[newIndex] = temp;
        form.questions.forEach((q, i) => {
            q.order = i;
        });
    }
};

const submit = () => {
    form.put(route('business.custdev.update', { custdev: props.survey.id }));
};

const copyLink = () => {
    const link = `${window.location.origin}/s/${props.survey.slug}`;
    navigator.clipboard.writeText(link);
};
</script>

<template>
    <BusinessLayout title="So'rovnomani Tahrirlash">
        <Head :title="`Tahrirlash - ${survey.title}`" />

        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('business.custdev.index')"
                    class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 mb-4"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Orqaga
                </Link>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/25">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">So'rovnomani Tahrirlash</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ survey.title }}</p>
                        </div>
                    </div>

                    <!-- Survey Link -->
                    <div class="flex items-center gap-3">
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-xl px-4 py-2 flex items-center gap-2">
                            <code class="text-sm text-gray-600 dark:text-gray-400">/s/{{ survey.slug }}</code>
                            <button
                                @click="copyLink"
                                class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                title="Linkni nusxalash"
                            >
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </div>
                        <Link
                            :href="route('business.custdev.results', { custdev: survey.id })"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium rounded-xl hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Natijalar
                        </Link>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Asosiy Ma'lumotlar</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                So'rovnoma nomi *
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                required
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Qisqacha tavsif
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Ideal Mijoz bilan bog'lash
                            </label>
                            <select
                                v-model="form.dream_buyer_id"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                            >
                                <option value="">Bog'lamaslik</option>
                                <option v-for="buyer in dreamBuyers" :key="buyer.id" :value="buyer.id">
                                    {{ buyer.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tema rangi
                            </label>
                            <div class="flex gap-2">
                                <button
                                    v-for="color in themeColors"
                                    :key="color.value"
                                    type="button"
                                    @click="form.theme_color = color.value"
                                    :class="form.theme_color === color.value ? 'ring-2 ring-offset-2 dark:ring-offset-gray-800' : ''"
                                    class="w-8 h-8 rounded-lg transition-all"
                                    :style="{ backgroundColor: color.value, ringColor: color.value }"
                                    :title="color.name"
                                ></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Xabarlar</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Kutib olish xabari
                            </label>
                            <textarea
                                v-model="form.welcome_message"
                                rows="2"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Rahmat xabari
                            </label>
                            <textarea
                                v-model="form.thank_you_message"
                                rows="2"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Sozlamalar</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl cursor-pointer">
                            <input
                                v-model="form.collect_contact"
                                type="checkbox"
                                class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500"
                            />
                            <div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">Kontakt ma'lumotlar</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ism, telefon, email so'rash</p>
                            </div>
                        </label>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Javoblar limiti
                            </label>
                            <input
                                v-model="form.response_limit"
                                type="number"
                                min="1"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Cheksiz"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Amal qilish muddati
                            </label>
                            <input
                                v-model="form.expires_at"
                                type="date"
                                class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Savollar</h2>
                        <button
                            type="button"
                            @click="showAddQuestion = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-medium rounded-xl transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Savol qo'shish
                        </button>
                    </div>

                    <div v-if="form.questions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        Hech qanday savol qo'shilmagan
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(question, index) in form.questions"
                            :key="index"
                            class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                        >
                            <div class="flex flex-col gap-1">
                                <button
                                    type="button"
                                    @click="moveQuestion(index, -1)"
                                    :disabled="index === 0"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="moveQuestion(index, 1)"
                                    :disabled="index === form.questions.length - 1"
                                    class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium mr-2">
                                            {{ index + 1 }}
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ question.question }}</span>
                                        <span v-if="question.is_required" class="text-red-500 ml-1">*</span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeQuestion(index)"
                                        class="p-1 text-red-400 hover:text-red-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span :class="getCategoryColor(question.category)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ getCategoryLabel(question.category) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ questionTypes.find(t => t.value === question.type)?.label || question.type }}
                                    </span>
                                </div>
                                <div v-if="question.options && question.options.length > 0" class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="(option, optIndex) in question.options"
                                        :key="optIndex"
                                        class="px-2 py-0.5 bg-white dark:bg-gray-800 rounded text-xs text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                                    >
                                        {{ option }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('business.custdev.index')"
                        class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                    >
                        Bekor qilish
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50"
                    >
                        <span v-if="form.processing">Saqlanmoqda...</span>
                        <span v-else>Saqlash</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Add Question Modal -->
        <Teleport to="body">
            <div
                v-if="showAddQuestion"
                class="fixed inset-0 z-50 overflow-y-auto"
            >
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showAddQuestion = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Yangi Savol Qo'shish</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Savol matni *
                                </label>
                                <textarea
                                    v-model="newQuestion.question"
                                    rows="2"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="Savolingizni yozing..."
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Savol turi
                                    </label>
                                    <select
                                        v-model="newQuestion.type"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                    >
                                        <option v-for="type in questionTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Kategoriya
                                    </label>
                                    <select
                                        v-model="newQuestion.category"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                    >
                                        <option value="custom">Maxsus savol</option>
                                        <option value="where_spend_time">Vaqt o'tkazish</option>
                                        <option value="info_sources">Ma'lumot manbalari</option>
                                        <option value="frustrations">Muammolar</option>
                                        <option value="dreams">Orzular</option>
                                        <option value="fears">Qo'rquvlar</option>
                                        <option value="satisfaction">Qoniqish</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Options for select/multiselect -->
                            <div v-if="newQuestion.type === 'select' || newQuestion.type === 'multiselect'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Variantlar
                                </label>
                                <div class="flex gap-2 mb-2">
                                    <input
                                        v-model="newOption"
                                        type="text"
                                        class="flex-1 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500"
                                        placeholder="Variant qo'shish"
                                        @keyup.enter="addOption"
                                    />
                                    <button
                                        type="button"
                                        @click="addOption"
                                        class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-xl hover:bg-emerald-200 dark:hover:bg-emerald-900/50"
                                    >
                                        Qo'shish
                                    </button>
                                </div>
                                <div v-if="newQuestion.options.length > 0" class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(option, index) in newQuestion.options"
                                        :key="index"
                                        class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm"
                                    >
                                        {{ option }}
                                        <button
                                            type="button"
                                            @click="removeOption(index)"
                                            class="text-gray-400 hover:text-red-500"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </div>
                            </div>

                            <label class="flex items-center gap-2">
                                <input
                                    v-model="newQuestion.is_required"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">Majburiy savol</span>
                            </label>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                @click="showAddQuestion = false"
                                class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                type="button"
                                @click="addQuestion"
                                :disabled="!newQuestion.question.trim()"
                                class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50"
                            >
                                Qo'shish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
