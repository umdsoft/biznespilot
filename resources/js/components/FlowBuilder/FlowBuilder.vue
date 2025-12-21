<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    automation: Object,
    isOpen: Boolean,
});

const emit = defineEmits(['close', 'saved']);

// State
const loading = ref(false);
const saving = ref(false);
const nodeTypes = ref({});
const templates = ref([]);
const automationName = ref('');
const automationDescription = ref('');
const posts = ref([]); // Instagram posts for selector

// Canvas state
const nodes = ref([]);
const edges = ref([]);
const selectedNode = ref(null);
const draggingNode = ref(null);
const draggingNewNode = ref(null);
const canvasOffset = ref({ x: 0, y: 0 });
const scale = ref(1);
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });

// Connection state
const isConnecting = ref(false);
const connectionStart = ref(null);
const connectionHandle = ref(null);
const mousePosition = ref({ x: 0, y: 0 });

// Node panel
const showTemplates = ref(false);
const activeCategory = ref('trigger');

// SVG Icons for each node type (mapped by both node_type key and icon name)
const nodeIconPaths = {
    // Triggers
    chat: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />`,
    comment: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />`,
    story_mention: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />`,
    story_reply: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />`,
    new_follower: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />`,
    // Conditions
    is_follower: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    liked_post: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />`,
    saved_post: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />`,
    has_tag: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />`,
    time_passed: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    // Actions
    send_dm: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
    send_media: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
    send_link: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />`,
    add_tag: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14v6m-3-3h6M6 10h2a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 0h2a2 2 0 002-2V6a2 2 0 00-2-2h-2a2 2 0 00-2 2v2a2 2 0 002 2zM6 20h2a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2z" />`,
    remove_tag: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />`,
    delay: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    reply_comment: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />`,
    ai_response: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
};

// Get icon path by node type or icon name
const getNodeIcon = (nodeTypeOrIconName) => {
    // First try to get by icon name directly
    if (nodeIconPaths[nodeTypeOrIconName]) {
        return nodeIconPaths[nodeTypeOrIconName];
    }
    // Then try to get icon name from node type config
    const nodeConfig = nodeTypes.value[nodeTypeOrIconName];
    if (nodeConfig?.icon && nodeIconPaths[nodeConfig.icon]) {
        return nodeIconPaths[nodeConfig.icon];
    }
    // Fallback icon (question mark)
    return `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`;
};

// Categories for node panel
const categories = [
    { id: 'trigger', label: 'Triggerlar', color: 'purple' },
    { id: 'condition', label: 'Shartlar', color: 'yellow' },
    { id: 'action', label: 'Harakatlar', color: 'green' },
];

// Generate unique ID
const generateId = () => 'node_' + Math.random().toString(36).substr(2, 9);

// Filtered nodes by category
const filteredNodeTypes = computed(() => {
    if (!nodeTypes.value) return [];
    return Object.entries(nodeTypes.value)
        .filter(([_, node]) => node.category === activeCategory.value)
        .map(([key, node]) => ({ key, ...node }));
});

// Load data
const loadNodeTypes = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/node-types');
        nodeTypes.value = response.data.node_types || {};
    } catch (e) {
        console.error('Error loading node types:', e);
    }
};

const loadTemplates = async () => {
    try {
        const response = await axios.get('/business/api/instagram-chatbot/templates');
        templates.value = response.data.templates || [];
    } catch (e) {
        console.error('Error loading templates:', e);
    }
};

const loadPosts = async () => {
    try {
        const response = await axios.get('/business/api/instagram/posts');
        posts.value = response.data.posts || [];
    } catch (e) {
        // Posts may not be available, that's ok
        posts.value = [];
    }
};

const loadAutomation = async () => {
    if (!props.automation?.id) return;

    loading.value = true;
    try {
        const response = await axios.get(`/business/api/instagram-chatbot/flow-automations/${props.automation.id}`);
        const data = response.data;

        automationName.value = data.automation.name;
        automationDescription.value = data.automation.description || '';
        nodes.value = data.nodes || [];
        edges.value = data.edges || [];
    } catch (e) {
        console.error('Error loading automation:', e);
    }
    loading.value = false;
};

