<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, nextTick, onMounted } from 'vue';

const props = defineProps({
    conversation: Object,
});

const messagesContainer = ref(null);

const channelIcons = {
    telegram: '📱 Telegram',
    instagram: '📷 Instagram',
    facebook: '👥 Facebook',
};

const stageLabels = {
    AWARENESS: 'Xabardorlik',
    INTEREST: 'Qiziqish',
    CONSIDERATION: 'Mulohaza',
    INTENT: 'Niyat',
    PURCHASE: 'Xarid',
    POST_PURCHASE: 'Xariddan keyin',
};

const formatTime = (date) => {
    return new Date(date).toLocaleString('uz-UZ', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

onMounted(() => {
    scrollToBottom();
});

const closeConversation = () => {
    if (confirm('Suhbatni yopmoqchimisiz?')) {
        router.post(route('customer-bot.conversation.close', props.conversation.id), {}, {
            preserveScroll: true,
        });
    }
};

const reopenConversation = () => {
    router.post(route('customer-bot.conversation.reopen', props.conversation.id), {}, {
        preserveScroll: true,
    });
};

const handoffToHuman = () => {
    if (confirm('Suhbatni operatorga topshirmoqchimisiz?')) {
        router.post(route('customer-bot.conversation.handoff', props.conversation.id), {}, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head :title="`Suhbat - ${conversation.customer_name || 'Anonim'}`" />

    <BusinessLayout>
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('customer-bot.conversations')"
                            class="p-2 hover:bg-gray-100 rounded-md"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ conversation.customer_name || 'Anonim' }}
                            </h1>
                            <p class="text-sm text-gray-600">
                                {{ channelIcons[conversation.channel] || '💬' }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <button
                            v-if="!conversation.handed_off"
                            @click="handoffToHuman"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 text-sm"
                        >
                            Operatorga topshirish
                        </button>

                        <button
                            v-if="conversation.status === 'active'"
                            @click="closeConversation"
                            class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm"
                        >
                            Suhbatni yopish
                        </button>

                        <button
                            v-else-if="conversation.status === 'closed'"
                            @click="reopenConversation"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm"
                        >
                            Qayta ochish
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Messages Column (2/3) -->
                    <div class="col-span-2">
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden flex flex-col" style="height: calc(100vh - 250px);">
                            <!-- Messages -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
                                <div
                                    v-for="message in conversation.messages"
                                    :key="message.id"
                                    :class="[
                                        'flex',
                                        message.role === 'bot' ? 'justify-start' : 'justify-end'
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'max-w-md px-4 py-3 rounded-lg',
                                            message.role === 'bot'
                                                ? 'bg-gray-100 text-gray-900'
                                                : 'bg-blue-600 text-white'
                                        ]"
                                    >
                                        <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>

                                        <div
                                            :class="[
                                                'mt-1 text-xs flex items-center gap-2',
                                                message.role === 'bot' ? 'text-gray-500' : 'text-blue-100'
                                            ]"
                                        >
                                            <span>{{ formatTime(message.created_at) }}</span>
                                            <span v-if="message.detected_intent" class="px-2 py-0.5 bg-white/20 rounded">
                                                {{ message.detected_intent }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="conversation.messages.length === 0" class="flex items-center justify-center h-full text-gray-500">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                        <p class="mt-2">Hech qanday xabar yo'q</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Sidebar (1/3) -->
                    <div class="col-span-1 space-y-6">
                        <!-- Customer Info -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Mijoz ma'lumotlari</h3>

                            <dl class="space-y-3">
                                <div v-if="conversation.customer_name">
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Ism</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ conversation.customer_name }}</dd>
                                </div>

                                <div v-if="conversation.customer_email">
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ conversation.customer_email }}</dd>
                                </div>

                                <div v-if="conversation.customer_phone">
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Telefon</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ conversation.customer_phone }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Kanal</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ channelIcons[conversation.channel] || conversation.channel }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Channel User ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ conversation.channel_user_id }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Conversation Status -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Holat</h3>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Status</dt>
                                    <dd class="mt-1">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            conversation.status === 'active' ? 'bg-green-100 text-green-800' :
                                            conversation.status === 'closed' ? 'bg-gray-100 text-gray-800' :
                                            'bg-yellow-100 text-yellow-800'
                                        ]">
                                            {{ conversation.status === 'active' ? 'Faol' : conversation.status === 'closed' ? 'Yopilgan' : 'Arxiv' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Bosqich</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ stageLabels[conversation.current_stage] || conversation.current_stage }}</dd>
                                </div>

                                <div v-if="conversation.handed_off">
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Operatorga topshirilgan</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatTime(conversation.handed_off_at) }}</dd>
                                </div>

                                <div v-if="conversation.closed_at">
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Yopilgan vaqti</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatTime(conversation.closed_at) }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Boshlangan</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatTime(conversation.created_at) }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Oxirgi faollik</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatTime(conversation.updated_at) }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Lead Info -->
                        <div v-if="conversation.lead" class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Lid</h3>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Lid ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ conversation.lead.id }}</dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase">Holat</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ conversation.lead.status }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <Link
                                        :href="route('leads.show', conversation.lead.id)"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Lidni ko'rish
                                    </Link>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
