<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    conversations: Object,
    filters: Object,
});

const filters = ref({
    status: props.filters.status || 'all',
    channel: props.filters.channel || 'all',
    stage: props.filters.stage || 'all',
    search: props.filters.search || '',
});

const applyFilters = () => {
    router.get(route('customer-bot.conversations'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(() => filters.value.search, (value) => {
    if (value.length === 0 || value.length >= 3) {
        applyFilters();
    }
});

const statusColors = {
    active: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
    archived: 'bg-yellow-100 text-yellow-800',
};

const channelIcons = {
    telegram: '📱',
    instagram: '📷',
    facebook: '👥',
};

const stageLabels = {
    AWARENESS: 'Xabardorlik',
    INTEREST: 'Qiziqish',
    CONSIDERATION: 'Mulohaza',
    INTENT: 'Niyat',
    PURCHASE: 'Xarid',
    POST_PURCHASE: 'Xariddan keyin',
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('uz-UZ', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Suhbatlar" />

    <BusinessLayout>
        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Suhbatlar</h1>
                        <p class="mt-2 text-gray-600">Mijozlar bilan barcha suhbatlar</p>
                    </div>
                    <Link
                        :href="route('customer-bot.settings')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                    >
                        Sozlamalar
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qidirish</label>
                            <input
                                type="text"
                                v-model="filters.search"
                                placeholder="Ism, email, telefon..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Holat</label>
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="all">Barchasi</option>
                                <option value="active">Faol</option>
                                <option value="closed">Yopilgan</option>
                                <option value="archived">Arxivlangan</option>
                            </select>
                        </div>

                        <!-- Channel Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kanal</label>
                            <select
                                v-model="filters.channel"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="all">Barchasi</option>
                                <option value="telegram">Telegram</option>
                                <option value="instagram">Instagram</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>

                        <!-- Stage Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bosqich</label>
                            <select
                                v-model="filters.stage"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="all">Barchasi</option>
                                <option value="AWARENESS">Xabardorlik</option>
                                <option value="INTEREST">Qiziqish</option>
                                <option value="CONSIDERATION">Mulohaza</option>
                                <option value="INTENT">Niyat</option>
                                <option value="PURCHASE">Xarid</option>
                                <option value="POST_PURCHASE">Xariddan keyin</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <ul class="divide-y divide-gray-200">
                        <li
                            v-for="conversation in conversations.data"
                            :key="conversation.id"
                            class="hover:bg-gray-50 transition-colors"
                        >
                            <Link
                                :href="route('customer-bot.conversation', conversation.id)"
                                class="block p-6"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4 flex-1">
                                        <!-- Channel Icon -->
                                        <div class="flex-shrink-0 text-2xl">
                                            {{ channelIcons[conversation.channel] || '💬' }}
                                        </div>

                                        <!-- Conversation Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3">
                                                <p class="text-lg font-semibold text-gray-900 truncate">
                                                    {{ conversation.customer_name || 'Anonim' }}
                                                </p>
                                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', statusColors[conversation.status]]">
                                                    {{ conversation.status === 'active' ? 'Faol' : conversation.status === 'closed' ? 'Yopilgan' : 'Arxiv' }}
                                                </span>
                                                <span v-if="conversation.handed_off" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    Operatorda
                                                </span>
                                            </div>

                                            <div class="mt-1 flex items-center gap-4 text-sm text-gray-500">
                                                <span v-if="conversation.customer_email">{{ conversation.customer_email }}</span>
                                                <span v-if="conversation.customer_phone">{{ conversation.customer_phone }}</span>
                                                <span>{{ conversation.messages_count }} xabar</span>
                                            </div>

                                            <!-- Last Message -->
                                            <p v-if="conversation.messages && conversation.messages.length > 0" class="mt-2 text-sm text-gray-600 truncate">
                                                {{ conversation.messages[0].content }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Right Side Info -->
                                    <div class="flex-shrink-0 ml-4 text-right">
                                        <p class="text-sm text-gray-500">
                                            {{ formatDate(conversation.updated_at) }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-400">
                                            {{ stageLabels[conversation.current_stage] || conversation.current_stage }}
                                        </p>
                                        <p v-if="conversation.lead_id" class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Lid
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </li>

                        <li v-if="conversations.data.length === 0" class="p-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="mt-4">Hech qanday suhbat topilmadi</p>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <div v-if="conversations.data.length > 0" class="bg-white px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">{{ conversations.from }}</span>
                                -
                                <span class="font-medium">{{ conversations.to }}</span>
                                dan
                                <span class="font-medium">{{ conversations.total }}</span>
                                ta
                            </div>

                            <div class="flex gap-2">
                                <Link
                                    v-if="conversations.prev_page_url"
                                    :href="conversations.prev_page_url"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    preserve-state
                                >
                                    Oldingi
                                </Link>

                                <Link
                                    v-if="conversations.next_page_url"
                                    :href="conversations.next_page_url"
                                    class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                                    preserve-state
                                >
                                    Keyingi
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