// Template handlers
const useTemplate = (template) => {
    // Create node ID mapping (old template ID -> new generated ID)
    const nodeIdMap = {};

    // Process nodes with new IDs
    nodes.value = template.nodes.map(n => {
        const newId = generateId();
        nodeIdMap[n.node_id] = newId;
        return {
            node_id: newId,
            node_type: n.node_type,
            position: { ...n.position },
            data: { ...n.data },
        };
    });

    // Process edges with mapped IDs
    edges.value = (template.edges || []).map(e => ({
        edge_id: generateId(),
        source_node_id: nodeIdMap[e.source_node_id],
        target_node_id: nodeIdMap[e.target_node_id],
        source_handle: e.source_handle || null,
    }));

    automationName.value = template.name;
    automationDescription.value = template.description || '';
    showTemplates.value = false;
};

// Canvas handlers
const getCanvasPosition = (e) => {
    const canvas = document.getElementById('flow-canvas');
    if (!canvas) return { x: 0, y: 0 };

    const rect = canvas.getBoundingClientRect();
    return {
        x: (e.clientX - rect.left - canvasOffset.value.x) / scale.value,
        y: (e.clientY - rect.top - canvasOffset.value.y) / scale.value,
    };
};

const onCanvasMouseDown = (e) => {
    if (e.target.id === 'flow-canvas' || e.target.classList.contains('canvas-grid')) {
        isPanning.value = true;
        panStart.value = { x: e.clientX - canvasOffset.value.x, y: e.clientY - canvasOffset.value.y };
        selectedNode.value = null;
    }
};

const onCanvasMouseMove = (e) => {
    mousePosition.value = getCanvasPosition(e);

    if (isPanning.value) {
        canvasOffset.value = {
            x: e.clientX - panStart.value.x,
            y: e.clientY - panStart.value.y,
        };
    }

    if (draggingNode.value) {
        const pos = getCanvasPosition(e);
        const node = nodes.value.find(n => n.node_id === draggingNode.value.id);
        if (node) {
            node.position = {
                x: pos.x - draggingNode.value.offsetX,
                y: pos.y - draggingNode.value.offsetY,
            };
        }
    }
};

const onCanvasMouseUp = () => {
    isPanning.value = false;
    draggingNode.value = null;

    if (draggingNewNode.value) {
        const pos = mousePosition.value;
        addNode(draggingNewNode.value, pos);
        draggingNewNode.value = null;
    }

    if (isConnecting.value) {
        isConnecting.value = false;
        connectionStart.value = null;
        connectionHandle.value = null;
    }
};

const onWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    scale.value = Math.min(Math.max(scale.value * delta, 0.5), 2);
};

// Node handlers
const addNode = (nodeType, position) => {
    const nodeConfig = nodeTypes.value[nodeType];
    if (!nodeConfig) return;

    const newNode = {
        node_id: generateId(),
        node_type: nodeType,
        data: getDefaultNodeData(nodeType),
        position: position || { x: 200, y: 200 },
    };

    nodes.value.push(newNode);
    selectedNode.value = newNode;
};

const getDefaultNodeData = (nodeType) => {
    const config = nodeTypes.value[nodeType];
    if (!config?.fields) return {};

    const data = {};
    config.fields.forEach(field => {
        if (field.default !== undefined) {
            data[field.name] = field.default;
        } else if (field.type === 'checkbox') {
            data[field.name] = false;
        } else {
            data[field.name] = '';
        }
    });
    return data;
};

const onNodeMouseDown = (e, node) => {
    e.stopPropagation();
    selectedNode.value = node;

    const pos = getCanvasPosition(e);
    draggingNode.value = {
        id: node.node_id,
        offsetX: pos.x - node.position.x,
        offsetY: pos.y - node.position.y,
    };
};

const deleteNode = (nodeId) => {
    nodes.value = nodes.value.filter(n => n.node_id !== nodeId);
    edges.value = edges.value.filter(e =>
        e.source_node_id !== nodeId && e.target_node_id !== nodeId
    );
    if (selectedNode.value?.node_id === nodeId) {
        selectedNode.value = null;
    }
};

// Connection handlers
const startConnection = (e, nodeId, handle = null) => {
    e.stopPropagation();
    isConnecting.value = true;
    connectionStart.value = nodeId;
    connectionHandle.value = handle;
};

