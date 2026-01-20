<script setup>
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { useI18n } from '@/i18n';
import {
    UserCircleIcon,
    EnvelopeIcon,
    PhoneIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const page = usePage();
const user = page.props.auth?.user;

const form = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    form.put('/sales-head/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Success handling
        },
    });
};

const updatePassword = () => {
    passwordForm.put('/sales-head/profile', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <SalesHeadLayout :title="t('nav.profile')">
        <Head :title="t('nav.profile')" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ t('nav.profile') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('profile.manage_info') }}</p>
            </div>

            <!-- Profile Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center gap-6 mb-8">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-2xl font-bold">
                        {{ getInitials(user?.name) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ user?.name }}</h2>
                        <p class="text-gray-500 dark:text-gray-400">{{ t('profile.sales_head') }}</p>
                    </div>
                </div>

                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <UserCircleIcon class="w-4 h-4 inline mr-1" />
                                {{ t('profile.name') }}
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <EnvelopeIcon class="w-4 h-4 inline mr-1" />
                                Email
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <PhoneIcon class="w-4 h-4 inline mr-1" />
                                {{ t('profile.phone') }}
                            </label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                            <p v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</p>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ form.processing ? t('common.saving') : t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">
                    <KeyIcon class="w-5 h-5 inline mr-2" />
                    {{ t('profile.change_password') }}
                </h3>

                <form @submit.prevent="updatePassword" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('profile.current_password') }}
                            </label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                            <p v-if="passwordForm.errors.current_password" class="text-red-500 text-sm mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('profile.new_password') }}
                            </label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                            <p v-if="passwordForm.errors.password" class="text-red-500 text-sm mt-1">{{ passwordForm.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('profile.confirm_password') }}
                            </label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="w-full px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? t('common.saving') : t('profile.update_password') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SalesHeadLayout>
</template>
