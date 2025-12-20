<template>
  <BusinessLayout title="AI Chatbot">
  <div class="flex flex-col h-[calc(100vh-12rem)]">
    <div class="mb-4 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">AI Marketing Maslahatchi</h1>
        <p class="text-sm text-gray-600 mt-1">Biznes va marketing savollari bo'yicha yordam</p>
      </div>
      <button
        v-if="chatMessages.length > 0"
        @click="clearHistory"
        class="px-4 py-2 text-sm text-red-600 hover:text-red-700 font-medium"
      >
        Tarixni Tozalash
      </button>
    </div>

    <!-- API Key Warning -->
    <div v-if="!hasApiKey" class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <h3 class="text-sm font-medium text-yellow-800">API Kaliti Kerak</h3>
          <p class="mt-1 text-sm text-yellow-700">
            Chatbot funksiyasidan foydalanish uchun Settings sahifasida OpenAI yoki Claude API kalitini qo'shing.
          </p>
          <Link :href="route('business.settings.index')" class="mt-3 inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900">
            Settings ga o'tish
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </div>

    <!-- Chat Container -->
    <Card class="flex-1 flex flex-col overflow-hidden">
      <!-- Messages Area -->
      <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Welcome Message -->
        <div v-if="chatMessages.length === 0 && hasApiKey" class="text-center py-12">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Assalomu alaykum!</h3>
          <p class="text-gray-600 max-w-md mx-auto">
            Men sizning AI marketing maslahatchiingizman. Biznes, marketing, mijozlar va strategiya haqida savollaringizga yordam beraman.
          </p>
          <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-3 max-w-2xl mx-auto">
            <button
              v-for="suggestion in suggestions"
              :key="suggestion"
              @click="sendSuggestion(suggestion)"
              class="px-4 py-3 text-left text-sm bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {{ suggestion }}
            </button>
          </div>
        </div>

        <!-- Chat Messages -->
        <div
          v-for="message in chatMessages"
          :key="message.id"
          :class="[
            'flex',
            message.role === 'user' ? 'justify-end' : 'justify-start'
          ]"
        >
          <div
            :class="[
              'max-w-[70%] rounded-lg px-4 py-3',
              message.role === 'user'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-900'
            ]"
          >
            <div class="whitespace-pre-line text-sm">{{ message.message }}</div>
            <div
              :class="[
                'text-xs mt-1 flex items-center justify-between',
                message.role === 'user' ? 'text-blue-100' : 'text-gray-500'
              ]"
            >
              <span>{{ message.created_at }}</span>
              <span v-if="message.ai_model && message.role === 'assistant'" class="ml-2">
                {{ getModelLabel(message.ai_model) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Typing Indicator -->
        <div v-if="isTyping" class="flex justify-start">
          <div class="bg-gray-100 rounded-lg px-4 py-3">
            <div class="flex space-x-2">
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
              <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Input Area -->
      <div class="border-t border-gray-200 p-4">
        <form @submit.prevent="sendMessage" class="flex items-end space-x-3">
          <div class="flex-1">
            <textarea
              v-model="newMessage"
              @keydown.enter.exact.prevent="sendMessage"
              rows="1"
              :disabled="!hasApiKey || isTyping"
              class="input resize-none"
              placeholder="Savolingizni yozing..."
              style="min-height: 44px; max-height: 120px"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">Enter - yuborish, Shift+Enter - yangi qator</p>
          </div>
          <button
            type="submit"
            :disabled="!newMessage.trim() || !hasApiKey || isTyping"
            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
          </button>
        </form>
      </div>
    </Card>
  </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';
import axios from 'axios';

const props = defineProps({
  messages: Array,
  hasApiKey: Boolean,
});

const chatMessages = ref([...props.messages]);
const newMessage = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);

const suggestions = [
  "Dream Buyer qanday yaratish kerak?",
  "Ijtimoiy tarmoqlarda marketing strategiyasi",
  "Raqobatchilardan qanday ajralib turish mumkin?",
  "Konversiyani oshirish usullari",
];

const getModelLabel = (model) => {
  if (model.includes('gpt-4')) return 'GPT-4';
  if (model.includes('gpt-3.5')) return 'GPT-3.5';
  if (model.includes('claude-3-opus')) return 'Claude 3 Opus';
  if (model.includes('claude-3-sonnet')) return 'Claude 3 Sonnet';
  return model;
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

const sendMessage = async () => {
  if (!newMessage.value.trim() || !props.hasApiKey || isTyping.value) return;

  const message = newMessage.value.trim();
  newMessage.value = '';
  isTyping.value = true;

  try {
    const response = await axios.post(route('business.chatbot.send'), {
      message: message,
    });

    if (response.data.success) {
      chatMessages.value.push(response.data.userMessage);
      scrollToBottom();

      // Small delay before showing AI response
      setTimeout(() => {
        chatMessages.value.push(response.data.assistantMessage);
        scrollToBottom();
      }, 300);
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Xatolik yuz berdi');
  } finally {
    isTyping.value = false;
  }
};

const sendSuggestion = (suggestion) => {
  newMessage.value = suggestion;
  sendMessage();
};

const clearHistory = async () => {
  if (!confirm('Haqiqatan ham chat tarixini tozalamoqchimisiz?')) return;

  try {
    await axios.delete(route('business.chatbot.clear'));
    chatMessages.value = [];
  } catch (error) {
    alert('Xatolik yuz berdi');
  }
};

onMounted(() => {
  scrollToBottom();
});
</script>

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