const endConnection = (e, targetNodeId) => {
    e.stopPropagation();

    if (isConnecting.value && connectionStart.value && connectionStart.value !== targetNodeId) {
        const exists = edges.value.some(edge =>
            edge.source_node_id === connectionStart.value &&
            edge.target_node_id === targetNodeId &&
            edge.source_handle === connectionHandle.value
        );

        if (!exists) {
            edges.value.push({
                edge_id: generateId(),
                source_node_id: connectionStart.value,
                target_node_id: targetNodeId,
                source_handle: connectionHandle.value,
            });
        }
    }

    isConnecting.value = false;
    connectionStart.value = null;
    connectionHandle.value = null;
};

const deleteEdge = (edgeId) => {
    edges.value = edges.value.filter(e => e.edge_id !== edgeId);
};

// Get node position for edges
const getNodeCenter = (nodeId, handle = null) => {
    const node = nodes.value.find(n => n.node_id === nodeId);
    if (!node) return { x: 0, y: 0 };

    const nodeConfig = nodeTypes.value[node.node_type];
    let x = node.position.x + 130;
    let y = node.position.y + 100;

    if (handle === 'yes') {
        x = node.position.x + 65;
        y = node.position.y + 100;
    } else if (handle === 'no') {
        x = node.position.x + 195;
        y = node.position.y + 100;
    }

    return { x, y };
};

// Edge path calculation
const getEdgePath = (edge) => {
    const source = getNodeCenter(edge.source_node_id, edge.source_handle);
    const target = nodes.value.find(n => n.node_id === edge.target_node_id);
    if (!target) return '';

    const targetPos = { x: target.position.x + 130, y: target.position.y };
    const midY = (source.y + targetPos.y) / 2;

    return `M ${source.x} ${source.y} C ${source.x} ${midY}, ${targetPos.x} ${midY}, ${targetPos.x} ${targetPos.y}`;
};

// Drag from panel
const onPanelDragStart = (e, nodeType) => {
    draggingNewNode.value = nodeType;
};

// Save automation
const saveAutomation = async () => {
    if (!automationName.value.trim()) {
        alert('Iltimos avtomat nomini kiriting');
        return;
    }

    if (nodes.value.length === 0) {
        alert('Iltimos kamida bitta blok qo\'shing');
        return;
    }

    const hasTrigger = nodes.value.some(n => nodeTypes.value[n.node_type]?.category === 'trigger');
    if (!hasTrigger) {
        alert('Iltimos kamida bitta trigger qo\'shing');
        return;
    }

    saving.value = true;
    try {
        const payload = {
            name: automationName.value,
            description: automationDescription.value,
            status: 'draft',
            nodes: nodes.value.map(n => ({
                node_id: n.node_id,
                node_type: n.node_type,
                data: n.data,
                position: n.position,
            })),
            edges: edges.value.map(e => ({
                edge_id: e.edge_id,
                source_node_id: e.source_node_id,
                target_node_id: e.target_node_id,
                source_handle: e.source_handle,
            })),
        };

        if (props.automation?.id) {
            await axios.put(`/business/api/instagram-chatbot/flow-automations/${props.automation.id}`, payload);
        } else {
            await axios.post('/business/api/instagram-chatbot/flow-automations', payload);
        }

        emit('saved');
    } catch (e) {
        console.error('Error saving automation:', e);
        alert('Saqlashda xatolik yuz berdi');
    }
    saving.value = false;
};

// Get node colors
const getNodeBorderColor = (category) => {
    const colors = {
        trigger: 'border-purple-400',
        condition: 'border-amber-400',
        action: 'border-emerald-400',
    };
    return colors[category] || 'border-gray-400';
};

const getNodeHeaderBg = (category) => {
    const colors = {
        trigger: 'bg-gradient-to-r from-purple-500 to-purple-600',
        condition: 'bg-gradient-to-r from-amber-500 to-amber-600',
        action: 'bg-gradient-to-r from-emerald-500 to-emerald-600',
    };
    return colors[category] || 'bg-gray-500';
};

const getNodeBg = (category) => {
    const colors = {
        trigger: 'bg-purple-50',
        condition: 'bg-amber-50',
        action: 'bg-emerald-50',
    };
    return colors[category] || 'bg-gray-50';
};

const getCategoryBg = (category) => {
    const colors = {
        trigger: 'bg-purple-500',
        condition: 'bg-amber-500',
        action: 'bg-emerald-500',
    };
    return colors[category] || 'bg-gray-500';
};

