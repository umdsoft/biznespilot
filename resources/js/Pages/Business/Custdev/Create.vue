<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
    dreamBuyers: Array,
    defaultQuestions: Array,
});

const form = useForm({
    title: '',
    description: '',
    dream_buyer_id: '',
    collect_contact: true,
    welcome_message: "Sizning fikringiz biz uchun juda muhim! Bu so'rovnoma 3-5 daqiqangizni oladi.",
    thank_you_message: "Javoblaringiz uchun katta rahmat! Sizning fikringiz bizga juda qimmatli.",
    theme_color: '#10B981',
    response_limit: null,
    expires_at: null,
    questions: [],
});

// Initialize questions from defaultQuestions
if (props.defaultQuestions && props.defaultQuestions.length > 0) {
    form.questions = props.defaultQuestions.map((q, index) => ({
        ...q,
        order: index,
        is_required: true,
    }));
}

const themeColors = [
    { name: 'Emerald', value: '#10B981' },
    { name: 'Blue', value: '#3B82F6' },
    { name: 'Purple', value: '#8B5CF6' },
    { name: 'Pink', value: '#EC4899' },
    { name: 'Orange', value: '#F97316' },
    { name: 'Teal', value: '#14B8A6' },
];

const questionTypes = [
    { value: 'text', label: 'Qisqa matn', icon: 'text' },
    { value: 'textarea', label: 'Uzun matn', icon: 'textarea' },
    { value: 'select', label: 'Bir tanlov', icon: 'select' },
    { value: 'multiselect', label: 'Ko\'p tanlov', icon: 'multiselect' },
    { value: 'rating', label: 'Reyting (1-5)', icon: 'rating' },
    { value: 'scale', label: 'Shkala (1-10)', icon: 'scale' },
];

const showAddQuestion = ref(false);
const showEditQuestion = ref(false);
const showPreview = ref(false);
const activeSection = ref('basic');
const editingQuestionIndex = ref(null);
const newQuestion = ref({
    question: '',
    type: 'textarea',
    options: [],
    category: 'custom',
    is_required: true,
});
const editQuestion = ref({
    question: '',
    type: 'textarea',
    options: [],
    category: 'custom',
    is_required: true,
});
const newOption = ref('');
const editOption = ref('');

const sections = [
    { id: 'basic', label: 'Asosiy', icon: 'info' },
    { id: 'messages', label: 'Xabarlar', icon: 'message' },
    { id: 'settings', label: 'Sozlamalar', icon: 'settings' },
    { id: 'questions', label: 'Savollar', icon: 'questions' },
];

const formProgress = computed(() => {
    let completed = 0;
    if (form.title) completed++;
    if (form.welcome_message) completed++;
    if (form.thank_you_message) completed++;
    if (form.questions.length > 0) completed++;
    return Math.round((completed / 4) * 100);
});

const getCategoryColor = (category) => {
    const colors = {
        where_spend_time: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
        info_sources: 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
        frustrations: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
        dreams: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
        fears: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
        communication_preferences: 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
        daily_routine: 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        happiness_triggers: 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-300 border-pink-200 dark:border-pink-800',
        satisfaction: 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800',
        custom: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
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
        communication_preferences: 'Kommunikatsiya',
        daily_routine: 'Kundalik hayot',
        happiness_triggers: 'Baxt omillari',
        satisfaction: 'Qoniqish',
        custom: 'Maxsus',
    };
    return labels[category] || category;
};

