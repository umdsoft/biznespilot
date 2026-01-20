<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import InstagramFlowBuilder from '@/components/instagram/InstagramFlowBuilder.vue';
import axios from 'axios';
import { useI18n } from '@/i18n';

const { t } = useI18n();

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
const flowBuilderData = ref({ nodes: [], connections: [] });

// Wizard steps
const wizardStep = ref(1);
const totalSteps = 3;

// Trigger categories with icons and descriptions
const triggerCategories = [
    {
        id: 'dm',
        name: 'DM Triggerlar',
        icon: 'chat',
        color: 'from-purple-500 to-indigo-600',
        triggers: [
            { type: 'keyword_dm', name: 'Kalit so\'z', description: 'DM da ma\'lum so\'z yozilganda', icon: 'key' },
            { type: 'any_dm', name: 'Har qanday xabar', description: 'Istalgan DM kelganda', icon: 'inbox' },
        ]
    },
    {
        id: 'engagement',
        name: 'Faollik Triggerlar',
        icon: 'heart',
        color: 'from-pink-500 to-rose-600',
        triggers: [
            { type: 'keyword_comment', name: 'Comment kalit so\'z', description: 'Commentda kalit so\'z bo\'lganda', icon: 'comment' },
            { type: 'story_mention', name: 'Story mention', description: 'Sizni storyda etiketlaganda', icon: 'at' },
            { type: 'story_reply', name: 'Story javob', description: 'Storyngizga javob kelganda', icon: 'reply' },
        ]
    },
    {
        id: 'follower',
        name: 'Follower Triggerlar',
        icon: 'users',
        color: 'from-blue-500 to-cyan-600',
        triggers: [
            { type: 'new_follower', name: 'Yangi follower', description: 'Kimdir follow qilganda', icon: 'user-plus' },
        ]
    }
];

// Action types with icons
const actionCategories = [
    { type: 'send_dm', name: 'DM yuborish', description: 'Foydalanuvchiga xabar yuborish', icon: 'paper-airplane', color: 'purple' },
    { type: 'send_media', name: 'Media yuborish', description: 'Rasm yoki video yuborish', icon: 'photo', color: 'pink' },
    { type: 'delay', name: 'Kutish', description: 'Ma\'lum vaqt kutish', icon: 'clock', color: 'blue' },
    { type: 'add_tag', name: 'Tag qo\'shish', description: 'Foydalanuvchiga tag biriktirish', icon: 'tag', color: 'green' },
    { type: 'ai_response', name: 'AI javob', description: 'Sun\'iy intellekt orqali javob', icon: 'sparkles', color: 'amber' },
];

// New automation form
const automationForm = ref({
    name: '',
    description: '',
    type: 'keyword',
    status: 'draft',
    is_ai_enabled: false,
    triggers: [{ trigger_type: 'keyword_dm', keywords: [], exact_match: false }],
    actions: [{ action_type: 'send_dm', message_template: '', delay_seconds: 0 }],
});

const newKeyword = ref('');

// Wizard navigation
const nextStep = () => {
    if (wizardStep.value < totalSteps) {
        wizardStep.value++;
    }
};

const prevStep = () => {
    if (wizardStep.value > 1) {
        wizardStep.value--;
    }
};

const canProceed = computed(() => {
    if (wizardStep.value === 1) {
        return automationForm.value.triggers[0]?.trigger_type;
    }
    if (wizardStep.value === 2) {
        const hasAction = automationForm.value.actions.length > 0;
        const firstAction = automationForm.value.actions[0];
        if (firstAction?.action_type === 'send_dm') {
            return hasAction && firstAction.message_template?.trim();
        }
        return hasAction;
    }
    if (wizardStep.value === 3) {
        return automationForm.value.name?.trim();
    }
    return true;
});

// Select trigger type
const selectTrigger = (triggerType) => {
    automationForm.value.triggers[0].trigger_type = triggerType;
    automationForm.value.triggers[0].keywords = [];
};

// Get selected trigger info
const selectedTriggerInfo = computed(() => {
    const selectedType = automationForm.value.triggers[0]?.trigger_type;
    for (const category of triggerCategories) {
        const trigger = category.triggers.find(t => t.type === selectedType);
        if (trigger) {
            return { ...trigger, category };
        }
    }
    return null;
});
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

// API base path
const apiBase = '/integrations/instagram/chatbot/api';

// Load data
const loadDashboard = async () => {
    try {
        const response = await axios.get(`${apiBase}/dashboard`);
        dashboard.value = response.data;
    } catch (e) {
        console.error('Error loading dashboard:', e);
    }
};

const loadAutomations = async () => {
    try {
        const response = await axios.get(`${apiBase}/automations`);
        automations.value = response.data.automations || [];
    } catch (e) {
        console.error('Error loading automations:', e);
    }
};

const loadConversations = async () => {
    try {
        const response = await axios.get(`${apiBase}/conversations`);
        conversations.value = response.data.data || [];
    } catch (e) {
        console.error('Error loading conversations:', e);
    }
};

const loadTriggerTypes = async () => {
    try {
        const response = await axios.get(`${apiBase}/trigger-types`);
        triggerTypes.value = response.data.trigger_types || [];
    } catch (e) {
        console.error('Error loading trigger types:', e);
    }
};

