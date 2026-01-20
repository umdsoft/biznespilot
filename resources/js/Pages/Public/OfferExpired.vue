<script setup>
import { Head } from '@inertiajs/vue3';
import { ClockIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    offer: Object,
    business: Object,
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-100 via-white to-gray-100 flex items-center justify-center p-4">
        <Head :title="t('public_offer.expired_title')" />

        <div class="max-w-md w-full text-center">
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <ClockIcon class="w-10 h-10 text-orange-600" />
                </div>

                <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ t('public_offer.expired_title') }}</h1>

                <p class="text-gray-600 mb-6">
                    {{ t('public_offer.expired_message', { name: offer?.name }) }}
                </p>

                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl mb-6">
                    <div class="flex items-center gap-2 text-yellow-700">
                        <ExclamationTriangleIcon class="w-5 h-5" />
                        <span class="font-medium">{{ t('public_offer.contact_for_new') }}</span>
                    </div>
                </div>

                <div class="text-gray-500">
                    <p class="font-medium">{{ business?.name }}</p>
                    <p v-if="business?.phone">{{ business.phone }}</p>
                    <p v-if="business?.email">{{ business.email }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
