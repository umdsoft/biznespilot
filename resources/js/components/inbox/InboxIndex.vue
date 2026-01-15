<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    Yagona Inbox
                    <span v-if="statsData.unread?.total > 0" class="inline-flex items-center justify-center px-2.5 py-1 text-sm font-bold bg-red-500 text-white rounded-full animate-pulse">
                        {{ statsData.unread.total }}
                    </span>
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Barcha kanallardan kelgan xabarlarni bir joyda boshqaring
                </p>
            </div>
            <button
                @click="refreshInbox"
                :disabled="isRefreshing"
                :class="[
                    'inline-flex items-center px-4 py-2.5 text-white text-sm font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50',
                    panelConfig.buttonClass
                ]"
            >
                <svg :class="['w-4 h-4 mr-2', isRefreshing ? 'animate-spin' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yangilash
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Total -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statsData.total }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami suhbatlar</p>
                </div>
            </div>

            <!-- Open -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-green-300 dark:hover:border-green-600 transition-all cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-200 dark:from-green-900/30 dark:to-emerald-800/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <EnvelopeOpenIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ statsData.open }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ochiq</p>
                </div>
            </div>

            <!-- Pending -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-amber-300 dark:hover:border-amber-600 transition-all cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-orange-200 dark:from-amber-900/30 dark:to-orange-800/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <ClockIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ statsData.pending }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kutilmoqda</p>
                </div>
            </div>

            <!-- Closed -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-100 to-slate-200 dark:from-gray-700 dark:to-slate-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <CheckIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ statsData.closed }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Yopilgan</p>
                </div>
            </div>

            <!-- Unread -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-red-300 dark:hover:border-red-600 transition-all cursor-pointer group">
                <div class="flex items-center justify-between">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-rose-200 dark:from-red-900/30 dark:to-rose-800/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <BellAlertIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ statsData.unread?.total || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">O'qilmagan</p>
                </div>
            </div>

            <!-- Response Rate -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all cursor-pointer group" :class="panelConfig.statsBorderHover">
                <div class="flex items-center justify-between">
                    <div :class="['w-10 h-10 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform', panelConfig.statsIconBg]">
                        <ArrowTrendingUpIcon :class="['w-5 h-5', panelConfig.statsIconColor]" />
                    </div>
                </div>
                <div class="mt-3">
                    <p :class="['text-2xl font-bold', panelConfig.statsValueColor]">{{ statsData.response_rate?.toFixed(1) || 0 }}%</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob darajasi</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Conversations List -->
            <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col shadow-sm" style="height: 700px;">
                <!-- Channel Filters with Badges -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3 bg-gray-50/50 dark:bg-gray-800/50">
                    <div class="flex flex-wrap gap-2">
                        <!-- Barchasi -->
                        <button
                            @click="updateFilter('channel', null)"
                            class="relative inline-flex items-center px-3.5 py-2 text-sm font-semibold rounded-xl transition-all"
                            :class="!currentFilters.channel
                                ? panelConfig.filterActiveClass
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600'"
                        >
                            <Bars3Icon class="w-4 h-4 mr-1.5" />
                            Barchasi
                            <span v-if="statsData.unread?.total > 0" class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full"
                                :class="!currentFilters.channel ? 'bg-white/20 text-white' : 'bg-red-500 text-white'">
                                {{ statsData.unread.total }}
                            </span>
                        </button>

                        <!-- Instagram -->
                        <button
                            @click="updateFilter('channel', 'instagram')"
                            class="relative inline-flex items-center px-3.5 py-2 text-sm font-semibold rounded-xl transition-all"
                            :class="currentFilters.channel === 'instagram'
                                ? 'bg-gradient-to-r from-purple-600 via-pink-600 to-orange-500 text-white shadow-lg shadow-pink-500/30'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600'"
                        >
                            <component :is="InstagramIcon" class="w-4 h-4 mr-1.5" />
                            Instagram
                            <span v-if="(statsData.unread?.instagram || 0) > 0" class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full"
                                :class="currentFilters.channel === 'instagram' ? 'bg-white/20 text-white' : 'bg-gradient-to-r from-purple-500 to-pink-500 text-white'">
                                {{ statsData.unread.instagram }}
                            </span>
                        </button>

                        <!-- Telegram -->
                        <button
                            @click="updateFilter('channel', 'telegram')"
                            class="relative inline-flex items-center px-3.5 py-2 text-sm font-semibold rounded-xl transition-all"
                            :class="currentFilters.channel === 'telegram'
                                ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/30'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600'"
                        >
                            <component :is="TelegramIcon" class="w-4 h-4 mr-1.5" />
                            Telegram
                            <span v-if="(statsData.unread?.telegram || 0) > 0" class="ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-xs font-bold rounded-full"
                                :class="currentFilters.channel === 'telegram' ? 'bg-white/20 text-white' : 'bg-sky-500 text-white'">
                                {{ statsData.unread.telegram }}
                            </span>
                        </button>
                    </div>

                    <!-- Search -->
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input
                            v-model="searchQuery"
                            @input="debouncedSearch"
                            type="text"
                            placeholder="Mijoz nomi yoki telefon..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white dark:placeholder-gray-400 transition-all"
                        />
                    </div>
                </div>

                <!-- Conversations List -->
                <div class="flex-1 overflow-y-auto">
                    <div
                        v-for="conversation in conversationsList"
                        :key="conversation.id"
                        @click="selectConversation(conversation.id)"
                        class="relative p-4 border-b border-gray-100 dark:border-gray-700/50 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        :class="{ 'bg-blue-50/70 dark:bg-blue-900/20 border-l-4 border-l-blue-500': selectedConversationId === conversation.id }"
                    >
                        <!-- Unread indicator -->
                        <div v-if="conversation.is_unread" class="absolute top-4 right-4 w-3 h-3 bg-blue-500 rounded-full animate-pulse ring-4 ring-blue-500/20"></div>

                        <div class="flex items-start gap-3">
                            <!-- Channel Avatar -->
                            <div class="relative flex-shrink-0">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-md"
                                     :class="getChannelBgClass(conversation.channel)">
                                    <component :is="getChannelIcon(conversation.channel)" class="w-6 h-6 text-white" />
                                </div>
                                <!-- Online indicator -->
                                <div v-if="conversation.status === 'open'" class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ conversation.customer_name || 'Noma\'lum' }}
                                    </h4>
                                    <span class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                                        {{ conversation.last_message_time }}
                                    </span>
                                </div>

                                <!-- Username for Telegram -->
                                <p v-if="conversation.customer_username" class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                    @{{ conversation.customer_username }}
                                </p>

                                <p class="text-sm text-gray-600 dark:text-gray-400 truncate mt-1" :class="{ 'font-medium text-gray-900 dark:text-white': conversation.is_unread }">
                                    {{ conversation.last_message || 'Xabar yo\'q' }}
                                </p>

                                <div class="flex items-center gap-2 mt-2">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full"
                                          :class="getStatusClass(conversation.status)">
                                        {{ getStatusLabel(conversation.status) }}
                                    </span>
                                    <span v-if="conversation.unread_count > 0" class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold bg-blue-500 text-white rounded-full">
                                        {{ conversation.unread_count }}
                                    </span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ conversation.message_count }} xabar
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="conversationsList.length === 0" class="flex flex-col items-center justify-center h-full p-8 text-center">
                        <div class="relative w-20 h-20 mb-4">
                            <div class="absolute inset-0 bg-gray-500/20 rounded-full animate-ping"></div>
                            <div class="relative w-20 h-20 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 flex items-center justify-center">
                                <ChatBubbleLeftRightIcon class="w-10 h-10 text-gray-400" />
                            </div>
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Suhbat topilmadi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Hozircha hech qanday suhbat yo'q</p>
                    </div>
                </div>
            </div>

            <!-- Conversation Detail -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col shadow-sm" style="height: 700px;">
                <!-- Empty State -->
                <div v-if="!selectedConversationId" class="flex-1 flex flex-col items-center justify-center text-center p-8">
                    <div class="relative w-24 h-24 mb-6">
                        <div :class="['absolute inset-0 rounded-full animate-pulse', panelConfig.emptyStatePulseBg]"></div>
                        <div :class="['relative w-24 h-24 rounded-full flex items-center justify-center', panelConfig.emptyStateIconBg]">
                            <ChatBubbleOvalLeftEllipsisIcon :class="['w-12 h-12', panelConfig.emptyStateIconColor]" />
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Suhbatni tanlang</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-sm">
                        Chap tarafdagi ro'yxatdan suhbatni tanlab, mijoz bilan muloqotni davom ettiring
                    </p>
                </div>

                <template v-else>
                    <!-- Loading -->
                    <div v-if="isLoadingConversation" class="flex-1 flex items-center justify-center">
                        <div class="flex flex-col items-center gap-3">
                            <svg :class="['w-8 h-8 animate-spin', panelConfig.loadingSpinnerColor]" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Yuklanmoqda...</span>
                        </div>
                    </div>

                    <template v-else>
                        <!-- Conversation Header -->
                        <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-800/50 dark:to-gray-900/30">
                            <div v-if="currentConversation" class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="relative">
                                        <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg"
                                             :class="getChannelBgClass(currentConversation.channel)">
                                            <component :is="getChannelIcon(currentConversation.channel)" class="w-7 h-7 text-white" />
                                        </div>
                                        <div v-if="currentConversation.status === 'open'" class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ currentConversation.customer?.name || 'Noma\'lum mijoz' }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span v-if="currentConversation.customer?.username" class="text-sm text-blue-500 dark:text-blue-400">
                                                @{{ currentConversation.customer.username }}
                                            </span>
                                            <span v-if="currentConversation.customer?.phone" class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ currentConversation.customer.phone }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">&#8226;</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 capitalize flex items-center gap-1">
                                                <component :is="getChannelIcon(currentConversation.channel)" class="w-3.5 h-3.5 text-gray-500" />
                                                {{ currentConversation.channel }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-full"
                                          :class="getStatusClass(currentConversation.status)">
                                        {{ getStatusLabel(currentConversation.status) }}
                                    </span>
                                    <button class="p-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                                        <EllipsisVerticalIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800/50">
                            <div
                                v-for="message in currentConversation?.messages || []"
                                :key="message.id"
                                class="flex"
                                :class="message.direction === 'outbound' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm"
                                    :class="message.direction === 'outbound'
                                        ? panelConfig.outboundMessageClass
                                        : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-bl-md shadow-md'"
                                >
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ message.content }}</p>
                                    <p class="text-xs mt-1.5" :class="message.direction === 'outbound' ? panelConfig.outboundTimeClass : 'text-gray-400'">
                                        {{ message.human_time }}
                                    </p>
                                </div>
                            </div>

                            <!-- Empty messages -->
                            <div v-if="!currentConversation?.messages?.length" class="flex flex-col items-center justify-center h-full text-center">
                                <ChatBubbleOvalLeftEllipsisIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">Xabarlar yo'q</p>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="flex-shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                            <form @submit.prevent="sendMessage" class="flex items-center gap-3">
                                <button type="button" class="p-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors">
                                    <PaperClipIcon class="w-5 h-5" />
                                </button>
                                <input
                                    v-model="newMessage"
                                    type="text"
                                    placeholder="Xabar yozish..."
                                    class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white dark:placeholder-gray-400 text-sm transition-all"
                                />
                                <button
                                    type="submit"
                                    :disabled="!newMessage.trim() || sending"
                                    :class="[
                                        'inline-flex items-center justify-center w-12 h-12 text-white rounded-xl transition-all shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none',
                                        panelConfig.sendButtonClass
                                    ]"
                                >
                                    <PaperAirplaneIcon v-if="!sending" class="w-5 h-5" />
                                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, h, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    ChatBubbleLeftRightIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    EnvelopeOpenIcon,
    ClockIcon,
    CheckIcon,
    BellAlertIcon,
    ArrowTrendingUpIcon,
    Bars3Icon,
    MagnifyingGlassIcon,
    EllipsisVerticalIcon,
    PaperClipIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
    currentBusiness: Object,
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead', 'operator'].includes(value),
    },
});

