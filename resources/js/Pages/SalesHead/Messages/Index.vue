<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    messages: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            unread: 0,
            today: 0,
        }),
    },
});

const searchQuery = ref('');

const filteredMessages = computed(() => {
    if (!searchQuery.value) return props.messages;
    const q = searchQuery.value.toLowerCase();
    return props.messages.filter(m => m.lead?.name?.toLowerCase().includes(q) || m.content?.toLowerCase().includes(q));
});

const formatDate = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    const mins = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);

    if (mins < 1) return 'Hozirgina';
    if (mins < 60) return `${mins} daqiqa oldin`;
    if (hours < 24) return `${hours} soat oldin`;
    return d.toLocaleDateString('uz-UZ');
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getChannelIcon = (channel) => {
    // Could return different icons based on channel type
    return ChatBubbleLeftRightIcon;
};

const getChannelColor = (channel) => {
    const colors = {
        telegram: 'bg-blue-500',
        whatsapp: 'bg-green-500',
        instagram: 'bg-pink-500',
        sms: 'bg-purple-500',
    };
    return colors[channel] || 'bg-gray-500';
};
</script>

<template>
    <SalesHeadLayout title="Xabarlar">
        <Head title="Xabarlar" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Xabarlar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mijozlar bilan yozishmalar</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <ChatBubbleLeftRightIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami xabarlar</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <EnvelopeIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'qilmagan</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ stats.unread }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <ChatBubbleLeftRightIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bugun</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.today }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Xabar qidirish..."
                    class="w-full max-w-md pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
            </div>

            <!-- Messages List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="filteredMessages.length === 0" class="p-12 text-center">
                    <ChatBubbleLeftRightIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Xabar topilmadi</h3>
                    <p class="text-gray-500 dark:text-gray-400">Hali xabarlar yo'q</p>
                </div>

                <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="message in filteredMessages"
                        :key="message.id"
                        :class="['p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors', !message.read ? 'bg-blue-50/50 dark:bg-blue-900/10' : '']"
                    >
                        <div class="flex items-start gap-4">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold">
                                    {{ getInitials(message.lead?.name) }}
                                </div>
                                <div :class="[getChannelColor(message.channel), 'absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800']"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ message.lead?.name || 'Noma\'lum' }}</h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ formatDate(message.created_at) }}</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ message.content }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span v-if="message.channel" class="text-xs text-gray-400 capitalize">{{ message.channel }}</span>
                                    <span v-if="message.operator" class="text-xs text-gray-400">{{ message.operator.name }}</span>
                                </div>
                            </div>
                            <div v-if="!message.read" class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0 mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
