<script setup>
import { ref, computed } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PhoneIcon,
    EnvelopeIcon,
    BuildingOfficeIcon,
    UserIcon,
    CalendarIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    CurrencyDollarIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
    TrashIcon,
    ShareIcon,
    EllipsisVerticalIcon,
    ChevronRightIcon,
    TagIcon,
    PaperAirplaneIcon,
    PlusIcon,
    ArrowPathIcon,
    CheckIcon,
    XMarkIcon,
    BellIcon,
    UserPlusIcon,
    ArrowUpRightIcon,
    ChatBubbleOvalLeftIcon,
    PhoneArrowUpRightIcon,
    DocumentPlusIcon,
    CogIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarSolidIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
});

const showDeleteModal = ref(false);
const activeTab = ref('activity');
const showMoreActions = ref(false);
const newNote = ref('');
const isAddingNote = ref(false);

// Status configuration
const statusConfig = {
    new: { label: 'Yangi', color: 'bg-blue-500', bgLight: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-600 dark:text-blue-400', icon: SparklesIcon },
    contacted: { label: "Bog'lanildi", color: 'bg-indigo-500', bgLight: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-600 dark:text-indigo-400', icon: PhoneIcon },
    qualified: { label: 'Qualified', color: 'bg-purple-500', bgLight: 'bg-purple-50 dark:bg-purple-900/20', text: 'text-purple-600 dark:text-purple-400', icon: CheckCircleIcon },
    proposal: { label: 'Taklif', color: 'bg-orange-500', bgLight: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-600 dark:text-orange-400', icon: DocumentTextIcon },
    negotiation: { label: 'Muzokara', color: 'bg-yellow-500', bgLight: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-600 dark:text-yellow-400', icon: ChatBubbleLeftRightIcon },
    won: { label: 'Yutildi', color: 'bg-green-500', bgLight: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-600 dark:text-green-400', icon: CheckCircleIcon },
    lost: { label: "Yo'qoldi", color: 'bg-red-500', bgLight: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-600 dark:text-red-400', icon: XCircleIcon },
};

const pipelineStages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won'];

const currentStatus = computed(() => statusConfig[props.lead.status] || statusConfig.new);
const currentStageIndex = computed(() => pipelineStages.indexOf(props.lead.status));

// Mock activity history - bu keyinchalik backenddan keladi
const activityHistory = ref([
    { id: 1, type: 'created', title: 'Lead yaratildi', description: 'Yangi lead tizimga qo\'shildi', date: props.lead.created_at, icon: PlusIcon, color: 'bg-blue-500' },
    { id: 2, type: 'status_change', title: 'Holat o\'zgardi', description: `Holat "${statusConfig[props.lead.status]?.label}" ga o'zgartirildi`, date: props.lead.created_at, icon: ArrowPathIcon, color: 'bg-purple-500' },
]);

// Add contact history if exists
if (props.lead.last_contacted_at) {
    activityHistory.value.push({
        id: 3,
        type: 'contact',
        title: 'Bog\'lanildi',
        description: 'Lead bilan aloqa o\'rnatildi',
        date: props.lead.last_contacted_at,
        icon: PhoneArrowUpRightIcon,
        color: 'bg-green-500'
    });
}

const formatCurrency = (amount) => {
    if (!amount) return "Belgilanmagan";
    return new Intl.NumberFormat('uz-UZ').format(amount) + " so'm";
};

const getScoreColor = (score) => {
    if (score >= 70) return 'text-green-500';
    if (score >= 40) return 'text-yellow-500';
    return 'text-red-500';
};

const getScoreBg = (score) => {
    if (score >= 70) return 'from-green-500 to-emerald-600';
    if (score >= 40) return 'from-yellow-500 to-orange-500';
    return 'from-red-500 to-rose-600';
};

const getScoreLabel = (score) => {
    if (score >= 70) return 'Yuqori sifatli';
    if (score >= 40) return "O'rtacha";
    return 'Past';
};

const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const deleteLead = () => {
    router.delete(route('business.sales.destroy', props.lead.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        }
    });
};

const updateStatus = (newStatus) => {
    router.put(route('business.sales.update', props.lead.id), {
        name: props.lead.name,
        email: props.lead.email,
        phone: props.lead.phone,
        company: props.lead.company,
        source_id: props.lead.source?.id || null,
        status: newStatus,
        score: props.lead.score,
        estimated_value: props.lead.estimated_value,
        notes: props.lead.notes,
    }, {
        preserveScroll: true,
    });
};

const addNote = () => {
    if (!newNote.value.trim()) return;
    // Bu keyinchalik backendga yuboriladi
    activityHistory.value.unshift({
        id: Date.now(),
        type: 'note',
        title: 'Izoh qo\'shildi',
        description: newNote.value,
        date: new Date().toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
        icon: ChatBubbleOvalLeftIcon,
        color: 'bg-gray-500'
    });
    newNote.value = '';
    isAddingNote.value = false;
};

const tabs = [
    { id: 'activity', label: 'Faoliyat tarixi', icon: ClockIcon },
    { id: 'notes', label: 'Izohlar', icon: DocumentTextIcon },
    { id: 'tasks', label: 'Vazifalar', icon: CheckCircleIcon },
    { id: 'files', label: 'Fayllar', icon: DocumentPlusIcon },
];
</script>

<template>
    <BusinessLayout :title="lead.name">
        <Head :title="lead.name" />

        <div class="h-full flex flex-col -m-4 sm:-m-6 lg:-m-8">
            <!-- Compact Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-between">
                    <!-- Left: Back & Lead Info -->
                    <div class="flex items-center gap-4">
                        <Link
                            :href="route('business.sales.index')"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm shadow-lg">
                                {{ getInitials(lead.name) }}
                            </div>
                            <div>
                                <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ lead.name }}</h1>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.company || lead.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions -->
                    <div class="flex items-center gap-2">
                        <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" title="Qo'ng'iroq">
                            <PhoneIcon class="w-5 h-5" />
                        </a>
                        <a v-if="lead.email" :href="`mailto:${lead.email}`" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" title="Email">
                            <EnvelopeIcon class="w-5 h-5" />
                        </a>
                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>
                        <Link :href="route('business.sales.edit', lead.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors shadow-lg shadow-blue-500/25">
                            <PencilSquareIcon class="w-4 h-4" />
                            Tahrirlash
                        </Link>
                        <button @click="showDeleteModal = true" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="O'chirish">
                            <TrashIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pipeline Progress -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center gap-2">
                    <template v-for="(stage, index) in pipelineStages" :key="stage">
                        <button
                            @click="updateStatus(stage)"
                            :class="[
                                'flex-1 h-2 rounded-full transition-all duration-300 hover:opacity-80',
                                index <= currentStageIndex ? statusConfig[lead.status].color : 'bg-gray-200 dark:bg-gray-700'
                            ]"
                            :title="statusConfig[stage].label"
                        ></button>
                    </template>
                    <span :class="['ml-4 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium', currentStatus.bgLight, currentStatus.text]">
                        <component :is="currentStatus.icon" class="w-4 h-4" />
                        {{ currentStatus.label }}
                    </span>
                </div>
            </div>

            <!-- Main Content - Full Width -->
            <div class="flex-1 overflow-auto">
                <div class="h-full flex">
                    <!-- Left Panel - Lead Details -->
                    <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
                        <!-- Lead Card -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <!-- Large Avatar -->
                            <div class="flex flex-col items-center text-center mb-6">
                                <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-3xl shadow-xl mb-4">
                                    {{ getInitials(lead.name) }}
                                </div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ lead.name }}</h2>
                                <p v-if="lead.company" class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                    <BuildingOfficeIcon class="w-4 h-4" />
                                    {{ lead.company }}
                                </p>
                            </div>

                            <!-- Score Circle -->
                            <div class="flex justify-center mb-6">
                                <div class="relative">
                                    <svg class="w-28 h-28 transform -rotate-90">
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                                        <circle cx="56" cy="56" r="48" stroke="url(#scoreGradient)" stroke-width="8" fill="transparent" stroke-linecap="round" :stroke-dasharray="`${(lead.score / 100) * 301.59} 301.59`" />
                                        <defs>
                                            <linearGradient id="scoreGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" :stop-color="lead.score >= 70 ? '#10b981' : lead.score >= 40 ? '#f59e0b' : '#ef4444'" />
                                                <stop offset="100%" :stop-color="lead.score >= 70 ? '#059669' : lead.score >= 40 ? '#d97706' : '#dc2626'" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ lead.score || 0 }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">ball</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="space-y-3">
                                <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors group">
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800/30 transition-colors">
                                        <PhoneIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Telefon</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.phone }}</p>
                                    </div>
                                    <ArrowUpRightIcon class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                </a>

                                <a v-if="lead.email" :href="`mailto:${lead.email}`" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-800/30 transition-colors">
                                        <EnvelopeIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Email</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ lead.email }}</p>
                                    </div>
                                    <ArrowUpRightIcon class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                                </a>
                            </div>
                        </div>

                        <!-- Deal Info -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Bitim ma'lumotlari</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Taxminiy qiymat</span>
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(lead.estimated_value) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Manba</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.source?.name || 'Belgilanmagan' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Sifat bahosi</span>
                                    <span class="text-sm font-medium" :class="getScoreColor(lead.score)">{{ getScoreLabel(lead.score) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Tezkor harakatlar</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <a v-if="lead.phone" :href="`https://wa.me/${lead.phone.replace(/\D/g, '')}`" target="_blank" class="flex flex-col items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-xl transition-colors">
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <span class="text-xs font-medium">WhatsApp</span>
                                </a>
                                <a v-if="lead.phone" :href="`https://t.me/${lead.phone.replace(/\D/g, '')}`" target="_blank" class="flex flex-col items-center gap-2 p-3 bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 text-sky-700 dark:text-sky-400 rounded-xl transition-colors">
                                    <PaperAirplaneIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">Telegram</span>
                                </a>
                                <button class="flex flex-col items-center gap-2 p-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl transition-colors">
                                    <BellIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">Eslatma</span>
                                </button>
                                <button class="flex flex-col items-center gap-2 p-3 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-xl transition-colors">
                                    <UserPlusIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">Tayinlash</span>
                                </button>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="p-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Vaqt chizig'i</h3>
                            <div class="space-y-4">
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <CalendarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Yaratilgan</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.created_at }}</p>
                                    </div>
                                </div>
                                <div v-if="lead.last_contacted_at" class="flex gap-3">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <PhoneIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Oxirgi aloqa</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.last_contacted_at }}</p>
                                    </div>
                                </div>
                                <div v-if="lead.converted_at" class="flex gap-3">
                                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <CheckCircleIcon class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">Konvertatsiya</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.converted_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Activity & History -->
                    <div class="flex-1 flex flex-col bg-gray-50 dark:bg-gray-900">
                        <!-- Tabs -->
                        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6">
                            <nav class="flex gap-1">
                                <button
                                    v-for="tab in tabs"
                                    :key="tab.id"
                                    @click="activeTab = tab.id"
                                    :class="[
                                        'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                                        activeTab === tab.id
                                            ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                    ]"
                                >
                                    <component :is="tab.icon" class="w-4 h-4" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Activity Tab -->
                            <div v-if="activeTab === 'activity'" class="max-w-3xl mx-auto">
                                <!-- Add Note Form -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
                                    <div v-if="!isAddingNote" class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ getInitials(lead.name) }}
                                        </div>
                                        <button
                                            @click="isAddingNote = true"
                                            class="flex-1 text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 rounded-xl text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                        >
                                            Izoh, eslatma yoki faoliyat qo'shing...
                                        </button>
                                    </div>
                                    <div v-else>
                                        <textarea
                                            v-model="newNote"
                                            rows="4"
                                            placeholder="Izoh yozing..."
                                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                            autofocus
                                        ></textarea>
                                        <div class="flex items-center justify-between mt-3">
                                            <div class="flex items-center gap-2">
                                                <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" title="Fayl biriktirish">
                                                    <DocumentPlusIcon class="w-5 h-5" />
                                                </button>
                                                <button class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors" title="Eslatma qo'shish">
                                                    <BellIcon class="w-5 h-5" />
                                                </button>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button
                                                    @click="isAddingNote = false; newNote = ''"
                                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                                >
                                                    Bekor qilish
                                                </button>
                                                <button
                                                    @click="addNote"
                                                    :disabled="!newNote.trim()"
                                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors"
                                                >
                                                    Saqlash
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Timeline -->
                                <div class="space-y-4">
                                    <div
                                        v-for="activity in activityHistory"
                                        :key="activity.id"
                                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', activity.color]">
                                                <component :is="activity.icon" class="w-5 h-5 text-white" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ activity.title }}</h4>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ activity.date }}</span>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ activity.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State if no activity -->
                                <div v-if="activityHistory.length === 0" class="text-center py-12">
                                    <ClockIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Faoliyat tarixi yo'q</h3>
                                    <p class="text-gray-500 dark:text-gray-400">Bu lead bilan bog'liq faoliyatlar bu yerda ko'rsatiladi</p>
                                </div>
                            </div>

                            <!-- Notes Tab -->
                            <div v-if="activeTab === 'notes'" class="max-w-3xl mx-auto">
                                <div v-if="lead.notes" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Izohlar</h3>
                                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ lead.notes }}</p>
                                </div>
                                <div v-else class="text-center py-12">
                                    <DocumentTextIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Izohlar yo'q</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">Bu lead uchun hech qanday izoh qo'shilmagan</p>
                                    <Link :href="route('business.sales.edit', lead.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                        <PlusIcon class="w-4 h-4" />
                                        Izoh qo'shish
                                    </Link>
                                </div>
                            </div>

                            <!-- Tasks Tab -->
                            <div v-if="activeTab === 'tasks'" class="max-w-3xl mx-auto text-center py-12">
                                <CheckCircleIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Vazifalar</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Bu funksiya tez orada qo'shiladi</p>
                                <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-medium rounded-lg cursor-not-allowed">
                                    <PlusIcon class="w-4 h-4" />
                                    Vazifa qo'shish
                                </button>
                            </div>

                            <!-- Files Tab -->
                            <div v-if="activeTab === 'files'" class="max-w-3xl mx-auto text-center py-12">
                                <DocumentPlusIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Fayllar</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-4">Bu funksiya tez orada qo'shiladi</p>
                                <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-medium rounded-lg cursor-not-allowed">
                                    <PlusIcon class="w-4 h-4" />
                                    Fayl yuklash
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="showDeleteModal" class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl z-10">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <TrashIcon class="w-8 h-8 text-red-600 dark:text-red-400" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Leadni o'chirish</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-6">
                                    "{{ lead.name }}" leadini o'chirmoqchimisiz? Bu amalni qaytarib bo'lmaydi.
                                </p>
                                <div class="flex gap-3">
                                    <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                                        Bekor qilish
                                    </button>
                                    <button @click="deleteLead" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                                        O'chirish
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </BusinessLayout>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.3);
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.5);
}
</style>
