<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import { ref, nextTick, computed } from 'vue';
import axios from 'axios';
import {
    ChatBubbleLeftRightIcon,
    PlusIcon,
    TrashIcon,
    ArchiveBoxIcon,
    SparklesIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => []
    },
    currentConversation: {
        type: Object,
        default: null
    }
});

// State
const newMessage = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);
const selectedContextType = ref('general');

// Computed
const currentMessages = computed(() => {
    return props.currentConversation?.messages || [];
});

// Context type options
const contextTypes = [
    { value: 'general', label: 'Umumiy', icon: '💬' },
    { value: 'marketing', label: 'Marketing', icon: '📈' },
    { value: 'sales', label: 'Sotuv', icon: '💰' },
    { value: 'customer', label: 'Mijozlar', icon: '👥' },
    { value: 'strategy', label: 'Strategiya', icon: '🎯' }
];

// Suggestions for new chat
const suggestions = {
    general: [
        "Biznesim uchun qanday yaxshilashlar kerak?",
        "Ko'rsatkichlarimni tahlil qiling",
        "Oxirgi oyning hisobotini ko'rsating"
    ],
    marketing: [
        "Marketing strategiyamni tahlil qiling",
        "Qaysi kanallar eng samarali?",
        "Kontent rejalash uchun tavsiyalar"
    ],
    sales: [
        "Sotuvni oshirish usullari",
        "Konversiya stavkasini yaxshilash",
        "Pricing strategiyasi"
    ],
    customer: [
        "Mijozlar ehtiyojlarini qanday aniqlash mumkin?",
        "Customer retention strategiyasi",
        "Mijozlar feedbackini tahlil qilish"
    ]
};

// Format date
const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);

    if (diffMins < 1) return 'Hozir';
    if (diffMins < 60) return `${diffMins} daqiqa oldin`;
    if (diffMins < 1440) return `${Math.floor(diffMins / 60)} soat oldin`;

    return d.toLocaleDateString('uz-UZ', {
        month: 'short',
        day: 'numeric'
    });
};

