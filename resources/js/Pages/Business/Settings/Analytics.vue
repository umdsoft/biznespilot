<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
    analyticsSettings: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    ga4_measurement_id: props.analyticsSettings.ga4_measurement_id || '',
    ga4_enabled: props.analyticsSettings.ga4_enabled || false,
    yandex_metrika_id: props.analyticsSettings.yandex_metrika_id || '',
    yandex_metrika_enabled: props.analyticsSettings.yandex_metrika_enabled || false,
    facebook_pixel_id: props.analyticsSettings.facebook_pixel_id || '',
    facebook_pixel_enabled: props.analyticsSettings.facebook_pixel_enabled || false,
});

const isSaving = ref(false);

const saveSettings = () => {
    isSaving.value = true;
    form.post(route('business.settings.analytics.update'), {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        },
    });
};
</script>

<template>
    <Head title="Analytics Sozlamalari" />

    <BusinessLayout title="Analytics Sozlamalari">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('business.settings.index')" class="inline-flex items-center text-sm text-slate-400 hover:text-white mb-4 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Sozlamalarga qaytish
                </Link>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/25">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Analytics & Tracking</h1>
                        <p class="text-slate-400">Google Analytics, Yandex Metrika va Facebook Pixel sozlamalari</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="saveSettings" class="space-y-6">
                <!-- Google Analytics 4 -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-orange-500/10 to-yellow-500/10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.87 15.07l-2.54-2.51.03-.03A11.83 11.83 0 0014.5 4H17V2h-7v2h4.62a9.86 9.86 0 01-3.5 5.64A9.91 9.91 0 018.77 6H6.5a11.83 11.83 0 003.5 5.44l-4.5 4.5 1.42 1.42 4.5-4.5L14.03 15l.84-.93zM15.5 17h-3l3.5 5h3l-3.5-5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-white">Google Analytics 4</h2>
                                    <p class="text-sm text-slate-400">Sayt tashriflarini kuzatish</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.ga4_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-orange-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Measurement ID
                            </label>
                            <input
                                v-model="form.ga4_measurement_id"
                                type="text"
                                placeholder="G-XXXXXXXXXX"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-orange-500 focus:ring-1 focus:ring-orange-500"
                                :class="{ 'border-red-500': form.errors.ga4_measurement_id }"
                            />
                            <p v-if="form.errors.ga4_measurement_id" class="mt-1 text-sm text-red-400">{{ form.errors.ga4_measurement_id }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                Google Analytics 4 dan Measurement ID ni oling. Format: G-XXXXXXXXXX
                            </p>
                        </div>
                        <div class="p-4 bg-slate-700/30 rounded-xl">
                            <h4 class="text-sm font-medium text-slate-300 mb-2">Qanday olish mumkin?</h4>
                            <ol class="text-xs text-slate-400 space-y-1 list-decimal list-inside">
                                <li><a href="https://analytics.google.com" target="_blank" class="text-orange-400 hover:underline">analytics.google.com</a> ga kiring</li>
                                <li>Admin > Data Streams > Web stream tanlang</li>
                                <li>Measurement ID (G-XXXXXXXXXX) ni nusxalang</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Yandex Metrika -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-[#FC3F1D]/10 to-red-500/10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-[#FC3F1D] rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                                        <path d="M14.5 5H12.2C9.6 5 8 6.8 8 9.1C8 10.9 8.9 12.2 10.5 13L8 19H10.5L12.8 13.4V19H15V5H14.5ZM12.8 11.5L11.5 10.8C10.6 10.4 10.2 9.8 10.2 9C10.2 7.9 10.9 7.2 12.1 7.2H12.8V11.5Z" fill="white"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-white">Yandex Metrika</h2>
                                    <p class="text-sm text-slate-400">Webvisor, issiqlik xaritasi va boshqalar</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.yandex_metrika_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#FC3F1D]/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#FC3F1D]"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Counter ID (Schyotchik raqami)
                            </label>
                            <input
                                v-model="form.yandex_metrika_id"
                                type="text"
                                placeholder="12345678"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-[#FC3F1D] focus:ring-1 focus:ring-[#FC3F1D]"
                                :class="{ 'border-red-500': form.errors.yandex_metrika_id }"
                            />
                            <p v-if="form.errors.yandex_metrika_id" class="mt-1 text-sm text-red-400">{{ form.errors.yandex_metrika_id }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                Yandex Metrika counter ID. Faqat raqamlar.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-700/30 rounded-xl">
                            <h4 class="text-sm font-medium text-slate-300 mb-2">Qanday olish mumkin?</h4>
                            <ol class="text-xs text-slate-400 space-y-1 list-decimal list-inside">
                                <li><a href="https://metrika.yandex.ru" target="_blank" class="text-[#FC3F1D] hover:underline">metrika.yandex.ru</a> ga kiring</li>
                                <li>Schyotchik qo'shing yoki mavjudini tanlang</li>
                                <li>Counter ID raqamini nusxalang</li>
                            </ol>
                        </div>
                        <div class="mt-4 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm text-emerald-400 font-medium">Webvisor yoqilgan</p>
                                    <p class="text-xs text-slate-400 mt-1">Foydalanuvchilar harakatlarini video yozib olish avtomatik yoqiladi</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facebook Pixel -->
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700 rounded-2xl overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-blue-500/10 to-indigo-500/10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-white">Facebook Pixel</h2>
                                    <p class="text-sm text-slate-400">Facebook/Meta reklama konversiyalari</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.facebook_pixel_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Pixel ID
                            </label>
                            <input
                                v-model="form.facebook_pixel_id"
                                type="text"
                                placeholder="123456789012345"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 rounded-xl text-white placeholder-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                :class="{ 'border-red-500': form.errors.facebook_pixel_id }"
                            />
                            <p v-if="form.errors.facebook_pixel_id" class="mt-1 text-sm text-red-400">{{ form.errors.facebook_pixel_id }}</p>
                            <p class="mt-2 text-xs text-slate-500">
                                Facebook Pixel ID. 15-16 ta raqam.
                            </p>
                        </div>
                        <div class="p-4 bg-slate-700/30 rounded-xl">
                            <h4 class="text-sm font-medium text-slate-300 mb-2">Qanday olish mumkin?</h4>
                            <ol class="text-xs text-slate-400 space-y-1 list-decimal list-inside">
                                <li><a href="https://business.facebook.com/events_manager" target="_blank" class="text-blue-400 hover:underline">Facebook Events Manager</a> ga kiring</li>
                                <li>Data Sources > Pixels bo'limini tanlang</li>
                                <li>Pixel ID ni nusxalang</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="isSaving || form.processing"
                        class="px-8 py-3.5 bg-gradient-to-r from-orange-500 to-yellow-500 text-white font-semibold rounded-xl hover:from-orange-600 hover:to-yellow-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-orange-500/25"
                    >
                        <span v-if="isSaving || form.processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saqlanmoqda...
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Saqlash
                        </span>
                    </button>
                </div>
            </form>

            <!-- Info Section -->
            <div class="mt-8 p-6 bg-slate-800/30 border border-slate-700 rounded-2xl">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-2">Tracking kodlari qayerda ishlaydi?</h3>
                        <p class="text-sm text-slate-400 mb-4">
                            Yuqoridagi sozlamalarni saqlaganingizdan so'ng, tracking kodlari avtomatik ravishda quyidagi sahifalarga qo'shiladi:
                        </p>
                        <ul class="text-sm text-slate-400 space-y-2">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                Lead Formalar (f/* sahifalari)
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                CustDev So'rovnomalari (s/* sahifalari)
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full"></span>
                                Landing sahifalar
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