const loadActionTypes = async () => {
    try {
        const response = await axios.get(`${apiBase}/action-types`);
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
    automationForm.value = {
        name: '',
        description: '',
        type: 'flow',
        status: 'draft',
        is_ai_enabled: false,
    };
    flowBuilderData.value = { nodes: [], connections: [] };
    showFlowBuilder.value = true;
};

const editAutomation = (automation) => {
    // To'g'ridan-to'g'ri visual builderga o'tkazish
    openFlowBuilderForEdit(automation);
};

const saveAutomation = async () => {
    try {
        if (editingAutomation.value) {
            await axios.put(`${apiBase}/automations/${editingAutomation.value.id}`, automationForm.value);
        } else {
            await axios.post(`${apiBase}/automations`, automationForm.value);
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
        await axios.post(`${apiBase}/automations/${automation.id}/toggle`);
        await loadAutomations();
    } catch (e) {
        console.error('Error toggling automation:', e);
    }
};

const deleteAutomation = async (automation) => {
    if (!confirm('Rostdan ham o\'chirmoqchimisiz?')) return;
    try {
        await axios.delete(`${apiBase}/automations/${automation.id}`);
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
        const response = await axios.get(`${apiBase}/conversations/${conversation.id}`);
        conversationMessages.value = response.data.messages || [];
    } catch (e) {
        console.error('Error loading conversation:', e);
    }
};

const sendMessage = async () => {
    if (!messageInput.value.trim() || !selectedConversation.value) return;
    try {
        await axios.post(`${apiBase}/conversations/${selectedConversation.value.id}/message`, {
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
        case 'active': return 'bg-green-500/20 text-green-600 dark:text-green-400 border border-green-500/30';
        case 'paused': return 'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400 border border-yellow-500/30';
        case 'draft': return 'bg-gray-500/20 text-gray-600 dark:text-gray-400 border border-gray-500/30';
        default: return 'bg-gray-500/20 text-gray-600 dark:text-gray-400 border border-gray-500/30';
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

const getStatusBorderColor = (status) => {
    switch (status) {
        case 'active': return 'border-l-green-500';
        case 'paused': return 'border-l-yellow-500';
        case 'draft': return 'border-l-gray-400';
        default: return 'border-l-gray-400';
    }
};

const getStatusIcon = (status) => {
    switch (status) {
        case 'active': return 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z';
        case 'paused': return 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z';
        case 'draft': return 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';
        default: return 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    }
};

// Flow Builder Functions
const openFlowBuilder = () => {
    showAutomationModal.value = false;
    flowBuilderData.value = { nodes: [], connections: [] };
    showFlowBuilder.value = true;
};

const openFlowBuilderForEdit = (automation) => {
    editingAutomation.value = automation;
    // Load existing flow data if available and transform from backend format
    if (automation.flow_data) {
        const backendNodes = automation.flow_data.nodes || [];
        const backendEdges = automation.flow_data.edges || [];

        // Transform backend format to frontend format
        const nodes = backendNodes.map(node => ({
            id: node.node_id,
            type: node.node_type,
            name: node.data?.name || getNodeLabelByType(node.node_type),
            x: node.position?.x || 100,
            y: node.position?.y || 100,
            data: node.data || {},
            keywords: node.data?.keywords || []
        }));

        const connections = backendEdges.map(edge => ({
            from: edge.source_node_id,
            to: edge.target_node_id,
            type: edge.source_handle || 'default'
        }));

        flowBuilderData.value = { nodes, connections };
    } else {
        flowBuilderData.value = { nodes: [], connections: [] };
    }
    showFlowBuilder.value = true;
};

// Helper function to get node label by type
const getNodeLabelByType = (type) => {
    const labels = {
        'trigger_keyword_dm': 'DM Kalit so\'z',
        'trigger_keyword_comment': 'Comment Kalit so\'z',
        'trigger_story_mention': 'Story Mention',
        'trigger_story_reply': 'Story Javob',
        'trigger_new_follower': 'Yangi Follower',
        'action_send_dm': 'DM Yuborish',
        'action_reply_comment': 'Commentga Javob',
        'action_ai_response': 'AI Javob',
        'action_delay': 'Kutish',
        'action_add_tag': 'Tag Qo\'shish',
        'condition_keyword': 'Kalit So\'z Sharti',
        'condition_is_follower': 'Follower Tekshirish',
        'condition_has_tag': 'Tag Tekshirish',
    };
    return labels[type] || 'Element';
};

const closeFlowBuilder = () => {
    showFlowBuilder.value = false;
    editingAutomation.value = null;
    flowBuilderData.value = { nodes: [], connections: [] };
};

const saveFlowBuilderData = async (flowData) => {
    try {
        // Transform nodes to backend format
        const nodes = (flowData.nodes || []).map(node => ({
            node_id: node.id,
            node_type: node.type,
            position: { x: node.x, y: node.y },
            data: {
                ...node.data,
                keywords: node.keywords || [],
                name: node.name
            }
        }));

        // Transform connections to edges format
        const edges = (flowData.connections || []).map((conn, index) => ({
            edge_id: `edge-${index}-${Date.now()}`,
            source_node_id: conn.from,
            target_node_id: conn.to,
            source_handle: conn.type !== 'default' ? conn.type : null
        }));

        const payload = {
            name: flowData.settings?.name || 'Yangi Flow',
            description: flowData.settings?.description || '',
            status: flowData.settings?.status || 'draft',
            nodes: nodes,
            edges: edges
        };

        if (editingAutomation.value) {
            await axios.put(`${apiBase}/flow-automations/${editingAutomation.value.id}`, payload);
        } else {
            await axios.post(`${apiBase}/flow-automations`, payload);
        }

        closeFlowBuilder();
        await loadAutomations();
    } catch (e) {
        console.error('Error saving flow:', e);
        // Show detailed error message
        let errorMessage = 'Saqlashda xatolik yuz berdi';
        if (e.response?.data?.error) {
            errorMessage = e.response.data.error;
        } else if (e.response?.data?.message) {
            errorMessage = e.response.data.message;
        } else if (e.response?.data?.errors) {
            const errors = Object.values(e.response.data.errors).flat();
            errorMessage = errors.join('\n');
        }
        alert(errorMessage);
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
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 relative overflow-hidden -mx-4 sm:-mx-6 lg:-mx-8 -mt-6 mb-6">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIyIi8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>
            <div class="px-4 sm:px-6 lg:px-8 py-6 relative">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-bold text-white">Instagram Chatbot</h1>
                            <p class="text-pink-100 text-sm">DM avtomatizatsiyasi va suhbat boshqaruvi</p>
                        </div>
                    </div>
                    <button @click="openNewAutomation"
                        class="px-4 py-2 bg-white text-purple-600 rounded-lg hover:bg-pink-50 flex items-center gap-2 text-sm font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi avtomat
                    </button>
                </div>
            </div>
        </div>

        <!-- Not Connected State -->
        <div v-if="!hasAccount" class="rounded-2xl p-8 md:p-12 text-center bg-white dark:bg-gray-800/30 shadow-sm">
            <div class="mx-auto w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Instagram ulanmagan</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-md mx-auto">
                Chatbot ishlatish uchun avval Instagram akkountingizni ulang.
            </p>
            <a href="/integrations/instagram"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl hover:from-purple-600 hover:to-pink-700 font-semibold transition-all">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Instagram ulash
            </a>
        </div>

        <!-- Loading -->
        <div v-else-if="loading" class="rounded-2xl p-12 text-center">
            <div class="relative w-16 h-16 mx-auto mb-4">
                <div class="absolute inset-0 border-4 border-purple-200 dark:border-purple-900/50 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <p class="text-gray-500 dark:text-gray-400">Yuklanmoqda...</p>
        </div>

        <!-- Main Content -->
        <div v-else class="space-y-5">
            <!-- Tabs -->
            <div class="bg-gray-100 dark:bg-gray-800/50 rounded-xl p-1.5 flex gap-1.5">
                <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
                    :class="[
                        'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2',
                        activeTab === tab.id
                            ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700/50'
                    ]">
                    <svg v-if="tab.icon === 'chart-bar'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <svg v-if="tab.icon === 'bolt'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <svg v-if="tab.icon === 'chat'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    {{ tab.label }}
                </button>
            </div>

            <!-- Dashboard Tab -->
            <div v-if="activeTab === 'dashboard'" class="space-y-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Faol avtomatlar -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl p-5 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all group shadow-sm border border-gray-100 dark:border-transparent">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Faol avtomatlar</span>
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ dashboard?.automations?.active || 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">/ {{ dashboard?.automations?.total || 0 }} jami</div>
                    </div>

                    <!-- Bugungi triggerlar -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl p-5 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all group shadow-sm border border-gray-100 dark:border-transparent">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Bugungi triggerlar</span>
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ dashboard?.triggers?.today || 0 }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ dashboard?.triggers?.week || 0 }} haftalik</div>
                    </div>

                    <!-- Suhbatlar -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl p-5 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all group shadow-sm border border-gray-100 dark:border-transparent">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Suhbatlar</span>
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ dashboard?.conversations?.active || 0 }}</div>
                        <div class="text-sm text-gray-500">{{ dashboard?.conversations?.total || 0 }} jami</div>
                    </div>

                    <!-- Avtomatizatsiya -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent p-5 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all group">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Avtomatizatsiya</span>
                            <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ dashboard?.messages?.automation_rate || 0 }}%</div>
                        <div class="text-sm text-gray-500">xabarlar avtomatik</div>
                    </div>
                </div>

                <!-- Top Automations & Recent Conversations -->
                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Top Automations -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Top avtomatlar</h3>
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                        <div v-if="dashboard?.top_automations?.length" class="space-y-3">
                            <div v-for="auto in dashboard.top_automations" :key="auto.id"
                                class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700/50 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors cursor-pointer">
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ auto.name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ auto.triggers }} trigger
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-green-400">{{ auto.conversion_rate }}%</div>
                                    <span :class="getStatusColor(auto.status)" class="text-xs px-2.5 py-1 rounded-full font-medium">
                                        {{ getStatusLabel(auto.status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <p class="text-gray-400">Hozircha avtomatlar yo'q</p>
                            <button @click="openNewAutomation" class="mt-3 text-sm text-purple-400 hover:underline">
                                Yangi avtomat yaratish
                            </button>
                        </div>
                    </div>

                    <!-- Recent Conversations -->
                    <div class="bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">So'nggi suhbatlar</h3>
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                        </div>
                        <div v-if="dashboard?.recent_conversations?.length" class="space-y-3">
                            <div v-for="conv in dashboard.recent_conversations" :key="conv.id"
                                class="flex items-center gap-3 p-3 bg-gray-100 dark:bg-gray-700/50 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                                @click="activeTab = 'conversations'; openConversation(conv)">
                                <img v-if="conv.profile_picture" :src="conv.profile_picture"
                                    class="w-11 h-11 rounded-full object-cover" />
                                <div v-else class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                    <span class="text-white font-bold">{{ (conv.username || 'U')[0].toUpperCase() }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white truncate">@{{ conv.username || 'Foydalanuvchi' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ conv.last_message || 'Xabar yo\'q' }}</div>
                                </div>
                                <div class="text-xs text-gray-500">{{ conv.last_message_at }}</div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <p class="text-gray-400">Hozircha suhbatlar yo'q</p>
                            <p class="text-xs text-gray-500 mt-1">Suhbatlar avtomatik paydo bo'ladi</p>
                        </div>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 rounded-2xl p-6 text-white relative overflow-hidden">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIyIi8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>
                    <div class="relative">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold">Chatbot qanday ishlaydi?</h3>
                        </div>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 hover:bg-white/20 transition-colors">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">1</div>
                                    <span class="font-bold text-lg">Trigger</span>
                                </div>
                                <p class="text-white/80 text-sm leading-relaxed">Kalit so'z, comment yoki story mention kabi hodisalar triggerlarni ishga tushiradi</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 hover:bg-white/20 transition-colors">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">2</div>
                                    <span class="font-bold text-lg">Action</span>
                                </div>
                                <p class="text-white/80 text-sm leading-relaxed">Avtomatik DM yuborish, tag qo'shish yoki AI javob berish kabi harakatlar bajariladi</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 hover:bg-white/20 transition-colors">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-xl font-bold">3</div>
                                    <span class="font-bold text-lg">Natija</span>
                                </div>
                                <p class="text-white/80 text-sm leading-relaxed">Lead yig'ish, mijozlarni segmentlash va sotuvlarni oshirish</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Automations Tab -->
            <div v-if="activeTab === 'automations'" class="space-y-4">
                <!-- Action Bar -->
                <div class="bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Avtomatlar</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ automations.length }} ta avtomat</p>
                        </div>
                    </div>
                    <button @click="openNewAutomation"
                        class="px-5 py-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yangi avtomat
                    </button>
                </div>

                <div v-if="automations.length === 0" class="bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent p-12 text-center">
                    <div class="w-20 h-20 bg-purple-100 dark:bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Hozircha avtomatlar yo'q</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Yangi avtomat yaratib Instagram DM avtomatizatsiyasini boshlang</p>
                    <button @click="openNewAutomation"
                        class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-600 transition-all">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Yangi avtomat yaratish
                        </span>
                    </button>
                </div>

                <div v-else class="grid gap-4">
                    <div v-for="automation in automations" :key="automation.id"
                        :class="[
                            'bg-white dark:bg-gray-800/60 rounded-2xl shadow-sm border border-gray-100 dark:border-transparent overflow-hidden transition-all hover:shadow-md',
                            'border-l-4',
                            getStatusBorderColor(automation.status)
                        ]">
                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <!-- Left side: Status indicator + Content -->
                                <div class="flex gap-4 flex-1">
                                    <!-- Status Indicator Circle -->
                                    <div class="flex-shrink-0 relative">
                                        <div :class="[
                                            'w-12 h-12 rounded-xl flex items-center justify-center relative',
                                            automation.status === 'active' ? 'bg-green-500/20' : automation.status === 'paused' ? 'bg-yellow-500/20' : 'bg-gray-200 dark:bg-gray-700'
                                        ]">
                                            <div :class="[
                                                'w-8 h-8 rounded-lg flex items-center justify-center z-10',
                                                automation.status === 'active' ? 'bg-green-500 text-white' : automation.status === 'paused' ? 'bg-yellow-500 text-white' : 'bg-gray-400 dark:bg-gray-500 text-white'
                                            ]">
                                                <svg v-if="automation.status === 'active'" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z"/>
                                                </svg>
                                                <svg v-else-if="automation.status === 'paused'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <!-- Pulse animation for active -->
                                        <div v-if="automation.status === 'active'" class="absolute inset-0 w-12 h-12 rounded-xl bg-green-500/40 animate-ping" style="animation-duration: 2s;"></div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ automation.name }}</h3>
                                            <span :class="getStatusColor(automation.status)" class="text-xs px-3 py-1 rounded-full font-semibold inline-flex items-center gap-1.5">
                                                <span :class="[
                                                    'w-2 h-2 rounded-full',
                                                    automation.status === 'active' ? 'bg-green-500 animate-pulse' : automation.status === 'paused' ? 'bg-yellow-500' : 'bg-gray-400'
                                                ]"></span>
                                                {{ getStatusLabel(automation.status) }}
                                            </span>
                                            <span v-if="automation.is_flow_based" class="text-xs px-2.5 py-1 rounded-full font-medium bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                                </svg>
                                                Flow
                                            </span>
                                        </div>
                                        <p v-if="automation.description" class="text-gray-500 dark:text-gray-400 text-sm mb-3">{{ automation.description }}</p>

                                        <!-- Triggers -->
                                        <div v-if="automation.triggers?.length" class="flex flex-wrap gap-2 mb-3">
                                            <span v-for="trigger in automation.triggers" :key="trigger.id"
                                                class="px-3 py-1.5 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 text-sm rounded-full">
                                                {{ trigger.trigger_type_label }}
                                                <span v-if="trigger.keywords?.length" class="text-purple-500 dark:text-purple-400">
                                                    ({{ trigger.keywords.join(', ') }})
                                                </span>
                                            </span>
                                        </div>

                                        <!-- Stats -->
                                        <div class="flex items-center gap-6 text-sm">
                                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ automation.trigger_count || 0 }}</span> trigger
                                            </span>
                                            <span class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ automation.conversion_count || 0 }}</span> konversiya
                                            </span>
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                                <span class="font-bold text-green-600 dark:text-green-400">{{ automation.conversion_rate || 0 }}%</span>
                                                <span class="text-gray-400">CR</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 ml-4">
                                    <!-- Toggle Button - More prominent -->
                                    <button @click="toggleAutomation(automation)"
                                        :class="[
                                            'px-4 py-2 rounded-xl font-medium text-sm flex items-center gap-2 transition-all hover:scale-105',
                                            automation.status === 'active'
                                                ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/60'
                                                : 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/60'
                                        ]"
                                        :title="automation.status === 'active' ? 'To\'xtatish' : 'Ishga tushirish'">
                                        <svg v-if="automation.status === 'active'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                                        </svg>
                                        <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                        {{ automation.status === 'active' ? 'To\'xtatish' : 'Ishga tushirish' }}
                                    </button>

                                    <div class="flex items-center gap-1">
                                        <button @click="openFlowBuilderForEdit(automation)"
                                            class="p-2.5 bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-900/60 transition-all"
                                            title="Visual Builder">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                            </svg>
                                        </button>
                                        <button @click="editAutomation(automation)"
                                            class="p-2.5 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-900/60 transition-all"
                                            title="Tahrirlash">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button @click="deleteAutomation(automation)"
                                            class="p-2.5 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300 rounded-xl hover:bg-red-200 dark:hover:bg-red-900/60 transition-all"
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
                </div>
            </div>

            <!-- Conversations Tab -->
            <div v-if="activeTab === 'conversations'" class="grid lg:grid-cols-3 gap-6">
                <!-- Conversations List -->
                <div class="lg:col-span-1 bg-white dark:bg-gray-800/60 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-transparent">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white">Suhbatlar</h3>
                        <span class="text-xs px-2 py-1 bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 rounded-full">
                            {{ conversations.length }}
                        </span>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700/50 max-h-[600px] overflow-y-auto">
                        <div v-for="conv in conversations" :key="conv.id"
                            @click="openConversation(conv)"
                            :class="selectedConversation?.id === conv.id ? 'bg-purple-100 dark:bg-purple-900/30' : 'hover:bg-gray-100 dark:hover:bg-gray-700/50'"
                            class="p-4 cursor-pointer transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <span class="text-white font-bold">{{ (conv.participant_username || 'U')[0].toUpperCase() }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-gray-900 dark:text-white truncate">@{{ conv.participant_username || 'Foydalanuvchi' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ conv.latestMessage?.content || 'Xabar yo\'q' }}</div>
                                </div>
                                <div v-if="conv.needs_human" class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                            </div>
                        </div>
                        <div v-if="conversations.length === 0" class="p-10 text-center">
                            <div class="w-14 h-14 bg-gray-200 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <p class="text-gray-400 text-sm">Suhbatlar yo'q</p>
                        </div>
                    </div>
                </div>

                <!-- Conversation Detail -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800/60 rounded-2xl overflow-hidden flex flex-col shadow-sm border border-gray-100 dark:border-transparent" style="height: 600px;">
                    <template v-if="selectedConversation">
                        <!-- Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700/50 flex items-center gap-3 bg-gray-50 dark:bg-gray-700/30">
                            <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold">{{ (selectedConversation.participant_username || 'U')[0].toUpperCase() }}</span>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-gray-900 dark:text-white">@{{ selectedConversation.participant_username }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    {{ selectedConversation.status }}
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-100 dark:bg-gray-900/30">
                            <div v-for="msg in conversationMessages" :key="msg.id"
                                :class="msg.direction === 'outgoing' ? 'flex justify-end' : 'flex justify-start'">
                                <div :class="msg.direction === 'outgoing' ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'"
                                    class="max-w-[70%] px-4 py-3 rounded-2xl">
                                    <p class="leading-relaxed">{{ msg.content }}</p>
                                    <div :class="msg.direction === 'outgoing' ? 'text-white/70' : 'text-gray-500 dark:text-gray-400'"
                                        class="text-xs mt-1.5 flex items-center gap-2">
                                        {{ msg.time_ago }}
                                        <span v-if="msg.is_automated" class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 rounded text-[10px] uppercase">Bot</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input -->
                        <div class="p-4 border-t border-gray-200 dark:border-gray-700/50 bg-gray-50 dark:bg-gray-800/50">
                            <div class="flex gap-2">
                                <input v-model="messageInput" @keyup.enter="sendMessage"
                                    type="text" placeholder="Xabar yozing..."
                                    class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-400">
                                <button @click="sendMessage"
                                    class="px-5 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:to-pink-600 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400">
                        <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">Suhbat tanlang</p>
                        <p class="text-sm text-gray-500 mt-1">Chap paneldan suhbatni tanlang</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Automation Modal - ChatPlace Style Wizard -->
        <div v-if="showAutomationModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-gray-900 rounded-3xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <!-- Header with Progress -->
                <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-rose-500 p-6 relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIyIi8+PC9nPjwvZz48L3N2Zz4=')] opacity-30"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-white flex items-center gap-3">
                                <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                {{ editingAutomation ? 'Avtomatni tahrirlash' : 'Yangi avtomat yaratish' }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <button @click="openFlowBuilder" class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white rounded-lg transition-colors text-sm font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                    </svg>
                                    Visual Builder
                                </button>
                                <button @click="showAutomationModal = false" class="text-white/70 hover:text-white p-2 hover:bg-white/10 rounded-lg transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Step Progress -->
                        <div class="flex items-center justify-between">
                            <div v-for="step in 3" :key="step" class="flex items-center" :class="step < 3 ? 'flex-1' : ''">
                                <div class="flex items-center gap-2">
                                    <div :class="[
                                        'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all',
                                        wizardStep >= step ? 'bg-white text-purple-600' : 'bg-white/20 text-white/60'
                                    ]">
                                        {{ step }}
                                    </div>
                                    <span class="text-sm font-medium text-white/90 hidden sm:block">
                                        {{ step === 1 ? 'Trigger' : step === 2 ? 'Harakat' : 'Sozlamalar' }}
                                    </span>
                                </div>
                                <div v-if="step < 3" class="flex-1 h-0.5 mx-3" :class="wizardStep > step ? 'bg-white' : 'bg-white/20'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Step 1: Trigger Selection -->
                    <div v-if="wizardStep === 1" class="space-y-6">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-white mb-2">Qachon ishga tushsin?</h3>
                            <p class="text-gray-400">Avtomatni qanday holatda boshlashini tanlang</p>
                        </div>

                        <!-- Trigger Categories -->
                        <div v-for="category in triggerCategories" :key="category.id" class="space-y-3">
                            <div class="flex items-center gap-2 mb-2">
                                <div :class="['w-6 h-6 rounded-lg bg-gradient-to-br flex items-center justify-center', category.color]">
                                    <svg v-if="category.icon === 'chat'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <svg v-else-if="category.icon === 'heart'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    <svg v-else class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-300">{{ category.name }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button v-for="trigger in category.triggers" :key="trigger.type"
                                    @click="selectTrigger(trigger.type)"
                                    :class="[
                                        'p-4 rounded-xl text-left transition-all border-2',
                                        automationForm.triggers[0]?.trigger_type === trigger.type
                                            ? 'bg-purple-500/20 border-purple-500 ring-2 ring-purple-500/30'
                                            : 'bg-gray-800/50 border-gray-700 hover:border-gray-600 hover:bg-gray-800'
                                    ]">
                                    <div class="flex items-start gap-3">
                                        <div :class="[
                                            'w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0',
                                            automationForm.triggers[0]?.trigger_type === trigger.type ? 'bg-purple-500' : 'bg-gray-700'
                                        ]">
                                            <svg v-if="trigger.icon === 'key'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <svg v-else-if="trigger.icon === 'inbox'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                            <svg v-else-if="trigger.icon === 'comment'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                            <svg v-else-if="trigger.icon === 'at'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                            </svg>
                                            <svg v-else-if="trigger.icon === 'reply'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ trigger.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ trigger.description }}</div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Keyword Input (if keyword trigger selected) -->
                        <div v-if="automationForm.triggers[0]?.trigger_type === 'keyword_dm' || automationForm.triggers[0]?.trigger_type === 'keyword_comment'"
                            class="bg-gray-800/50 rounded-2xl p-5 mt-6 border border-gray-700">
                            <h4 class="font-semibold text-white mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                                Kalit so'zlarni kiriting
                            </h4>
                            <div class="flex gap-2 mb-3">
                                <input v-model="newKeyword" @keyup.enter="addKeyword(0)" type="text"
                                    placeholder="Masalan: narx, baho, price..."
                                    class="flex-1 px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-400">
                                <button @click="addKeyword(0)" type="button"
                                    class="px-5 py-3 bg-purple-500 text-white rounded-xl hover:bg-purple-600 font-medium transition-colors">
                                    Qo'shish
                                </button>
                            </div>
                            <div v-if="automationForm.triggers[0]?.keywords?.length" class="flex flex-wrap gap-2">
                                <span v-for="(kw, kwIndex) in automationForm.triggers[0].keywords" :key="kwIndex"
                                    class="px-3 py-2 bg-purple-500/20 border border-purple-500/50 text-purple-300 rounded-xl text-sm flex items-center gap-2 group">
                                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                    </svg>
                                    {{ kw }}
                                    <button @click="removeKeyword(0, kwIndex)" type="button" class="text-purple-400 hover:text-white transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-500">Hech bo'lmaganda bitta kalit so'z kiriting</p>
                        </div>
                    </div>

                    <!-- Step 2: Action Configuration -->
                    <div v-if="wizardStep === 2" class="space-y-6">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-white mb-2">Nima qilsin?</h3>
                            <p class="text-gray-400">Trigger ishga tushganda qanday harakat bajarilsin</p>
                        </div>

                        <!-- Selected Trigger Summary -->
                        <div v-if="selectedTriggerInfo" class="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4 flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-500 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm text-purple-400">Trigger</div>
                                <div class="font-semibold text-gray-900 dark:text-white">{{ selectedTriggerInfo.name }}</div>
                            </div>
                            <div v-if="automationForm.triggers[0]?.keywords?.length" class="ml-auto flex gap-1">
                                <span v-for="kw in automationForm.triggers[0].keywords.slice(0, 3)" :key="kw"
                                    class="px-2 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-lg">#{{ kw }}</span>
                                <span v-if="automationForm.triggers[0].keywords.length > 3"
                                    class="px-2 py-1 bg-purple-500/20 text-purple-300 text-xs rounded-lg">+{{ automationForm.triggers[0].keywords.length - 3 }}</span>
                            </div>
                        </div>

                        <!-- Actions Flow -->
                        <div class="space-y-4">
                            <div v-for="(action, index) in automationForm.actions" :key="index" class="relative">
                                <!-- Connection Line -->
                                <div v-if="index > 0" class="absolute -top-4 left-5 w-0.5 h-4 bg-gradient-to-b from-pink-500 to-purple-500"></div>

                                <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-purple-500 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                                                {{ index + 1 }}
                                            </div>
                                            <span class="font-medium text-white">Harakat</span>
                                        </div>
                                        <button v-if="automationForm.actions.length > 1" @click="removeAction(index)" type="button"
                                            class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Action Type Selection -->
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                                        <button v-for="actionType in actionCategories" :key="actionType.type"
                                            @click="action.action_type = actionType.type"
                                            :class="[
                                                'p-3 rounded-xl text-left transition-all border',
                                                action.action_type === actionType.type
                                                    ? 'bg-pink-500/20 border-pink-500'
                                                    : 'bg-gray-700/50 border-gray-600 hover:border-gray-500'
                                            ]">
                                            <div class="flex items-center gap-2">
                                                <div :class="[
                                                    'w-8 h-8 rounded-lg flex items-center justify-center',
                                                    action.action_type === actionType.type ? 'bg-pink-500' : 'bg-gray-600'
                                                ]">
                                                    <svg v-if="actionType.icon === 'paper-airplane'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                    </svg>
                                                    <svg v-else-if="actionType.icon === 'clock'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <svg v-else-if="actionType.icon === 'tag'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                    <svg v-else-if="actionType.icon === 'sparkles'" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                                    </svg>
                                                    <svg v-else class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <span class="text-sm font-medium" :class="action.action_type === actionType.type ? 'text-white' : 'text-gray-300'">
                                                    {{ actionType.name }}
                                                </span>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Action Config -->
                                    <div v-if="action.action_type === 'send_dm'" class="space-y-3">
                                        <textarea v-model="action.message_template" rows="4"
                                            placeholder="Salom {name}! Bizga yozganingiz uchun rahmat..."
                                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent placeholder-gray-400 resize-none"></textarea>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-500">O'zgaruvchilar:</span>
                                            <div class="flex gap-2">
                                                <button type="button" @click="action.message_template += ' {name}'" class="px-2 py-1 bg-gray-700 text-gray-300 rounded hover:bg-gray-600">{name}</button>
                                                <button type="button" @click="action.message_template += ' {username}'" class="px-2 py-1 bg-gray-700 text-gray-300 rounded hover:bg-gray-600">{username}</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="action.action_type === 'delay'" class="flex items-center gap-4">
                                        <input v-model.number="action.delay_seconds" type="number" min="1" max="3600"
                                            placeholder="60"
                                            class="w-24 px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-transparent text-center">
                                        <span class="text-gray-400">soniya kutish</span>
                                    </div>

                                    <div v-if="action.action_type === 'ai_response'" class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4">
                                        <div class="flex items-center gap-2 text-amber-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                            </svg>
                                            <span class="font-medium">AI avtomatik javob beradi</span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Sun'iy intellekt xabarni tahlil qilib mos javob yuboradi</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Action Button -->
                            <button @click="addAction" type="button"
                                class="w-full py-4 border-2 border-dashed border-gray-600 text-gray-400 rounded-2xl hover:border-pink-500 hover:text-pink-400 hover:bg-pink-500/5 transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Yana harakat qo'shish
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Settings -->
                    <div v-if="wizardStep === 3" class="space-y-6">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-bold text-white mb-2">Oxirgi sozlamalar</h3>
                            <p class="text-gray-400">Avtomatingizga nom bering va ishga tushiring</p>
                        </div>

                        <!-- Name Input -->
                        <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700">
                            <label class="block text-sm font-semibold text-gray-300 mb-3">Avtomat nomi *</label>
                            <input v-model="automationForm.name" type="text"
                                placeholder="Masalan: Narx so'rovlariga javob"
                                class="w-full px-4 py-4 bg-gray-700 border border-gray-600 text-white text-lg rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-400">
                        </div>

                        <!-- Description -->
                        <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700">
                            <label class="block text-sm font-semibold text-gray-300 mb-3">Tavsif (ixtiyoriy)</label>
                            <textarea v-model="automationForm.description" rows="2"
                                placeholder="Bu avtomat nima ish qiladi..."
                                class="w-full px-4 py-3 bg-gray-700 border border-gray-600 text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent placeholder-gray-400 resize-none"></textarea>
                        </div>

                        <!-- AI Toggle -->
                        <div class="bg-gradient-to-r from-purple-500/10 to-pink-500/10 border border-purple-500/30 rounded-2xl p-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white">AI yordamchisi</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Sun'iy intellekt xabarlarni tahlil qilsin</div>
                                    </div>
                                </div>
                                <button @click="automationForm.is_ai_enabled = !automationForm.is_ai_enabled"
                                    :class="[
                                        'w-14 h-8 rounded-full transition-all relative',
                                        automationForm.is_ai_enabled ? 'bg-purple-500' : 'bg-gray-600'
                                    ]">
                                    <div :class="[
                                        'w-6 h-6 bg-white rounded-full absolute top-1 transition-all shadow',
                                        automationForm.is_ai_enabled ? 'right-1' : 'left-1'
                                    ]"></div>
                                </button>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="bg-gray-800 rounded-2xl p-5 border border-gray-700">
                            <label class="block text-sm font-semibold text-gray-300 mb-3">Boshlang'ich holati</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button @click="automationForm.status = 'active'" type="button"
                                    :class="[
                                        'p-3 rounded-xl border transition-all text-center',
                                        automationForm.status === 'active'
                                            ? 'bg-green-500/20 border-green-500 text-green-400'
                                            : 'bg-gray-700 border-gray-600 text-gray-400 hover:border-gray-500'
                                    ]">
                                    <div class="font-medium">Faol</div>
                                    <div class="text-xs mt-1 opacity-70">Darhol ishlaydi</div>
                                </button>
                                <button @click="automationForm.status = 'draft'" type="button"
                                    :class="[
                                        'p-3 rounded-xl border transition-all text-center',
                                        automationForm.status === 'draft'
                                            ? 'bg-gray-500/20 border-gray-500 text-gray-300'
                                            : 'bg-gray-700 border-gray-600 text-gray-400 hover:border-gray-500'
                                    ]">
                                    <div class="font-medium">Qoralama</div>
                                    <div class="text-xs mt-1 opacity-70">Keyinroq</div>
                                </button>
                                <button @click="automationForm.status = 'paused'" type="button"
                                    :class="[
                                        'p-3 rounded-xl border transition-all text-center',
                                        automationForm.status === 'paused'
                                            ? 'bg-yellow-500/20 border-yellow-500 text-yellow-400'
                                            : 'bg-gray-700 border-gray-600 text-gray-400 hover:border-gray-500'
                                    ]">
                                    <div class="font-medium">To'xtatilgan</div>
                                    <div class="text-xs mt-1 opacity-70">Pauza</div>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Summary -->
                        <div class="bg-gray-800/50 rounded-2xl p-5 border border-gray-700">
                            <h4 class="font-semibold text-white mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Xulosa
                            </h4>
                            <div class="space-y-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                                    <span class="text-gray-400">Trigger:</span>
                                    <span class="text-white">{{ selectedTriggerInfo?.name || 'Tanlanmagan' }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-pink-500 rounded-full"></div>
                                    <span class="text-gray-400">Harakatlar:</span>
                                    <span class="text-white">{{ automationForm.actions.length }} ta</span>
                                </div>
                                <div v-if="automationForm.triggers[0]?.keywords?.length" class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-gray-400">Kalit so'zlar:</span>
                                    <span class="text-white">{{ automationForm.triggers[0].keywords.join(', ') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 border-t border-gray-800 flex justify-between bg-gray-900/80">
                    <button v-if="wizardStep > 1" @click="prevStep"
                        class="px-6 py-3 border border-gray-600 text-gray-300 rounded-xl hover:bg-gray-800 font-medium transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Orqaga
                    </button>
                    <div v-else></div>

                    <div class="flex gap-3">
                        <button @click="showAutomationModal = false"
                            class="px-6 py-3 text-gray-400 hover:text-white font-medium transition-colors">
                            Bekor qilish
                        </button>
                        <button v-if="wizardStep < 3" @click="nextStep" :disabled="!canProceed"
                            :class="[
                                'px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2',
                                canProceed
                                    ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white hover:from-purple-600 hover:to-pink-600'
                                    : 'bg-gray-700 text-gray-500 cursor-not-allowed'
                            ]">
                            Davom etish
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button v-else @click="saveAutomation" :disabled="!canProceed"
                            :class="[
                                'px-8 py-3 rounded-xl font-semibold transition-all flex items-center gap-2',
                                canProceed
                                    ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600'
                                    : 'bg-gray-700 text-gray-500 cursor-not-allowed'
                            ]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ editingAutomation ? 'Saqlash' : 'Yaratish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Flow Builder (Fullscreen) -->
        <div v-if="showFlowBuilder" class="fixed inset-0 z-50">
            <InstagramFlowBuilder
                :automation-id="editingAutomation?.id"
                :automation-name="editingAutomation?.name || ''"
                :automation-status="editingAutomation?.status || 'draft'"
                :automation-ai-enabled="editingAutomation?.is_ai_enabled || false"
                :initial-nodes="flowBuilderData.nodes"
                :initial-connections="flowBuilderData.connections"
                @close="closeFlowBuilder"
                @save="saveFlowBuilderData"
            />
        </div>
    </BusinessLayout>
</template>