// Format time
const formatTime = (timestamp) => {
    if (!timestamp) return '';
    return new Date(timestamp).toLocaleTimeString('uz-UZ', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Scroll to bottom
const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

// Create new conversation
const createNewConversation = async (message = null, contextType = 'general') => {
    if (!message && !newMessage.value.trim()) return;

    const messageText = message || newMessage.value.trim();
    newMessage.value = '';
    isTyping.value = true;

    try {
        const response = await axios.post(route('business.ai.chat.store'), {
            message: messageText,
            context_type: contextType
        });

        // Redirect to the new conversation
        router.visit(route('business.ai.chat.show', response.data.conversation.id));
    } catch (error) {
        console.error('Error creating conversation:', error);
        alert(error.response?.data?.message || 'Xatolik yuz berdi');
    } finally {
        isTyping.value = false;
    }
};

// Send message in existing conversation
const sendMessage = async () => {
    if (!newMessage.value.trim() || isTyping.value || !props.currentConversation) return;

    const messageText = newMessage.value.trim();
    newMessage.value = '';
    isTyping.value = true;

    try {
        const response = await axios.post(
            route('business.ai.chat.send-message', props.currentConversation.id),
            { message: messageText }
        );

        // Reload the page to show updated conversation
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error('Error sending message:', error);
        alert(error.response?.data?.message || 'Xatolik yuz berdi');
    } finally {
        isTyping.value = false;
    }
};

// Send suggestion
const sendSuggestion = (suggestion) => {
    if (props.currentConversation) {
        newMessage.value = suggestion;
        sendMessage();
    } else {
        createNewConversation(suggestion, selectedContextType.value);
    }
};

// Archive conversation
const archiveConversation = async (conversationId) => {
    if (!confirm('Suhbatni arxivlamoqchimisiz?')) return;

    try {
        await axios.post(route('business.ai.chat.archive', conversationId));
        router.reload();
    } catch (error) {
        alert('Xatolik yuz berdi');
    }
};

// Delete conversation
const deleteConversation = async (conversationId) => {
    if (!confirm('Haqiqatan ham suhbatni o\'chirmoqchimisiz?')) return;

    try {
        await router.delete(route('business.ai.chat.destroy', conversationId));
    } catch (error) {
        alert('Xatolik yuz berdi');
    }
};

// Handle form submit
const handleSubmit = () => {
    if (props.currentConversation) {
        sendMessage();
    } else {
        createNewConversation();
    }
};

// Auto-scroll on mount and when messages change
nextTick(() => {
    scrollToBottom();
});
</script>

<template>
    <Head title="AI Chat" />

    <BusinessLayout>
        <div class="flex h-[calc(100vh-4rem)]">
            <!-- Conversations Sidebar -->
            <div class="w-80 bg-white border-r border-gray-200 flex flex-col">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-gray-200">
                    <Link
                        :href="route('business.ai.chat.index')"
                        class="flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Yangi Chat
                    </Link>
                </div>

                <!-- Conversations List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500 text-sm">
                        Suhbatlar yo'q
                    </div>
                    <div v-else class="divide-y divide-gray-200">
                        <Link
                            v-for="conversation in conversations"
                            :key="conversation.id"
                            :href="route('business.ai.chat.show', conversation.id)"
                            :class="[
                                'block p-4 hover:bg-gray-50 transition-colors cursor-pointer',
                                currentConversation?.id === conversation.id ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''
                            ]"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="text-sm font-medium text-gray-900 line-clamp-1">
                                    {{ conversation.title || 'Yangi Suhbat' }}
                                </h3>
                                <span class="text-xs text-gray-500 ml-2 flex-shrink-0">
                                    {{ formatDate(conversation.last_message_at) }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 line-clamp-2">
                                {{ conversation.messages?.[conversation.messages.length - 1]?.content || 'Xabar yo\'q' }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="flex-1 flex flex-col bg-gray-50">
                <!-- Chat Header -->
                <div v-if="currentConversation" class="bg-white border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                {{ currentConversation.title || 'Yangi Suhbat' }}
                            </h2>
                            <p class="text-sm text-gray-500">
                                {{ currentConversation.messages?.length || 0 }} xabar
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                @click="archiveConversation(currentConversation.id)"
                                class="p-2 text-gray-400 hover:text-gray-600 transition"
                                title="Arxivlash"
                            >
                                <ArchiveBoxIcon class="h-5 w-5" />
                            </button>
                            <button
                                @click="deleteConversation(currentConversation.id)"
                                class="p-2 text-gray-400 hover:text-red-600 transition"
                                title="O'chirish"
                            >
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6">
                    <!-- Welcome Screen (No Conversation) -->
                    <div v-if="!currentConversation" class="max-w-3xl mx-auto">
                        <div class="text-center py-12">
                            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <SparklesIcon class="h-10 w-10 text-indigo-600" />
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-3">AI Maslahatchi</h1>
                            <p class="text-gray-600 max-w-xl mx-auto mb-8">
                                Biznesingiz haqida savollar bering, marketing, sotuv va mijozlar bo'yicha professional tavsiyalar oling.
                            </p>

                            <!-- Context Type Selector -->
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Suhbat Mavzusi
                                </label>
                                <div class="flex justify-center space-x-2">
                                    <button
                                        v-for="type in contextTypes"
                                        :key="type.value"
                                        @click="selectedContextType = type.value"
                                        :class="[
                                            'px-4 py-2 rounded-lg border-2 transition-all',
                                            selectedContextType === type.value
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                                        ]"
                                    >
                                        <span class="mr-2">{{ type.icon }}</span>
                                        <span class="text-sm font-medium">{{ type.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Suggestions -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 max-w-3xl mx-auto">
                                <button
                                    v-for="suggestion in (suggestions[selectedContextType] || suggestions.general)"
                                    :key="suggestion"
                                    @click="sendSuggestion(suggestion)"
                                    class="px-4 py-3 text-left text-sm bg-white border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 rounded-lg transition-all"
                                >
                                    {{ suggestion }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div v-else class="max-w-4xl mx-auto space-y-4">
                        <div
                            v-for="(message, index) in currentMessages"
                            :key="index"
                            :class="[
                                'flex',
                                message.role === 'user' ? 'justify-end' : 'justify-start'
                            ]"
                        >
                            <div
                                :class="[
                                    'max-w-[75%] rounded-2xl px-4 py-3',
                                    message.role === 'user'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-white text-gray-900 border border-gray-200'
                                ]"
                            >
                                <div class="whitespace-pre-line text-sm">{{ message.content }}</div>
                                <div
                                    :class="[
                                        'text-xs mt-1',
                                        message.role === 'user' ? 'text-indigo-100' : 'text-gray-500'
                                    ]"
                                >
                                    {{ formatTime(message.timestamp) }}
                                </div>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div v-if="isTyping" class="flex justify-start">
                            <div class="bg-white border border-gray-200 rounded-2xl px-4 py-3">
                                <div class="flex space-x-2">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="bg-white border-t border-gray-200 px-6 py-4">
                    <div class="max-w-4xl mx-auto">
                        <form @submit.prevent="handleSubmit" class="flex items-end space-x-3">
                            <div class="flex-1">
                                <textarea
                                    v-model="newMessage"
                                    @keydown.enter.exact.prevent="handleSubmit"
                                    rows="1"
                                    :disabled="isTyping"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                    placeholder="Xabar yozing..."
                                    style="min-height: 50px; max-height: 120px"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    Enter - yuborish, Shift+Enter - yangi qator
                                </p>
                            </div>
                            <button
                                type="submit"
                                :disabled="!newMessage.trim() || isTyping"
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center space-x-2"
                            >
                                <PaperAirplaneIcon class="h-5 w-5" />
                                <span class="font-medium">Yuborish</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>

<style scoped>
@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>
