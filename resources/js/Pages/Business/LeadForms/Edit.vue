<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';

const props = defineProps({
    leadForm: Object,
    leadSources: Object,
    fieldTypes: Object,
});

const form = useForm({
    name: props.leadForm.name,
    title: props.leadForm.title,
    description: props.leadForm.description || '',
    fields: props.leadForm.fields || [],
    submit_button_text: props.leadForm.submit_button_text || 'Yuborish',
    theme_color: props.leadForm.theme_color || '#10b981',
    default_source_id: props.leadForm.default_source_id,
    default_status: props.leadForm.default_status || 'new',
    default_score: props.leadForm.default_score || 50,
    lead_magnet_type: props.leadForm.lead_magnet_type || 'none',
    lead_magnet_title: props.leadForm.lead_magnet_title || '',
    lead_magnet_file: null,
    lead_magnet_link: props.leadForm.lead_magnet_link || '',
    lead_magnet_text: props.leadForm.lead_magnet_text || '',
    success_message: props.leadForm.success_message || 'Rahmat! Ma\'lumotlaringiz qabul qilindi.',
    redirect_url: props.leadForm.redirect_url || '',
    show_lead_magnet_on_success: props.leadForm.show_lead_magnet_on_success ?? true,
    track_utm: props.leadForm.track_utm ?? true,
    is_active: props.leadForm.is_active ?? true,
});

const activeStep = ref(1);
const showFieldModal = ref(false);
const editingFieldIndex = ref(null);

const newField = ref({
    id: '',
    type: 'text',
    label: '',
    placeholder: '',
    required: false,
    map_to: '',
    options: [],
});

const colors = [
    '#10b981', '#3b82f6', '#8b5cf6', '#ec4899',
    '#ef4444', '#f97316', '#eab308', '#06b6d4',
];

const leadMagnetTypes = [
    { value: 'none', label: 'Oddiy forma', icon: '📝', description: 'Faqat ma\'lumot to\'plash' },
    { value: 'file', label: 'Fayl yuklab olish', icon: '📄', description: 'PDF, Word, Excel va h.k.' },
    { value: 'video', label: 'Video dars', icon: '🎬', description: 'YouTube, Vimeo video (ko\'chirib bo\'lmaydi)' },
    { value: 'link', label: 'Link', icon: '🔗', description: 'Maxsus sahifa yoki video linki' },
    { value: 'coupon', label: 'Kupon kodi', icon: '🎟️', description: 'Chegirma yoki maxsus kod' },
    { value: 'text', label: 'Maxsus matn', icon: '💬', description: 'Ko\'rsatma yoki sirli so\'z' },
];

const statusOptions = [
    { value: 'new', label: 'Yangi' },
    { value: 'contacted', label: 'Bog\'lanildi' },
    { value: 'qualified', label: 'Qualified' },
];

const mapToOptions = [
    { value: 'name', label: 'Ism (name)' },
    { value: 'email', label: 'Email' },
    { value: 'phone', label: 'Telefon' },
    { value: 'company', label: 'Kompaniya' },
    { value: 'position', label: 'Lavozim' },
    { value: 'notes', label: 'Izoh' },
    { value: 'estimated_value', label: 'Taxminiy qiymat' },
    { value: 'custom', label: 'Maxsus maydon' },
];

const addField = () => {
    editingFieldIndex.value = null;
    newField.value = {
        id: `field_${Date.now()}`,
        type: 'text',
        label: '',
        placeholder: '',
        required: false,
        map_to: '',
        options: [],
    };
    showFieldModal.value = true;
};

const editField = (index) => {
    editingFieldIndex.value = index;
    newField.value = { ...form.fields[index] };
    showFieldModal.value = true;
};

const saveField = () => {
    if (editingFieldIndex.value !== null) {
        form.fields[editingFieldIndex.value] = { ...newField.value };
    } else {
        form.fields.push({ ...newField.value });
    }
    showFieldModal.value = false;
};

const removeField = (index) => {
    form.fields.splice(index, 1);
};

