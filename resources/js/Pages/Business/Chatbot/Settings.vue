<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    config: Object,
    telegram_webhook_url: String,
    instagram_webhook_url: String,
    facebook_webhook_url: String,
});

const form = useForm({
    bot_name: props.config.bot_name,
    welcome_message: props.config.welcome_message,
    default_response: props.config.default_response,
    business_hours_start: props.config.business_hours_start,
    business_hours_end: props.config.business_hours_end,
    outside_hours_message: props.config.outside_hours_message,
    auto_response_enabled: props.config.auto_response_enabled,
    ai_enabled: props.config.ai_enabled,
    telegram_enabled: props.config.telegram_enabled,
    telegram_bot_token: props.config.telegram_bot_token,
    instagram_enabled: props.config.instagram_enabled,
    instagram_page_id: props.config.instagram_page_id,
    instagram_access_token: props.config.instagram_access_token,
    facebook_enabled: props.config.facebook_enabled,
    facebook_page_id: props.config.facebook_page_id,
    facebook_access_token: props.config.facebook_access_token,
});

const showTokens = ref({
    telegram: false,
    instagram: false,
    facebook: false,
});

const submit = () => {
    form.put(route('customer-bot.settings.update'));
};

const setupTelegramWebhook = () => {
    form.post(route('customer-bot.settings.telegram.webhook'), {
        preserveScroll: true,
    });
};

const copyWebhookUrl = (url) => {
    navigator.clipboard.writeText(url);
    // You can add a toast notification here
};
</script>

