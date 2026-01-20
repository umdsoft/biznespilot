<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const code = ref('')
const useRecovery = ref(false)

const form = useForm({
    code: '',
    recovery: false,
})

const submit = () => {
    form.code = code.value
    form.recovery = useRecovery.value

    form.post(route('two-factor.verify'), {
        onSuccess: () => {
            code.value = ''
        },
        onError: () => {
            code.value = ''
        },
    })
}

const toggleRecovery = () => {
    useRecovery.value = !useRecovery.value
    code.value = ''
    form.clearErrors()
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-indigo-100">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                    {{ t('auth.two_factor.title') }}
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    <span v-if="!useRecovery">
                        {{ t('auth.two_factor.enter_code') }}
                    </span>
                    <span v-else>
                        {{ t('auth.two_factor.enter_recovery') }}
                    </span>
                </p>
            </div>

            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="rounded-md shadow-sm">
                    <div>
                        <label for="code" class="sr-only">
                            {{ useRecovery ? t('auth.two_factor.recovery_code') : t('auth.two_factor.auth_code') }}
                        </label>
                        <input
                            id="code"
                            v-model="code"
                            type="text"
                            :maxlength="useRecovery ? 17 : 6"
                            :placeholder="useRecovery ? 'xxxx-xxxx-xxxx-xxxx' : '000000'"
                            class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                            :class="{ 'border-red-500': form.errors.code }"
                            required
                            autofocus
                        />
                    </div>
                </div>

                <div v-if="form.errors.code" class="text-red-600 text-sm text-center">
                    {{ form.errors.code }}
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!form.processing">{{ t('auth.two_factor.verify') }}</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ t('auth.two_factor.verifying') }}
                        </span>
                    </button>
                </div>

                <div class="text-center">
                    <button
                        type="button"
                        @click="toggleRecovery"
                        class="text-sm text-indigo-600 hover:text-indigo-500"
                    >
                        <span v-if="!useRecovery">{{ t('auth.two_factor.use_recovery') }}</span>
                        <span v-else>{{ t('auth.two_factor.use_auth_code') }}</span>
                    </button>
                </div>

                <div class="text-center">
                    <a
                        :href="route('login')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        {{ t('auth.two_factor.back_to_login') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>