// Panel-specific configuration
const panelConfig = computed(() => {
    const configs = {
        business: {
            routePrefix: 'business',
            buttonClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
            filterActiveClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30',
            statsBorderHover: 'hover:border-blue-300 dark:hover:border-blue-600',
            statsIconBg: 'bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-900/30 dark:to-indigo-800/30',
            statsIconColor: 'text-blue-600 dark:text-blue-400',
            statsValueColor: 'text-blue-600 dark:text-blue-400',
            emptyStatePulseBg: 'bg-blue-500/20',
            emptyStateIconBg: 'bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-900/30 dark:to-indigo-800/30',
            emptyStateIconColor: 'text-blue-500 dark:text-blue-400',
            loadingSpinnerColor: 'text-blue-500',
            outboundMessageClass: 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-br-md',
            outboundTimeClass: 'text-blue-200',
            sendButtonClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
        },
        saleshead: {
            routePrefix: 'sales-head',
            buttonClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-500/30',
            filterActiveClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30',
            statsBorderHover: 'hover:border-emerald-300 dark:hover:border-emerald-600',
            statsIconBg: 'bg-gradient-to-br from-emerald-100 to-teal-200 dark:from-emerald-900/30 dark:to-teal-800/30',
            statsIconColor: 'text-emerald-600 dark:text-emerald-400',
            statsValueColor: 'text-emerald-600 dark:text-emerald-400',
            emptyStatePulseBg: 'bg-emerald-500/20',
            emptyStateIconBg: 'bg-gradient-to-br from-emerald-100 to-teal-200 dark:from-emerald-900/30 dark:to-teal-800/30',
            emptyStateIconColor: 'text-emerald-500 dark:text-emerald-400',
            loadingSpinnerColor: 'text-emerald-500',
            outboundMessageClass: 'bg-gradient-to-br from-emerald-600 to-teal-600 text-white rounded-br-md',
            outboundTimeClass: 'text-emerald-200',
            sendButtonClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-500/30',
        },
        operator: {
            routePrefix: 'operator',
            buttonClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
            filterActiveClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30',
            statsBorderHover: 'hover:border-blue-300 dark:hover:border-blue-600',
            statsIconBg: 'bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-900/30 dark:to-indigo-800/30',
            statsIconColor: 'text-blue-600 dark:text-blue-400',
            statsValueColor: 'text-blue-600 dark:text-blue-400',
            emptyStatePulseBg: 'bg-blue-500/20',
            emptyStateIconBg: 'bg-gradient-to-br from-blue-100 to-indigo-200 dark:from-blue-900/30 dark:to-indigo-800/30',
            emptyStateIconColor: 'text-blue-500 dark:text-blue-400',
            loadingSpinnerColor: 'text-blue-500',
            outboundMessageClass: 'bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-br-md',
            outboundTimeClass: 'text-blue-200',
            sendButtonClass: 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/30',
        },
    };
    return configs[props.panelType];
});

