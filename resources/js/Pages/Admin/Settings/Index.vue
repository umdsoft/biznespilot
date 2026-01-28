<template>
    <AdminLayout :title="t('admin.settings.title')">
        <div class="py-6">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Compact Header -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ t('admin.settings.title') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('admin.settings.subtitle') }}
                    </p>
                </div>

                <!-- Settings Sections -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- General Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Umumiy sozlamalar</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- Platform Name -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Platforma nomi
                                </label>
                                <input
                                    v-model="form.platform_name"
                                    type="text"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Support Email -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Yordam emaili
                                </label>
                                <input
                                    v-model="form.support_email"
                                    type="email"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>

                            <!-- Default Language -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Standart til
                                </label>
                                <select
                                    v-model="form.default_language"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option value="uz">O'zbek (Lotin)</option>
                                    <option value="uz-cyrl">O'zbek (Kirill)</option>
                                    <option value="ru">Русский</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center">
                            <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Xavfsizlik sozlamalari</h3>
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- 2FA Requirement -->
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Majburiy 2FA</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Barcha adminlar uchun 2FA talab qilish</p>
                                </div>
                                <button
                                    @click="form.require_2fa = !form.require_2fa"
                                    :class="[
                                        form.require_2fa ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700',
                                        'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            form.require_2fa ? 'translate-x-4' : 'translate-x-0',
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                        ]"
                                    />
                                </button>
                            </div>

                            <!-- Session Timeout -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Sessiya muddati (daqiqa)
                                </label>
                                <input
                                    v-model="form.session_timeout"
                                    type="number"
                                    min="5"
                                    max="1440"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Mode -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center">
                            <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Texnik xizmat rejimi</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between py-2">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Texnik xizmat rejimi</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Platformani vaqtincha o'chirish</p>
                                </div>
                                <button
                                    @click="form.maintenance_mode = !form.maintenance_mode"
                                    :class="[
                                        form.maintenance_mode ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-700',
                                        'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            form.maintenance_mode ? 'translate-x-4' : 'translate-x-0',
                                            'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                        ]"
                                    />
                                </button>
                            </div>

                            <div v-if="form.maintenance_mode" class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                                    Texnik xizmat xabari
                                </label>
                                <textarea
                                    v-model="form.maintenance_message"
                                    rows="2"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Foydalanuvchilarga ko'rsatiladigan xabar..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Management -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Kesh boshqaruvi</h3>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Tizim keshini tozalash</h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Barcha keshlangan ma'lumotlarni tozalash</p>
                                </div>
                                <button
                                    @click="clearCache"
                                    :disabled="clearingCache"
                                    class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium disabled:opacity-50"
                                >
                                    <svg v-if="clearingCache" class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Tozalash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex justify-end">
                    <button
                        @click="saveSettings"
                        :disabled="saving"
                        class="inline-flex items-center px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors text-sm font-medium disabled:opacity-50"
                    >
                        <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Saqlash
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import axios from 'axios'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({})
    }
})

const form = ref({
    platform_name: props.settings.platform_name || 'BiznesPilot',
    support_email: props.settings.support_email || 'support@biznespilot.uz',
    default_language: props.settings.default_language || 'uz',
    require_2fa: props.settings.require_2fa || false,
    session_timeout: props.settings.session_timeout || 120,
    maintenance_mode: props.settings.maintenance_mode || false,
    maintenance_message: props.settings.maintenance_message || '',
})

const saving = ref(false)
const clearingCache = ref(false)

const saveSettings = async () => {
    saving.value = true
    try {
        await axios.put('/dashboard/settings', form.value)
        alert('Sozlamalar saqlandi!')
    } catch (error) {
        console.error('Error saving settings:', error)
        alert('Xatolik yuz berdi!')
    } finally {
        saving.value = false
    }
}

const clearCache = async () => {
    clearingCache.value = true
    try {
        await axios.post('/dashboard/clear-cache')
        alert('Kesh tozalandi!')
    } catch (error) {
        console.error('Error clearing cache:', error)
        alert('Xatolik yuz berdi!')
    } finally {
        clearingCache.value = false
    }
}
</script>