const moveField = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex >= 0 && newIndex < form.fields.length) {
        const temp = form.fields[index];
        form.fields[index] = form.fields[newIndex];
        form.fields[newIndex] = temp;
    }
};

const submit = () => {
    form.put(route('business.lead-forms.update', props.leadForm.id));
};

const getFieldTypeLabel = (type) => {
    return props.fieldTypes[type]?.label || type;
};

const handleFileChange = (event) => {
    form.lead_magnet_file = event.target.files[0];
};
</script>

<template>
    <BusinessLayout title="Formani Tahrirlash">
        <Head :title="`${leadForm.name} - Tahrirlash`" />

        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link
                    :href="route('business.lead-forms.show', leadForm.id)"
                    class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-center transition-colors shadow-sm"
                >
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Formani Tahrirlash</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ leadForm.name }}</p>
                </div>
                <div v-if="!form.is_active" class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-lg text-sm font-medium">
                    Faol emas
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="flex items-center justify-center mb-8">
                <div class="inline-flex items-center bg-white dark:bg-gray-800 rounded-2xl p-1.5 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <button
                        v-for="step in 3"
                        :key="step"
                        @click="activeStep = step"
                        :class="[
                            'flex items-center gap-2 px-5 py-2.5 rounded-xl font-medium transition-all',
                            activeStep === step
                                ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        ]"
                    >
                        <span
                            class="w-6 h-6 rounded-full flex items-center justify-center text-sm font-semibold"
                            :class="activeStep === step ? 'bg-white/20' : 'bg-gray-100 dark:bg-gray-700'"
                        >
                            {{ step }}
                        </span>
                        <span class="hidden sm:inline">
                            {{ step === 1 ? 'Forma' : step === 2 ? 'Lead Magnet' : 'Sozlamalar' }}
                        </span>
                    </button>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Step 1: Form Fields -->
                <div v-show="activeStep === 1" class="space-y-6">
                    <!-- Basic Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            Asosiy Ma'lumotlar
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Forma nomi (ichki) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sarlavha (ochiq) <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tavsif (ixtiyoriy)
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                            ></textarea>
                        </div>

                        <!-- Theme Color -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Tema rangi
                            </label>
                            <div class="flex flex-wrap gap-3">
                                <button
                                    v-for="color in colors"
                                    :key="color"
                                    @click="form.theme_color = color"
                                    class="w-10 h-10 rounded-xl transition-all hover:scale-110 shadow-sm"
                                    :class="form.theme_color === color ? 'ring-2 ring-offset-2 ring-offset-white dark:ring-offset-gray-800 ring-gray-900 dark:ring-white scale-110' : ''"
                                    :style="{ backgroundColor: color }"
                                ></button>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="mt-6 flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                id="is_active"
                                class="w-5 h-5 text-emerald-500 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-emerald-500"
                            />
                            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">Forma faol</span>
                                <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">Faol bo'lmasa, foydalanuvchilar formani to'ldira olmaydi</p>
                            </label>
                        </div>
                    </div>

                    <!-- Form Fields -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                </span>
                                Forma Maydonlari
                            </h3>
                            <button
                                @click="addField"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-sm font-medium transition-colors shadow-sm"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Maydon qo'shish
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(field, index) in form.fields"
                                :key="field.id"
                                class="group flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 rounded-xl hover:border-gray-200 dark:hover:border-gray-600 transition-colors"
                            >
                                <!-- Drag Handle & Order Buttons -->
                                <div class="flex flex-col items-center gap-0.5">
                                    <button
                                        @click="moveField(index, -1)"
                                        :disabled="index === 0"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                        </svg>
                                    </button>
                                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM8 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM14 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM14 12a2 2 0 1 1-4 0 2 2 0 0 1 4 0ZM14 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z" />
                                    </svg>
                                    <button
                                        @click="moveField(index, 1)"
                                        :disabled="index === form.fields.length - 1"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Field Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-semibold text-gray-900 dark:text-gray-100">{{ field.label }}</span>
                                        <span class="px-2.5 py-1 bg-gray-200 dark:bg-gray-700 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ getFieldTypeLabel(field.type) }}
                                        </span>
                                        <span v-if="field.required" class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 rounded text-xs font-medium text-red-600 dark:text-red-400">
                                            majburiy
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        <span v-if="field.map_to" class="inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                            {{ field.map_to }}
                                        </span>
                                        <span v-else class="text-gray-400 dark:text-gray-500 italic">Mapping belgilanmagan</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="editField(index)"
                                        class="p-2.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-colors"
                                        title="Tahrirlash"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="removeField(index)"
                                        class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-colors"
                                        title="O'chirish"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Lead Magnet -->
                <div v-show="activeStep === 2" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </span>
                            Lead Magnet Turi
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 ml-10">
                            Foydalanuvchi formani to'ldirgandan keyin nima oladi?
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <button
                                v-for="type in leadMagnetTypes"
                                :key="type.value"
                                @click="form.lead_magnet_type = type.value"
                                :class="[
                                    'p-5 rounded-xl border-2 text-left transition-all hover:shadow-md',
                                    form.lead_magnet_type === type.value
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 shadow-md'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-800'
                                ]"
                            >
                                <span class="text-3xl">{{ type.icon }}</span>
                                <div class="mt-3 font-semibold text-gray-900 dark:text-gray-100">{{ type.label }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ type.description }}</div>
                            </button>
                        </div>
                    </div>

                    <!-- Lead Magnet Details -->
                    <div v-if="form.lead_magnet_type !== 'none'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            Lead Magnet Sozlamalari
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Lead Magnet sarlavhasi
                                </label>
                                <input
                                    v-model="form.lead_magnet_title"
                                    type="text"
                                    placeholder="Masalan: Sizning sovg'angiz tayyor!"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>

                            <!-- File Upload -->
                            <div v-if="form.lead_magnet_type === 'file'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Yangi fayl (mavjudini almashtiradi)
                                </label>
                                <div class="relative">
                                    <input
                                        type="file"
                                        @change="handleFileChange"
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 transition-colors"
                                    />
                                </div>
                                <p v-if="leadForm.lead_magnet_file" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    Hozirgi fayl: <span class="font-medium">{{ leadForm.lead_magnet_file }}</span>
                                </p>
                            </div>

                            <!-- Video Input -->
                            <div v-if="form.lead_magnet_type === 'video'" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Video URL <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.lead_magnet_link"
                                        type="url"
                                        placeholder="https://www.youtube.com/watch?v=... yoki https://vimeo.com/..."
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    />
                                </div>
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="text-sm">
                                            <p class="font-medium text-blue-800 dark:text-blue-300 mb-1">Qo'llab-quvvatlanadigan platformalar:</p>
                                            <ul class="text-blue-700 dark:text-blue-400 space-y-1">
                                                <li>• <strong>YouTube</strong> - unlisted (ro'yxatda yo'q) qilib qo'ying</li>
                                                <li>• <strong>Vimeo</strong> - privacy sozlamalarini tekshiring</li>
                                                <li>• <strong>Kinescope</strong> - professional video hosting</li>
                                            </ul>
                                            <p class="mt-2 text-blue-600 dark:text-blue-500 text-xs">
                                                Video embed qilinadi va ko'chirib olish imkonsiz bo'ladi
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Link Input -->
                            <div v-if="form.lead_magnet_type === 'link'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Link <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.lead_magnet_link"
                                    type="url"
                                    placeholder="https://..."
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>

                            <!-- Coupon/Text Input -->
                            <div v-if="form.lead_magnet_type === 'coupon' || form.lead_magnet_type === 'text'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ form.lead_magnet_type === 'coupon' ? 'Kupon kodi' : 'Maxsus matn' }} <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.lead_magnet_text"
                                    rows="3"
                                    :placeholder="form.lead_magnet_type === 'coupon' ? 'SALE20' : 'Maxsus ko\'rsatma yoki matn...'"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Settings -->
                <div v-show="activeStep === 3" class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </span>
                            Lead Sozlamalari
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Lead manbasi
                                </label>
                                <select
                                    v-model="form.default_source_id"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                >
                                    <option :value="null">Tanlanmagan</option>
                                    <template v-for="(sources, category) in leadSources" :key="category">
                                        <optgroup :label="category">
                                            <option v-for="source in sources" :key="source.id" :value="source.id">
                                                {{ source.name }}
                                            </option>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Boshlang'ich status
                                </label>
                                <select
                                    v-model="form.default_status"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                >
                                    <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                Lead ball: <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ form.default_score }}</span>
                            </label>
                            <input
                                v-model="form.default_score"
                                type="range"
                                min="0"
                                max="100"
                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                            />
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <span>❄️ Sovuq (0)</span>
                                <span>🔥 Issiq (100)</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            Yakunlash Sozlamalari
                        </h3>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Rahmat xabari
                                </label>
                                <textarea
                                    v-model="form.success_message"
                                    rows="2"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                                ></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Yuborish tugmasi matni
                                    </label>
                                    <input
                                        v-model="form.submit_button_text"
                                        type="text"
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Redirect URL (ixtiyoriy)
                                    </label>
                                    <input
                                        v-model="form.redirect_url"
                                        type="url"
                                        placeholder="https://..."
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                    />
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                <input
                                    v-model="form.track_utm"
                                    type="checkbox"
                                    id="track_utm"
                                    class="w-5 h-5 text-emerald-500 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-emerald-500"
                                />
                                <label for="track_utm" class="text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">UTM parametrlarini kuzatish</span>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs mt-0.5">Lead qaysi reklama kampaniyasidan kelganini kuzating</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center justify-center gap-4 mt-8 pb-4">
                    <button
                        v-if="activeStep > 1"
                        @click="activeStep--"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Orqaga
                    </button>

                    <button
                        v-if="activeStep < 3"
                        @click="activeStep++"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-emerald-500 hover:bg-emerald-600 text-white font-medium rounded-xl transition-all shadow-lg shadow-emerald-500/25"
                    >
                        Keyingi
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button
                        v-else
                        @click="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white font-medium rounded-xl transition-all shadow-lg shadow-emerald-500/25 disabled:shadow-none"
                    >
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Field Modal -->
        <Teleport to="body">
            <div v-if="showFieldModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showFieldModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Modal Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </span>
                                    {{ editingFieldIndex !== null ? 'Maydonni tahrirlash' : 'Yangi maydon qo\'shish' }}
                                </h3>
                                <button
                                    @click="showFieldModal = false"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Maydon turi
                                </label>
                                <select
                                    v-model="newField.type"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                >
                                    <option v-for="(config, type) in fieldTypes" :key="type" :value="type">
                                        {{ config.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Label <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="newField.label"
                                    type="text"
                                    placeholder="Masalan: Ismingiz"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Placeholder
                                </label>
                                <input
                                    v-model="newField.placeholder"
                                    type="text"
                                    placeholder="Masalan: Ismingizni kiriting"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Lead maydoniga ulash
                                </label>
                                <select
                                    v-model="newField.map_to"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                >
                                    <option value="">Tanlanmagan</option>
                                    <option v-for="opt in mapToOptions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900 rounded-xl">
                                <input
                                    v-model="newField.required"
                                    type="checkbox"
                                    id="field_required"
                                    class="w-5 h-5 text-emerald-500 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-emerald-500"
                                />
                                <label for="field_required" class="text-sm text-gray-700 dark:text-gray-300 font-medium">
                                    Majburiy maydon
                                </label>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex gap-3">
                            <button
                                @click="showFieldModal = false"
                                class="flex-1 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="saveField"
                                :disabled="!newField.label"
                                class="flex-1 px-4 py-3 bg-emerald-500 hover:bg-emerald-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white font-medium rounded-xl transition-colors shadow-sm"
                            >
                                {{ editingFieldIndex !== null ? 'Saqlash' : 'Qo\'shish' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </BusinessLayout>
</template>
