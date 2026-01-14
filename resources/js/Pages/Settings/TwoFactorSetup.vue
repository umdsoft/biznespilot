<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    secret: String,
    qrCodeUrl: String,
})

const code = ref('')
const showSecret = ref(false)

const form = useForm({
    code: '',
})

const submit = () => {
    form.code = code.value

    form.post(route('business.settings.two-factor.enable'), {
        onError: () => {
            code.value = ''
        },
    })
}

const copySecret = () => {
    navigator.clipboard.writeText(props.secret)
}
</script>

<template>
    <BusinessLayout title="2FA Setup">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                2FA ni sozlash
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Quyidagi qadamlarni bajaring
                            </p>
                        </div>

                        <div class="space-y-8">
                            <!-- Step 1: Install App -->
                            <div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-lg">
                                            1
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            Authenticator ilovasini o'rnating
                                        </h3>
                                        <p class="mt-2 text-sm text-gray-600">
                                            Google Authenticator, Authy yoki boshqa TOTP authenticator ilovasini telefoningizga o'rnating.
                                        </p>
                                        <div class="mt-3 flex space-x-4">
                                            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500">
                                                <svg class="mr-1 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 1 3,20.09 3,20.5Z M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z"/>
                                                </svg>
                                                Google Play
                                            </a>
                                            <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500">
                                                <svg class="mr-1 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M18.71,19.5C17.88,20.74 17,21.95 15.66,21.97C14.32,22 13.89,21.18 12.37,21.18C10.84,21.18 10.37,21.95 9.1,22C7.79,22.05 6.8,20.68 5.96,19.47C4.25,17 2.94,12.45 4.7,9.39C5.57,7.87 7.13,6.91 8.82,6.88C10.1,6.86 11.32,7.75 12.11,7.75C12.89,7.75 14.37,6.68 15.92,6.84C16.57,6.87 18.39,7.1 19.56,8.82C19.47,8.88 17.39,10.1 17.41,12.63C17.44,15.65 20.06,16.66 20.09,16.67C20.06,16.74 19.67,18.11 18.71,19.5M13,3.5C13.73,2.67 14.94,2.04 15.94,2C16.07,3.17 15.6,4.35 14.9,5.19C14.21,6.04 13.07,6.7 11.95,6.61C11.8,5.46 12.36,4.26 13,3.5Z"/>
                                                </svg>
                                                App Store
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Scan QR Code -->
                            <div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-lg">
                                            2
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            QR kodni skanerlang
                                        </h3>
                                        <p class="mt-2 text-sm text-gray-600">
                                            Authenticator ilovangizda "+" yoki "Add" tugmasini bosing va QR kodni skanerlang.
                                        </p>

                                        <div class="mt-4 bg-white p-4 rounded-lg border-2 border-gray-200 inline-block">
                                            <img :src="qrCodeUrl" alt="QR Code" class="w-64 h-64" />
                                        </div>

                                        <div class="mt-4">
                                            <button
                                                @click="showSecret = !showSecret"
                                                class="text-sm text-indigo-600 hover:text-indigo-500"
                                            >
                                                {{ showSecret ? 'Secret kodni yashirish' : 'QR kod ishlamasa, secret kodni ko\'ring' }}
                                            </button>

                                            <div v-if="showSecret" class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <code class="text-sm font-mono text-gray-900">{{ secret }}</code>
                                                    <button
                                                        @click="copySecret"
                                                        class="ml-2 p-1 text-gray-500 hover:text-gray-700"
                                                        title="Nusxa olish"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Verify Code -->
                            <div>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <div class="flex items-center justify-center h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 font-semibold text-lg">
                                            3
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            Kodni tasdiqlang
                                        </h3>
                                        <p class="mt-2 text-sm text-gray-600">
                                            Authenticator ilovasidan 6 raqamli kodni kiriting.
                                        </p>

                                        <form @submit.prevent="submit" class="mt-4 max-w-xs">
                                            <div>
                                                <label for="code" class="block text-sm font-medium text-gray-700">
                                                    Autentifikatsiya kodi
                                                </label>
                                                <input
                                                    id="code"
                                                    v-model="code"
                                                    type="text"
                                                    maxlength="6"
                                                    placeholder="000000"
                                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 text-center text-2xl tracking-widest focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                                                    :class="{ 'border-red-500': form.errors.code }"
                                                    required
                                                    autofocus
                                                />
                                                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                                                    {{ form.errors.code }}
                                                </p>
                                            </div>

                                            <div class="mt-6 flex space-x-3">
                                                <button
                                                    type="submit"
                                                    :disabled="form.processing || code.length !== 6"
                                                    class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <span v-if="!form.processing">2FA ni yoqish</span>
                                                    <span v-else class="flex items-center">
                                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Tekshirilmoqda...
                                                    </span>
                                                </button>
                                                <a
                                                    :href="route('business.settings.two-factor')"
                                                    class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                >
                                                    Bekor qilish
                                                </a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
