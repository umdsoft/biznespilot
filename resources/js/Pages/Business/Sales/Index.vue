<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    PlusIcon,
    UsersIcon,
    UserPlusIcon,
    CheckBadgeIcon,
    CurrencyDollarIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    PhoneIcon,
    EnvelopeIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    Squares2X2Icon,
    ListBulletIcon,
    EllipsisVerticalIcon,
    ClockIcon,
    CheckIcon,
    XMarkIcon,
    ArrowsPointingOutIcon,
    ChartPieIcon,
    PresentationChartLineIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';
import BulkSmsModal from '@/components/BulkSmsModal.vue';
import BulkAssignModal from '@/components/BulkAssignModal.vue';
import SalesFunnelChart from '@/components/SalesFunnelChart.vue';
import SourceAnalyticsChart from '@/components/SourceAnalyticsChart.vue';
import LostReasonModal from '@/components/LostReasonModal.vue';

// SMS state
const showBulkSmsModal = ref(false);
const selectedLeads = ref([]);
const smsConnected = ref(false);

// Bulk Assign state
const showBulkAssignModal = ref(false);

// Check SMS status
const checkSmsStatus = async () => {
    try {
        const response = await fetch(route('business.sms.status'), {
            headers: { 'Accept': 'application/json' },
        });
        if (response.ok) {
            const data = await response.json();
            smsConnected.value = data.connected;
        }
    } catch (error) {
        console.error('Failed to check SMS status:', error);
    }
};

// Toggle lead selection
const toggleLeadSelection = (lead) => {
    const index = selectedLeads.value.findIndex(l => l.id === lead.id);
    if (index === -1) {
        selectedLeads.value.push(lead);
    } else {
        selectedLeads.value.splice(index, 1);
    }
};

// Check if lead is selected
const isLeadSelected = (lead) => {
    return selectedLeads.value.some(l => l.id === lead.id);
};

// Select all visible leads
const selectAllLeads = () => {
    if (selectedLeads.value.length === filteredLeads.value.length) {
        selectedLeads.value = [];
    } else {
        selectedLeads.value = [...filteredLeads.value];
    }
};

// Clear selection
const clearSelection = () => {
    selectedLeads.value = [];
};

// Open bulk SMS modal
const openBulkSmsModal = () => {
    if (selectedLeads.value.length > 0) {
        showBulkSmsModal.value = true;
    }
};

// Handle bulk SMS button click
const handleBulkSmsClick = () => {
    if (!smsConnected.value) {
        // Redirect to SMS settings
        router.visit(route('business.settings.sms'));
        return;
    }
    openBulkSmsModal();
};

// Handle SMS sent
const handleSmsSent = () => {
    clearSelection();
};

// Handle bulk assign
const handleBulkAssignClick = () => {
    if (selectedLeads.value.length > 0) {
        showBulkAssignModal.value = true;
    }
};

// Handle bulk assigned
const handleBulkAssigned = (data) => {
    // Refresh leads to get updated data
    fetchLeads();
    clearSelection();
};

const props = defineProps({
    leads: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_leads: 0,
            new_leads: 0,
            qualified_leads: 0,
            pipeline_value: 0,
            won_deals: 0,
            total_value: 0
        }),
    },
    channels: {
        type: Array,
        default: () => [],
    },
    currentBusiness: {
        type: Object,
        default: null,
    },
    lazyLoad: {
        type: Boolean,
        default: false,
    },
    canAssignLeads: {
        type: Boolean,
        default: false,
    },
});

// Lazy loading state
const isLoading = ref(false);
const loadedLeads = ref([]);
const loadedStats = ref(null);
const localStateInitialized = ref(false); // Flag to track when local state is ready
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 0,
});

// Use local state after initialization - never fall back to props after init
const leads = computed(() => {
    if (localStateInitialized.value) {
        return loadedLeads.value;
    }
    return props.leads || [];
});
const stats = computed(() => loadedStats.value || props.stats || {
    total_leads: 0,
    new_leads: 0,
    qualified_leads: 0,
    pipeline_value: 0,
    won_deals: 0,
    total_value: 0
});

// View mode: 'kanban' or 'list'
const viewMode = ref('kanban');
const searchQuery = ref('');
const sourceFilter = ref('');
const operatorFilter = ref('');
const operators = ref([]);
const deletingLead = ref(null);
const isDragging = ref(false);

// Analytics state
const funnelData = ref([]);
const sourceData = ref([]);
const analyticsLoading = ref(false);
const showLostReasonModal = ref(false);
const leadToMarkLost = ref(null);
const draggedLead = ref(null);
const dragOverColumn = ref(null);
const showLeadMenu = ref(null);

