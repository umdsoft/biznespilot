<template>
  <BusinessLayout title="AI Agent">
    <Head title="AI Agent" />
    <div class="flex h-[calc(100vh-120px)]">
      <!-- Sidebar: Conversations -->
      <div class="w-72 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col flex-shrink-0">
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
          <button @click="startNewChat" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Yangi suhbat
          </button>
        </div>
        <div class="flex-1 overflow-y-auto">
          <div v-if="conversations.length === 0" class="p-4 text-center text-sm text-gray-400">
            Suhbatlar yo'q
          </div>
          <button
            v-for="conv in conversations"
            :key="conv.id"
            @click="loadConversation(conv.id)"
            class="w-full text-left px-3 py-2.5 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            :class="{ 'bg-blue-50 dark:bg-blue-900/20': activeConversationId === conv.id }"
          >
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
              Suhbat #{{ conv.message_count || 0 }}
            </p>
            <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(conv.updated_at) }}</p>
          </button>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
          <div v-if="messages.length === 0 && !loading" class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">BiznesPilot AI Agent</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md">
              Biznesingiz haqida savol bering — sotuv tahlili, marketing strategiya, moliya hisoboti, HR masalalari va boshqalar bo'yicha yordam beraman.
            </p>
          </div>

          <div v-for="msg in messages" :key="msg.id" class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
            <div class="max-w-[70%] rounded-2xl px-4 py-3" :class="msg.role === 'user' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100'">
              <div class="ai-message-content text-sm prose prose-sm dark:prose-invert max-w-none" v-html="renderMarkdown(msg.content)"></div>
              <div class="flex items-center justify-end gap-2 mt-1">
                <span v-if="msg.agent_type" class="text-[10px] opacity-60">{{ msg.agent_type }}</span>
                <span class="text-[10px] opacity-50">{{ formatTime(msg.created_at) }}</span>
              </div>
            </div>
          </div>

          <div v-if="sending" class="flex justify-start">
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="flex gap-1">
                  <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                  <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                  <span class="w-2 h-2 bg-blue-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                </div>
                <span class="text-xs text-gray-400">O'ylayapman...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
          <form @submit.prevent="sendMessage" class="flex gap-3">
            <input
              v-model="inputMessage"
              type="text"
              placeholder="Savolingizni yozing..."
              class="flex-1 px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              :disabled="sending"
              @keydown.enter.prevent="sendMessage"
            />
            <button
              type="submit"
              :disabled="!inputMessage.trim() || sending"
              class="px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import { marked } from 'marked';

const props = defineProps({ conversations: Array });

const inputMessage = ref('');
const messages = ref([]);
const sending = ref(false);
const loading = ref(false);
const activeConversationId = ref(null);
const messagesContainer = ref(null);

const scrollToBottom = async () => {
  await nextTick();
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
  }
};

const startNewChat = () => {
  activeConversationId.value = null;
  messages.value = [];
};

const loadConversation = async (id) => {
  activeConversationId.value = id;
  loading.value = true;
  try {
    const res = await axios.get(`/api/v1/agent/conversations/${id}/messages`);
    messages.value = res.data.data || [];
    scrollToBottom();
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const sendMessage = async () => {
  const text = inputMessage.value.trim();
  if (!text || sending.value) return;

  // Optimistic UI — xabarni darhol ko'rsatish
  messages.value.push({
    id: Date.now(),
    role: 'user',
    content: text,
    created_at: new Date().toISOString(),
  });
  inputMessage.value = '';
  sending.value = true;
  scrollToBottom();

  try {
    const res = await axios.post('/api/v1/agent/ask', {
      message: text,
      conversation_id: activeConversationId.value,
    });

    if (res.data.success) {
      if (res.data.conversation_id) {
        activeConversationId.value = res.data.conversation_id;
      }
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        content: res.data.message || res.data.response || 'Javob olinmadi.',
        agent_type: res.data.agent_type,
        created_at: new Date().toISOString(),
      });
    } else {
      messages.value.push({
        id: Date.now() + 1,
        role: 'assistant',
        content: res.data.message || 'Xatolik yuz berdi.',
        created_at: new Date().toISOString(),
      });
    }
  } catch (e) {
    messages.value.push({
      id: Date.now() + 1,
      role: 'assistant',
      content: e.response?.data?.message || 'Server bilan aloqa uzildi. Qayta urinib ko\'ring.',
      created_at: new Date().toISOString(),
    });
  } finally {
    sending.value = false;
    scrollToBottom();
  }
};

const timeAgo = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const now = new Date();
  const diff = Math.floor((now - d) / 1000);
  if (diff < 60) return 'Hozirgina';
  if (diff < 3600) return Math.floor(diff / 60) + ' daq oldin';
  if (diff < 86400) return Math.floor(diff / 3600) + ' soat oldin';
  return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const formatTime = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit' });
};

// Markdown parser — marked kutubxonasi
marked.setOptions({
  breaks: true,
  gfm: true,
});

const renderMarkdown = (text) => {
  if (!text) return '';
  try {
    return marked.parse(text);
  } catch (e) {
    return text;
  }
};
</script>

<style scoped>
.ai-message-content :deep(h1) { font-size: 1.25rem; font-weight: 700; margin: 1rem 0 0.5rem; }
.ai-message-content :deep(h2) { font-size: 1.15rem; font-weight: 600; margin: 0.75rem 0 0.5rem; }
.ai-message-content :deep(h3) { font-size: 1.05rem; font-weight: 600; margin: 0.5rem 0 0.25rem; }
.ai-message-content :deep(strong) { font-weight: 600; }
.ai-message-content :deep(em) { font-style: italic; }
.ai-message-content :deep(ul) { list-style: disc; padding-left: 1.25rem; margin: 0.5rem 0; }
.ai-message-content :deep(ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.5rem 0; }
.ai-message-content :deep(li) { margin: 0.25rem 0; }
.ai-message-content :deep(hr) { margin: 0.75rem 0; border: none; border-top: 1px solid #e5e7eb; }
.ai-message-content :deep(p) { margin: 0.35rem 0; }
.ai-message-content :deep(code) { background: #f3f4f6; padding: 0.1rem 0.3rem; border-radius: 0.25rem; font-size: 0.85em; }
</style>