// Get node preview text
const getNodePreview = (node) => {
    if (!node.data) return '';

    if (node.data.keywords) {
        const kw = node.data.keywords;
        if (kw === '__all__') return 'Barcha xabarlar';
        return kw.split(',').slice(0, 2).join(', ') + (kw.split(',').length > 2 ? '...' : '');
    }
    if (node.data.message) {
        return node.data.message.substring(0, 30) + (node.data.message.length > 30 ? '...' : '');
    }
    if (node.data.media_id) {
        if (node.data.media_id === '__all__') return 'Barcha postlar';
        const post = posts.value.find(p => p.id === node.data.media_id);
        return post?.caption?.substring(0, 20) || 'Post tanlangan';
    }
    return '';
};

// Initialize
onMounted(async () => {
    loading.value = true;
    await Promise.all([loadNodeTypes(), loadTemplates(), loadPosts()]);

    if (props.automation?.id) {
        await loadAutomation();
    }
    loading.value = false;
});

watch(() => props.isOpen, (val) => {
    if (val && props.automation?.id) {
        loadAutomation();
    }
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="bg-white w-full h-full flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4 flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-4">
                    <button @click="emit('close')" class="text-white/80 hover:text-white transition-colors p-1 hover:bg-white/10 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <input v-model="automationName" type="text" placeholder="Avtomat nomini kiriting..."
                            class="bg-white/20 text-white placeholder-white/60 px-4 py-2.5 rounded-xl border-0 focus:ring-2 focus:ring-white/50 font-semibold text-lg w-80">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showTemplates = true"
                        class="px-5 py-2.5 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-colors flex items-center gap-2 font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                        </svg>
                        Shablonlar
                    </button>
                    <button @click="saveAutomation" :disabled="saving"
                        class="px-6 py-2.5 bg-white text-purple-600 rounded-xl font-bold hover:bg-purple-50 transition-colors flex items-center gap-2 disabled:opacity-50 shadow-lg">
                        <svg v-if="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Saqlash
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex overflow-hidden">
                <!-- Left Panel - Node Types -->
                <div class="w-80 bg-white border-r border-gray-200 flex flex-col shadow-lg">
                    <!-- Category Tabs -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex gap-1 bg-gray-100 rounded-xl p-1">
                            <button v-for="cat in categories" :key="cat.id" @click="activeCategory = cat.id"
                                :class="[
                                    'flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all',
                                    activeCategory === cat.id
                                        ? 'bg-white shadow-sm text-gray-900'
                                        : 'text-gray-500 hover:text-gray-700'
                                ]">
                                {{ cat.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Node List -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-for="node in filteredNodeTypes" :key="node.key"
                            draggable="true"
                            @dragstart="(e) => onPanelDragStart(e, node.key)"
                            @dragend="onCanvasMouseUp"
                            class="bg-white rounded-2xl p-4 border-2 border-gray-100 cursor-grab hover:border-purple-300 hover:shadow-lg transition-all group">
                            <div class="flex items-start gap-4">
                                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', getCategoryBg(node.category)]">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getNodeIcon(node.key)"></svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-gray-900">{{ node.label }}</div>
                                    <div class="text-sm text-gray-500 mt-1 leading-relaxed">{{ node.description }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help Text -->
                    <div class="p-4 border-t border-gray-100 bg-gradient-to-r from-purple-50 to-pink-50">
                        <div class="flex items-center gap-3 text-sm text-purple-700">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Bloklarni sudrab o'ng tomonga tashlang</span>
                        </div>
                    </div>
                </div>

                <!-- Canvas -->
                <div class="flex-1 relative overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100"
                    id="flow-canvas"
                    @mousedown="onCanvasMouseDown"
                    @mousemove="onCanvasMouseMove"
                    @mouseup="onCanvasMouseUp"
                    @wheel.prevent="onWheel"
                    @dragover.prevent
                    @drop="onCanvasMouseUp">

                    <!-- Grid Background -->
                    <div class="canvas-grid absolute inset-0 pointer-events-none"
                        :style="{
                            backgroundImage: 'radial-gradient(circle, #d1d5db 1px, transparent 1px)',
                            backgroundSize: `${20 * scale}px ${20 * scale}px`,
                            backgroundPosition: `${canvasOffset.x}px ${canvasOffset.y}px`,
                        }">
                    </div>

                    <!-- Canvas Content -->
                    <div class="absolute" :style="{
                        transform: `translate(${canvasOffset.x}px, ${canvasOffset.y}px) scale(${scale})`,
                        transformOrigin: '0 0',
                    }">
                        <!-- Edges (SVG) -->
                        <svg class="absolute top-0 left-0 pointer-events-none" style="width: 5000px; height: 5000px; overflow: visible;">
                            <defs>
                                <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                    <polygon points="0 0, 10 3.5, 0 7" fill="#8b5cf6" />
                                </marker>
                                <marker id="arrowhead-green" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                    <polygon points="0 0, 10 3.5, 0 7" fill="#22c55e" />
                                </marker>
                                <marker id="arrowhead-red" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                    <polygon points="0 0, 10 3.5, 0 7" fill="#ef4444" />
                                </marker>
                            </defs>

                            <!-- Connection Line (while connecting) -->
                            <path v-if="isConnecting && connectionStart"
                                :d="`M ${getNodeCenter(connectionStart, connectionHandle).x} ${getNodeCenter(connectionStart, connectionHandle).y} L ${mousePosition.x} ${mousePosition.y}`"
                                stroke="#8b5cf6" stroke-width="3" fill="none" stroke-dasharray="8,4" />

                            <!-- Existing Edges -->
                            <g v-for="edge in edges" :key="edge.edge_id">
                                <path
                                    :d="getEdgePath(edge)"
                                    :stroke="edge.source_handle === 'yes' ? '#22c55e' : edge.source_handle === 'no' ? '#ef4444' : '#8b5cf6'"
                                    stroke-width="3" fill="none"
                                    :marker-end="edge.source_handle === 'yes' ? 'url(#arrowhead-green)' : edge.source_handle === 'no' ? 'url(#arrowhead-red)' : 'url(#arrowhead)'"
                                    class="cursor-pointer hover:stroke-gray-400 transition-colors"
                                    @click="deleteEdge(edge.edge_id)" />
                            </g>
                        </svg>

                        <!-- Nodes -->
                        <div v-for="node in nodes" :key="node.node_id"
                            :class="[
                                'absolute w-64 rounded-2xl shadow-xl border-2 overflow-hidden transition-all cursor-move',
                                getNodeBorderColor(nodeTypes[node.node_type]?.category),
                                getNodeBg(nodeTypes[node.node_type]?.category),
                                selectedNode?.node_id === node.node_id ? 'ring-4 ring-purple-400 ring-offset-2 scale-105' : 'hover:shadow-2xl',
                            ]"
                            :style="{ left: node.position.x + 'px', top: node.position.y + 'px' }"
                            @mousedown="(e) => onNodeMouseDown(e, node)">

                            <!-- Node Header -->
                            <div :class="['px-4 py-3 text-white flex items-center gap-3', getNodeHeaderBg(nodeTypes[node.node_type]?.category)]">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getNodeIcon(node.node_type)"></svg>
                                <span class="font-bold text-sm flex-1 truncate">{{ nodeTypes[node.node_type]?.label }}</span>
                                <button @click.stop="deleteNode(node.node_id)" class="text-white/70 hover:text-white hover:bg-white/20 p-1 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Node Preview Content -->
                            <div class="px-4 py-3 bg-white min-h-[40px]">
                                <p v-if="getNodePreview(node)" class="text-sm text-gray-600 truncate">
                                    {{ getNodePreview(node) }}
                                </p>
                                <p v-else class="text-sm text-gray-400 italic">
                                    Sozlanmagan
                                </p>
                            </div>

                            <!-- Input Handle (top) -->
                            <div v-if="nodeTypes[node.node_type]?.category !== 'trigger'"
                                class="absolute -top-3 left-1/2 transform -translate-x-1/2 w-6 h-6 bg-white border-3 border-purple-500 rounded-full cursor-pointer hover:bg-purple-100 hover:scale-125 transition-all shadow-lg"
                                @mouseup="(e) => endConnection(e, node.node_id)">
                            </div>

                            <!-- Output Handle (bottom) - for triggers and actions -->
                            <div v-if="nodeTypes[node.node_type]?.category !== 'condition'"
                                class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 w-6 h-6 bg-purple-500 border-3 border-white rounded-full cursor-pointer hover:bg-purple-600 hover:scale-125 transition-all shadow-lg"
                                @mousedown="(e) => startConnection(e, node.node_id)">
                            </div>

                            <!-- Condition Outputs (yes/no) -->
                            <template v-if="nodeTypes[node.node_type]?.category === 'condition'">
                                <div class="absolute -bottom-6 left-1/4 transform -translate-x-1/2 flex flex-col items-center">
                                    <div class="w-6 h-6 bg-green-500 border-3 border-white rounded-full cursor-pointer hover:bg-green-600 hover:scale-125 transition-all shadow-lg"
                                        @mousedown="(e) => startConnection(e, node.node_id, 'yes')">
                                    </div>
                                    <span class="text-xs text-green-600 font-bold mt-1 bg-white px-2 py-0.5 rounded-full shadow">Ha</span>
                                </div>
                                <div class="absolute -bottom-6 right-1/4 transform translate-x-1/2 flex flex-col items-center">
                                    <div class="w-6 h-6 bg-red-500 border-3 border-white rounded-full cursor-pointer hover:bg-red-600 hover:scale-125 transition-all shadow-lg"
                                        @mousedown="(e) => startConnection(e, node.node_id, 'no')">
                                    </div>
                                    <span class="text-xs text-red-600 font-bold mt-1 bg-white px-2 py-0.5 rounded-full shadow">Yo'q</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="nodes.length === 0 && !loading"
                        class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="text-center max-w-md">
                            <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-pink-100 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                                <svg class="w-12 h-12 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800 mb-3">Avtomatingizni yarating</h3>
                            <p class="text-gray-500 text-lg">
                                Chap paneldan bloklarni sudrab shu joyga tashlang yoki tayyor shablondan foydalaning
                            </p>
                        </div>
                    </div>

                    <!-- Zoom Controls -->
                    <div class="absolute bottom-6 right-6 flex items-center gap-2 bg-white rounded-2xl shadow-xl p-2">
                        <button @click="scale = Math.max(scale - 0.1, 0.5)" class="p-3 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </button>
                        <span class="text-sm font-bold text-gray-700 w-14 text-center">{{ Math.round(scale * 100) }}%</span>
                        <button @click="scale = Math.min(scale + 0.1, 2)" class="p-3 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                        <div class="w-px h-8 bg-gray-200"></div>
                        <button @click="canvasOffset = { x: 0, y: 0 }; scale = 1" class="p-3 hover:bg-gray-100 rounded-xl transition-colors" title="Markazga qaytarish">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Right Panel - Node Properties -->
                <div v-if="selectedNode" class="w-96 bg-white border-l border-gray-200 flex flex-col shadow-xl">
                    <div class="p-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center gap-3">
                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', getCategoryBg(nodeTypes[selectedNode.node_type]?.category)]">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="getNodeIcon(selectedNode.node_type)"></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ nodeTypes[selectedNode.node_type]?.label }}</h3>
                                <p class="text-sm text-gray-500">Sozlamalar</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5">
                        <div class="space-y-5">
                            <div v-for="field in nodeTypes[selectedNode.node_type]?.fields" :key="field.name">
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ field.label }}</label>

                                <!-- Text Input -->
                                <input v-if="field.type === 'text' && !field.has_all_option"
                                    v-model="selectedNode.data[field.name]"
                                    type="text"
                                    :placeholder="field.placeholder"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">

                                <!-- Text Input with "All" option -->
                                <div v-else-if="field.type === 'text' && field.has_all_option" class="space-y-2">
                                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-300 transition-all"
                                        :class="selectedNode.data[field.name] === '__all__' ? 'border-purple-500 bg-purple-50' : ''">
                                        <input type="radio" :value="'__all__'" v-model="selectedNode.data[field.name]" class="w-4 h-4 text-purple-600">
                                        <span class="font-medium text-gray-700">{{ field.all_label || 'Barchasi' }}</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-300 transition-all"
                                        :class="selectedNode.data[field.name] !== '__all__' && selectedNode.data[field.name] ? 'border-purple-500 bg-purple-50' : ''">
                                        <input type="radio" :value="'custom'" @change="selectedNode.data[field.name] = ''" class="w-4 h-4 text-purple-600"
                                            :checked="selectedNode.data[field.name] !== '__all__'">
                                        <span class="font-medium text-gray-700">Maxsus</span>
                                    </label>
                                    <input v-if="selectedNode.data[field.name] !== '__all__'"
                                        v-model="selectedNode.data[field.name]"
                                        type="text"
                                        :placeholder="field.placeholder"
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                </div>

                                <!-- Post Selector -->
                                <div v-else-if="field.type === 'post_select'" class="space-y-2">
                                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-300 transition-all"
                                        :class="selectedNode.data[field.name] === '__all__' ? 'border-purple-500 bg-purple-50' : ''">
                                        <input type="radio" :value="'__all__'" v-model="selectedNode.data[field.name]" class="w-4 h-4 text-purple-600">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                            <span class="font-medium text-gray-700">Barcha postlar/reelslar</span>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-300 transition-all"
                                        :class="selectedNode.data[field.name] !== '__all__' ? 'border-purple-500 bg-purple-50' : ''">
                                        <input type="radio" :value="'specific'" @change="selectedNode.data[field.name] = ''" class="w-4 h-4 text-purple-600"
                                            :checked="selectedNode.data[field.name] !== '__all__'">
                                        <span class="font-medium text-gray-700">Aniq post tanlash</span>
                                    </label>

                                    <div v-if="selectedNode.data[field.name] !== '__all__'" class="mt-3">
                                        <select v-if="posts.length > 0"
                                            v-model="selectedNode.data[field.name]"
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                            <option value="">Post tanlang...</option>
                                            <option v-for="post in posts" :key="post.id" :value="post.id">
                                                {{ post.caption ? post.caption.substring(0, 50) + '...' : 'Post #' + post.id }}
                                            </option>
                                        </select>
                                        <input v-else
                                            v-model="selectedNode.data[field.name]"
                                            type="text"
                                            placeholder="Post ID kiriting"
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                    </div>
                                </div>

                                <!-- Textarea -->
                                <textarea v-else-if="field.type === 'textarea'"
                                    v-model="selectedNode.data[field.name]"
                                    :placeholder="field.placeholder"
                                    rows="4"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all resize-none"></textarea>

                                <!-- Number Input -->
                                <input v-else-if="field.type === 'number'"
                                    v-model.number="selectedNode.data[field.name]"
                                    type="number"
                                    :placeholder="field.placeholder"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">

                                <!-- Select -->
                                <select v-else-if="field.type === 'select'"
                                    v-model="selectedNode.data[field.name]"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                    <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>

                                <!-- Checkbox -->
                                <label v-else-if="field.type === 'checkbox'" class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="selectedNode.data[field.name]" class="w-5 h-5 text-purple-600 rounded">
                                    <span class="text-gray-700">{{ field.checkbox_label || field.label }}</span>
                                </label>

                                <p v-if="field.help" class="text-sm text-gray-500 mt-2 flex items-start gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ field.help }}
                                </p>
                            </div>

                            <button @click="deleteNode(selectedNode.node_id)"
                                class="w-full py-3 border-2 border-red-200 text-red-600 rounded-xl hover:bg-red-50 transition-colors flex items-center justify-center gap-2 mt-8 font-semibold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Blokni o'chirish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Modal -->
        <div v-if="showTemplates" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60]">
            <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden mx-4">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-purple-50 to-pink-50">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Tayyor shablonlar</h2>
                            <p class="text-sm text-gray-500">Tez boshlash uchun shablonlardan birini tanlang</p>
                        </div>
                    </div>
                    <button @click="showTemplates = false" class="p-2 hover:bg-gray-100 rounded-xl transition-colors">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[65vh]">
                    <div class="grid md:grid-cols-2 gap-5">
                        <div v-for="template in templates" :key="template.id"
                            @click="useTemplate(template)"
                            class="border-2 border-gray-100 rounded-2xl p-5 cursor-pointer hover:border-purple-400 hover:shadow-xl transition-all group bg-white">
                            <div class="flex items-start gap-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-bold text-gray-900 text-lg group-hover:text-purple-600 transition-colors">{{ template.name }}</h3>
                                    <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ template.description }}</p>
                                    <div class="flex items-center gap-3 mt-3">
                                        <span class="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full font-medium">
                                            {{ template.nodes?.length || 0 }} blok
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            {{ template.usage_count || 0 }} marta ishlatilgan
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="templates.length === 0" class="text-center py-16">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                            </svg>
                        </div>
                        <p class="text-gray-500 text-lg">Hozircha shablonlar yo'q</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.border-3 {
    border-width: 3px;
}
</style>