// Pipeline stages configuration
const pipelineStages = [
    { value: 'new', label: 'Yangi', color: 'blue', bgColor: 'bg-blue-500', lightBg: 'bg-blue-50 dark:bg-blue-900/20', borderColor: 'border-blue-200 dark:border-blue-800' },
    { value: 'contacted', label: 'Bog\'lanildi', color: 'indigo', bgColor: 'bg-indigo-500', lightBg: 'bg-indigo-50 dark:bg-indigo-900/20', borderColor: 'border-indigo-200 dark:border-indigo-800' },
    { value: 'qualified', label: 'Qualified', color: 'purple', bgColor: 'bg-purple-500', lightBg: 'bg-purple-50 dark:bg-purple-900/20', borderColor: 'border-purple-200 dark:border-purple-800' },
    { value: 'proposal', label: 'Taklif', color: 'orange', bgColor: 'bg-orange-500', lightBg: 'bg-orange-50 dark:bg-orange-900/20', borderColor: 'border-orange-200 dark:border-orange-800' },
    { value: 'negotiation', label: 'Muzokara', color: 'yellow', bgColor: 'bg-yellow-500', lightBg: 'bg-yellow-50 dark:bg-yellow-900/20', borderColor: 'border-yellow-200 dark:border-yellow-800' },
    { value: 'won', label: 'Yutildi', color: 'green', bgColor: 'bg-green-500', lightBg: 'bg-green-50 dark:bg-green-900/20', borderColor: 'border-green-200 dark:border-green-800' },
    { value: 'lost', label: 'Yo\'qoldi', color: 'red', bgColor: 'bg-red-500', lightBg: 'bg-red-50 dark:bg-red-900/20', borderColor: 'border-red-200 dark:border-red-800' },
];

// Load operators
const loadOperators = async () => {
    try {
        const response = await axios.get('/business/api/sales/operators');
        operators.value = response.data.operators || [];
    } catch (err) {
        console.error('Error fetching operators:', err);
    }
};

// Fetch leads with pagination
const fetchLeads = async (page = 1) => {
    try {
        const params = { page, per_page: 25 };
        if (searchQuery.value) params.search = searchQuery.value;
        if (sourceFilter.value) params.source = sourceFilter.value;
        if (operatorFilter.value) params.operator = operatorFilter.value;

        const response = await axios.get('/business/api/sales/leads', { params });
        loadedLeads.value = response.data.data || [];
        localStateInitialized.value = true; // Mark local state as initialized
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (err) {
        console.error('Error fetching leads:', err);
    }
};

// Fetch stats
const fetchStats = async () => {
    try {
        const response = await axios.get('/business/api/sales/stats');
        loadedStats.value = response.data;
    } catch (err) {
        console.error('Error fetching stats:', err);
    }
};

// Fetch all data
const fetchData = async () => {
    if (!props.lazyLoad) return;
    isLoading.value = true;
    try {
        await Promise.all([fetchLeads(), fetchStats()]);
    } finally {
        isLoading.value = false;
    }
};

// Fetch funnel stats
const fetchFunnelStats = async () => {
    try {
        const response = await axios.get('/business/api/sales/funnel-stats');
        funnelData.value = response.data.funnel || [];
    } catch (err) {
        console.error('Error fetching funnel stats:', err);
    }
};

// Fetch source stats
const fetchSourceStats = async () => {
    try {
        const response = await axios.get('/business/api/sales/source-stats');
        sourceData.value = response.data.sources || [];
    } catch (err) {
        console.error('Error fetching source stats:', err);
    }
};

// Fetch analytics data
const fetchAnalytics = async () => {
    analyticsLoading.value = true;
    try {
        await Promise.all([fetchFunnelStats(), fetchSourceStats()]);
    } finally {
        analyticsLoading.value = false;
    }
};

// Mark lead as lost
const openLostReasonModal = (lead) => {
    leadToMarkLost.value = lead;
    showLostReasonModal.value = true;
};

// Handle lost reason confirm
const handleLostReasonConfirm = async ({ leadId, reason, details }) => {
    try {
        await axios.post(`/business/api/sales/leads/${leadId}/mark-lost`, {
            lost_reason: reason,
            lost_reason_details: details,
        });
        showLostReasonModal.value = false;
        leadToMarkLost.value = null;

        // Update local state optimistically
        if (!props.lazyLoad && localStateInitialized.value) {
            const updatedLeads = loadedLeads.value.map(l => {
                if (l.id === leadId) {
                    return { ...l, status: 'lost', lost_reason: reason, lost_reason_details: details };
                }
                return { ...l };
            });
            loadedLeads.value = updatedLeads;
            await nextTick();
        }

        // Refresh data for lazy load mode
        if (props.lazyLoad) {
            await fetchLeads();
        }

        // Refresh stats
        fetchStats();

        // Refresh analytics if in analytics view
        if (viewMode.value === 'analytics') {
            await fetchAnalytics();
        }
    } catch (err) {
        console.error('Error marking lead as lost:', err);
    }
};

// Watch for view mode changes to load analytics
watch(viewMode, async (newMode) => {
    if (newMode === 'analytics' && funnelData.value.length === 0) {
        await fetchAnalytics();
    }
});

// Initialize local state from props (for non-lazy mode)
// This runs once on mount - after that, local state is independent
const initializeLocalState = () => {
    if (!props.lazyLoad && props.leads?.length > 0 && !localStateInitialized.value) {
        loadedLeads.value = JSON.parse(JSON.stringify(props.leads));
        localStateInitialized.value = true;
    }
};

// Force refresh local state from props (used after server-side changes like delete error)
const forceRefreshFromProps = () => {
    if (props.leads?.length >= 0) {
        loadedLeads.value = JSON.parse(JSON.stringify(props.leads || []));
    }
};

// Watch for initial props - only initialize once
// After initialization, local state is independent from props
watch(() => props.leads, (newLeads, oldLeads) => {
    if (!localStateInitialized.value) {
        initializeLocalState();
    }
    // If props change dramatically (e.g., after Inertia router.reload), sync local state
    // But only for significant changes (like full page reload)
    else if (!props.lazyLoad && oldLeads === undefined && newLeads?.length > 0) {
        forceRefreshFromProps();
    }
}, { immediate: true });

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        if (props.lazyLoad) {
            fetchLeads(1);
        }
    }, 300);
};

