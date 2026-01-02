<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
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
    ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';

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
});

// View mode: 'kanban' or 'list'
const viewMode = ref('kanban');
const searchQuery = ref('');
const sourceFilter = ref('');
const deletingLead = ref(null);
const isDragging = ref(false);
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

// Filter leads based on search and source
const filteredLeads = computed(() => {
    let filtered = props.leads;

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

const handleDrop = (e, newStatus) => {
    e.preventDefault();
    dragOverColumn.value = null;

    if (draggedLead.value && draggedLead.value.status !== newStatus) {
        // Update lead status via API
        router.put(route('business.sales.update', draggedLead.value.id), {
            name: draggedLead.value.name,
            email: draggedLead.value.email,
            phone: draggedLead.value.phone,
            company: draggedLead.value.company,
            source_id: draggedLead.value.source?.id || null,
            status: newStatus,
            score: draggedLead.value.score,
            estimated_value: draggedLead.value.estimated_value,
        }, {
            preserveScroll: true,
            preserveState: true,
            only: ['leads', 'stats'],
        });
    }

    isDragging.value = false;
    draggedLead.value = null;
};

const confirmDelete = (lead) => {
    deletingLead.value = lead;
    showLeadMenu.value = null;
};

const deleteLead = () => {
    if (deletingLead.value) {
        router.delete(route('business.sales.destroy', deletingLead.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                deletingLead.value = null;
            },
        });
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
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ stats?.total_leads || 0 }} ta lead • {{ formatFullCurrency(stats?.pipeline_value) }} pipeline
                            </p>
                        </div>

                        <!-- Quick Stats -->
                        <div class="hidden xl:flex items-center gap-6 pl-6 border-l border-gray-200 dark:border-gray-700">
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

            <!-- Kanban Board -->
            <div v-if="viewMode === 'kanban'" class="flex-1 overflow-x-auto overflow-y-hidden">
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
                                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 cursor-grab active:cursor-grabbing hover:border-blue-300 dark:hover:border-blue-600 hover:shadow-md transition-all group"
                                >
                                    <!-- Card Header -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2 min-w-0">
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
                                        <!-- Value or Source -->
                                        <div v-if="lead.estimated_value" class="flex items-center gap-1 text-green-600 dark:text-green-400">
                                            <CurrencyDollarIcon class="w-3.5 h-3.5" />
                                            <span class="text-xs font-semibold">{{ formatCurrency(lead.estimated_value) }}</span>
                                        </div>
                                        <div v-else-if="lead.source" class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-500 dark:text-gray-400 truncate max-w-20">
                                            {{ lead.source.name }}
                                        </div>
                                        <div v-else class="text-xs text-gray-400">-</div>

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
            <div v-else class="flex-1 overflow-auto p-4 sm:p-6">
                <!-- Empty State -->
                <div v-if="!leads || leads.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
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
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lead</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kontakt</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Holat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Qiymat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ball</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Manba</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="lead in filteredLeads"
                                :key="lead.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
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
