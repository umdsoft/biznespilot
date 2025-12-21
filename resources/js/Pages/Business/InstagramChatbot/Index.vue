<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import FlowBuilder from '@/Components/FlowBuilder/FlowBuilder.vue';
import axios from 'axios';

const props = defineProps({
    business: Object,
    account: Object,
    hasAccount: Boolean,
});

// State
const loading = ref(true);
const activeTab = ref('dashboard');
const dashboard = ref(null);
const automations = ref([]);
const conversations = ref([]);
const selectedConversation = ref(null);
const conversationMessages = ref([]);
const triggerTypes = ref([]);
const actionTypes = ref([]);

// Modals
const showAutomationModal = ref(false);
const showFlowBuilder = ref(false);
const editingAutomation = ref(null);

// New automation form
const automationForm = ref({
    name: '',
    description: '',
    type: 'keyword',
    status: 'draft',
    is_ai_enabled: false,
    triggers: [{ trigger_type: 'keyword_dm', keywords: [], exact_match: false }],
    actions: [{ action_type: 'send_dm', message_template: '' }],
});

const newKeyword = ref('');
const messageInput = ref('');

const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: 'chart-bar' },
    { id: 'automations', label: 'Avtomatlar', icon: 'bolt' },
    { id: 'conversations', label: 'Suhbatlar', icon: 'chat' },
];

// Format helpers
const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return new Intl.NumberFormat('uz-UZ').format(num || 0);
};

// Load data
const loadDashboard = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/dashboard');
        dashboard.value = response.data;
    } catch (e) {
        console.error('Error loading dashboard:', e);
    }
};

const loadAutomations = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/automations');
        automations.value = response.data.automations || [];
    } catch (e) {
        console.error('Error loading automations:', e);
    }
};

const loadConversations = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/conversations');
        conversations.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading conversations:', e);
    }
};

const loadTriggerTypes = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/trigger-types');
        triggerTypes.value = response.data.trigger_types || [];
    } catch (e) {
        console.error('Error loading trigger types:', e);
    }
};

const loadActionTypes = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/action-types');
        actionTypes.value = response.data.action_types || [];
    } catch (e) {
        console.error('Error loading action types:', e);
    }
};

const loadAll = async () => {
    loading.value = true;
    await Promise.all([
        loadDashboard(),
        loadAutomations(),
        loadConversations(),
        loadTriggerTypes(),
        loadActionTypes(),
    ]);
    loading.value = false;
};

// Automation CRUD
const openNewAutomation = () => {
    editingAutomation.value = null;
    showFlowBuilder.value = true;
};

const openOldAutomationModal = () => {
    editingAutomation.value = null;
    automationForm.value = {
        name: '',
        description: '',
        type: 'keyword',
        status: 'draft',
        is_ai_enabled: false,
        triggers: [{ trigger_type: 'keyword_dm', keywords: [], exact_match: false }],
        actions: [{ action_type: 'send_dm', message_template: '' }],
    };
    showAutomationModal.value = true;
};

const openFlowEditor = (automation) => {
    editingAutomation.value = automation;
    showFlowBuilder.value = true;
};

const onFlowBuilderSaved = () => {
    showFlowBuilder.value = false;
    editingAutomation.value = null;
    loadAutomations();
};

const editAutomation = (automation) => {
    editingAutomation.value = automation;
    automationForm.value = {
        name: automation.name,
        description: automation.description || '',
        type: automation.type,
        status: automation.status,
        is_ai_enabled: automation.is_ai_enabled,
        triggers: automation.triggers.map(t => ({
            trigger_type: t.trigger_type,
            keywords: t.keywords || [],
            exact_match: t.exact_match || false,
        })),
        actions: automation.actions.map(a => ({
            action_type: a.action_type,
            message_template: a.message_template || '',
            delay_seconds: a.delay_seconds,
        })),
    };
    showAutomationModal.value = true;
};

