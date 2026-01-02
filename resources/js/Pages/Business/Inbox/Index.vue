<template>
    <BusinessLayout title="Yagona Inbox">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Yagona Inbox</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Barcha kanallardan kelgan xabarlarni bir joyda boshqaring
                    </p>
                </div>
                <button class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Yangilash
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Total -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Jami suhbatlar</p>
                    </div>
                </div>

                <!-- Open -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.open }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ochiq</p>
                    </div>
                </div>

                <!-- Pending -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.pending }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kutilmoqda</p>
                    </div>
                </div>

                <!-- Closed -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ stats.closed }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Yopilgan</p>
                    </div>
                </div>

                <!-- Response Rate -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.response_rate.toFixed(1) }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Javob darajasi</p>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Conversations List -->
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col" style="height: 650px;">
                    <!-- Channel Filters -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                        <div class="flex flex-wrap gap-2">
                            <button
                                @click="updateFilter('channel', null)"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg transition-all"
                                :class="!currentFilters.channel
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            >
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Barchasi
                            </button>
                            <button
                                @click="updateFilter('channel', 'instagram')"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg transition-all"
                                :class="currentFilters.channel === 'instagram'
                                    ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            >
                                <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                                Instagram
                            </button>
                            <button
                                @click="updateFilter('channel', 'telegram')"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-lg transition-all"
                                :class="currentFilters.channel === 'telegram'
                                    ? 'bg-sky-500 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                            >
                                <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                </svg>
                                Telegram
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input
                                v-model="searchQuery"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Mijoz nomi yoki telefon..."
                                class="w-full pl-10 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white dark:placeholder-gray-400"
                            />
                        </div>
                    </div>

                    <!-- Conversations List -->
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-for="conversation in conversations"
                            :key="conversation.id"
                            @click="selectConversation(conversation.id)"
                            class="p-4 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            :class="{ 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-l-blue-500': selectedConversationId === conversation.id }"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Channel Icon -->
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
                                     :class="getChannelBgClass(conversation.channel)">
                                    <component :is="getChannelIcon(conversation.channel)" class="w-5 h-5" :class="getChannelIconClass(conversation.channel)" />
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                            {{ conversation.customer_name || 'Noma\'lum' }}
                                        </h4>
                                        <span class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                            {{ conversation.last_message_time }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 truncate mt-0.5">
                                        {{ conversation.last_message || 'Xabar yo\'q' }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full"
                                              :class="getStatusClass(conversation.status)">
                                            {{ getStatusLabel(conversation.status) }}
                                        </span>
                                        <span v-if="conversation.is_unread" class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ conversation.message_count }} xabar
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="conversations.length === 0" class="flex flex-col items-center justify-center h-full p-8 text-center">
                            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Suhbat topilmadi</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Hozircha hech qanday suhbat yo'q</p>
                        </div>
                    </div>
                </div>

                <!-- Conversation Detail -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col" style="height: 650px;">
                    <!-- Empty State -->
                    <div v-if="!selectedConversationId" class="flex-1 flex flex-col items-center justify-center text-center p-8">
                        <div class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Suhbatni tanlang</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm">
                            Chap tarafdagi ro'yxatdan suhbatni tanlab, mijoz bilan muloqotni davom ettiring
                        </p>
                    </div>

                    <template v-else>
                        <!-- Conversation Header -->
                        <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <div v-if="currentConversation" class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                                         :class="getChannelBgClass(currentConversation.channel)">
                                        <component :is="getChannelIcon(currentConversation.channel)" class="w-6 h-6" :class="getChannelIconClass(currentConversation.channel)" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">
                                            {{ currentConversation.customer?.name || 'Noma\'lum mijoz' }}
                                        </h3>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ currentConversation.customer?.phone || 'Telefon yo\'q' }}
                                            </span>
                                            <span class="text-gray-300 dark:text-gray-600">•</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400 capitalize">
                                                {{ currentConversation.channel }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full"
                                          :class="getStatusClass(currentConversation.status)">
                                        {{ getStatusLabel(currentConversation.status) }}
                                    </span>
                                    <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50 dark:bg-gray-900/50">
                            <div
                                v-for="message in currentConversation?.messages || []"
                                :key="message.id"
                                class="flex"
                                :class="message.direction === 'outbound' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-sm"
                                    :class="message.direction === 'outbound'
                                        ? 'bg-blue-600 text-white rounded-br-md'
                                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-bl-md'"
                                >
                                    <p class="text-sm leading-relaxed">{{ message.content }}</p>
                                    <p class="text-xs mt-1.5" :class="message.direction === 'outbound' ? 'text-blue-200' : 'text-gray-400'">
                                        {{ message.human_time }}
                                    </p>
                                </div>
                            </div>

                            <!-- Empty messages -->
                            <div v-if="!currentConversation?.messages?.length" class="flex flex-col items-center justify-center h-full text-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Xabarlar yo'q</p>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="flex-shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                            <form @submit.prevent="sendMessage" class="flex items-center gap-3">
                                <button type="button" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </button>
                                <input
                                    v-model="newMessage"
                                    type="text"
                                    placeholder="Xabar yozish..."
                                    class="flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white dark:placeholder-gray-400 text-sm"
                                />
                                <button
                                    type="submit"
                                    :disabled="!newMessage.trim() || sending"
                                    class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="!sending" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, nextTick, h } from 'vue'