const selectedConversationId = ref(null);
const currentConversation = ref(null);
const newMessage = ref('');
const sending = ref(false);
const isRefreshing = ref(false);
const isLoadingConversation = ref(false);
const searchQuery = ref(props.filters?.search || '');
const currentFilters = ref({ ...props.filters });
const messagesContainer = ref(null);

// Real-time polling
const conversationsList = ref(props.conversations || []);
const statsData = ref(props.stats || {});
let pollingInterval = null;
let messagePollingInterval = null;
let searchTimeout = null;

// Watch for prop changes
watch(() => props.conversations, (newVal) => {
    conversationsList.value = newVal || [];
});

watch(() => props.stats, (newVal) => {
    statsData.value = newVal || {};
});

// Channel icons as render functions
const InstagramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
        ]);
    }
};

const TelegramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
        ]);
    }
};

const FacebookIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
        ]);
    }
};

const DefaultIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' })
        ]);
    }
};

const getChannelIcon = (channel) => {
    const icons = {
        instagram: InstagramIcon,
        telegram: TelegramIcon,
        facebook: FacebookIcon
    };
    return icons[channel] || DefaultIcon;
};

const getChannelBgClass = (channel) => {
    const classes = {
        instagram: 'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400',
        telegram: 'bg-gradient-to-br from-sky-400 to-blue-500',
        facebook: 'bg-gradient-to-br from-blue-500 to-blue-600'
    };
    return classes[channel] || 'bg-gradient-to-br from-gray-400 to-gray-500';
};

