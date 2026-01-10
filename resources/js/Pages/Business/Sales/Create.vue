<script setup>
import { computed, ref, watch } from 'vue';
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    ArrowLeftIcon,
    UserPlusIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    SparklesIcon,
    CheckIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    EyeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    channels: {
        type: Array,
        default: () => [],
    },
});

// Get flash messages
const page = usePage();

// Duplicate warning from backend (via session flash)
const duplicateWarning = computed(() => page.props.flash?.duplicate_warning);

// Real-time duplicate check state
const duplicateCheck = ref({
    loading: false,
    duplicates: null,
});

// Dismiss duplicate warning
const forceCreate = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    source_id: '',
    status: 'new',
    score: 0,
    estimated_value: null,
    notes: '',
});

// Validation state
const validationErrors = ref({
    name: '',
    email: '',
    phone: '',
});

// Phone mask options for Uzbekistan format
const phoneMaskOptions = {
    mask: '+998 ## ### ## ##',
    eager: true,
};

// Currency mask options
const currencyMaskOptions = {
    mask: '9,99#',
    tokens: {
        '9': { pattern: /[0-9]/, repeated: true },
    },
};

// Group channels by category
const groupedChannels = computed(() => {
    const groups = {};
    const categoryLabels = {
        digital: 'Digital',
        offline: 'Offline',
        referral: 'Tavsiya',
        organic: 'Organik',
    };

    props.channels.forEach(channel => {
        const category = channel.category || 'other';
        if (!groups[category]) {
            groups[category] = {
                label: categoryLabels[category] || category,
                items: []
            };
        }
        groups[category].items.push(channel);
    });

    return groups;
});

// Email validation
const validateEmail = (email) => {
    if (!email) {
        validationErrors.value.email = '';
        return true;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        validationErrors.value.email = 'Email formati noto\'g\'ri';
        return false;
    }
    validationErrors.value.email = '';
    return true;
};

// Phone validation
const validatePhone = (phone) => {
    if (!phone) {
        validationErrors.value.phone = '';
        return true;
    }
    // Check if phone is complete (should be +998 XX XXX XX XX format = 17 chars with spaces)
    const cleanPhone = phone.replace(/\s/g, '');
    if (cleanPhone.length < 13) {
        validationErrors.value.phone = 'Telefon raqamini to\'liq kiriting';
        return false;
    }
    validationErrors.value.phone = '';
    return true;
};

// Name validation (only letters and spaces)
const validateName = (name) => {
    if (!name) {
        validationErrors.value.name = '';
        return true;
    }
    // Allow letters (including Uzbek/Russian), spaces, apostrophes
    const nameRegex = /^[a-zA-ZА-Яа-яЎўҚқҒғҲҳ\s'`'-]+$/;
    if (!nameRegex.test(name)) {
        validationErrors.value.name = 'Faqat harflar va bo\'shliq kiritish mumkin';
        return false;
    }
    if (name.length < 2) {
        validationErrors.value.name = 'Ism kamida 2 ta belgidan iborat bo\'lishi kerak';
        return false;
    }
    validationErrors.value.name = '';
    return true;
};

// Format currency input
const formatCurrency = (value) => {
    if (!value) return '';
    const num = parseFloat(value);
    if (isNaN(num)) return '';
    return new Intl.NumberFormat('uz-UZ').format(num);
};

// Parse currency input
const parseCurrency = (value) => {
    if (!value) return null;
    const cleaned = value.replace(/[^\d]/g, '');
    return cleaned ? parseInt(cleaned, 10) : null;
};

// Handle currency input
const handleCurrencyInput = (event) => {
    const value = event.target.value;
    form.estimated_value = parseCurrency(value);
};

// Check for duplicates when phone is complete
const checkDuplicates = async () => {
    const cleanPhone = form.phone?.replace(/\s/g, '');
    if (!cleanPhone || cleanPhone.length < 13) {
        duplicateCheck.value.duplicates = null;
        return;
    }

    duplicateCheck.value.loading = true;
    try {
        const response = await fetch(route('business.api.sales.check-duplicate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: JSON.stringify({ phone: form.phone }),
        });
        const data = await response.json();
        duplicateCheck.value.duplicates = data.has_duplicates ? data.duplicates : null;
    } catch (error) {
        console.error('Duplicate check failed:', error);
    } finally {
        duplicateCheck.value.loading = false;
    }
};

// Debounced duplicate check
let duplicateTimeout;
watch(() => form.phone, (newPhone) => {
    clearTimeout(duplicateTimeout);
    const cleanPhone = newPhone?.replace(/\s/g, '');
    if (cleanPhone && cleanPhone.length >= 13) {
        duplicateTimeout = setTimeout(checkDuplicates, 500);
    } else {
        duplicateCheck.value.duplicates = null;
    }
});