watch(searchQuery, debouncedSearch);
watch(sourceFilter, () => {
    if (props.lazyLoad) fetchLeads(1);
});
watch(operatorFilter, () => {
    if (props.lazyLoad) fetchLeads(1);
});

// Filter leads based on search and source (for non-lazy mode)
const filteredLeads = computed(() => {
    let filtered = leads.value;

    // Only apply client-side filtering if not using lazy load
    if (!props.lazyLoad) {
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();
            filtered = filtered.filter(lead =>
                lead.name?.toLowerCase().includes(query) ||
                lead.email?.toLowerCase().includes(query) ||
                lead.company?.toLowerCase().includes(query) ||
                lead.phone?.toLowerCase().includes(query)
            );
        }

        if (sourceFilter.value) {
            filtered = filtered.filter(lead => lead.source?.id === parseInt(sourceFilter.value));
        }
    }

    return filtered;
});

// Group leads by status for Kanban view
const leadsByStatus = computed(() => {
    const grouped = {};
    pipelineStages.forEach(stage => {
        grouped[stage.value] = filteredLeads.value.filter(lead => lead.status === stage.value);
    });
    return grouped;
});

// Calculate totals for each column
const columnTotals = computed(() => {
    const totals = {};
    pipelineStages.forEach(stage => {
        const leads = leadsByStatus.value[stage.value] || [];
        totals[stage.value] = {
            count: leads.length,
            value: leads.reduce((sum, lead) => sum + (parseFloat(lead.estimated_value) || 0), 0)
        };
    });
    return totals;
});

const formatCurrency = (amount) => {
    if (!amount) return '0';
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + 'M';
    }
    if (amount >= 1000) {
        return (amount / 1000).toFixed(0) + 'K';
    }
    return new Intl.NumberFormat('uz-UZ').format(amount);
};

const formatFullCurrency = (amount) => {
    if (!amount) return '0 so\'m';
    return new Intl.NumberFormat('uz-UZ').format(amount) + ' so\'m';
};

const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getAvatarColor = (name) => {
    const colors = [
        'from-blue-500 to-blue-600',
        'from-purple-500 to-purple-600',
        'from-green-500 to-green-600',
        'from-orange-500 to-orange-600',
        'from-pink-500 to-pink-600',
        'from-indigo-500 to-indigo-600',
        'from-teal-500 to-teal-600',
        'from-red-500 to-red-600',
    ];
    const index = name ? name.charCodeAt(0) % colors.length : 0;
    return colors[index];
};

const getScoreStars = (score) => {
    if (score >= 80) return 5;
    if (score >= 60) return 4;
    if (score >= 40) return 3;
    if (score >= 20) return 2;
    return 1;
};

// Format relative time (e.g., "2 soat oldin", "Bugun 14:30")
const formatRelativeTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Hozirgina';
    if (diffMins < 60) return `${diffMins} daq oldin`;
    if (diffHours < 24) return `${diffHours} soat oldin`;
    if (diffDays < 7) return `${diffDays} kun oldin`;

    // Format as date
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const hours = date.getHours().toString().padStart(2, '0');
    const mins = date.getMinutes().toString().padStart(2, '0');
    return `${day}.${month} ${hours}:${mins}`;
};

// Drag and Drop handlers
const handleDragStart = (e, lead) => {
    isDragging.value = true;
    draggedLead.value = lead;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', lead.id);

    // Add dragging class after a small delay for visual feedback
    setTimeout(() => {
        e.target.classList.add('opacity-50', 'scale-95');
    }, 0);
};

