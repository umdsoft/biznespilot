<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    twoFactorData: Object,
})

const showDisableModal = ref(false)
const showRegenerateModal = ref(false)

const disableForm = useForm({
    password: '',
})

const regenerateForm = useForm({
    password: '',
})

const startSetup = () => {
    router.visit(route('business.settings.two-factor.setup'))
}

const disable2FA = () => {
    disableForm.post(route('business.settings.two-factor.disable'), {
        onSuccess: () => {
            showDisableModal.value = false
            disableForm.reset()
        },
    })
}

const viewRecoveryCodes = () => {
    router.visit(route('business.settings.two-factor.recovery-codes'))
}

const regenerateRecoveryCodes = () => {
    regenerateForm.post(route('business.settings.two-factor.recovery-codes.regenerate'), {
        onSuccess: () => {
            showRegenerateModal.value = false
            regenerateForm.reset()
        },
    })
}
</script>

<template>
    <BusinessLayout :title="t('two_factor.title')">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    {{ t('two_factor.header') }}
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ t('two_factor.subtitle') }}
                                </p>
                            </div>
                        </div>

                        <!-- 2FA Not Enabled -->
                        <div v-if="!twoFactorData || !twoFactorData.enabled" class="space-y-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">
                                            {{ t('two_factor.not_enabled') }}
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>
                                                {{ t('two_factor.not_enabled_desc') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ t('two_factor.how_it_works') }}</h3>
                                <div class="space-y-3">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold">
                                                1
                                            </div>
                                        </div>
                                        <p class="ml-3 text-sm text-gray-600">
                                            {{ t('two_factor.step1') }}
                                        </p>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold">
                                                2
                                            </div>
                                        </div>
                                        <p class="ml-3 text-sm text-gray-600">
                                            {{ t('two_factor.step2') }}
                                        </p>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold">
                                                3
                                            </div>
                                        </div>
                                        <p class="ml-3 text-sm text-gray-600">
                                            {{ t('two_factor.step3') }}
                                        </p>
                                    </div>
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="flex items-center justify-center h-8 w-8 rounded-full bg-indigo-100 text-indigo-600 font-semibold">
                                                4
                                            </div>
                                        </div>
                                        <p class="ml-3 text-sm text-gray-600">
                                            {{ t('two_factor.step4') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4">
                                <button
                                    @click="startSetup"
                                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    {{ t('two_factor.enable') }}
                                </button>
                            </div>
                        </div>

                        <!-- 2FA Enabled -->
                        <div v-else class="space-y-6">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h3 class="text-sm font-medium text-green-800">
                                            {{ t('two_factor.enabled') }}
                                        </h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p>
                                                {{ t('two_factor.enabled_desc') }}
                                                {{ t('two_factor.enabled_date') }}: {{ new Date(twoFactorData.enabled_at).toLocaleDateString('uz-UZ') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white border border-gray-200 rounded-lg divide-y divide-gray-200">
                                <div class="p-4 sm:p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-base font-medium text-gray-900">{{ t('two_factor.recovery_codes') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ twoFactorData.recovery_codes_count }} {{ t('two_factor.codes_available') }}
                                            </p>
                                        </div>
                                        <button
                                            @click="viewRecoveryCodes"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            {{ t('common.view') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="p-4 sm:p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-base font-medium text-gray-900">{{ t('two_factor.new_recovery_codes') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ t('two_factor.regenerate_desc') }}
                                            </p>
                                        </div>
                                        <button
                                            @click="showRegenerateModal = true"
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            {{ t('two_factor.regenerate') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="p-4 sm:p-6">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-base font-medium text-gray-900">{{ t('two_factor.disable') }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">
                                                {{ t('two_factor.disable_desc') }}
                                            </p>
                                        </div>
                                        <button
                                            @click="showDisableModal = true"
                                            class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        >
                                            {{ t('common.delete') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable 2FA Modal -->
        <div v-if="showDisableModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDisableModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                2FA ni o'chirish
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Hisobingizdan ikki faktorli autentifikatsiyani olib tashlashni xohlaysizmi? Bu hisobingiz xavfsizligini kamaytiradi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="disable2FA" class="mt-5">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Parolni tasdiqlang
                            </label>
                            <input
                                id="password"
                                v-model="disableForm.password"
                                type="password"
                                required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                :class="{ 'border-red-500': disableForm.errors.password }"
                            />
                            <p v-if="disableForm.errors.password" class="mt-1 text-sm text-red-600">
                                {{ disableForm.errors.password }}
                            </p>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button
                                type="submit"
                                :disabled="disableForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                            >
                                O'chirish
                            </button>
                            <button
                                type="button"
                                @click="showDisableModal = false; disableForm.reset()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                            >
                                Bekor qilish
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Regenerate Recovery Codes Modal -->
        <div v-if="showRegenerateModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showRegenerateModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Recovery codelarni yangilash
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Eski recovery codelar bekor qilinadi va ular bilan login qilish imkonsiz bo'ladi. Yangi codelarni xavfsiz joyda saqlang.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="regenerateRecoveryCodes" class="mt-5">
                        <div>
                            <label for="regen-password" class="block text-sm font-medium text-gray-700">
                                Parolni tasdiqlang
                            </label>
                            <input
                                id="regen-password"
                                v-model="regenerateForm.password"
                                type="password"
                                required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                :class="{ 'border-red-500': regenerateForm.errors.password }"
                            />
                            <p v-if="regenerateForm.errors.password" class="mt-1 text-sm text-red-600">
                                {{ regenerateForm.errors.password }}
                            </p>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button
                                type="submit"
                                :disabled="regenerateForm.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                            >
                                Yangilash
                            </button>
                            <button
                                type="button"
                                @click="showRegenerateModal = false; regenerateForm.reset()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                            >
                                Bekor qilish
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