import { router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'
import axios from 'axios'

const props = defineProps({
    conversations: Array,
    stats: Object,
    filters: Object,
    currentBusiness: Object
})

const selectedConversationId = ref(null)
const currentConversation = ref(null)
const newMessage = ref('')
const sending = ref(false)
const searchQuery = ref(props.filters?.search || '')
const currentFilters = ref({ ...props.filters })
const messagesContainer = ref(null)

let searchTimeout = null

// Channel icons as render functions
const InstagramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
        ])
    }
}

const TelegramIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z' })
        ])
    }
}

const FacebookIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
        ])
    }
}

const DefaultIcon = {
    render() {
        return h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
            h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' })
        ])
    }
}

const getChannelIcon = (channel) => {
    const icons = {
        instagram: InstagramIcon,
        telegram: TelegramIcon,
        facebook: FacebookIcon
    }
    return icons[channel] || DefaultIcon
}

const getChannelBgClass = (channel) => {
    const classes = {
        instagram: 'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30',
        telegram: 'bg-sky-100 dark:bg-sky-900/30',
        facebook: 'bg-blue-100 dark:bg-blue-900/30'
    }
    return classes[channel] || 'bg-gray-100 dark:bg-gray-700'
}

const getChannelIconClass = (channel) => {
    const classes = {
        instagram: 'text-pink-600 dark:text-pink-400',
        telegram: 'text-sky-600 dark:text-sky-400',
        facebook: 'text-blue-600 dark:text-blue-400'
    }
    return classes[channel] || 'text-gray-600 dark:text-gray-400'
}

const getStatusClass = (status) => {
    const classes = {
        open: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        active: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        pending: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        closed: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400'
    }
    return classes[status] || classes.closed
}

const getStatusLabel = (status) => {
    const labels = {
        open: 'Ochiq',
        active: 'Faol',
        pending: 'Kutilmoqda',
        closed: 'Yopilgan'
    }
    return labels[status] || status
}

const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        updateFilter('search', searchQuery.value)
    }, 500)
}

const updateFilter = (key, value) => {
    currentFilters.value[key] = value
    router.get(route('business.inbox.index'), currentFilters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

const selectConversation = async (conversationId) => {
    selectedConversationId.value = conversationId

    try {
        const response = await axios.get(route('business.inbox.show', conversationId))
        currentConversation.value = response.data.conversation

        await nextTick()
        scrollToBottom()
    } catch (error) {
        console.error('Error loading conversation:', error)
    }
}

const sendMessage = async () => {
    if (!newMessage.value.trim() || sending.value) return

    sending.value = true
    try {
        const response = await axios.post(route('business.inbox.send', selectedConversationId.value), {
            message: newMessage.value
        })

        if (response.data.success) {
            newMessage.value = ''
            await selectConversation(selectedConversationId.value)
        }
    } catch (error) {
        console.error('Error sending message:', error)
    } finally {
        sending.value = false
    }
}

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}
</script>