const saveAutomation = async () => {
    try {
        if (editingAutomation.value) {
            await axios.put(`/business/api/instagram-chatbot/automations/${editingAutomation.value.id}`, automationForm.value);
        } else {
            await axios.post('/business/api/instagram-chatbot/automations', automationForm.value);
        }
        showAutomationModal.value = false;
        await loadAutomations();
    } catch (e) {
        console.error('Error saving automation:', e);
        alert('Xatolik yuz berdi');
    }
};

const toggleAutomation = async (automation) => {
    try {
        await axios.post(`/business/api/instagram-chatbot/automations/${automation.id}/toggle`);
        await loadAutomations();
    } catch (e) {
        console.error('Error toggling automation:', e);
    }
};

const deleteAutomation = async (automation) => {
    if (!confirm('Rostdan ham o\'chirmoqchimisiz?')) return;
    try {
        await axios.delete(`/business/api/instagram-chatbot/automations/${automation.id}`);
        await loadAutomations();
    } catch (e) {
        console.error('Error deleting automation:', e);
    }
};

// Keywords management
const addKeyword = (triggerIndex) => {
    if (newKeyword.value.trim()) {
        if (!automationForm.value.triggers[triggerIndex].keywords) {
            automationForm.value.triggers[triggerIndex].keywords = [];
        }
        automationForm.value.triggers[triggerIndex].keywords.push(newKeyword.value.trim());
        newKeyword.value = '';
    }
};

const removeKeyword = (triggerIndex, keywordIndex) => {
    automationForm.value.triggers[triggerIndex].keywords.splice(keywordIndex, 1);
};

// Actions management
const addAction = () => {
    automationForm.value.actions.push({ action_type: 'send_dm', message_template: '' });
};

const removeAction = (index) => {
    if (automationForm.value.actions.length > 1) {
        automationForm.value.actions.splice(index, 1);
    }
};

// Conversation
const openConversation = async (conversation) => {
    selectedConversation.value = conversation;
    try {
        const response = await axios.get(`/business/api/instagram-chatbot/conversations/${conversation.id}`);
        conversationMessages.value = response.data.messages || [];
    } catch (e) {
        console.error('Error loading conversation:', e);
    }
};

const sendMessage = async () => {
    if (!messageInput.value.trim() || !selectedConversation.value) return;
    try {
        await axios.post(`/business/api/instagram-chatbot/conversations/${selectedConversation.value.id}/message`, {
            message: messageInput.value,
        });
        messageInput.value = '';
        await openConversation(selectedConversation.value);
    } catch (e) {
        console.error('Error sending message:', e);
        alert('Xabar yuborishda xatolik');
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'active': return 'bg-green-100 text-green-800';
        case 'paused': return 'bg-yellow-100 text-yellow-800';
        case 'draft': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'active': return 'Faol';
        case 'paused': return 'To\'xtatilgan';
        case 'draft': return 'Qoralama';
        default: return status;
    }
};

