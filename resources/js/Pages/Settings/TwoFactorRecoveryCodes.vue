<script setup>
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    recoveryCodes: Array,
    message: String,
})

const copied = ref(false)

const copyAllCodes = () => {
    const codesText = props.recoveryCodes.join('\n')
    navigator.clipboard.writeText(codesText)
    copied.value = true
    setTimeout(() => {
        copied.value = false
    }, 2000)
}

const downloadCodes = () => {
    const codesText = `BiznesPilot - 2FA Recovery Codes\n\nSaqlangan sana: ${new Date().toLocaleString('uz-UZ')}\n\n${props.recoveryCodes.join('\n')}\n\nDIQQAT: Bu codelarni xavfsiz joyda saqlang. Har bir code faqat bir marta ishlatilishi mumkin.`

    const blob = new Blob([codesText], { type: 'text/plain' })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `biznespilot-recovery-codes-${Date.now()}.txt`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
}

const printCodes = () => {
    window.print()
}
</script>

<template>
    <BusinessLayout title="Recovery Codes">
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                Recovery Codes
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Bu codelarni xavfsiz joyda saqlang
                            </p>
                        </div>

                        <!-- Success Message -->
                        <div v-if="message" class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ message }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Warning -->
                        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Muhim eslatma
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li>Har bir recovery code faqat bir marta ishlatilishi mumkin</li>
                                            <li>Authenticator ilovasiga kirish imkoniyatingiz bo'lmasa, bu codelardan foydalaning</li>
                                            <li>Bu codelarni xavfsiz joyda saqlang (password manager, shifrlangan fayl)</li>
                                            <li>Boshqa odamlar bilan bo'lishmang</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recovery Codes -->
                        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div
                                    v-for="(code, index) in recoveryCodes"
                                    :key="index"
                                    class="bg-white px-4 py-3 rounded-md border border-gray-200 font-mono text-sm text-gray-900 text-center"
                                >
                                    {{ code }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                @click="copyAllCodes"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span v-if="!copied">Nusxa olish</span>
                                <span v-else class="text-green-600">Nusxa olindi!</span>
                            </button>

                            <button
                                @click="downloadCodes"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Yuklab olish
                            </button>

                            <button
                                @click="printCodes"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Chop etish
                            </button>
                        </div>

                        <!-- Back Button -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <a
                                :href="route('business.settings.two-factor')"
                                class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500"
                            >
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                2FA sozlamalariga qaytish
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Styles -->
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }

                body {
                    background: white;
                }

                .bg-gray-50 {
                    background: white !important;
                }
            }
        </style>
    </BusinessLayout>
</template>