const getStatusClass = (status) => {
    const classes = {
        open: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        pending: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        closed: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400'
    };
    return classes[status] || classes.closed;
};

const getStatusLabel = (status) => {
    const labels = {
        open: 'Ochiq',
        active: 'Faol',
        pending: 'Kutilmoqda',
        closed: 'Yopilgan'
    };
    return labels[status] || status;
};

const refreshInbox = () => {
    isRefreshing.value = true;
    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        updateFilter('search', searchQuery.value);
    }, 500);
};

const updateFilter = (key, value) => {
    currentFilters.value[key] = value;
    router.get(route(`${panelConfig.value.routePrefix}.inbox.index`), currentFilters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const selectConversation = async (conversationId) => {
    selectedConversationId.value = conversationId;
    isLoadingConversation.value = true;

    try {
        const response = await axios.get(route(`${panelConfig.value.routePrefix}.inbox.show`, conversationId));
        currentConversation.value = response.data.conversation;

        await nextTick();
        scrollToBottom();
    } catch (error) {
        console.error('Error loading conversation:', error);
    } finally {
        isLoadingConversation.value = false;
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return;

    sending.value = true;
    try {
        const response = await axios.post(route(`${panelConfig.value.routePrefix}.inbox.send`, selectedConversationId.value), {
            message: newMessage.value
        });

        if (response.data.success) {
            newMessage.value = '';
            await selectConversation(selectedConversationId.value);
        }
    } catch (error) {
        console.error('Error sending message:', error);
    } finally {
        sending.value = false;
    }
};

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Real-time polling functions
const fetchConversations = async () => {
    try {
        const params = new URLSearchParams();
        if (currentFilters.value.channel) params.append('channel', currentFilters.value.channel);
        if (currentFilters.value.search) params.append('search', currentFilters.value.search);

        const response = await axios.get(route(`${panelConfig.value.routePrefix}.inbox.index`) + '?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        });

        if (response.data.conversations) {
            conversationsList.value = response.data.conversations;
        }
        if (response.data.stats) {
            statsData.value = response.data.stats;
        }
    } catch (error) {
        console.error('Error fetching conversations:', error);
    }
};

const fetchCurrentMessages = async () => {
    if (!selectedConversationId.value || isLoadingConversation.value) return;

    try {
        const response = await axios.get(route(`${panelConfig.value.routePrefix}.inbox.show`, selectedConversationId.value));
        if (response.data.conversation) {
            const oldMessageCount = currentConversation.value?.messages?.length || 0;
            const newMessageCount = response.data.conversation.messages?.length || 0;

            currentConversation.value = response.data.conversation;

            // Scroll to bottom if new messages arrived
            if (newMessageCount > oldMessageCount) {
                await nextTick();
                scrollToBottom();
            }
        }
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
};

const startPolling = () => {
    // Poll conversations every 5 seconds
    pollingInterval = setInterval(fetchConversations, 5000);

    // Poll current conversation messages every 3 seconds
    messagePollingInterval = setInterval(fetchCurrentMessages, 3000);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
        messagePollingInterval = null;
    }
};

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }
});
</script>