onMounted(() => {
    if (props.hasAccount) {
        loadAll();
    } else {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="Instagram Chatbot" />
    <BusinessLayout>
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50/30 to-pink-50/30">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 relative overflow-hidden">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIyIi8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-white">Instagram Chatbot</h1>
                                <p class="text-pink-100 text-sm mt-1">DM avtomatizatsiyasi va suhbat boshqaruvi</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="openNewAutomation"
                                class="px-4 py-2.5 bg-white text-purple-600 rounded-xl hover:bg-pink-50 flex items-center gap-2 text-sm font-semibold shadow-lg transition-all hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Yangi avtomat
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <!-- Not Connected State -->
                <div v-if="!hasAccount" class="bg-white rounded-3xl shadow-xl p-8 md:p-12 text-center">
                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-6 shadow-2xl">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Instagram ulanmagan</h3>
                    <p class="text-gray-600 mb-8 max-w-lg mx-auto text-lg">
                        Chatbot ishlatish uchun avval Instagram akkountingizni ulang.
                    </p>
                    <a href="/business/instagram-analysis"
                        class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-2xl hover:from-purple-600 hover:to-pink-700 font-bold shadow-xl transition-all hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        Instagram ulash
                    </a>
                </div>

                <!-- Loading -->
                <div v-else-if="loading" class="bg-white rounded-3xl shadow-xl p-12 text-center">
                    <div class="relative w-20 h-20 mx-auto mb-6">
                        <div class="absolute inset-0 border-4 border-purple-200 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <p class="text-gray-500 text-lg">Yuklanmoqda...</p>
                </div>

                <!-- Main Content -->
                <div v-else class="space-y-6">
                    <!-- Tabs -->
                    <div class="bg-white rounded-2xl shadow-lg p-2 flex gap-2">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                            :class="[
                                'flex-1 px-4 py-3 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2',
                                activeTab === tab.id
                                    ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg'
                                    : 'text-gray-600 hover:bg-gray-100'
                            ]">
                            <!-- Icons -->
                            <svg v-if="tab.icon === 'chart-bar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <svg v-if="tab.icon === 'bolt'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <svg v-if="tab.icon === 'chat'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Dashboard Tab -->
                    <div v-if="activeTab === 'dashboard'" class="space-y-6">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-500">Faol avtomatlar</span>
                                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-900">{{ dashboard?.automations?.active || 0 }}</div>
                                <div class="text-sm text-gray-500">/ {{ dashboard?.automations?.total || 0 }} jami</div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-500">Bugungi triggerlar</span>
                                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-900">{{ dashboard?.triggers?.today || 0 }}</div>
                                <div class="text-sm text-gray-500">{{ dashboard?.triggers?.week || 0 }} haftalik</div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-500">Suhbatlar</span>
                                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-900">{{ dashboard?.conversations?.active || 0 }}</div>
                                <div class="text-sm text-gray-500">{{ dashboard?.conversations?.total || 0 }} jami</div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl transition-all">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-semibold text-gray-500">Avtomatizatsiya</span>
                                    <div class="w-10 h-10 bg-pink-100 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-900">{{ dashboard?.messages?.automation_rate || 0 }}%</div>
                                <div class="text-sm text-gray-500">xabarlar avtomatik</div>
                            </div>
                        </div>

                        <!-- Top Automations & Recent Conversations -->
                        <div class="grid lg:grid-cols-2 gap-6">
                            <!-- Top Automations -->
                            <div class="bg-white rounded-2xl shadow-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Top avtomatlar</h3>
                                <div v-if="dashboard?.top_automations?.length" class="space-y-3">
                                    <div v-for="auto in dashboard.top_automations" :key="auto.id"
                                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                                        <div>
                                            <div class="font-semibold text-gray-900">{{ auto.name }}</div>
                                            <div class="text-sm text-gray-500">{{ auto.triggers }} trigger</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-green-600">{{ auto.conversion_rate }}%</div>
                                            <span :class="getStatusColor(auto.status)" class="text-xs px-2 py-1 rounded-full">
                                                {{ getStatusLabel(auto.status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-500">
                                    Hozircha avtomatlar yo'q
                                </div>
                            </div>

                            <!-- Recent Conversations -->
                            <div class="bg-white rounded-2xl shadow-lg p-6">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">So'nggi suhbatlar</h3>
                                <div v-if="dashboard?.recent_conversations?.length" class="space-y-3">
                                    <div v-for="conv in dashboard.recent_conversations" :key="conv.id"
                                        class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors"
                                        @click="activeTab = 'conversations'; openConversation(conv)">
                                        <img v-if="conv.profile_picture" :src="conv.profile_picture"
                                            class="w-10 h-10 rounded-full" />
                                        <div v-else class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                            <span class="text-purple-600 font-semibold">{{ (conv.username || 'U')[0].toUpperCase() }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-900 truncate">@{{ conv.username || 'Foydalanuvchi' }}</div>
                                            <div class="text-sm text-gray-500 truncate">{{ conv.last_message || 'Xabar yo\'q' }}</div>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ conv.last_message_at }}</div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-500">
                                    Hozircha suhbatlar yo'q
                                </div>
                            </div>
                        </div>

                        <!-- Help Section -->
                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl p-6 text-white">
                            <h3 class="text-xl font-bold mb-3">Chatbot qanday ishlaydi?</h3>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-2xl mb-2">1. Trigger</div>
                                    <p class="text-purple-100 text-sm">Kalit so'z, comment yoki story mention kabi hodisalar triggerlarni ishga tushiradi</p>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-2xl mb-2">2. Action</div>
                                    <p class="text-purple-100 text-sm">Avtomatik DM yuborish, tag qo'shish yoki AI javob berish kabi harakatlar bajariladi</p>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-2xl mb-2">3. Natija</div>
                                    <p class="text-purple-100 text-sm">Lead yig'ish, mijozlarni segmentlash va sotuvlarni oshirish</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Automations Tab -->
                    <div v-if="activeTab === 'automations'" class="space-y-4">
                        <!-- Action Bar -->
                        <div class="bg-white rounded-2xl shadow-lg p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Avtomatlar</h3>
                                    <p class="text-sm text-gray-500">{{ automations.length }} ta avtomat</p>
                                </div>
                            </div>
                            <button @click="openNewAutomation"
                                class="px-5 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all flex items-center gap-2 shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Yangi avtomat
                            </button>
                        </div>

                        <div v-if="automations.length === 0" class="bg-white rounded-2xl shadow-lg p-12 text-center">
                            <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Hozircha avtomatlar yo'q</h3>
                            <p class="text-gray-500 mb-6">Vizual flow builder yordamida oson avtomat yarating</p>
                            <button @click="openNewAutomation"
                                class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    </svg>
                                    Flow Builder orqali yaratish
                                </span>
                            </button>
                        </div>

                        <div v-else class="grid gap-4">
                            <div v-for="automation in automations" :key="automation.id"
                                class="bg-white rounded-2xl shadow-lg p-5 hover:shadow-xl transition-all">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-lg font-bold text-gray-900">{{ automation.name }}</h3>
                                            <span :class="getStatusColor(automation.status)" class="text-xs px-2 py-1 rounded-full font-medium">
                                                {{ getStatusLabel(automation.status) }}
                                            </span>
                                            <span v-if="automation.is_flow_based" class="text-xs px-2 py-1 rounded-full font-medium bg-purple-100 text-purple-700 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                                                </svg>
                                                Flow
                                            </span>
                                        </div>
                                        <p v-if="automation.description" class="text-gray-500 text-sm mb-3">{{ automation.description }}</p>

                                        <!-- Triggers -->
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <span v-for="trigger in automation.triggers" :key="trigger.id"
                                                class="px-3 py-1 bg-purple-100 text-purple-700 text-sm rounded-full">
                                                {{ trigger.trigger_type_label }}
                                                <span v-if="trigger.keywords?.length" class="text-purple-500">
                                                    ({{ trigger.keywords.join(', ') }})
                                                </span>
                                            </span>
                                        </div>

                                        <!-- Stats -->
                                        <div class="flex items-center gap-6 text-sm text-gray-500">
                                            <span>{{ automation.trigger_count }} trigger</span>
                                            <span>{{ automation.conversion_count }} konversiya</span>
                                            <span class="text-green-600 font-medium">{{ automation.conversion_rate }}% CR</span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button @click="toggleAutomation(automation)"
                                            :class="automation.status === 'active' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                                            class="p-2 rounded-lg hover:scale-105 transition-all"
                                            :title="automation.status === 'active' ? 'To\'xtatish' : 'Ishga tushirish'">
                                            <svg v-if="automation.status === 'active'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button v-if="automation.is_flow_based" @click="openFlowEditor(automation)"
                                            class="p-2 bg-purple-100 text-purple-700 rounded-lg hover:scale-105 transition-all"
                                            title="Flow Builder">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                            </svg>
                                        </button>
                                        <button v-else @click="editAutomation(automation)"
                                            class="p-2 bg-blue-100 text-blue-700 rounded-lg hover:scale-105 transition-all"
                                            title="Tahrirlash">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteAutomation(automation)"
                                            class="p-2 bg-red-100 text-red-700 rounded-lg hover:scale-105 transition-all"
                                            title="O'chirish">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversations Tab -->
                    <div v-if="activeTab === 'conversations'" class="grid lg:grid-cols-3 gap-6">
                        <!-- Conversations List -->
                        <div class="lg:col-span-1 bg-white rounded-2xl shadow-lg overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-bold text-gray-900">Suhbatlar</h3>
                            </div>
                            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                                <div v-for="conv in conversations" :key="conv.id"
                                    @click="openConversation(conv)"
                                    :class="selectedConversation?.id === conv.id ? 'bg-purple-50' : 'hover:bg-gray-50'"
                                    class="p-4 cursor-pointer transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                                            <span class="text-purple-600 font-semibold">{{ (conv.participant_username || 'U')[0].toUpperCase() }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-gray-900 truncate">@{{ conv.participant_username || 'Foydalanuvchi' }}</div>
                                            <div class="text-sm text-gray-500 truncate">{{ conv.latestMessage?.content || 'Xabar yo\'q' }}</div>
                                        </div>
                                        <div v-if="conv.needs_human" class="w-3 h-3 bg-red-500 rounded-full"></div>
                                    </div>
                                </div>
                                <div v-if="conversations.length === 0" class="p-8 text-center text-gray-500">
                                    Suhbatlar yo'q
                                </div>
                            </div>
                        </div>

                        <!-- Conversation Detail -->
                        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col" style="height: 600px;">
                            <template v-if="selectedConversation">
                                <!-- Header -->
                                <div class="p-4 border-b border-gray-100 flex items-center gap-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                                        <span class="text-purple-600 font-semibold">{{ (selectedConversation.participant_username || 'U')[0].toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900">@{{ selectedConversation.participant_username }}</div>
                                        <div class="text-sm text-gray-500">{{ selectedConversation.status }}</div>
                                    </div>
                                </div>

                                <!-- Messages -->
                                <div class="flex-1 overflow-y-auto p-4 space-y-4">
                                    <div v-for="msg in conversationMessages" :key="msg.id"
                                        :class="msg.direction === 'outgoing' ? 'flex justify-end' : 'flex justify-start'">
                                        <div :class="msg.direction === 'outgoing' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-900'"
                                            class="max-w-[70%] px-4 py-2 rounded-2xl">
                                            <p>{{ msg.content }}</p>
                                            <div :class="msg.direction === 'outgoing' ? 'text-purple-200' : 'text-gray-400'"
                                                class="text-xs mt-1">
                                                {{ msg.time_ago }}
                                                <span v-if="msg.is_automated" class="ml-1">Bot</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input -->
                                <div class="p-4 border-t border-gray-100">
                                    <div class="flex gap-2">
                                        <input v-model="messageInput" @keyup.enter="sendMessage"
                                            type="text" placeholder="Xabar yozing..."
                                            class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <button @click="sendMessage"
                                            class="px-4 py-2 bg-purple-500 text-white rounded-xl hover:bg-purple-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div v-else class="flex-1 flex items-center justify-center text-gray-500">
                                Suhbat tanlang
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Automation Modal -->
            <div v-if="showAutomationModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">
                                {{ editingAutomation ? 'Avtomatni tahrirlash' : 'Yangi avtomat' }}
                            </h2>
                            <button @click="showAutomationModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nomi *</label>
                            <input v-model="automationForm.name" type="text" placeholder="Masalan: Narx so'rovlariga javob"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tavsif</label>
                            <textarea v-model="automationForm.description" rows="2" placeholder="Avtomat haqida qisqacha"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                        </div>

                        <!-- Trigger -->
                        <div class="bg-purple-50 rounded-2xl p-5">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Trigger (qachon ishga tushadi?)
                            </h3>

                            <div v-for="(trigger, index) in automationForm.triggers" :key="index" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Trigger turi</label>
                                    <select v-model="trigger.trigger_type"
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <option v-for="type in triggerTypes" :key="type.value" :value="type.value">
                                            {{ type.label }} - {{ type.description }}
                                        </option>
                                    </select>
                                </div>

                                <div v-if="trigger.trigger_type === 'keyword_dm' || trigger.trigger_type === 'keyword_comment'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Kalit so'zlar</label>
                                    <div class="flex gap-2 mb-2">
                                        <input v-model="newKeyword" @keyup.enter="addKeyword(index)" type="text"
                                            placeholder="Kalit so'z kiriting"
                                            class="flex-1 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                        <button @click="addKeyword(index)" type="button"
                                            class="px-4 py-2 bg-purple-500 text-white rounded-xl hover:bg-purple-600">
                                            Qo'shish
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="(kw, kwIndex) in trigger.keywords" :key="kwIndex"
                                            class="px-3 py-1 bg-white border border-purple-200 text-purple-700 rounded-full text-sm flex items-center gap-2">
                                            {{ kw }}
                                            <button @click="removeKeyword(index, kwIndex)" type="button" class="text-purple-400 hover:text-purple-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-pink-50 rounded-2xl p-5">
                            <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Harakatlar (nima qiladi?)
                            </h3>

                            <div v-for="(action, index) in automationForm.actions" :key="index" class="mb-4 p-4 bg-white rounded-xl">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium text-gray-500">Harakat {{ index + 1 }}</span>
                                    <button v-if="automationForm.actions.length > 1" @click="removeAction(index)" type="button"
                                        class="text-red-400 hover:text-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <select v-model="action.action_type"
                                        class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                        <option v-for="type in actionTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>

                                    <div v-if="action.action_type === 'send_dm'">
                                        <textarea v-model="action.message_template" rows="3"
                                            placeholder="Xabar matni... {name} - foydalanuvchi ismi"
                                            class="w-full px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">O'zgaruvchilar: {name}, {username}</p>
                                    </div>

                                    <div v-if="action.action_type === 'delay'" class="flex items-center gap-3">
                                        <input v-model.number="action.delay_seconds" type="number" min="1" max="3600"
                                            placeholder="Soniyalar"
                                            class="w-32 px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                                        <span class="text-gray-500">soniya kutish</span>
                                    </div>
                                </div>
                            </div>

                            <button @click="addAction" type="button"
                                class="w-full py-2 border-2 border-dashed border-pink-300 text-pink-600 rounded-xl hover:bg-pink-50 transition-colors">
                                + Harakat qo'shish
                            </button>
                        </div>

                        <!-- Status -->
                        <div class="flex items-center gap-4">
                            <label class="block text-sm font-semibold text-gray-700">Status:</label>
                            <select v-model="automationForm.status"
                                class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="draft">Qoralama</option>
                                <option value="active">Faol</option>
                                <option value="paused">To'xtatilgan</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-6 border-t border-gray-100 flex justify-end gap-3">
                        <button @click="showAutomationModal = false"
                            class="px-6 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Bekor qilish
                        </button>
                        <button @click="saveAutomation"
                            class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all">
                            {{ editingAutomation ? 'Saqlash' : 'Yaratish' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flow Builder -->
            <FlowBuilder
                :is-open="showFlowBuilder"
                :automation="editingAutomation"
                @close="showFlowBuilder = false; editingAutomation = null"
                @saved="onFlowBuilderSaved"
            />
        </div>
    </BusinessLayout>
</template>