<template>
    <Head title="Chatbot Sozlamalari" />

    <BusinessLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Chatbot Sozlamalari</h1>
                    <p class="mt-2 text-gray-600">Mijozlar bilan muloqot qiluvchi bot sozlamalari</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- General Settings -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Umumiy sozlamalar</h3>

                        <div class="space-y-4">
                            <!-- Bot Name -->
                            <div>
                                <label for="bot_name" class="block text-sm font-medium text-gray-700">Bot nomi</label>
                                <input
                                    type="text"
                                    id="bot_name"
                                    v-model="form.bot_name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p v-if="form.errors.bot_name" class="mt-1 text-sm text-red-600">{{ form.errors.bot_name }}</p>
                            </div>

                            <!-- Welcome Message -->
                            <div>
                                <label for="welcome_message" class="block text-sm font-medium text-gray-700">Salomlashish xabari</label>
                                <textarea
                                    id="welcome_message"
                                    v-model="form.welcome_message"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">Mavjud o'zgaruvchilar: {business_name}, {customer_name}</p>
                                <p v-if="form.errors.welcome_message" class="mt-1 text-sm text-red-600">{{ form.errors.welcome_message }}</p>
                            </div>

                            <!-- Default Response -->
                            <div>
                                <label for="default_response" class="block text-sm font-medium text-gray-700">Standart javob</label>
                                <textarea
                                    id="default_response"
                                    v-model="form.default_response"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">Botga tushunarsiz xabar kelganda</p>
                                <p v-if="form.errors.default_response" class="mt-1 text-sm text-red-600">{{ form.errors.default_response }}</p>
                            </div>

                            <!-- Toggles -->
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="auto_response_enabled"
                                        v-model="form.auto_response_enabled"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label for="auto_response_enabled" class="ml-2 block text-sm text-gray-900">
                                        Avtomatik javoblar yoqilgan
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="ai_enabled"
                                        v-model="form.ai_enabled"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <label for="ai_enabled" class="ml-2 block text-sm text-gray-900">
                                        AI javoblar yoqilgan (Claude AI)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ish vaqti</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="business_hours_start" class="block text-sm font-medium text-gray-700">Boshlanishi</label>
                                <input
                                    type="time"
                                    id="business_hours_start"
                                    v-model="form.business_hours_start"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label for="business_hours_end" class="block text-sm font-medium text-gray-700">Tugashi</label>
                                <input
                                    type="time"
                                    id="business_hours_end"
                                    v-model="form.business_hours_end"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="outside_hours_message" class="block text-sm font-medium text-gray-700">Ish vaqtidan tashqari xabar</label>
                            <textarea
                                id="outside_hours_message"
                                v-model="form.outside_hours_message"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Telegram Integration -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Telegram Bot</h3>
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="telegram_enabled"
                                    v-model="form.telegram_enabled"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="telegram_enabled" class="ml-2 block text-sm text-gray-900">
                                    Yoqilgan
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="telegram_bot_token" class="block text-sm font-medium text-gray-700">Bot Token</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        :type="showTokens.telegram ? 'text' : 'password'"
                                        id="telegram_bot_token"
                                        v-model="form.telegram_bot_token"
                                        class="flex-1 rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"
                                    />
                                    <button
                                        type="button"
                                        @click="showTokens.telegram = !showTokens.telegram"
                                        class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                    >
                                        {{ showTokens.telegram ? 'Yashirish' : 'Ko\'rish' }}
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">@BotFather dan olingan token</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        :value="telegram_webhook_url"
                                        readonly
                                        class="flex-1 rounded-md border-gray-300 bg-gray-50 text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="copyWebhookUrl(telegram_webhook_url)"
                                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm"
                                    >
                                        Nusxalash
                                    </button>
                                    <button
                                        type="button"
                                        @click="setupTelegramWebhook"
                                        class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                                    >
                                        O'rnatish
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instagram Integration -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Instagram Direct</h3>
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="instagram_enabled"
                                    v-model="form.instagram_enabled"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="instagram_enabled" class="ml-2 block text-sm text-gray-900">
                                    Yoqilgan
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="instagram_page_id" class="block text-sm font-medium text-gray-700">Page ID</label>
                                <input
                                    type="text"
                                    id="instagram_page_id"
                                    v-model="form.instagram_page_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label for="instagram_access_token" class="block text-sm font-medium text-gray-700">Access Token</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        :type="showTokens.instagram ? 'text' : 'password'"
                                        id="instagram_access_token"
                                        v-model="form.instagram_access_token"
                                        class="flex-1 rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <button
                                        type="button"
                                        @click="showTokens.instagram = !showTokens.instagram"
                                        class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                    >
                                        {{ showTokens.instagram ? 'Yashirish' : 'Ko\'rish' }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        :value="instagram_webhook_url"
                                        readonly
                                        class="flex-1 rounded-md border-gray-300 bg-gray-50 text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="copyWebhookUrl(instagram_webhook_url)"
                                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm"
                                    >
                                        Nusxalash
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Facebook Integration -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Facebook Messenger</h3>
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    id="facebook_enabled"
                                    v-model="form.facebook_enabled"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label for="facebook_enabled" class="ml-2 block text-sm text-gray-900">
                                    Yoqilgan
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label for="facebook_page_id" class="block text-sm font-medium text-gray-700">Page ID</label>
                                <input
                                    type="text"
                                    id="facebook_page_id"
                                    v-model="form.facebook_page_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label for="facebook_access_token" class="block text-sm font-medium text-gray-700">Page Access Token</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input
                                        :type="showTokens.facebook ? 'text' : 'password'"
                                        id="facebook_access_token"
                                        v-model="form.facebook_access_token"
                                        class="flex-1 rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <button
                                        type="button"
                                        @click="showTokens.facebook = !showTokens.facebook"
                                        class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm"
                                    >
                                        {{ showTokens.facebook ? 'Yashirish' : 'Ko\'rish' }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="text"
                                        :value="facebook_webhook_url"
                                        readonly
                                        class="flex-1 rounded-md border-gray-300 bg-gray-50 text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="copyWebhookUrl(facebook_webhook_url)"
                                        class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm"
                                    >
                                        Nusxalash
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saqlanmoqda...</span>
                            <span v-else>Saqlash</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </BusinessLayout>
</template>