const handleDragEnd = (e) => {
    isDragging.value = false;
    draggedLead.value = null;
    dragOverColumn.value = null;
    e.target.classList.remove('opacity-50', 'scale-95');
};

const handleDragOver = (e, status) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    dragOverColumn.value = status;
};

const handleDragLeave = (e) => {
    // Only clear if leaving the column entirely
    if (!e.currentTarget.contains(e.relatedTarget)) {
        dragOverColumn.value = null;
    }
};

const handleDrop = async (e, newStatus) => {
    e.preventDefault();
    dragOverColumn.value = null;

    if (draggedLead.value && draggedLead.value.status !== newStatus) {
        const lead = { ...draggedLead.value }; // Clone to avoid reference issues
        const leadId = lead.id;
        const oldStatus = lead.status;

        // Handle "lost" status - need to show modal first
        if (newStatus === 'lost') {
            openLostReasonModal(lead);
            isDragging.value = false;
            draggedLead.value = null;
            return;
        }

        // Clear drag state immediately
        isDragging.value = false;
        draggedLead.value = null;

        // Optimistic update - create completely new array with new objects
        const updatedLeads = loadedLeads.value.map(l => {
            if (l.id === leadId) {
                return { ...l, status: newStatus };
            }
            return { ...l }; // Clone all objects for reactivity
        });

        // Assign new array to trigger Vue reactivity
        loadedLeads.value = updatedLeads;

        // Force Vue to process the update
        await nextTick();

        try {
            // Send update to server with JSON headers to prevent Inertia redirect
            await axios.put(route('business.sales.update', leadId), {
                name: lead.name,
                email: lead.email,
                phone: lead.phone,
                company: lead.company,
                source_id: lead.source?.id || null,
                status: newStatus,
                score: lead.score,
                estimated_value: lead.estimated_value,
            }, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            // Refresh stats after successful update
            fetchStats();

            // Refresh analytics if in analytics view
            if (viewMode.value === 'analytics') {
                fetchAnalytics();
            }
        } catch (error) {
            console.error('Error updating lead status:', error);
            // Revert optimistic update on error
            const revertedLeads = loadedLeads.value.map(l => {
                if (l.id === leadId) {
                    return { ...l, status: oldStatus };
                }
                return { ...l };
            });
            loadedLeads.value = revertedLeads;
            await nextTick();
        }

        return;
    }

    isDragging.value = false;
    draggedLead.value = null;
};

const confirmDelete = (lead) => {
    deletingLead.value = lead;
    showLeadMenu.value = null;
};

const deleteLead = async () => {
    if (deletingLead.value) {
        const leadId = deletingLead.value.id;

        // Optimistic update - remove from local state immediately
        const updatedLeads = loadedLeads.value.filter(l => l.id !== leadId);
        loadedLeads.value = updatedLeads;
        deletingLead.value = null;

        try {
            await axios.delete(route('business.sales.destroy', leadId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            fetchStats();
        } catch (error) {
            console.error('Error deleting lead:', error);
            // Refresh leads to restore the deleted lead
            if (props.lazyLoad) {
                await fetchLeads();
            } else {
                // For non-lazy mode, we need to reload from server
                router.reload({ only: ['leads'] });
            }
        }
    }
};

const cancelDelete = () => {
    deletingLead.value = null;
};

const toggleLeadMenu = (leadId) => {
    showLeadMenu.value = showLeadMenu.value === leadId ? null : leadId;
};

// Close menu when clicking outside
const handleClickOutside = (e) => {
    if (!e.target.closest('.lead-menu')) {
        showLeadMenu.value = null;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    // Load operators
    loadOperators();
    // Lazy load data if needed
    if (props.lazyLoad) {
        fetchData();
    } else {
        // Initialize local state from props for non-lazy mode
        initializeLocalState();
    }
    // Check SMS status
    checkSmsStatus();
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <BusinessLayout title="Sotuv Pipeline">
        <Head title="Sotuv Pipeline" />

        <div class="h-full flex flex-col -m-4 sm:-m-6 lg:-m-8">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Title & Stats -->
                    <div class="flex items-center gap-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sotuv Pipeline</h1>
                            <p v-if="isLoading" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <span class="inline-block w-32 h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></span>
                            </p>
                            <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ stats?.total_leads || 0 }} ta lead • {{ formatFullCurrency(stats?.pipeline_value) }} pipeline
                            </p>
                        </div>

                        <!-- Quick Stats -->
                        <div v-if="!isLoading" class="hidden xl:flex items-center gap-6 pl-6 border-l border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats?.won_deals || 0 }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Yutilgan</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats?.new_leads || 0 }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Yangi</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats?.qualified_leads || 0 }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Qualified</p>
                            </div>
                        </div>
                        <div v-else class="hidden xl:flex items-center gap-6 pl-6 border-l border-gray-200 dark:border-gray-700">
                            <div v-for="i in 3" :key="i" class="text-center">
                                <div class="w-12 h-8 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-1"></div>
                                <div class="w-16 h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Qidirish..."
                                class="w-48 lg:w-64 pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400"
                            />
                        </div>

                        <!-- Source Filter -->
                        <select
                            v-model="sourceFilter"
                            class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">Barcha manbalar</option>
                            <option v-for="channel in channels" :key="channel.id" :value="channel.id">
                                {{ channel.name }}
                            </option>
                        </select>

                        <!-- Operator Filter -->
                        <select
                            v-model="operatorFilter"
                            class="px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm text-gray-900 dark:text-white"
                        >
                            <option value="">Barcha operatorlar</option>
                            <option value="unassigned">Tayinlanmagan</option>
                            <option v-for="op in operators" :key="op.id" :value="op.id">
                                {{ op.name }}
                            </option>
                        </select>

                        <!-- View Toggle -->
                        <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                            <button
                                @click="viewMode = 'kanban'"
                                :class="[
                                    'p-2 rounded-md transition-colors',
                                    viewMode === 'kanban'
                                        ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                                title="Kanban ko'rinishi"
                            >
                                <Squares2X2Icon class="w-5 h-5" />
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'p-2 rounded-md transition-colors',
                                    viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                                title="Ro'yxat ko'rinishi"
                            >
                                <ListBulletIcon class="w-5 h-5" />
                            </button>
                            <button
                                @click="viewMode = 'analytics'"
                                :class="[
                                    'p-2 rounded-md transition-colors',
                                    viewMode === 'analytics'
                                        ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                                title="Analitika"
                            >
                                <ChartPieIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Add Lead Button -->
                        <Link
                            :href="route('business.sales.create')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-lg shadow-blue-500/25"
                        >
                            <PlusIcon class="w-5 h-5" />
                            <span class="hidden sm:inline">Lead Qo'shish</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Loading Skeleton for Kanban -->
            <div v-if="viewMode === 'kanban' && isLoading" class="flex-1 overflow-x-auto overflow-y-hidden">
                <div class="h-full p-4 sm:p-6">
                    <div class="flex gap-4 h-full min-w-max">
                        <div v-for="i in 7" :key="i" class="w-72 flex-shrink-0 flex flex-col bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600 animate-pulse"></div>
                                    <div class="w-20 h-4 bg-gray-300 dark:bg-gray-600 rounded animate-pulse"></div>
                                </div>
                                <div class="w-24 h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                            </div>
                            <div class="flex-1 p-2 space-y-2">
                                <div v-for="j in 2" :key="j" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 animate-pulse"></div>
                                        <div class="w-24 h-4 bg-gray-300 dark:bg-gray-600 rounded animate-pulse"></div>
                                    </div>
                                    <div class="w-32 h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-2"></div>
                                    <div class="w-20 h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanban Board -->
            <div v-else-if="viewMode === 'kanban'" class="flex-1 overflow-x-auto overflow-y-hidden">
                <div class="h-full p-4 sm:p-6">
                    <div class="flex gap-4 h-full min-w-max">
                        <!-- Pipeline Columns -->
                        <div
                            v-for="stage in pipelineStages"
                            :key="stage.value"
                            class="w-72 flex-shrink-0 flex flex-col bg-gray-50 dark:bg-gray-900/50 rounded-xl"
                            @dragover="handleDragOver($event, stage.value)"
                            @dragleave="handleDragLeave"
                            @drop="handleDrop($event, stage.value)"
                        >
                            <!-- Column Header -->
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <div :class="[stage.bgColor, 'w-3 h-3 rounded-full']"></div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ stage.label }}</h3>
                                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded-full">
                                            {{ columnTotals[stage.value]?.count || 0 }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatFullCurrency(columnTotals[stage.value]?.value) }}
                                </div>
                            </div>

                            <!-- Column Content -->
                            <div
                                :class="[
                                    'flex-1 overflow-y-auto p-2 space-y-2 transition-colors',
                                    dragOverColumn === stage.value ? 'bg-blue-50 dark:bg-blue-900/20' : ''
                                ]"
                            >
                                <!-- Lead Cards -->
                                <div
                                    v-for="lead in leadsByStatus[stage.value]"
                                    :key="lead.id"
                                    draggable="true"
                                    @dragstart="handleDragStart($event, lead)"
                                    @dragend="handleDragEnd"
                                    :class="[
                                        'bg-white dark:bg-gray-800 rounded-lg border p-3 cursor-grab active:cursor-grabbing hover:shadow-md transition-all group',
                                        isLeadSelected(lead)
                                            ? 'border-blue-500 dark:border-blue-400 ring-2 ring-blue-500/20'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'
                                    ]"
                                >
                                    <!-- Card Header -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <!-- Selection Checkbox (visible on hover or when selected) -->
                                            <button
                                                @click.stop="toggleLeadSelection(lead)"
                                                :class="[
                                                    'w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 transition-all',
                                                    isLeadSelected(lead)
                                                        ? 'bg-blue-500 border-blue-500 text-white'
                                                        : 'border-gray-400 dark:border-gray-500 hover:border-blue-500 hover:bg-blue-500/10 opacity-0 group-hover:opacity-100'
                                                ]"
                                            >
                                                <CheckIcon v-if="isLeadSelected(lead)" class="w-3 h-3" />
                                            </button>
                                            <div :class="['w-8 h-8 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-xs font-semibold flex-shrink-0', getAvatarColor(lead.name)]">
                                                {{ getInitials(lead.name) }}
                                            </div>
                                            <div class="min-w-0">
                                                <Link
                                                    :href="route('business.sales.show', lead.id)"
                                                    class="font-medium text-gray-900 dark:text-white text-sm hover:text-blue-600 dark:hover:text-blue-400 truncate block"
                                                >
                                                    {{ lead.name }}
                                                </Link>
                                                <p v-if="lead.company" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                    {{ lead.company }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Card Menu -->
                                        <div class="relative lead-menu">
                                            <button
                                                @click.stop="toggleLeadMenu(lead.id)"
                                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                <EllipsisVerticalIcon class="w-4 h-4" />
                                            </button>

                                            <!-- Dropdown Menu -->
                                            <Transition
                                                enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95"
                                            >
                                                <div
                                                    v-if="showLeadMenu === lead.id"
                                                    class="absolute right-0 mt-1 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-20"
                                                >
                                                    <Link
                                                        :href="route('business.sales.show', lead.id)"
                                                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    >
                                                        <EyeIcon class="w-4 h-4" />
                                                        Ko'rish
                                                    </Link>
                                                    <Link
                                                        :href="route('business.sales.edit', lead.id)"
                                                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                                    >
                                                        <PencilIcon class="w-4 h-4" />
                                                        Tahrirlash
                                                    </Link>
                                                    <button
                                                        v-if="lead.status !== 'won' && lead.status !== 'lost'"
                                                        @click="openLostReasonModal(lead)"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-orange-600 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                                    >
                                                        <XMarkIcon class="w-4 h-4" />
                                                        Yo'qotilgan
                                                    </button>
                                                    <button
                                                        @click="confirmDelete(lead)"
                                                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                    >
                                                        <TrashIcon class="w-4 h-4" />
                                                        O'chirish
                                                    </button>
                                                </div>
                                            </Transition>
                                        </div>
                                    </div>

                                    <!-- Contact Info -->
                                    <div class="space-y-1 mb-3">
                                        <p v-if="lead.phone" class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            <PhoneIcon class="w-3 h-3" />
                                            {{ lead.phone }}
                                        </p>
                                        <p v-if="lead.email" class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                                            <EnvelopeIcon class="w-3 h-3 flex-shrink-0" />
                                            <span class="truncate">{{ lead.email }}</span>
                                        </p>
                                    </div>

                                    <!-- Card Footer -->
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <!-- Source -->
                                        <div v-if="lead.source" class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-500 dark:text-gray-400 truncate max-w-24">
                                            {{ lead.source.name }}
                                        </div>
                                        <div v-else class="text-xs text-gray-400 italic">Noma'lum</div>

                                        <!-- Value -->
                                        <div v-if="lead.estimated_value" class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                            <CurrencyDollarIcon class="w-3.5 h-3.5" />
                                            <span class="text-xs font-semibold">{{ formatCurrency(lead.estimated_value) }}</span>
                                        </div>

                                        <!-- Score Stars -->
                                        <div class="flex items-center gap-0.5">
                                            <StarIcon
                                                v-for="i in 5"
                                                :key="i"
                                                :class="[
                                                    'w-3 h-3',
                                                    i <= getScoreStars(lead.score || 0)
                                                        ? 'text-yellow-400'
                                                        : 'text-gray-200 dark:text-gray-700'
                                                ]"
                                            />
                                        </div>
                                    </div>

                                    <!-- Created At & Operator -->
                                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <!-- Created At -->
                                        <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                            <ClockIcon class="w-3 h-3" />
                                            <span>{{ formatRelativeTime(lead.created_at) }}</span>
                                        </div>

                                        <!-- Assigned Operator -->
                                        <div v-if="lead.assigned_to" class="flex items-center gap-1">
                                            <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white text-[10px] font-semibold flex-shrink-0">
                                                {{ lead.assigned_to.name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-16">
                                                {{ lead.assigned_to.name?.split(' ')[0] }}
                                            </span>
                                        </div>
                                    </div>

                                </div>

                                <!-- Empty State -->
                                <div
                                    v-if="!leadsByStatus[stage.value]?.length"
                                    :class="[
                                        'flex flex-col items-center justify-center py-8 px-4 border-2 border-dashed rounded-lg transition-colors',
                                        dragOverColumn === stage.value
                                            ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20'
                                            : 'border-gray-200 dark:border-gray-700'
                                    ]"
                                >
                                    <UsersIcon class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                                        Lead yo'q
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else-if="viewMode === 'list'" class="flex-1 overflow-auto p-4 sm:p-6">
                <!-- Loading State -->
                <div v-if="isLoading" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lead</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontakt</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qiymat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ball</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Manba</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Operator</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="i in 5" :key="i" class="animate-pulse">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600"></div>
                                        <div>
                                            <div class="w-24 h-4 bg-gray-300 dark:bg-gray-600 rounded mb-1"></div>
                                            <div class="w-16 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4"><div class="w-28 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-16 h-5 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-20 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-16 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-16 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-20 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div></td>
                                <td class="px-4 py-4"><div class="w-20 h-6 bg-gray-200 dark:bg-gray-700 rounded float-right"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else-if="!leads || leads.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <div class="mx-auto w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-6">
                        <UsersIcon class="w-10 h-10 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Hali Lead yo'q</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Birinchi leadingizni qo'shing</p>
                    <Link
                        :href="route('business.sales.create')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Lead Qo'shish
                    </Link>
                </div>

                <!-- List Table -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-4 py-3 w-12">
                                    <button
                                        @click="selectAllLeads"
                                        :class="[
                                            'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                                            selectedLeads.length === filteredLeads.length && filteredLeads.length > 0
                                                ? 'bg-blue-500 border-blue-500 text-white'
                                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                                        ]"
                                    >
                                        <CheckIcon v-if="selectedLeads.length === filteredLeads.length && filteredLeads.length > 0" class="w-3 h-3" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lead</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontakt</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qiymat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ball</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Manba</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Operator</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="lead in filteredLeads"
                                :key="lead.id"
                                :class="[
                                    'transition-colors',
                                    isLeadSelected(lead)
                                        ? 'bg-blue-50 dark:bg-blue-900/20'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                ]"
                            >
                                <td class="px-4 py-4">
                                    <button
                                        @click="toggleLeadSelection(lead)"
                                        :class="[
                                            'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                                            isLeadSelected(lead)
                                                ? 'bg-blue-500 border-blue-500 text-white'
                                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400'
                                        ]"
                                    >
                                        <CheckIcon v-if="isLeadSelected(lead)" class="w-3 h-3" />
                                    </button>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white text-sm font-semibold', getAvatarColor(lead.name)]">
                                            {{ getInitials(lead.name) }}
                                        </div>
                                        <div>
                                            <Link
                                                :href="route('business.sales.show', lead.id)"
                                                class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400"
                                            >
                                                {{ lead.name }}
                                            </Link>
                                            <p v-if="lead.company" class="text-sm text-gray-500 dark:text-gray-400">{{ lead.company }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="space-y-1">
                                        <p v-if="lead.phone" class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                                            <PhoneIcon class="w-4 h-4 text-gray-400" />
                                            {{ lead.phone }}
                                        </p>
                                        <p v-if="lead.email" class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400">
                                            <EnvelopeIcon class="w-4 h-4 text-gray-400" />
                                            {{ lead.email }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium',
                                        pipelineStages.find(s => s.value === lead.status)?.lightBg,
                                        pipelineStages.find(s => s.value === lead.status)?.borderColor,
                                        'border'
                                    ]">
                                        <span :class="[pipelineStages.find(s => s.value === lead.status)?.bgColor, 'w-1.5 h-1.5 rounded-full mr-1.5']"></span>
                                        {{ pipelineStages.find(s => s.value === lead.status)?.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span v-if="lead.estimated_value" class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ formatFullCurrency(lead.estimated_value) }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-0.5">
                                        <StarIcon
                                            v-for="i in 5"
                                            :key="i"
                                            :class="[
                                                'w-4 h-4',
                                                i <= getScoreStars(lead.score || 0)
                                                    ? 'text-yellow-400'
                                                    : 'text-gray-200 dark:text-gray-700'
                                            ]"
                                        />
                                        <span class="ml-1 text-xs text-gray-500">{{ lead.score || 0 }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span v-if="lead.source" class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ lead.source.name }}
                                    </span>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div v-if="lead.assigned_to" class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                            {{ lead.assigned_to.name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                            {{ lead.assigned_to.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="route('business.sales.show', lead.id)"
                                            class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <EyeIcon class="w-4 h-4" />
                                        </Link>
                                        <Link
                                            :href="route('business.sales.edit', lead.id)"
                                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <PencilIcon class="w-4 h-4" />
                                        </Link>
                                        <button
                                            v-if="lead.status !== 'won' && lead.status !== 'lost'"
                                            @click="openLostReasonModal(lead)"
                                            class="p-2 text-gray-400 hover:text-orange-600 dark:hover:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                                            title="Yo'qotilgan deb belgilash"
                                        >
                                            <XMarkIcon class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="confirmDelete(lead)"
                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="O'chirish"
                                        >
                                            <TrashIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Analytics View -->
            <div v-else-if="viewMode === 'analytics'" class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Loading State -->
                <div v-if="analyticsLoading" class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <div class="h-8 w-48 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-4"></div>
                        <div class="h-80 bg-gray-100 dark:bg-gray-700/50 rounded-lg animate-pulse"></div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <div class="h-8 w-48 bg-gray-200 dark:bg-gray-700 rounded animate-pulse mb-4"></div>
                        <div class="h-80 bg-gray-100 dark:bg-gray-700/50 rounded-lg animate-pulse"></div>
                    </div>
                </div>

                <!-- Analytics Content -->
                <div v-else class="space-y-6">
                    <!-- Header with refresh button -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Sotuv Analitikasi</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Voronka va kanal statistikasi</p>
                        </div>
                        <button
                            @click="fetchAnalytics"
                            :disabled="analyticsLoading"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors disabled:opacity-50"
                        >
                            <svg class="w-4 h-4" :class="{ 'animate-spin': analyticsLoading }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Yangilash
                        </button>
                    </div>

                    <!-- Charts Grid -->
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <!-- Funnel Chart -->
                        <SalesFunnelChart :funnel-data="funnelData" />

                        <!-- Source Analytics Chart -->
                        <SourceAnalyticsChart :source-data="sourceData" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="deletingLead" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="cancelDelete"></div>

                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="deletingLead" class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 z-10">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                                        <TrashIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Lead o'chirish</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Bu amalni qaytarib bo'lmaydi</p>
                                    </div>
                                </div>

                                <p class="text-gray-700 dark:text-gray-300 mb-6">
                                    <strong class="text-gray-900 dark:text-white">{{ deletingLead.name }}</strong> nomli Leadni o'chirishni xohlaysizmi?
                                </p>

                                <div class="flex gap-3">
                                    <button
                                        @click="cancelDelete"
                                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        @click="deleteLead"
                                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors"
                                    >
                                        O'chirish
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk Selection Bar -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="selectedLeads.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40">
                    <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl px-6 py-4 flex items-center gap-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold">{{ selectedLeads.length }}</span>
                            </div>
                            <span class="text-white font-medium">ta lead tanlandi</span>
                        </div>

                        <div class="h-8 w-px bg-slate-600"></div>

                        <div class="flex items-center gap-3">
                            <!-- Bulk SMS Button -->
                            <button
                                @click="handleBulkSmsClick"
                                :title="!smsConnected ? 'SMS sozlamalarini sozlash uchun bosing' : 'Tanlangan leadlarga SMS yuborish'"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2 font-medium rounded-xl transition-all',
                                    smsConnected
                                        ? 'bg-gradient-to-r from-teal-500 to-cyan-600 text-white hover:from-teal-600 hover:to-cyan-700'
                                        : 'bg-slate-700 text-slate-300 hover:bg-slate-600'
                                ]"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                {{ smsConnected ? 'SMS yuborish' : 'SMS sozlash' }}
                            </button>

                            <!-- Bulk Assign Button (only for owner or sales head) -->
                            <button
                                v-if="canAssignLeads"
                                @click="handleBulkAssignClick"
                                title="Tanlangan leadlarni operatorga tayinlash"
                                class="inline-flex items-center gap-2 px-4 py-2 font-medium rounded-xl transition-all bg-gradient-to-r from-orange-500 to-amber-600 text-white hover:from-orange-600 hover:to-amber-700"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                                Operatorga tayinlash
                            </button>

                            <!-- Select All / Clear -->
                            <button
                                @click="selectAllLeads"
                                class="px-4 py-2 bg-slate-700 text-slate-300 rounded-xl hover:bg-slate-600 transition-colors"
                            >
                                {{ selectedLeads.length === filteredLeads.length ? 'Tanlovni bekor qilish' : 'Barchasini tanlash' }}
                            </button>

                            <!-- Close -->
                            <button
                                @click="clearSelection"
                                class="p-2 text-slate-400 hover:text-white transition-colors"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Bulk SMS Modal -->
        <BulkSmsModal
            :show="showBulkSmsModal"
            :leads="selectedLeads"
            @close="showBulkSmsModal = false"
            @sent="handleSmsSent"
        />

        <!-- Bulk Assign Modal -->
        <BulkAssignModal
            :show="showBulkAssignModal"
            :leads="selectedLeads"
            @close="showBulkAssignModal = false"
            @assigned="handleBulkAssigned"
        />

        <!-- Lost Reason Modal -->
        <LostReasonModal
            :show="showLostReasonModal"
            :lead="leadToMarkLost"
            @close="showLostReasonModal = false; leadToMarkLost = null"
            @confirm="handleLostReasonConfirm"
        />
    </BusinessLayout>
</template>

<style scoped>
/* Custom scrollbar for kanban columns */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.5);
}

/* Horizontal scroll for kanban board */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.5);
}
</style>
