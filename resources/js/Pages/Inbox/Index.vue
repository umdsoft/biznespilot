<template>
    <BusinessLayout title="Yagona Inbox">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Yagona Inbox</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Barcha kanallardan kelgan xabarlarni bir joyda boshqaring
                    </p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="text-sm text-gray-600">Jami</div>
                        <div class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="text-sm text-gray-600">Ochiq</div>
                        <div class="text-2xl font-bold text-green-600 mt-1">{{ stats.open }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="text-sm text-gray-600">Kutilmoqda</div>
                        <div class="text-2xl font-bold text-orange-600 mt-1">{{ stats.pending }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="text-sm text-gray-600">Yopilgan</div>
                        <div class="text-2xl font-bold text-gray-600 mt-1">{{ stats.closed }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="text-sm text-gray-600">Javob darajasi</div>
                        <div class="text-2xl font-bold text-purple-600 mt-1">{{ stats.response_rate.toFixed(1) }}%</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Conversations List -->
                    <div class="lg:col-span-1 bg-white rounded-xl shadow-md overflow-hidden">
                        <!-- Filters -->
                        <div class="p-4 border-b border-gray-200">
                            <div class="flex space-x-2 mb-3">
                                <button
                                    @click="updateFilter('channel', null)"
                                    class="px-3 py-1 text-sm rounded-lg transition-colors"
                                    :class="!currentFilters.channel ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                >
                                    Barchasi
                                </button>
                                <button
                                    @click="updateFilter('channel', 'whatsapp')"
                                    class="px-3 py-1 text-sm rounded-lg transition-colors"
                                    :class="currentFilters.channel === 'whatsapp' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                >
                                    💬 WhatsApp
                                </button>
                                <button
                                    @click="updateFilter('channel', 'instagram')"
                                    class="px-3 py-1 text-sm rounded-lg transition-colors"
                                    :class="currentFilters.channel === 'instagram' ? 'bg-pink-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                >
                                    📸 Instagram
                                </button>
                            </div>

                            <input
                                v-model="searchQuery"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Qidirish..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>

                        <!-- Conversations -->
                        <div class="overflow-y-auto" style="max-height: 600px;">
                            <div
                                v-for="conversation in conversations"
                                :key="conversation.id"
                                @click="selectConversation(conversation.id)"
                                class="p-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors"
                                :class="{ 'bg-purple-50': selectedConversationId === conversation.id }"
                            >
                                <div class="flex items-start">
                                    <div class="text-2xl mr-3">{{ conversation.customer_avatar }}</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-900 truncate">
                                                {{ conversation.customer_name }}
                                            </h4>
                                            <span class="text-xs text-gray-500 ml-2">{{ conversation.last_message_time }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 truncate mt-1">{{ conversation.last_message }}</p>
                                        <div class="flex items-center mt-2 space-x-2">
                                            <span class="text-xs px-2 py-0.5 rounded-full"
                                                  :class="{
                                                      'bg-green-100 text-green-800': conversation.status === 'open',
                                                      'bg-gray-100 text-gray-800': conversation.status === 'closed',
                                                      'bg-orange-100 text-orange-800': conversation.status === 'pending'
                                                  }">
                                                {{ conversation.status }}
                                            </span>
                                            <span v-if="conversation.is_unread" class="w-2 h-2 bg-purple-600 rounded-full"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="conversations.length === 0" class="p-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <p class="text-sm">Hech qanday suhbat topilmadi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Conversation Detail -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-md overflow-hidden flex flex-col" style="height: 700px;">
                        <div v-if="!selectedConversationId" class="flex-1 flex items-center justify-center text-gray-500">
                            <div class="text-center">
                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p class="text-lg">Suhbatni tanlang</p>
                            </div>
                        </div>

                        <template v-else>
                            <!-- Conversation Header -->
                            <div class="p-4 border-b border-gray-200 bg-gray-50">
                                <div v-if="currentConversation" class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="text-2xl mr-3">
                                            {{ currentConversation.channel === 'whatsapp' ? '💬' : '📸' }}
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ currentConversation.customer?.name }}</h3>
                                            <p class="text-sm text-gray-600">{{ currentConversation.customer?.phone }}</p>
                                        </div>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                          :class="{
                                              'bg-green-100 text-green-800': currentConversation.status === 'open',
                                              'bg-gray-100 text-gray-800': currentConversation.status === 'closed'
                                          }">
                                        {{ currentConversation.status }}
                                    </span>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-gray-50">
                                <div
                                    v-for="message in currentConversation?.messages || []"
                                    :key="message.id"
                                    class="flex"
                                    :class="message.direction === 'outgoing' ? 'justify-end' : 'justify-start'"
                                >
                                    <div
                                        class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                                        :class="message.direction === 'outgoing'
                                            ? 'bg-purple-600 text-white'
                                            : 'bg-white text-gray-900 border border-gray-200'"
                                    >
                                        <p class="text-sm">{{ message.content }}</p>
                                        <p class="text-xs mt-1 opacity-75">{{ message.human_time }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Message Input -->
                            <div class="p-4 border-t border-gray-200 bg-white">
                                <form @submit.prevent="sendMessage" class="flex space-x-2">
                                    <input
                                        v-model="newMessage"
                                        type="text"
                                        placeholder="Xabar yozish..."
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="!newMessage.trim() || sending"
                                        class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg v-if="!sending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
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
const searchQuery = ref(props.filters.search || '')
const currentFilters = ref({ ...props.filters })
const messagesContainer = ref(null)

let searchTimeout = null

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
            // Reload conversation to get new message
            await selectConversation(selectedConversationId.value)
        }
    } catch (error) {
        alert('Xabar yuborishda xatolik yuz berdi')
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