const submit = () => {
    // Validate all fields before submit
    const isNameValid = validateName(form.name);
    const isEmailValid = validateEmail(form.email);
    const isPhoneValid = validatePhone(form.phone);

    if (!isNameValid || !isEmailValid || !isPhoneValid) {
        return;
    }

    // Add force_create if user confirmed
    if (forceCreate.value) {
        form.transform((data) => ({
            ...data,
            force_create: true,
        })).post(route('business.sales.store'));
    } else {
        form.post(route('business.sales.store'));
    }
};

// Handle force create (user wants to create despite duplicate)
const handleForceCreate = () => {
    forceCreate.value = true;
    submit();
};

const getScoreColor = (score) => {
    if (score >= 70) return 'text-green-600 dark:text-green-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getScoreBg = (score) => {
    if (score >= 70) return 'bg-green-500';
    if (score >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};
</script>

<template>
    <BusinessLayout title="Yangi Lead">
        <Head title="Yangi Lead Qo'shish" />

        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <Link
                        :href="route('business.sales.index')"
                        class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-4"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Sotuv va Leadlarga qaytish
                    </Link>

                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <UserPlusIcon class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Yangi Lead Qo'shish</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Potensial mijoz ma'lumotlarini kiriting
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Backend Duplicate Warning (from session flash) -->
                <div v-if="duplicateWarning && !forceCreate" class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                {{ duplicateWarning.message }}
                            </h3>
                            <div class="mt-2 p-3 bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ duplicateWarning.existing_lead.name }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ duplicateWarning.existing_lead.phone }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Yaratilgan: {{ duplicateWarning.existing_lead.created_at }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="route('business.sales.show', duplicateWarning.existing_lead.id)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                        Ko'rish
                                    </Link>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="handleForceCreate"
                                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    Baribir yaratish
                                </button>
                                <Link
                                    :href="route('business.sales.show', duplicateWarning.existing_lead.id)"
                                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
                                >
                                    Mavjud lidga o'tish
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Real-time Duplicate Warning (from API check) -->
                <div v-if="duplicateCheck.duplicates?.phone && !duplicateWarning" class="mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                Bu telefon raqami bilan lid(lar) mavjud
                            </h3>
                            <div class="mt-2 space-y-2">
                                <div
                                    v-for="lead in duplicateCheck.duplicates.phone"
                                    :key="lead.id"
                                    class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-amber-200 dark:border-amber-700"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ lead.name }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ lead.phone }} • {{ lead.status }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                {{ lead.created_at }}
                                            </p>
                                        </div>
                                        <Link
                                            :href="route('business.sales.show', lead.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                            Ko'rish
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                                Agar yangi lid yaratmoqchi bo'lsangiz, davom eting. Tizim dublikat sifatida belgilaydi.
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Asosiy Ma'lumotlar -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <UserIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Asosiy Ma'lumotlar</h2>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Name -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Ism Familiya <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <UserIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            placeholder="Alisher Valiyev"
                                            required
                                            @blur="validateName(form.name)"
                                            @input="validateName(form.name)"
                                            class="w-full pl-11 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                            :class="{
                                                'border-red-500 dark:border-red-500': form.errors.name || validationErrors.name,
                                                'border-green-500 dark:border-green-500': form.name && !validationErrors.name && !form.errors.name
                                            }"
                                        />
                                        <div v-if="validationErrors.name" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <ExclamationCircleIcon class="w-5 h-5 text-red-500" />
                                        </div>
                                        <div v-else-if="form.name && !form.errors.name" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <CheckIcon class="w-5 h-5 text-green-500" />
                                        </div>
                                    </div>
                                    <p v-if="form.errors.name || validationErrors.name" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.name || validationErrors.name }}
                                    </p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Email
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="alisher@example.com"
                                            @blur="validateEmail(form.email)"
                                            @input="validateEmail(form.email)"
                                            class="w-full pl-11 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                            :class="{
                                                'border-red-500 dark:border-red-500': form.errors.email || validationErrors.email,
                                                'border-green-500 dark:border-green-500': form.email && !validationErrors.email && !form.errors.email
                                            }"
                                        />
                                        <div v-if="validationErrors.email" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <ExclamationCircleIcon class="w-5 h-5 text-red-500" />
                                        </div>
                                        <div v-else-if="form.email && !form.errors.email" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <CheckIcon class="w-5 h-5 text-green-500" />
                                        </div>
                                    </div>
                                    <p v-if="form.errors.email || validationErrors.email" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.email || validationErrors.email }}
                                    </p>
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Telefon
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <PhoneIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.phone"
                                            v-maska:[phoneMaskOptions]
                                            type="tel"
                                            placeholder="+998 90 123 45 67"
                                            @blur="validatePhone(form.phone)"
                                            class="w-full pl-11 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                            :class="{
                                                'border-red-500 dark:border-red-500': form.errors.phone || validationErrors.phone,
                                                'border-green-500 dark:border-green-500': form.phone && form.phone.replace(/\s/g, '').length >= 13 && !validationErrors.phone && !form.errors.phone
                                            }"
                                        />
                                        <div v-if="validationErrors.phone" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <ExclamationCircleIcon class="w-5 h-5 text-red-500" />
                                        </div>
                                        <div v-else-if="form.phone && form.phone.replace(/\s/g, '').length >= 13 && !form.errors.phone" class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                                            <CheckIcon class="w-5 h-5 text-green-500" />
                                        </div>
                                    </div>
                                    <p v-if="form.errors.phone || validationErrors.phone" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.phone || validationErrors.phone }}
                                    </p>
                                    <p v-else class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        Format: +998 XX XXX XX XX
                                    </p>
                                </div>

                                <!-- Company -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Kompaniya
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <BuildingOfficeIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            v-model="form.company"
                                            type="text"
                                            placeholder="ABC Company"
                                            maxlength="255"
                                            class="w-full pl-11 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                            :class="{ 'border-red-500 dark:border-red-500': form.errors.company }"
                                        />
                                    </div>
                                    <p v-if="form.errors.company" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.company }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Ma'lumotlari -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                    <ChartBarIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Lead Ma'lumotlari</h2>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Holat <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select
                                            v-model="form.status"
                                            required
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors appearance-none cursor-pointer"
                                            :class="{ 'border-red-500 dark:border-red-500': form.errors.status }"
                                        >
                                            <option value="new">Yangi</option>
                                            <option value="contacted">Bog'lanildi</option>
                                            <option value="qualified">Qualified</option>
                                            <option value="proposal">Taklif</option>
                                            <option value="negotiation">Muzokara</option>
                                            <option value="won">Yutildi</option>
                                            <option value="lost">Yo'qoldi</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.status" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.status }}
                                    </p>
                                </div>

                                <!-- Source -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Manba
                                    </label>
                                    <div class="relative">
                                        <select
                                            v-model="form.source_id"
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors appearance-none cursor-pointer"
                                            :class="{ 'border-red-500 dark:border-red-500': form.errors.source_id }"
                                        >
                                            <option value="">Tanlang</option>
                                            <optgroup
                                                v-for="(group, category) in groupedChannels"
                                                :key="category"
                                                :label="group.label"
                                            >
                                                <option
                                                    v-for="channel in group.items"
                                                    :key="channel.id"
                                                    :value="channel.id"
                                                >
                                                    {{ channel.name }}
                                                </option>
                                            </optgroup>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.source_id" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.source_id }}
                                    </p>
                                </div>

                                <!-- Score -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Lead Ball (0-100)
                                    </label>
                                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <SparklesIcon class="w-5 h-5 text-gray-400" />
                                                <span class="text-sm text-gray-600 dark:text-gray-400">Lead sifati</span>
                                            </div>
                                            <span
                                                class="text-2xl font-bold"
                                                :class="getScoreColor(form.score)"
                                            >
                                                {{ form.score }}
                                            </span>
                                        </div>
                                        <input
                                            v-model.number="form.score"
                                            type="range"
                                            min="0"
                                            max="100"
                                            class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600"
                                        />
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-3">
                                            <div
                                                class="h-1.5 rounded-full transition-all duration-300"
                                                :class="getScoreBg(form.score)"
                                                :style="{ width: form.score + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.score" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.score }}
                                    </p>
                                </div>

                                <!-- Estimated Value -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Taxminiy qiymat (so'm)
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                            <CurrencyDollarIcon class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <input
                                            :value="form.estimated_value ? formatCurrency(form.estimated_value) : ''"
                                            @input="handleCurrencyInput"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="1,000,000"
                                            class="w-full pl-11 pr-16 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                                            :class="{ 'border-red-500 dark:border-red-500': form.errors.estimated_value }"
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                            <span class="text-sm text-gray-400 dark:text-gray-500">so'm</span>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.estimated_value" class="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.estimated_value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Qo'shimcha Ma'lumotlar -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                    <DocumentTextIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Qo'shimcha Ma'lumotlar</h2>
                            </div>
                        </div>

                        <div class="p-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Izohlar
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="Lead haqida qo'shimcha ma'lumotlar..."
                                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none"
                                    :class="{ 'border-red-500 dark:border-red-500': form.errors.notes }"
                                ></textarea>
                                <div class="flex justify-between mt-1">
                                    <p v-if="form.errors.notes" class="text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.notes }}
                                    </p>
                                    <span v-else></span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ form.notes?.length || 0 }} / 2000
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4">
                        <Link
                            :href="route('business.sales.index')"
                            class="px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                        >
                            Bekor qilish
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !!validationErrors.name || !!validationErrors.email || !!validationErrors.phone"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-medium rounded-xl transition-colors"
                        >
                            <CheckIcon v-if="!form.processing" class="w-5 h-5" />
                            <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="form.processing">Saqlanmoqda...</span>
                            <span v-else>Lead Qo'shish</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </BusinessLayout>
</template>
