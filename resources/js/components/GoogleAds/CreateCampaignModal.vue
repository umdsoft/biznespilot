<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    businessId: String,
});

const emit = defineEmits(['close', 'created']);

const loading = ref(false);
const errors = ref({});
const form = ref({
    name: '',
    channel_type: 'SEARCH',
    daily_budget: '',
    start_date: '',
    end_date: '',
    geo_targets: [],
    language_targets: [],
});

const channelTypes = [
    { value: 'SEARCH', label: 'Qidiruv (Search)', description: 'Google qidiruv natijalarida reklama' },
    { value: 'DISPLAY', label: 'Display', description: 'Veb-saytlar va ilovalar tarmog\'ida' },
    { value: 'VIDEO', label: 'Video (YouTube)', description: 'YouTube va video platformalarida' },
    { value: 'SHOPPING', label: 'Shopping', description: 'Mahsulot reklama (e-commerce)' },
    { value: 'PERFORMANCE_MAX', label: 'Performance Max', description: 'Avtomatik barcha kanallarda' },
];

const isValid = computed(() => {
    return form.value.name.trim() !== '' && form.value.daily_budget !== '';
});

const submit = async () => {
    if (!isValid.value) return;

    loading.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/business/api/google-ads-campaigns', {
            name: form.value.name,
            channel_type: form.value.channel_type,
            daily_budget: parseFloat(form.value.daily_budget),
            start_date: form.value.start_date || null,
            end_date: form.value.end_date || null,
            geo_targets: form.value.geo_targets.length > 0 ? form.value.geo_targets : null,
            language_targets: form.value.language_targets.length > 0 ? form.value.language_targets : null,
        });

        if (response.data.success) {
            emit('created', response.data.campaign);
        } else {
            errors.value = response.data.errors || { general: 'Xatolik yuz berdi' };
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            errors.value = { general: error.message || 'Xatolik yuz berdi' };
        }
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Yangi kampaniya yaratish</h2>
                    <button
                        @click="$emit('close')"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Error Message -->
                    <div v-if="errors.general" class="p-4 bg-red-50 dark:bg-red-500/20 border border-red-200 dark:border-red-500/30 rounded-xl">
                        <p class="text-sm text-red-600 dark:text-red-400">{{ errors.general }}</p>
                    </div>

                    <!-- Campaign Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kampaniya nomi *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Masalan: Yangi yil aksiyasi"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white"
                            :class="{ 'border-red-500': errors.name }"
                        >
                        <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name[0] }}</p>
                    </div>

                    <!-- Channel Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kampaniya turi *
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <button
                                v-for="type in channelTypes"
                                :key="type.value"
                                type="button"
                                @click="form.channel_type = type.value"
                                class="p-4 text-left border-2 rounded-xl transition-all"
                                :class="form.channel_type === type.value
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-500/20'
                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'"
                            >
                                <p class="font-medium text-gray-900 dark:text-white">{{ type.label }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ type.description }}</p>
                            </button>
                        </div>
                    </div>

                    <!-- Daily Budget -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Kunlik byudjet (so'm) *
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.daily_budget"
                                type="number"
                                min="1000"
                                step="1000"
                                placeholder="50000"
                                class="w-full pl-4 pr-16 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white"
                                :class="{ 'border-red-500': errors.daily_budget }"
                            >
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">so'm</span>
                        </div>
                        <p v-if="errors.daily_budget" class="mt-1 text-sm text-red-500">{{ errors.daily_budget[0] }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Minimal: 1,000 so'm. Tavsiya: 50,000+ so'm
                        </p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Boshlanish sanasi
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tugash sanasi
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white"
                            >
                        </div>
                    </div>

                    <!-- Info Note -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/30 rounded-xl">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="text-sm text-blue-800 dark:text-blue-300 font-medium">Ma'lumot</p>
                                <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">
                                    Kampaniya "Pauza" holatida yaratiladi. Faollashtirish uchun keyinroq yoqishingiz mumkin.
                                </p>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-b-2xl">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                    >
                        Bekor qilish
                    </button>
                    <button
                        @click="submit"
                        :disabled="!isValid || loading"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <svg v-if="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ loading ? 'Yaratilmoqda...' : 'Yaratish' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