const getCategoryIcon = (category) => {
    const icons = {
        where_spend_time: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        info_sources: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        frustrations: 'M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        dreams: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
        fears: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        communication_preferences: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        daily_routine: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        happiness_triggers: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        satisfaction: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        custom: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    };
    return icons[category] || icons.custom;
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

// Edit question functions
const openEditQuestion = (index) => {
    editingQuestionIndex.value = index;
    const q = form.questions[index];
    editQuestion.value = {
        question: q.question,
        type: q.type,
        options: [...(q.options || [])],
        category: q.category,
        is_required: q.is_required,
    };
    showEditQuestion.value = true;
};

const addEditOption = () => {
    if (editOption.value.trim()) {
        editQuestion.value.options.push(editOption.value.trim());
        editOption.value = '';
    }
};

const removeEditOption = (index) => {
    editQuestion.value.options.splice(index, 1);
};

const saveEditQuestion = () => {
    if (editQuestion.value.question.trim() && editingQuestionIndex.value !== null) {
        form.questions[editingQuestionIndex.value] = {
            ...editQuestion.value,
            order: editingQuestionIndex.value,
        };
        showEditQuestion.value = false;
        editingQuestionIndex.value = null;
    }
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
    form.post(route('business.custdev.store'), {
        onError: (errors) => {
            console.error('Form errors:', errors);
        },
        onSuccess: () => {
            console.log('Survey created successfully');
        },
    });
};
</script>

<template>
    <BusinessLayout title="Yangi So'rovnoma">
        <Head title="Yangi CustDev So'rovnoma" />

        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <!-- Hero Header -->
            <div class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-600 to-teal-600"></div>
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                </div>

                <div class="relative px-6 py-8">
                    <Link
                        :href="route('business.custdev.index')"
                        class="inline-flex items-center gap-2 text-emerald-100 hover:text-white transition-colors mb-6"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span class="font-medium">Orqaga qaytish</span>
                    </Link>

                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Yangi So'rovnoma</h1>
                                <p class="text-emerald-100 mt-1">CustDev metodologiyasi asosida professional so'rovnoma yarating</p>
                            </div>
                        </div>

                        <!-- Progress Circle -->
                        <div class="hidden md:flex items-center gap-4 bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                            <div class="relative w-16 h-16">
                                <svg class="w-16 h-16 transform -rotate-90">
                                    <circle cx="32" cy="32" r="28" stroke="rgba(255,255,255,0.2)" stroke-width="4" fill="none"/>
                                    <circle cx="32" cy="32" r="28" stroke="white" stroke-width="4" fill="none"
                                        :stroke-dasharray="175.93"
                                        :stroke-dashoffset="175.93 - (175.93 * formProgress / 100)"
                                        stroke-linecap="round"/>
                                </svg>
                                <span class="absolute inset-0 flex items-center justify-center text-white font-bold">{{ formProgress }}%</span>
                            </div>
                            <div class="text-white">
                                <p class="font-semibold">To'ldirilganlik</p>
                                <p class="text-sm text-emerald-100">{{ form.questions.length }} ta savol</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Navigation Tabs -->
                    <div class="flex items-center gap-2 mb-8 overflow-x-auto pb-2">
                        <button
                            v-for="section in sections"
                            :key="section.id"
                            @click="activeSection = section.id"
                            :class="[
                                'flex items-center gap-2 px-5 py-3 rounded-xl font-medium transition-all whitespace-nowrap',
                                activeSection === section.id
                                    ? 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 shadow-lg shadow-emerald-500/10'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-white/50 dark:hover:bg-gray-800/50'
                            ]"
                        >
                            <svg v-if="section.icon === 'info'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <svg v-if="section.icon === 'message'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                            <svg v-if="section.icon === 'settings'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg v-if="section.icon === 'questions'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ section.label }}
                        </button>

                        <div class="flex-1"></div>

                        <!-- Preview Button -->
                        <button
                            @click="showPreview = !showPreview"
                            class="flex items-center gap-2 px-5 py-3 rounded-xl font-medium bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg shadow-purple-500/25 hover:shadow-purple-500/40 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Ko'rish
                        </button>
                    </div>

                    <form @submit.prevent="submit">
                        <!-- Basic Info Section -->
                        <div v-show="activeSection === 'basic'" class="space-y-6 animate-fadeIn">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Asosiy Ma'lumotlar</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">So'rovnoma nomi va tavsifi</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 space-y-6">
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                            So'rovnoma nomi
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.title"
                                            type="text"
                                            class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                            placeholder="Masalan: Mijozlar ehtiyojlari tadqiqoti"
                                            required
                                        />
                                        <p v-if="form.errors.title" class="mt-2 text-sm text-red-600">{{ form.errors.title }}</p>
                                    </div>

                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                            </svg>
                                            Qisqacha tavsif
                                        </label>
                                        <textarea
                                            v-model="form.description"
                                            rows="3"
                                            class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow resize-none"
                                            placeholder="Bu so'rovnoma nima uchun kerak? Qanday ma'lumotlar yig'iladi?"
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                Ideal Mijoz bilan bog'lash
                                            </label>
                                            <select
                                                v-model="form.dream_buyer_id"
                                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                            >
                                                <option value="">Yangi profil yaratish</option>
                                                <option v-for="buyer in dreamBuyers" :key="buyer.id" :value="buyer.id">
                                                    {{ buyer.name }}
                                                </option>
                                            </select>
                                            <p class="mt-2 flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Javoblar avtomatik ravishda Ideal Mijoz profiliga qo'shiladi
                                            </p>
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                                </svg>
                                                Tema rangi
                                            </label>
                                            <div class="flex flex-wrap gap-3">
                                                <button
                                                    v-for="color in themeColors"
                                                    :key="color.value"
                                                    type="button"
                                                    @click="form.theme_color = color.value"
                                                    :class="[
                                                        'w-10 h-10 rounded-xl transition-all duration-200 flex items-center justify-center',
                                                        form.theme_color === color.value ? 'ring-2 ring-offset-2 dark:ring-offset-gray-800 scale-110' : 'hover:scale-105'
                                                    ]"
                                                    :style="{ backgroundColor: color.value, ringColor: color.value }"
                                                    :title="color.name"
                                                >
                                                    <svg v-if="form.theme_color === color.value" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Messages Section -->
                        <div v-show="activeSection === 'messages'" class="space-y-6 animate-fadeIn">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Xabarlar</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Respondentlarga ko'rsatiladigan xabarlar</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6 space-y-6">
                                    <div class="relative">
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                            Kutib olish xabari
                                        </label>
                                        <div class="relative">
                                            <textarea
                                                v-model="form.welcome_message"
                                                rows="3"
                                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow resize-none"
                                                placeholder="So'rovnoma boshidagi xabar..."
                                            ></textarea>
                                            <div class="absolute right-3 bottom-3">
                                                <span class="text-xs text-gray-400">{{ form.welcome_message?.length || 0 }} belgi</span>
                                            </div>
                                        </div>
                                        <div class="mt-3 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800">
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-medium mb-1">Ko'rinishi:</p>
                                            <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ form.welcome_message || 'Xabar kiritilmagan' }}</p>
                                        </div>
                                    </div>

                                    <div class="relative">
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                            </svg>
                                            Rahmat xabari
                                        </label>
                                        <div class="relative">
                                            <textarea
                                                v-model="form.thank_you_message"
                                                rows="3"
                                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow resize-none"
                                                placeholder="So'rovnoma oxiridagi xabar..."
                                            ></textarea>
                                            <div class="absolute right-3 bottom-3">
                                                <span class="text-xs text-gray-400">{{ form.thank_you_message?.length || 0 }} belgi</span>
                                            </div>
                                        </div>
                                        <div class="mt-3 p-4 bg-pink-50 dark:bg-pink-900/20 rounded-xl border border-pink-100 dark:border-pink-800">
                                            <p class="text-xs text-pink-600 dark:text-pink-400 font-medium mb-1">Ko'rinishi:</p>
                                            <p class="text-sm text-pink-700 dark:text-pink-300">{{ form.thank_you_message || 'Xabar kiritilmagan' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Settings Section -->
                        <div v-show="activeSection === 'settings'" class="space-y-6 animate-fadeIn">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">Sozlamalar</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">So'rovnoma parametrlari</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Collect Contact -->
                                        <label class="relative flex items-start gap-4 p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl cursor-pointer border-2 border-transparent hover:border-emerald-200 dark:hover:border-emerald-800 transition-all group">
                                            <input
                                                v-model="form.collect_contact"
                                                type="checkbox"
                                                class="sr-only peer"
                                            />
                                            <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow peer-checked:bg-emerald-100 dark:peer-checked:bg-emerald-900/50">
                                                <svg class="w-6 h-6 text-gray-400 peer-checked:text-emerald-600" :class="form.collect_contact ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-semibold text-gray-900 dark:text-gray-100 block">Kontakt ma'lumotlar</span>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ism, telefon va email so'raladi</p>
                                            </div>
                                            <div class="absolute top-4 right-4">
                                                <div :class="form.collect_contact ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'" class="w-10 h-6 rounded-full transition-colors relative">
                                                    <div :class="form.collect_contact ? 'translate-x-4' : 'translate-x-0'" class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"></div>
                                                </div>
                                            </div>
                                        </label>

                                        <!-- Response Limit -->
                                        <div class="p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-gray-100 block">Javoblar limiti</span>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Maksimal respondentlar</p>
                                                </div>
                                            </div>
                                            <input
                                                v-model="form.response_limit"
                                                type="number"
                                                min="1"
                                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                                placeholder="Cheksiz"
                                            />
                                        </div>

                                        <!-- Expires At -->
                                        <div class="p-5 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl">
                                            <div class="flex items-center gap-3 mb-3">
                                                <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-gray-100 block">Amal qilish muddati</span>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Oxirgi qabul sanasi</p>
                                                </div>
                                            </div>
                                            <input
                                                v-model="form.expires_at"
                                                type="date"
                                                class="w-full px-4 py-3 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Section -->
                        <div v-show="activeSection === 'questions'" class="space-y-6 animate-fadeIn">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Savollar</h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ form.questions.length }} ta savol qo'shilgan</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="showAddQuestion = true"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Savol qo'shish
                                        </button>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div v-if="form.questions.length === 0" class="text-center py-16">
                                        <div class="w-20 h-20 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Hech qanday savol qo'shilmagan</h4>
                                        <p class="text-gray-500 dark:text-gray-400 mb-6">Yuqoridagi "Savol qo'shish" tugmasini bosing</p>
                                    </div>

                                    <div v-else class="space-y-4">
                                        <div
                                            v-for="(question, index) in form.questions"
                                            :key="index"
                                            class="group relative bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all"
                                        >
                                            <!-- Category Color Bar -->
                                            <div
                                                class="absolute left-0 top-0 bottom-0 w-1"
                                                :class="getCategoryColor(question.category).replace('bg-', 'bg-').split(' ')[0]"
                                            ></div>

                                            <div class="flex items-start gap-4 p-5 pl-6">
                                                <!-- Reorder Controls -->
                                                <div class="flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <button
                                                        type="button"
                                                        @click="moveQuestion(index, -1)"
                                                        :disabled="index === 0"
                                                        class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 disabled:opacity-30 transition-colors"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="moveQuestion(index, 1)"
                                                        :disabled="index === form.questions.length - 1"
                                                        class="p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 disabled:opacity-30 transition-colors"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </button>
                                                </div>

                                                <!-- Question Number -->
                                                <div class="flex-shrink-0">
                                                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-bold">
                                                        {{ index + 1 }}
                                                    </span>
                                                </div>

                                                <!-- Question Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-gray-100">
                                                                {{ question.question }}
                                                                <span v-if="question.is_required" class="text-red-500 ml-1">*</span>
                                                            </p>
                                                            <div class="flex items-center gap-2 mt-2">
                                                                <span :class="getCategoryColor(question.category)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getCategoryIcon(question.category)" />
                                                                    </svg>
                                                                    {{ getCategoryLabel(question.category) }}
                                                                </span>
                                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                                    {{ questionTypes.find(t => t.value === question.type)?.label || question.type }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1">
                                                            <button
                                                                type="button"
                                                                @click="openEditQuestion(index)"
                                                                class="flex-shrink-0 p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                                title="Tahrirlash"
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button
                                                                type="button"
                                                                @click="removeQuestion(index)"
                                                                class="flex-shrink-0 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                                title="O'chirish"
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Options -->
                                                    <div v-if="question.options && question.options.length > 0" class="mt-3 flex flex-wrap gap-2">
                                                        <span
                                                            v-for="(option, optIndex) in question.options"
                                                            :key="optIndex"
                                                            class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                                                        >
                                                            <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                                                            {{ option }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Section -->
                        <div class="sticky bottom-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-lg rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg p-4 mt-8">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" :class="form.title ? 'bg-emerald-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">Nom</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full" :class="form.questions.length > 0 ? 'bg-emerald-500' : 'bg-gray-300'"></div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ form.questions.length }} savol</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Link
                                        :href="route('business.custdev.index')"
                                        class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                                    >
                                        Bekor qilish
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="form.processing || !form.title || form.questions.length === 0"
                                        class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                    >
                                        <span v-if="form.processing" class="flex items-center gap-2">
                                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Saqlanmoqda...
                                        </span>
                                        <span v-else class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            So'rovnoma Yaratish
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Question Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showAddQuestion" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showAddQuestion = false"></div>

                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95 translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-4"
                        >
                            <div v-if="showAddQuestion" class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden">
                                <!-- Modal Header -->
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-white">Yangi Savol Qo'shish</h3>
                                                <p class="text-sm text-emerald-100">So'rovnomaga yangi savol qo'shing</p>
                                            </div>
                                        </div>
                                        <button
                                            @click="showAddQuestion = false"
                                            class="p-2 rounded-xl hover:bg-white/20 text-white transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modal Body -->
                                <div class="p-6 space-y-5">
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Savol matni
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="newQuestion.question"
                                            rows="3"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow resize-none placeholder-gray-400 dark:placeholder-gray-500"
                                            placeholder="Savolingizni yozing..."
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                                </svg>
                                                Savol turi
                                            </label>
                                            <select
                                                v-model="newQuestion.type"
                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                            >
                                                <option v-for="type in questionTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                Kategoriya
                                            </label>
                                            <select
                                                v-model="newQuestion.category"
                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                                            >
                                                <option value="custom">Maxsus savol</option>
                                                <option value="where_spend_time">Vaqt o'tkazish</option>
                                                <option value="info_sources">Ma'lumot manbalari</option>
                                                <option value="frustrations">Muammolar</option>
                                                <option value="dreams">Orzular</option>
                                                <option value="fears">Qo'rquvlar</option>
                                                <option value="communication_preferences">Kommunikatsiya</option>
                                                <option value="daily_routine">Kundalik hayot</option>
                                                <option value="happiness_triggers">Baxt omillari</option>
                                                <option value="satisfaction">Qoniqish</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Options for select/multiselect -->
                                    <div v-if="newQuestion.type === 'select' || newQuestion.type === 'multiselect'" class="bg-gray-50 dark:bg-gray-900/70 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                            </svg>
                                            Javob variantlari
                                        </label>
                                        <div class="flex gap-2 mb-3">
                                            <input
                                                v-model="newOption"
                                                type="text"
                                                class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow placeholder-gray-400 dark:placeholder-gray-500"
                                                placeholder="Variant qo'shish"
                                                @keyup.enter="addOption"
                                            />
                                            <button
                                                type="button"
                                                @click="addOption"
                                                class="px-4 py-2.5 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 rounded-xl hover:bg-emerald-200 dark:hover:bg-emerald-900/70 font-medium transition-colors"
                                            >
                                                Qo'shish
                                            </button>
                                        </div>
                                        <div v-if="newQuestion.options.length > 0" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="(option, index) in newQuestion.options"
                                                :key="index"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-500"
                                            >
                                                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                                {{ option }}
                                                <button
                                                    type="button"
                                                    @click="removeOption(index)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">Hech qanday variant qo'shilmagan</p>
                                    </div>

                                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/70 rounded-xl cursor-pointer border border-gray-200 dark:border-gray-600">
                                        <input
                                            v-model="newQuestion.is_required"
                                            type="checkbox"
                                            class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-500 text-emerald-600 focus:ring-emerald-500 bg-white dark:bg-gray-700"
                                        />
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">Majburiy savol</span>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Respondent bu savolga javob berishi shart</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Modal Footer -->
                                <div class="bg-gray-50 dark:bg-gray-900/70 px-6 py-4 flex gap-3 border-t border-gray-200 dark:border-gray-600">
                                    <button
                                        type="button"
                                        @click="showAddQuestion = false"
                                        class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl border-2 border-gray-300 dark:border-gray-500 transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="button"
                                        @click="addQuestion"
                                        :disabled="!newQuestion.question.trim()"
                                        class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-medium rounded-xl shadow-lg shadow-emerald-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Qo'shish
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Edit Question Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showEditQuestion" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditQuestion = false"></div>

                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95 translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-4"
                        >
                            <div v-if="showEditQuestion" class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden">
                                <!-- Modal Header -->
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-5">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-lg font-bold text-white">Savolni Tahrirlash</h3>
                                                <p class="text-sm text-blue-100">Savol {{ editingQuestionIndex !== null ? editingQuestionIndex + 1 : '' }}</p>
                                            </div>
                                        </div>
                                        <button
                                            @click="showEditQuestion = false"
                                            class="p-2 rounded-xl hover:bg-white/20 text-white transition-colors"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Modal Body -->
                                <div class="p-6 space-y-5">
                                    <div>
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Savol matni
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <textarea
                                            v-model="editQuestion.question"
                                            rows="3"
                                            class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow resize-none placeholder-gray-400 dark:placeholder-gray-500"
                                            placeholder="Savolingizni yozing..."
                                        ></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                                </svg>
                                                Savol turi
                                            </label>
                                            <select
                                                v-model="editQuestion.type"
                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow"
                                            >
                                                <option v-for="type in questionTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                Kategoriya
                                            </label>
                                            <select
                                                v-model="editQuestion.category"
                                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow"
                                            >
                                                <option value="custom">Maxsus savol</option>
                                                <option value="where_spend_time">Vaqt o'tkazish</option>
                                                <option value="info_sources">Ma'lumot manbalari</option>
                                                <option value="frustrations">Muammolar</option>
                                                <option value="dreams">Orzular</option>
                                                <option value="fears">Qo'rquvlar</option>
                                                <option value="communication_preferences">Kommunikatsiya</option>
                                                <option value="daily_routine">Kundalik hayot</option>
                                                <option value="happiness_triggers">Baxt omillari</option>
                                                <option value="satisfaction">Qoniqish</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Options for select/multiselect -->
                                    <div v-if="editQuestion.type === 'select' || editQuestion.type === 'multiselect'" class="bg-gray-50 dark:bg-gray-900/70 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                            </svg>
                                            Javob variantlari
                                        </label>
                                        <div class="flex gap-2 mb-3">
                                            <input
                                                v-model="editOption"
                                                type="text"
                                                class="flex-1 px-4 py-2.5 rounded-xl border-2 border-gray-300 dark:border-gray-500 bg-white dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow placeholder-gray-400 dark:placeholder-gray-500"
                                                placeholder="Variant qo'shish"
                                                @keyup.enter="addEditOption"
                                            />
                                            <button
                                                type="button"
                                                @click="addEditOption"
                                                class="px-4 py-2.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-900/70 font-medium transition-colors"
                                            >
                                                Qo'shish
                                            </button>
                                        </div>
                                        <div v-if="editQuestion.options.length > 0" class="flex flex-wrap gap-2">
                                            <span
                                                v-for="(option, index) in editQuestion.options"
                                                :key="index"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-700 rounded-lg text-sm text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-500"
                                            >
                                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                                {{ option }}
                                                <button
                                                    type="button"
                                                    @click="removeEditOption(index)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">Hech qanday variant qo'shilmagan</p>
                                    </div>

                                    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/70 rounded-xl cursor-pointer border border-gray-200 dark:border-gray-600">
                                        <input
                                            v-model="editQuestion.is_required"
                                            type="checkbox"
                                            class="w-5 h-5 rounded border-2 border-gray-300 dark:border-gray-500 text-blue-600 focus:ring-blue-500 bg-white dark:bg-gray-700"
                                        />
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">Majburiy savol</span>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Respondent bu savolga javob berishi shart</p>
                                        </div>
                                    </label>
                                </div>

                                <!-- Modal Footer -->
                                <div class="bg-gray-50 dark:bg-gray-900/70 px-6 py-4 flex gap-3 border-t border-gray-200 dark:border-gray-600">
                                    <button
                                        type="button"
                                        @click="showEditQuestion = false"
                                        class="flex-1 px-4 py-3 bg-white dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium rounded-xl border-2 border-gray-300 dark:border-gray-500 transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="button"
                                        @click="saveEditQuestion"
                                        :disabled="!editQuestion.question.trim()"
                                        class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-medium rounded-xl shadow-lg shadow-blue-500/25 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Saqlash
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Preview Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showPreview" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 py-8">
                        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPreview = false"></div>

                        <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden">
                            <!-- Preview Header -->
                            <div class="p-6 text-center" :style="{ backgroundColor: form.theme_color + '20' }">
                                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" :style="{ backgroundColor: form.theme_color }">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ form.title || 'So\'rovnoma nomi' }}</h3>
                                <p class="text-gray-600 dark:text-gray-400 mt-2">{{ form.welcome_message }}</p>
                            </div>

                            <div class="p-6 max-h-96 overflow-y-auto">
                                <div v-if="form.questions.length === 0" class="text-center py-8 text-gray-500">
                                    Savollar qo'shilmagan
                                </div>
                                <div v-else class="space-y-4">
                                    <div v-for="(question, index) in form.questions" :key="index" class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            {{ index + 1 }}. {{ question.question }}
                                            <span v-if="question.is_required" class="text-red-500">*</span>
                                        </p>
                                        <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex justify-center">
                                <button
                                    @click="showPreview = false"
                                    class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                                >
                                    Yopish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </BusinessLayout>
</template>

<style scoped>
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
