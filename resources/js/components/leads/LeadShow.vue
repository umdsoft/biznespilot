<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from '@/i18n';
import SmsModal from '@/components/SmsModal.vue';
import PaymentModal from '@/components/PaymentModal.vue';
import CallWidget from '@/components/CallWidget.vue';
import TaskModal from '@/components/TaskModal.vue';
import LeadAssignModal from '@/components/LeadAssignModal.vue';
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    PhoneIcon,
    EnvelopeIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
    TrashIcon,
    PlusIcon,
    ArrowPathIcon,
    XMarkIcon,
    BellIcon,
    UserPlusIcon,
    MapPinIcon,
    UserIcon,
    CurrencyDollarIcon,
    DocumentPlusIcon,
    PhoneArrowUpRightIcon,
    ArrowUpRightIcon,
    PaperAirplaneIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { ChatBubbleBottomCenterTextIcon, ChatBubbleOvalLeftIcon } from '@heroicons/vue/24/solid';
import { formatFullCurrency } from '@/utils/formatting';

const { t } = useI18n();

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
    operators: {
        type: Array,
        default: () => [],
    },
    sources: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Object,
        default: () => ({}),
    },
    districts: {
        type: Object,
        default: () => ({}),
    },
    canAssignLeads: {
        type: Boolean,
        default: false,
    },
    // Panel type: 'saleshead', 'business', or 'operator'
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['saleshead', 'business', 'operator'].includes(value),
    },
});

// Computed route prefixes based on panel type
const routePrefix = computed(() => {
    if (props.panelType === 'saleshead') return '/sales-head';
    if (props.panelType === 'operator') return '/operator/leads';
    return '/business/api/sales';
});
const indexRoute = computed(() => {
    if (props.panelType === 'saleshead') return '/sales-head/leads';
    if (props.panelType === 'operator') return '/operator/leads';
    return route('business.sales.index');
});
const backRoute = computed(() => {
    if (props.panelType === 'saleshead') return '/sales-head/leads';
    if (props.panelType === 'operator') return '/operator/leads';
    return route('business.sales.index');
});

// Theme colors based on panel type
const themeColors = computed(() => {
    if (props.panelType === 'saleshead') {
        return {
            primary: 'emerald',
            gradient: 'from-emerald-500 to-teal-600',
            ring: 'focus:ring-emerald-500',
            bg: 'bg-emerald-600 hover:bg-emerald-700',
            bgDisabled: 'disabled:bg-emerald-400',
            border: 'border-emerald-500',
            text: 'text-emerald-600 dark:text-emerald-400',
            bgLight: 'bg-emerald-50 dark:bg-emerald-900/20',
            spinner: 'border-emerald-500',
        };
    }
    return {
        primary: 'blue',
        gradient: 'from-blue-500 to-purple-600',
        ring: 'focus:ring-blue-500',
        bg: 'bg-blue-600 hover:bg-blue-700',
        bgDisabled: 'disabled:bg-blue-400',
        border: 'border-blue-500',
        text: 'text-blue-600 dark:text-blue-400',
        bgLight: 'bg-blue-50 dark:bg-blue-900/20',
        spinner: 'border-blue-500',
    };
});

// SMS Modal state
const showSmsModal = ref(false);
const smsConnected = ref(false);

// Call Widget state
const showCallWidget = ref(false);

// Payment modal state
const showPaymentModal = ref(false);

// Assign modal state
const showAssignModal = ref(false);
const leadData = ref(null);

// Edit form state
const editForm = ref({
    name: props.lead.name || '',
    phone: props.lead.phone || '',
    phone2: props.lead.phone2 || '',
    email: props.lead.email || '',
    company: props.lead.company || '',
    birth_date: props.lead.birth_date || '',
    gender: props.lead.gender || '',
    region: props.lead.region || '',
    district: props.lead.district || '',
    address: props.lead.address || '',
    source_id: props.lead.source_id || '',
    notes: props.lead.notes || '',
});
const isSaving = ref(false);
const showPhone2 = ref(!!props.lead.phone2);

// Get districts for selected region
const availableDistricts = computed(() => {
    if (!editForm.value.region || !props.districts[editForm.value.region]) {
        return {};
    }
    return props.districts[editForm.value.region];
});

// Watch for region change
const onRegionChange = () => {
    if (editForm.value.district && props.districts[editForm.value.region]) {
        if (!props.districts[editForm.value.region][editForm.value.district]) {
            editForm.value.district = '';
        }
    } else {
        editForm.value.district = '';
    }
};

// Task state
const showTaskModal = ref(false);
const editingTask = ref(null);
const tasks = ref({
    overdue: [],
    today: [],
    tomorrow: [],
    this_week: [],
    later: [],
    completed: [],
});
const tasksLoading = ref(false);

// Calls state
const calls = ref([]);
const callStats = ref({
    total_calls: 0,
    outbound_calls: 0,
    inbound_calls: 0,
    answered_calls: 0,
    missed_calls: 0,
    total_duration: 0,
    avg_duration: 0,
    answer_rate: 0,
    total_duration_formatted: '0:00',
});
const callsLoading = ref(false);

// Audio player state
const playingCallId = ref(null);
const loadingRecordingId = ref(null);
const audioPlayer = ref(null);

// Play audio from URL
const playAudio = (call, url) => {
    // Stop any currently playing audio
    if (audioPlayer.value) {
        audioPlayer.value.pause();
    }

    audioPlayer.value = new Audio(url);
    audioPlayer.value.play().catch(err => {
        console.error('Audio playback error:', err);
        showNotification('error', 'Audio ijro etishda xatolik');
        playingCallId.value = null;
    });

    audioPlayer.value.onended = () => {
        playingCallId.value = null;
        audioPlayer.value = null;
    };

    audioPlayer.value.onerror = () => {
        showNotification('error', 'Audio faylni yuklab bo\'lmadi');
        playingCallId.value = null;
        audioPlayer.value = null;
    };

    playingCallId.value = call.id;
};

// Toggle audio playback
const toggleAudio = async (call) => {
    if (playingCallId.value === call.id) {
        // Stop playing
        if (audioPlayer.value) {
            audioPlayer.value.pause();
            audioPlayer.value = null;
        }
        playingCallId.value = null;
        return;
    }

    // If we already have the recording URL, play directly
    if (call.recording_url) {
        playAudio(call, call.recording_url);
        return;
    }

    // Fetch recording URL from API
    loadingRecordingId.value = call.id;
    try {
        const baseUrl = props.panelType === 'saleshead' ? '/sales-head' : '/business/api/sales';
        const response = await axios.get(`${baseUrl}/calls/${call.id}/recording`);

        if (response.data.success && response.data.recording_url) {
            // Update call in local state
            const index = calls.value.findIndex(c => c.id === call.id);
            if (index !== -1) {
                calls.value[index].recording_url = response.data.recording_url;
            }
            playAudio(call, response.data.recording_url);
        } else {
            showNotification('error', response.data.error || 'Yozuv topilmadi');
        }
    } catch (error) {
        console.error('Failed to fetch recording:', error);
        showNotification('error', 'Yozuvni yuklashda xatolik');
    } finally {
        loadingRecordingId.value = null;
    }
};

// Update call status
const updatingCallId = ref(null);
const updateCallStatus = async (call, newStatus) => {
    if (updatingCallId.value === call.id) return;

    updatingCallId.value = call.id;
    try {
        const response = await axios.patch(`/business/api/sales/calls/${call.id}/status`, {
            status: newStatus,
        });

        if (response.data.success) {
            // Update the call in the local list
            const index = calls.value.findIndex(c => c.id === call.id);
            if (index !== -1) {
                calls.value[index] = {
                    ...calls.value[index],
                    status: response.data.call.status,
                    full_label: response.data.call.full_label,
                    duration: response.data.call.duration,
                    duration_formatted: response.data.call.duration_formatted,
                };
            }
            showNotification('success', response.data.message);
            // Reload stats
            loadCalls();
        }
    } catch (error) {
        console.error('Failed to update call status:', error);
        showNotification('error', error.response?.data?.error || 'Statusni yangilashda xatolik');
    } finally {
        updatingCallId.value = null;
    }
};

// Activity state
const newNote = ref('');
const isAddingNote = ref(false);
const isSubmittingNote = ref(false);
const activities = ref([]);
const activitiesLoading = ref(false);

// Notification state
const notification = ref({ show: false, type: 'success', message: '' });
const showNotification = (type, message) => {
    notification.value = { show: true, type, message };
    setTimeout(() => {
        notification.value.show = false;
    }, 3000);
};

// API endpoints based on panel type
const getApiEndpoint = (action) => {
    const baseUrl = props.panelType === 'saleshead' ? '/sales-head/leads' : '/business/api/sales/leads';

    const endpoints = {
        activities: `${baseUrl}/${props.lead.id}/activities`,
        notes: `${baseUrl}/${props.lead.id}/notes`,
        status: `${baseUrl}/${props.lead.id}/status`,
        assign: `${baseUrl}/${props.lead.id}/assign`,
        update: `${baseUrl}/${props.lead.id}`,
        delete: `${baseUrl}/${props.lead.id}`,
        calls: `${baseUrl}/${props.lead.id}/calls`,
        tasks: props.panelType === 'saleshead'
            ? `/sales-head/leads/${props.lead.id}/tasks`
            : route('business.tasks.lead', props.lead.id),
    };

    return endpoints[action];
};

// Load activities from API
const loadActivities = async () => {
    activitiesLoading.value = true;
    try {
        const response = await axios.get(getApiEndpoint('activities'));
        activities.value = response.data.activities || [];
    } catch (error) {
        console.error('Failed to load activities:', error);
    } finally {
        activitiesLoading.value = false;
    }
};

// Get icon for activity type
const getActivityIcon = (type) => {
    const icons = {
        created: PlusIcon,
        updated: PencilSquareIcon,
        status_changed: ArrowPathIcon,
        note_added: DocumentTextIcon,
        assigned: UserPlusIcon,
        contacted: PhoneIcon,
        task_created: BellIcon,
        task_completed: CheckCircleIcon,
    };
    return icons[type] || ClockIcon;
};

// Get color for activity type
const getActivityColor = (type) => {
    const colors = {
        created: 'bg-emerald-500',
        updated: 'bg-blue-500',
        status_changed: 'bg-purple-500',
        note_added: 'bg-teal-500',
        assigned: 'bg-orange-500',
        contacted: 'bg-green-500',
        task_created: 'bg-indigo-500',
        task_completed: 'bg-green-600',
    };
    return colors[type] || 'bg-gray-500';
};

// Other state
const showDeleteModal = ref(false);
const activeTab = ref('activity');
const showAssignDropdown = ref(false);
const isAssigning = ref(false);

// Task modal state (for SalesHead inline task creation)
const taskForm = ref({
    title: '',
    type: 'task',
    priority: 'normal',
    due_date: '',
    due_time: '',
    description: '',
});
const taskFormErrors = ref({});
const isSavingTask = ref(false);

// Check SMS status
const checkSmsStatus = async () => {
    try {
        const smsRoute = props.panelType === 'saleshead' ? '/business/sms/status' : route('business.sms.status');
        const response = await axios.get(smsRoute);
        smsConnected.value = response.data.connected;
    } catch (error) {
        console.error('Failed to check SMS status:', error);
    }
};

// Status configuration
const statusConfig = computed(() => ({
    new: { label: t('leads.pipeline.new'), color: 'bg-blue-500', bgLight: 'bg-blue-50 dark:bg-blue-900/20', text: 'text-blue-600 dark:text-blue-400', icon: SparklesIcon },
    contacted: { label: t('leads.pipeline.contacted'), color: 'bg-indigo-500', bgLight: 'bg-indigo-50 dark:bg-indigo-900/20', text: 'text-indigo-600 dark:text-indigo-400', icon: PhoneIcon },
    callback: { label: t('leads.pipeline.callback'), color: 'bg-purple-500', bgLight: 'bg-purple-50 dark:bg-purple-900/20', text: 'text-purple-600 dark:text-purple-400', icon: ClockIcon },
    considering: { label: t('leads.pipeline.considering'), color: 'bg-orange-500', bgLight: 'bg-orange-50 dark:bg-orange-900/20', text: 'text-orange-600 dark:text-orange-400', icon: DocumentTextIcon },
    meeting_scheduled: { label: t('leads.pipeline.meeting_scheduled'), color: 'bg-yellow-500', bgLight: 'bg-yellow-50 dark:bg-yellow-900/20', text: 'text-yellow-600 dark:text-yellow-400', icon: CalendarIcon },
    meeting_attended: { label: t('leads.pipeline.meeting_attended'), color: 'bg-teal-500', bgLight: 'bg-teal-50 dark:bg-teal-900/20', text: 'text-teal-600 dark:text-teal-400', icon: ChatBubbleLeftRightIcon },
    won: { label: t('leads.pipeline.won'), color: 'bg-green-500', bgLight: 'bg-green-50 dark:bg-green-900/20', text: 'text-green-600 dark:text-green-400', icon: CheckCircleIcon },
    lost: { label: t('leads.pipeline.lost'), color: 'bg-red-500', bgLight: 'bg-red-50 dark:bg-red-900/20', text: 'text-red-600 dark:text-red-400', icon: XCircleIcon },
}));

const pipelineStages = ['new', 'contacted', 'callback', 'considering', 'meeting_scheduled', 'meeting_attended', 'won'];

const currentStatus = computed(() => statusConfig.value[props.lead.status] || statusConfig.value.new);
const currentStageIndex = computed(() => pipelineStages.indexOf(props.lead.status));

// Get region label
const getRegionLabel = (key) => {
    return props.regions[key] || key || t('leads.show.not_specified');
};

// Get district label
const getDistrictLabel = (regionKey, districtKey) => {
    if (!regionKey || !districtKey) return districtKey || t('leads.show.not_specified');
    return props.districts[regionKey]?.[districtKey] || districtKey;
};

// Get gender label
const getGenderLabel = (gender) => {
    if (gender === 'male') return t('leads.show.gender_male');
    if (gender === 'female') return t('leads.show.gender_female');
    return t('leads.show.not_specified');
};

// Format birth date for display
const formatBirthDate = (date) => {
    if (!date) return t('leads.show.not_specified');
    const d = new Date(date);
    return d.toLocaleDateString('uz-UZ', { year: 'numeric', month: 'long', day: 'numeric' });
};

// Format currency
const formatCurrency = (amount) => {
    if (!amount) return t('leads.show.not_specified');
    return formatFullCurrency(amount);
};

// Save lead
const saveLead = async () => {
    isSaving.value = true;
    try {
        const response = await axios.put(getApiEndpoint('update'), editForm.value);
        showNotification('success', response.data.message || 'Ma\'lumotlar saqlandi');
        router.reload({ only: ['lead'] });
        loadActivities();
    } catch (error) {
        console.error('Error saving lead:', error);
        showNotification('error', error.response?.data?.message || 'Xatolik yuz berdi');
    } finally {
        isSaving.value = false;
    }
};

// Add note (activity)
const addNote = async () => {
    if (!newNote.value.trim()) return;

    isSubmittingNote.value = true;
    try {
        await axios.post(getApiEndpoint('notes'), {
            note: newNote.value.trim()
        });
        newNote.value = '';
        isAddingNote.value = false;
        showNotification('success', 'Izoh qo\'shildi');
        loadActivities();
    } catch (error) {
        console.error('Error adding note:', error);
        showNotification('error', 'Izoh qo\'shishda xatolik');
    } finally {
        isSubmittingNote.value = false;
    }
};

// Load calls history
const loadCalls = async () => {
    callsLoading.value = true;
    try {
        const response = await axios.get(getApiEndpoint('calls'));
        calls.value = response.data.calls || [];
        callStats.value = response.data.stats || {
            total_calls: 0,
            outbound_calls: 0,
            inbound_calls: 0,
            answered_calls: 0,
            missed_calls: 0,
            total_duration: 0,
            avg_duration: 0,
            answer_rate: 0,
            total_duration_formatted: '0:00',
        };
    } catch (error) {
        console.error('Failed to load calls:', error);
    } finally {
        callsLoading.value = false;
    }
};

// Sync calls from PBX
const syncingCalls = ref(false);
const syncCalls = async () => {
    syncingCalls.value = true;
    try {
        const baseUrl = props.panelType === 'saleshead' ? '/sales-head/leads' : '/business/api/sales/leads';
        const response = await axios.post(`${baseUrl}/${props.lead.id}/sync-calls`);

        if (response.data.success) {
            calls.value = response.data.calls || [];
            callStats.value = response.data.stats || callStats.value;

            // Show appropriate notification based on warning/success
            if (response.data.warning) {
                showNotification('warning', response.data.message);
            } else {
                const synced = response.data.synced || 0;
                const linked = response.data.linked || 0;
                let msg = `Sinxronlandi: ${synced} ta yangi`;
                if (linked > 0) msg += `, ${linked} ta ulandi`;
                showNotification('success', msg);
            }
        } else {
            showNotification('error', response.data.error || 'Sinxronlash xatosi');
        }
    } catch (error) {
        console.error('Failed to sync calls:', error);
        // Check for specific error types and show helpful messages
        const errorMsg = error.response?.data?.error;
        if (errorMsg && (errorMsg.includes('autentifikatsiya') || errorMsg.includes('key_id'))) {
            showNotification('error', 'PBX sozlamalari xato. Telefoniya sozlamalarini tekshiring.');
        } else if (error.response?.status === 404) {
            showNotification('error', 'PBX ulangan emas');
        } else {
            showNotification('error', errorMsg || 'Sinxronlash xatosi');
        }
    } finally {
        syncingCalls.value = false;
    }
};

// Load tasks
const loadTasks = async () => {
    tasksLoading.value = true;
    try {
        const response = await axios.get(getApiEndpoint('tasks'));
        tasks.value = response.data.tasks || {
            overdue: [],
            today: [],
            tomorrow: [],
            this_week: [],
            later: [],
            completed: [],
        };
    } catch (error) {
        console.error('Failed to load tasks:', error);
    } finally {
        tasksLoading.value = false;
    }
};

// Open task modal
const openTaskModal = (task = null) => {
    editingTask.value = task;
    showTaskModal.value = true;
};

// Close task modal
const closeTaskModal = () => {
    showTaskModal.value = false;
    editingTask.value = null;
};

// On task saved
const onTaskSaved = () => {
    loadTasks();
};

// Complete task
const completeTask = async (task) => {
    try {
        const completeUrl = props.panelType === 'saleshead'
            ? `/sales-head/tasks/${task.id}/complete`
            : route('business.tasks.complete', task.id);
        await axios.post(completeUrl);
        loadTasks();
    } catch (error) {
        console.error('Failed to complete task:', error);
    }
};

// Delete task
const deleteTask = async (task) => {
    if (!confirm('Vazifani o\'chirmoqchimisiz?')) return;

    try {
        const deleteUrl = props.panelType === 'saleshead'
            ? `/sales-head/tasks/${task.id}`
            : route('business.tasks.destroy', task.id);
        await axios.delete(deleteUrl);
        loadTasks();
    } catch (error) {
        console.error('Failed to delete task:', error);
    }
};

const getInitials = (name) => {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const deleteLead = async () => {
    try {
        if (props.panelType === 'saleshead') {
            await axios.delete(getApiEndpoint('delete'));
            router.visit('/sales-head/leads');
        } else {
            router.delete(route('business.sales.destroy', props.lead.id), {
                onSuccess: () => {
                    showDeleteModal.value = false;
                }
            });
        }
    } catch (error) {
        console.error('Error deleting lead:', error);
    }
};

const updateStatus = async (newStatus) => {
    try {
        await axios.post(getApiEndpoint('status'), {
            status: newStatus,
        });
        router.reload({ only: ['lead'] });
        loadActivities();
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

// Assign lead (for SalesHead)
const assignLead = async (operatorId) => {
    isAssigning.value = true;
    try {
        await axios.post(getApiEndpoint('assign'), {
            assigned_to: operatorId,
        });
        showAssignDropdown.value = false;
        router.reload({ only: ['lead'] });
        loadActivities();
    } catch (error) {
        console.error('Error assigning lead:', error);
    } finally {
        isAssigning.value = false;
    }
};

// Open assign modal (for Business)
const openAssignModal = () => {
    leadData.value = { ...props.lead };
    showAssignModal.value = true;
};

// Handle assignment (for Business)
const onLeadAssigned = (updatedLead) => {
    if (leadData.value) {
        leadData.value.assigned_to = updatedLead.assigned_to;
    }
    router.reload({ only: ['lead'] });
    loadActivities();
};

// Open call widget
const openCallWidget = () => {
    showCallWidget.value = true;
};

// Close call widget
const closeCallWidget = () => {
    showCallWidget.value = false;
};

// Tabs configuration
const tabs = computed(() => {
    const baseTabs = [
        { id: 'activity', label: t('leads.show.tab_activity'), icon: ClockIcon },
        { id: 'info', label: t('leads.show.tab_info'), icon: UserIcon },
        { id: 'calls', label: t('leads.show.tab_calls'), icon: PhoneIcon },
        { id: 'tasks', label: t('leads.show.tab_tasks'), icon: CheckCircleIcon },
    ];
    return baseTabs;
});

// Close dropdown on outside click
const closeDropdowns = (e) => {
    if (!e.target.closest('.assign-dropdown')) {
        showAssignDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdowns);
    loadTasks();
    loadActivities();
    loadCalls();
    checkSmsStatus();
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdowns);
});
</script>

<template>
    <div>
        <Head :title="lead.name" />

        <!-- Notification Toast -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform translate-y-2 opacity-0"
            >
                <div
                    v-if="notification.show"
                    class="fixed top-4 right-4 z-[100] flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg"
                    :class="{
                        'bg-emerald-500 text-white': notification.type === 'success',
                        'bg-amber-500 text-white': notification.type === 'warning',
                        'bg-red-500 text-white': notification.type === 'error'
                    }"
                >
                    <CheckCircleIcon v-if="notification.type === 'success'" class="w-5 h-5" />
                    <ExclamationTriangleIcon v-else-if="notification.type === 'warning'" class="w-5 h-5" />
                    <XCircleIcon v-else class="w-5 h-5" />
                    <span class="font-medium">{{ notification.message }}</span>
                    <button @click="notification.show = false" class="ml-2 p-1 hover:bg-white/20 rounded-lg transition-colors">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </Transition>
        </Teleport>

        <div class="h-full flex flex-col -m-4 sm:-m-6 lg:-m-8">
            <!-- Compact Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
                <div class="flex items-center justify-between">
                    <!-- Left: Back & Lead Info -->
                    <div class="flex items-center gap-4">
                        <Link
                            :href="backRoute"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <ArrowLeftIcon class="w-5 h-5" />
                        </Link>
                        <div class="flex items-center gap-3">
                            <div :class="['w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-semibold text-sm shadow-lg', themeColors.gradient]">
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
                        <button
                            v-if="lead.phone"
                            @click="openCallWidget"
                            class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                            :title="t('leads.show.call')"
                        >
                            <PhoneIcon class="w-5 h-5" />
                        </button>
                        <a
                            v-if="lead.email"
                            :href="`mailto:${lead.email}`"
                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                            title="Email"
                        >
                            <EnvelopeIcon class="w-5 h-5" />
                        </a>
                        <button
                            @click="showDeleteModal = true"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            :title="t('leads.show.delete')"
                        >
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

            <!-- Main Content -->
            <div class="flex-1 overflow-auto">
                <div class="h-full flex">
                    <!-- Left Panel - Lead Details -->
                    <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
                        <!-- Lead Card -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <!-- Large Avatar -->
                            <div class="flex flex-col items-center text-center mb-6">
                                <div :class="['w-24 h-24 rounded-2xl bg-gradient-to-br flex items-center justify-center text-white font-bold text-3xl shadow-xl mb-4', themeColors.gradient]">
                                    {{ getInitials(lead.name) }}
                                </div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ lead.name }}</h2>
                                <p v-if="lead.company" class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                                    <BuildingOfficeIcon class="w-4 h-4" />
                                    {{ lead.company }}
                                </p>
                            </div>

                            <!-- Contact Info -->
                            <div class="space-y-3">
                                <button
                                    v-if="lead.phone"
                                    @click="openCallWidget"
                                    class="w-full flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors group cursor-pointer"
                                >
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-800/30 transition-colors">
                                        <PhoneIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div class="min-w-0 flex-1 text-left">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.phone') }}</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.phone }}</p>
                                    </div>
                                    <PhoneArrowUpRightIcon class="w-5 h-5 text-green-500 opacity-0 group-hover:opacity-100 transition-opacity" />
                                </button>

                                <a
                                    v-if="lead.email"
                                    :href="`mailto:${lead.email}`"
                                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group"
                                >
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

                        <!-- Operator Assignment -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ t('leads.show.operator') }}</h3>

                            <!-- SalesHead: Dropdown inline -->
                            <div v-if="panelType === 'saleshead'" class="relative assign-dropdown">
                                <button
                                    @click.stop="showAssignDropdown = !showAssignDropdown"
                                    class="w-full flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <template v-if="lead.assigned_to">
                                        <span class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white font-semibold">
                                            {{ lead.assigned_to.name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                        <div class="min-w-0 flex-1 text-left">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.assigned') }}</p>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.assigned_to.name }}</p>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span class="w-10 h-10 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <UserPlusIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                        </span>
                                        <div class="min-w-0 flex-1 text-left">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.operator') }}</p>
                                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ t('leads.show.not_assigned') }}</p>
                                        </div>
                                    </template>
                                    <ArrowPathIcon v-if="isAssigning" class="w-5 h-5 text-gray-400 animate-spin" />
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
                                        v-if="showAssignDropdown"
                                        class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 max-h-60 overflow-auto"
                                    >
                                        <button
                                            @click="assignLead(null)"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            <span class="w-8 h-8 rounded-lg bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                <XMarkIcon class="w-4 h-4" />
                                            </span>
                                            {{ t('leads.show.remove_assignment') }}
                                        </button>
                                        <button
                                            v-for="operator in operators"
                                            :key="operator.id"
                                            @click="assignLead(operator.id)"
                                            class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                                            :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': lead.assigned_to?.id === operator.id }"
                                        >
                                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white text-xs font-semibold">
                                                {{ operator.name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                            {{ operator.name }}
                                        </button>
                                    </div>
                                </Transition>
                            </div>

                            <!-- Business: Modal based assignment -->
                            <div v-else class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('leads.show.operator') }}</span>
                                <button
                                    v-if="canAssignLeads"
                                    @click="openAssignModal"
                                    class="flex items-center gap-2 text-sm font-medium hover:text-orange-600 dark:hover:text-orange-400 transition-colors"
                                    :class="lead.assigned_to ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                                >
                                    <template v-if="lead.assigned_to">
                                        <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white text-xs font-semibold">
                                            {{ lead.assigned_to.name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                        {{ lead.assigned_to.name }}
                                    </template>
                                    <template v-else>
                                        {{ t('leads.show.not_assigned') }}
                                    </template>
                                </button>
                                <span
                                    v-else
                                    class="flex items-center gap-2 text-sm font-medium"
                                    :class="lead.assigned_to ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'"
                                >
                                    <template v-if="lead.assigned_to">
                                        <span class="w-5 h-5 rounded-full bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white text-xs font-semibold">
                                            {{ lead.assigned_to.name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                        {{ lead.assigned_to.name }}
                                    </template>
                                    <template v-else>
                                        {{ t('leads.show.not_assigned') }}
                                    </template>
                                </span>
                            </div>
                        </div>

                        <!-- Deal Info (Business panel) -->
                        <div v-if="panelType === 'business'" class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ t('leads.show.deal_info') }}</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('leads.show.estimated_value') }}</span>
                                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(lead.estimated_value) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ t('leads.show.source') }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.source?.name || t('leads.show.not_specified') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ t('leads.show.quick_actions') }}</h3>
                            <div class="grid grid-cols-2 gap-2">
                                <a
                                    v-if="lead.phone"
                                    :href="`https://wa.me/${lead.phone.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="flex flex-col items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-xl transition-colors"
                                >
                                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <span class="text-xs font-medium">WhatsApp</span>
                                </a>
                                <a
                                    v-if="lead.phone"
                                    :href="`https://t.me/${lead.phone.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="flex flex-col items-center gap-2 p-3 bg-sky-50 dark:bg-sky-900/20 hover:bg-sky-100 dark:hover:bg-sky-900/30 text-sky-700 dark:text-sky-400 rounded-xl transition-colors"
                                >
                                    <PaperAirplaneIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">Telegram</span>
                                </a>
                                <button
                                    v-if="lead.phone && smsConnected"
                                    @click="showSmsModal = true"
                                    class="flex flex-col items-center gap-2 p-3 bg-teal-50 dark:bg-teal-900/20 hover:bg-teal-100 dark:hover:bg-teal-900/30 text-teal-700 dark:text-teal-400 rounded-xl transition-colors"
                                >
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                    <span class="text-xs font-medium">SMS</span>
                                </button>
                                <button
                                    v-if="lead.phone"
                                    @click="openCallWidget"
                                    class="flex flex-col items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl transition-colors"
                                >
                                    <PhoneArrowUpRightIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">{{ t('leads.show.call') }}</span>
                                </button>
                                <button
                                    @click="openTaskModal()"
                                    class="flex flex-col items-center gap-2 p-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl transition-colors"
                                >
                                    <BellIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">{{ t('leads.show.task') }}</span>
                                </button>
                                <button
                                    @click="showPaymentModal = true"
                                    class="flex flex-col items-center gap-2 p-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl transition-colors"
                                >
                                    <CurrencyDollarIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">{{ t('leads.show.payment') }}</span>
                                </button>
                                <button
                                    v-if="canAssignLeads && panelType === 'business'"
                                    @click="openAssignModal"
                                    class="flex flex-col items-center gap-2 p-3 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-xl transition-colors"
                                >
                                    <UserPlusIcon class="w-6 h-6" />
                                    <span class="text-xs font-medium">{{ t('leads.show.assign') }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="p-6">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ t('leads.show.timeline') }}</h3>
                            <div class="space-y-4">
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <CalendarIcon class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t('leads.show.created') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.created_at }}</p>
                                    </div>
                                </div>
                                <div v-if="lead.last_contacted_at" class="flex gap-3">
                                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <PhoneIcon class="w-4 h-4 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ t('leads.show.last_contact') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.last_contacted_at }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Panel - Tabs Content -->
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
                                            ? `${themeColors.border} ${themeColors.text}`
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
                            <!-- Activity Tab (Default) -->
                            <div v-if="activeTab === 'activity'" class="max-w-3xl mx-auto">
                                <!-- Add Note Form -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6">
                                    <div class="flex items-start gap-4">
                                        <div :class="['w-10 h-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-semibold text-sm flex-shrink-0', themeColors.gradient]">
                                            {{ getInitials(lead.name) }}
                                        </div>
                                        <div class="flex-1">
                                            <div v-if="!isAddingNote">
                                                <input
                                                    type="text"
                                                    @focus="isAddingNote = true"
                                                    :class="['w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 cursor-text', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                    :placeholder="t('leads.show.add_note_placeholder')"
                                                />
                                            </div>
                                            <div v-else>
                                                <textarea
                                                    v-model="newNote"
                                                    rows="3"
                                                    :class="['w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 resize-none', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                    :placeholder="t('leads.show.add_note_placeholder')"
                                                    autofocus
                                                ></textarea>
                                                <div class="flex items-center justify-between mt-3">
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            @click="openTaskModal()"
                                                            :class="['p-2 text-gray-400 rounded-lg transition-colors', `hover:${themeColors.text} hover:${themeColors.bgLight}`]"
                                                            :title="t('leads.show.add_task')"
                                                        >
                                                            <BellIcon class="w-5 h-5" />
                                                        </button>
                                                        <button
                                                            v-if="lead.phone"
                                                            @click="showSmsModal = true"
                                                            class="p-2 text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 hover:bg-teal-50 dark:hover:bg-teal-900/20 rounded-lg transition-colors"
                                                            :title="t('leads.show.send_sms')"
                                                        >
                                                            <ChatBubbleBottomCenterTextIcon class="w-5 h-5" />
                                                        </button>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            @click="isAddingNote = false; newNote = ''"
                                                            :disabled="isSubmittingNote"
                                                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors disabled:opacity-50"
                                                        >
                                                            {{ t('common.cancel') }}
                                                        </button>
                                                        <button
                                                            @click="addNote"
                                                            :disabled="!newNote.trim() || isSubmittingNote"
                                                            :class="['px-4 py-2 text-white font-medium rounded-lg transition-colors flex items-center gap-2', themeColors.bg, themeColors.bgDisabled, 'disabled:cursor-not-allowed']"
                                                        >
                                                            <span v-if="isSubmittingNote" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                                            {{ isSubmittingNote ? t('common.saving') : t('common.save') }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading State -->
                                <div v-if="activitiesLoading" class="text-center py-12">
                                    <div :class="['w-8 h-8 border-4 border-t-transparent rounded-full animate-spin mx-auto', themeColors.spinner]"></div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-4">{{ t('common.loading') }}</p>
                                </div>

                                <!-- Activity Timeline -->
                                <div v-else-if="activities.length > 0" class="space-y-4">
                                    <div
                                        v-for="activity in activities"
                                        :key="activity.id"
                                        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4"
                                    >
                                        <div class="flex items-start gap-4">
                                            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0', getActivityColor(activity.type)]">
                                                <component :is="getActivityIcon(activity.type)" class="w-5 h-5 text-white" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-1">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ activity.title }}</h4>
                                                        <span v-if="activity.user" class="text-xs text-gray-500 dark:text-gray-400">
                                                            - {{ activity.user.name }}
                                                        </span>
                                                    </div>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400" :title="activity.created_at">
                                                        {{ activity.created_at_human }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ activity.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State if no activity -->
                                <div v-else class="text-center py-12">
                                    <ClockIcon class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ t('leads.show.no_activity') }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400">{{ t('leads.show.no_activity_desc') }}</p>
                                </div>
                            </div>

                            <!-- Info Tab - Edit Form -->
                            <div v-if="activeTab === 'info'" class="max-w-3xl mx-auto">
                                <div class="space-y-6">
                                    <!-- Personal Info Card -->
                                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                        <h3 :class="['text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2']">
                                            <UserIcon :class="['w-5 h-5', themeColors.text]" />
                                            {{ t('leads.show.personal_info') }}
                                        </h3>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.full_name') }} *</label>
                                                <input
                                                    v-model="editForm.name"
                                                    type="text"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.phone_number') }}</label>
                                                <input
                                                    v-model="editForm.phone"
                                                    type="text"
                                                    placeholder="+998 XX XXX XX XX"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center justify-between">
                                                    <span>{{ t('leads.show.additional_phone') }}</span>
                                                    <button
                                                        v-if="!showPhone2"
                                                        @click="showPhone2 = true"
                                                        type="button"
                                                        :class="['text-xs font-medium', themeColors.text]"
                                                    >
                                                        + {{ t('common.add') }}
                                                    </button>
                                                </label>
                                                <div v-if="showPhone2" class="flex gap-2">
                                                    <input
                                                        v-model="editForm.phone2"
                                                        type="text"
                                                        placeholder="+998 XX XXX XX XX"
                                                        :class="['flex-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                    />
                                                    <button
                                                        @click="showPhone2 = false; editForm.phone2 = ''"
                                                        type="button"
                                                        class="px-3 py-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                    >
                                                        <XMarkIcon class="w-5 h-5" />
                                                    </button>
                                                </div>
                                                <p v-else class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ t('common.none') }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                                <input
                                                    v-model="editForm.email"
                                                    type="email"
                                                    placeholder="email@example.com"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.company') }}</label>
                                                <input
                                                    v-model="editForm.company"
                                                    type="text"
                                                    :placeholder="t('leads.show.company_placeholder')"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.birth_date') }}</label>
                                                <input
                                                    v-model="editForm.birth_date"
                                                    type="date"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.gender') }}</label>
                                                <select
                                                    v-model="editForm.gender"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                >
                                                    <option value="">{{ t('common.select') }}</option>
                                                    <option value="male">{{ t('leads.show.gender_male') }}</option>
                                                    <option value="female">{{ t('leads.show.gender_female') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Location Card -->
                                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                        <h3 :class="['text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2']">
                                            <MapPinIcon :class="['w-5 h-5', themeColors.text]" />
                                            {{ t('leads.show.address_info') }}
                                        </h3>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.region') }}</label>
                                                <select
                                                    v-model="editForm.region"
                                                    @change="onRegionChange"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                >
                                                    <option value="">{{ t('common.select') }}</option>
                                                    <option v-for="(label, key) in regions" :key="key" :value="key">{{ label }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.district') }}</label>
                                                <select
                                                    v-if="editForm.region && Object.keys(availableDistricts).length > 0"
                                                    v-model="editForm.district"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                >
                                                    <option value="">{{ t('common.select') }}</option>
                                                    <option v-for="(label, key) in availableDistricts" :key="key" :value="key">{{ label }}</option>
                                                </select>
                                                <input
                                                    v-else
                                                    v-model="editForm.district"
                                                    type="text"
                                                    :placeholder="t('leads.show.select_region_first')"
                                                    :disabled="!editForm.region"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                            <div class="col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.full_address') }}</label>
                                                <input
                                                    v-model="editForm.address"
                                                    type="text"
                                                    :placeholder="t('leads.show.address_placeholder')"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Source & Notes Card -->
                                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                                        <h3 :class="['text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2']">
                                            <DocumentTextIcon :class="['w-5 h-5', themeColors.text]" />
                                            {{ t('leads.show.additional_info') }}
                                        </h3>
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.lead_source') }}</label>
                                                <select
                                                    v-model="editForm.source_id"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                >
                                                    <option value="">{{ t('common.select') }}</option>
                                                    <option v-for="source in sources" :key="source.id" :value="source.id">{{ source.name }}</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ t('leads.show.notes') }}</label>
                                                <textarea
                                                    v-model="editForm.notes"
                                                    rows="4"
                                                    :placeholder="t('leads.show.notes_placeholder')"
                                                    :class="['w-full px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white resize-none', themeColors.ring, 'focus:ring-2 focus:border-transparent']"
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Save Button -->
                                    <div class="flex justify-end">
                                        <button
                                            @click="saveLead"
                                            :disabled="isSaving"
                                            :class="['px-6 py-3 text-white font-medium rounded-xl transition-colors flex items-center gap-2 shadow-lg', themeColors.bg, themeColors.bgDisabled, 'disabled:cursor-not-allowed']"
                                        >
                                            <span v-if="isSaving" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                            <CheckCircleIcon v-else class="w-5 h-5" />
                                            {{ isSaving ? t('common.saving') : t('common.save') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Calls Tab - Call History -->
                            <div v-if="activeTab === 'calls'" class="space-y-4">
                                <!-- Stats Bar - Compact -->
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                                    <div class="flex items-center justify-between flex-wrap gap-4">
                                        <!-- Stats Grid - Compact -->
                                        <div class="flex items-center gap-6 flex-wrap">
                                            <!-- Total Calls -->
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-md shadow-blue-500/25">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ callStats.total_calls }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.total') }}</p>
                                                </div>
                                            </div>

                                            <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>

                                            <!-- Answer Rate -->
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-md shadow-emerald-500/25">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ callStats.answer_rate }}<span class="text-sm text-gray-400">%</span></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.answered') }}</p>
                                                </div>
                                            </div>

                                            <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>

                                            <!-- Duration -->
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-md shadow-violet-500/25">
                                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ callStats.total_duration_formatted || '0:00' }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('leads.show.duration') }}</p>
                                                </div>
                                            </div>

                                            <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>

                                            <!-- Inbound/Outbound Pills - Compact -->
                                            <div class="flex items-center gap-2">
                                                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                    <span class="text-sm font-bold text-blue-700 dark:text-blue-400">{{ callStats.inbound_calls || 0 }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                    </svg>
                                                    <span class="text-sm font-bold text-orange-700 dark:text-orange-400">{{ callStats.outbound_calls || 0 }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Sync Button - Compact -->
                                        <button
                                            @click="syncCalls"
                                            :disabled="syncingCalls"
                                            class="flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-all disabled:opacity-50"
                                        >
                                            <svg v-if="syncingCalls" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ syncingCalls ? t('leads.show.syncing') : t('leads.show.sync') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Loading -->
                                <div v-if="callsLoading" class="text-center py-12">
                                    <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-4 text-sm">{{ t('leads.show.loading_calls') }}</p>
                                </div>

                                <!-- Calls List -->
                                <div v-else-if="calls.length > 0" class="space-y-2">
                                    <div
                                        v-for="call in calls"
                                        :key="call.id"
                                        class="group"
                                    >
                                        <!-- Call Card - Compact Design -->
                                        <div class="relative flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-md shadow-sm transition-all duration-200">
                                            <!-- Direction Icon - Smaller -->
                                            <div class="relative flex-shrink-0">
                                                <div :class="[
                                                    'w-10 h-10 rounded-xl flex items-center justify-center shadow-md',
                                                    call.direction === 'inbound'
                                                        ? 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/20'
                                                        : 'bg-gradient-to-br from-orange-500 to-amber-600 shadow-orange-500/20'
                                                ]">
                                                    <!-- Inbound Icon -->
                                                    <svg v-if="call.direction === 'inbound'" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                    <!-- Outbound Icon -->
                                                    <svg v-else class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                </div>
                                                <!-- Status Indicator - Smaller -->
                                                <div :class="[
                                                    'absolute -bottom-0.5 -right-0.5 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center',
                                                    call.status === 'completed' || call.status === 'answered' ? 'bg-emerald-500' :
                                                    call.status === 'missed' || call.status === 'no_answer' || call.status === 'failed' || call.status === 'cancelled' ? 'bg-red-500' :
                                                    call.status === 'busy' ? 'bg-amber-500' :
                                                    call.status === 'ringing' || call.status === 'initiated' ? 'bg-blue-500' : 'bg-gray-400'
                                                ]">
                                                    <svg v-if="call.status === 'completed' || call.status === 'answered'" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg v-else-if="call.status === 'missed' || call.status === 'no_answer' || call.status === 'failed' || call.status === 'cancelled'" class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </div>
                                            </div>

                                            <!-- Call Info - More Compact -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <!-- Direction Badge -->
                                                    <span :class="[
                                                        'inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded',
                                                        call.direction === 'inbound'
                                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                                                            : 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
                                                    ]">
                                                        {{ call.direction === 'inbound' ? t('leads.show.inbound') : t('leads.show.outbound') }}
                                                    </span>

                                                    <!-- Status Badge -->
                                                    <span :class="[
                                                        'inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded',
                                                        call.status === 'completed' || call.status === 'answered'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                            : call.status === 'missed' || call.status === 'no_answer' || call.status === 'failed' || call.status === 'cancelled'
                                                                ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'
                                                                : call.status === 'busy'
                                                                    ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                                                    ]">
                                                        <svg v-if="call.status === 'completed' || call.status === 'answered'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <svg v-else-if="call.status === 'missed' || call.status === 'no_answer' || call.status === 'failed' || call.status === 'cancelled'" class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                        {{ call.status === 'completed' || call.status === 'answered' ? t('leads.show.call_answered') :
                                                           call.status === 'missed' || call.status === 'no_answer' || call.status === 'failed' || call.status === 'cancelled' ? t('leads.show.call_missed') :
                                                           call.status === 'busy' ? t('leads.show.call_busy') :
                                                           call.status === 'ringing' ? t('leads.show.call_ringing') : call.status }}
                                                    </span>

                                                    <!-- Quick Status Toggle (icon only) -->
                                                    <button
                                                        @click="updateCallStatus(call, call.status === 'completed' || call.status === 'answered' ? 'missed' : 'completed')"
                                                        :disabled="updatingCallId === call.id"
                                                        :class="[
                                                            'p-1 rounded transition-all',
                                                            call.status === 'completed' || call.status === 'answered'
                                                                ? 'text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20'
                                                                : 'text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
                                                        ]"
                                                        :title="call.status === 'completed' || call.status === 'answered' ? t('leads.show.mark_missed') : t('leads.show.mark_answered')"
                                                    >
                                                        <svg v-if="updatingCallId === call.id" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                        </svg>
                                                    </button>

                                                    <!-- Separator -->
                                                    <span class="text-gray-300 dark:text-gray-600">•</span>

                                                    <!-- Date -->
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ call.created_at }}
                                                    </span>
                                                    <span v-if="call.operator_name" class="text-xs text-gray-500 dark:text-gray-400">
                                                        • {{ call.operator_name }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Right Side: Duration & Actions - Compact -->
                                            <div class="flex items-center gap-2">
                                                <!-- Duration - Smaller -->
                                                <div v-if="call.duration > 0" class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ call.duration_formatted }}</span>
                                                </div>

                                                <!-- Recording Button - Show for answered calls -->
                                                <button
                                                    v-if="call.status === 'completed' || call.status === 'answered' || call.recording_url"
                                                    @click="toggleAudio(call)"
                                                    :disabled="loadingRecordingId === call.id"
                                                    :class="[
                                                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                                        playingCallId === call.id
                                                            ? 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white shadow-md shadow-blue-500/25'
                                                            : loadingRecordingId === call.id
                                                                ? 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500 cursor-wait'
                                                                : 'bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50'
                                                    ]"
                                                >
                                                    <svg v-if="loadingRecordingId === call.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                    <svg v-else-if="playingCallId === call.id" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                    </svg>
                                                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ loadingRecordingId === call.id ? t('common.loading') : playingCallId === call.id ? t('common.stop') : t('leads.show.recording') }}
                                                </button>

                                                <!-- Call Back Button - Smaller -->
                                                <button
                                                    @click="showCallWidget = true"
                                                    class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-all opacity-0 group-hover:opacity-100"
                                                    :title="t('leads.show.callback')"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State - Compact -->
                                <div v-else class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ t('leads.show.no_calls') }}</h3>
                                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto text-sm">{{ t('leads.show.no_calls_desc') }}</p>
                                    <div class="flex items-center justify-center gap-3">
                                        <button
                                            @click="syncCalls"
                                            :disabled="syncingCalls"
                                            class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-all disabled:opacity-50"
                                        >
                                            <svg v-if="syncingCalls" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            {{ t('leads.show.sync') }}
                                        </button>
                                        <button
                                            @click="showCallWidget = true"
                                            :class="['flex items-center gap-2 px-4 py-2 text-white text-sm font-medium rounded-lg transition-all shadow-md', themeColors.bg]"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            {{ t('leads.show.make_call') }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tasks Tab - Kanban Board -->
                            <div v-if="activeTab === 'tasks'" class="h-full">
                                <!-- Loading -->
                                <div v-if="tasksLoading" class="text-center py-12">
                                    <div :class="['w-8 h-8 border-4 border-t-transparent rounded-full animate-spin mx-auto', themeColors.spinner]"></div>
                                    <p class="text-gray-500 dark:text-gray-400 mt-4">{{ t('common.loading') }}</p>
                                </div>

                                <!-- Kanban Board - 4 Columns -->
                                <div v-else class="grid grid-cols-4 gap-4 h-full">
                                    <!-- Column 1: Muddati o'tgan (Overdue) -->
                                    <div class="flex flex-col bg-red-50/50 dark:bg-red-900/10 rounded-xl border border-red-200 dark:border-red-800/30 overflow-hidden">
                                        <div class="p-3 bg-red-100 dark:bg-red-900/30 border-b border-red-200 dark:border-red-800/30">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                    <span class="font-semibold text-red-700 dark:text-red-400">{{ t('leads.show.tasks_overdue') }}</span>
                                                </div>
                                                <span class="text-xs font-bold px-2 py-0.5 bg-red-200 dark:bg-red-800/50 text-red-700 dark:text-red-300 rounded-full">
                                                    {{ tasks.overdue?.length || 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                                            <div
                                                v-for="task in tasks.overdue"
                                                :key="task.id"
                                                class="bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ task.due_date_human }}</span>
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-medium',
                                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                    ]">{{ task.type_label }}</span>
                                                </div>
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                                <p class="text-xs text-red-500 dark:text-red-400 mb-2">{{ task.due_date_full?.split(' ')[0] }}</p>
                                                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                                    <button @click="completeTask(task)" class="text-xs text-green-600 hover:text-green-700 font-medium">{{ t('leads.show.task_done') }}</button>
                                                    <div class="flex gap-1">
                                                        <button @click="openTaskModal(task)" class="p-1 text-gray-400 hover:text-blue-600"><PencilSquareIcon class="w-4 h-4" /></button>
                                                        <button @click="deleteTask(task)" class="p-1 text-gray-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!tasks.overdue?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                                <CheckCircleIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p class="text-xs">{{ t('common.none') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Column 2: Bugun (Today) -->
                                    <div class="flex flex-col bg-blue-50/50 dark:bg-blue-900/10 rounded-xl border border-blue-200 dark:border-blue-800/30 overflow-hidden">
                                        <div class="p-3 bg-blue-100 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800/30">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                                    <span class="font-semibold text-blue-700 dark:text-blue-400">{{ t('leads.show.tasks_today') }}</span>
                                                </div>
                                                <span class="text-xs font-bold px-2 py-0.5 bg-blue-200 dark:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-full">
                                                    {{ tasks.today?.length || 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                                            <div
                                                v-for="task in tasks.today"
                                                :key="task.id"
                                                class="bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ task.due_date_human }}</span>
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-medium',
                                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                    ]">{{ task.type_label }}</span>
                                                </div>
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                                <div v-if="task.priority === 'urgent' || task.priority === 'high'" class="mb-2">
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-bold',
                                                        task.priority === 'urgent' ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-orange-100 text-orange-700'
                                                    ]">{{ task.priority_label }}</span>
                                                </div>
                                                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                                    <button @click="completeTask(task)" class="text-xs text-green-600 hover:text-green-700 font-medium">{{ t('leads.show.task_done') }}</button>
                                                    <div class="flex gap-1">
                                                        <button @click="openTaskModal(task)" class="p-1 text-gray-400 hover:text-blue-600"><PencilSquareIcon class="w-4 h-4" /></button>
                                                        <button @click="deleteTask(task)" class="p-1 text-gray-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!tasks.today?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p class="text-xs">{{ t('common.none') }}</p>
                                            </div>
                                        </div>
                                        <div class="p-2 border-t border-blue-200 dark:border-blue-800/30">
                                            <button @click="openTaskModal()" class="w-full py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors flex items-center justify-center gap-1">
                                                <PlusIcon class="w-4 h-4" />
                                                {{ t('leads.show.add_task_btn') }}
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Column 3: Ertaga (Tomorrow) -->
                                    <div class="flex flex-col bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-200 dark:border-indigo-800/30 overflow-hidden">
                                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 border-b border-indigo-200 dark:border-indigo-800/30">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                                                    <span class="font-semibold text-indigo-700 dark:text-indigo-400">{{ t('leads.show.tasks_tomorrow') }}</span>
                                                </div>
                                                <span class="text-xs font-bold px-2 py-0.5 bg-indigo-200 dark:bg-indigo-800/50 text-indigo-700 dark:text-indigo-300 rounded-full">
                                                    {{ tasks.tomorrow?.length || 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                                            <div
                                                v-for="task in tasks.tomorrow"
                                                :key="task.id"
                                                class="bg-white dark:bg-gray-800 rounded-lg border border-indigo-200 dark:border-indigo-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ task.due_date_human }}</span>
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-medium',
                                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                    ]">{{ task.type_label }}</span>
                                                </div>
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                                <div v-if="task.priority === 'urgent' || task.priority === 'high'" class="mb-2">
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-bold',
                                                        task.priority === 'urgent' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700'
                                                    ]">{{ task.priority_label }}</span>
                                                </div>
                                                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                                    <button @click="completeTask(task)" class="text-xs text-green-600 hover:text-green-700 font-medium">{{ t('leads.show.task_done') }}</button>
                                                    <div class="flex gap-1">
                                                        <button @click="openTaskModal(task)" class="p-1 text-gray-400 hover:text-blue-600"><PencilSquareIcon class="w-4 h-4" /></button>
                                                        <button @click="deleteTask(task)" class="p-1 text-gray-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!tasks.tomorrow?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p class="text-xs">{{ t('common.none') }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Column 4: Shu hafta (This Week) -->
                                    <div class="flex flex-col bg-purple-50/50 dark:bg-purple-900/10 rounded-xl border border-purple-200 dark:border-purple-800/30 overflow-hidden">
                                        <div class="p-3 bg-purple-100 dark:bg-purple-900/30 border-b border-purple-200 dark:border-purple-800/30">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                                    <span class="font-semibold text-purple-700 dark:text-purple-400">{{ t('leads.show.tasks_this_week') }}</span>
                                                </div>
                                                <span class="text-xs font-bold px-2 py-0.5 bg-purple-200 dark:bg-purple-800/50 text-purple-700 dark:text-purple-300 rounded-full">
                                                    {{ tasks.this_week?.length || 0 }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                                            <div
                                                v-for="task in tasks.this_week"
                                                :key="task.id"
                                                class="bg-white dark:bg-gray-800 rounded-lg border border-purple-200 dark:border-purple-700/50 p-3 shadow-sm hover:shadow-md transition-shadow"
                                            >
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ task.due_date_human }}</span>
                                                    <span :class="[
                                                        'text-xs px-2 py-0.5 rounded font-medium',
                                                        task.type === 'call' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' :
                                                        task.type === 'meeting' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400' :
                                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'
                                                    ]">{{ task.type_label }}</span>
                                                </div>
                                                <h4 class="font-medium text-gray-900 dark:text-white text-sm mb-1">{{ task.title }}</h4>
                                                <p class="text-xs text-purple-500 dark:text-purple-400 mb-2">{{ task.due_date_full?.split(' ')[0] }}</p>
                                                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                                                    <button @click="completeTask(task)" class="text-xs text-green-600 hover:text-green-700 font-medium">{{ t('leads.show.task_done') }}</button>
                                                    <div class="flex gap-1">
                                                        <button @click="openTaskModal(task)" class="p-1 text-gray-400 hover:text-blue-600"><PencilSquareIcon class="w-4 h-4" /></button>
                                                        <button @click="deleteTask(task)" class="p-1 text-gray-400 hover:text-red-600"><TrashIcon class="w-4 h-4" /></button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-if="!tasks.this_week?.length" class="text-center py-8 text-gray-400 dark:text-gray-500">
                                                <CalendarIcon class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                                <p class="text-xs">{{ t('common.none') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ t('leads.show.delete_lead') }}</h3>
                                <p class="text-gray-500 dark:text-gray-400 mb-6">
                                    {{ t('leads.show.delete_confirm', { name: lead.name }) }}
                                </p>
                                <div class="flex gap-3">
                                    <button @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-xl transition-colors">
                                        {{ t('common.cancel') }}
                                    </button>
                                    <button @click="deleteLead" class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl transition-colors">
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- SMS Modal -->
        <SmsModal
            :show="showSmsModal"
            :lead="lead"
            @close="showSmsModal = false"
            @sent="() => { loadActivities(); }"
        />

        <!-- Payment Modal -->
        <PaymentModal
            :show="showPaymentModal"
            :lead="lead"
            @close="showPaymentModal = false"
            @created="() => { loadActivities(); }"
        />

        <!-- Call Widget -->
        <CallWidget
            :show="showCallWidget"
            :lead="lead"
            @close="closeCallWidget"
        />

        <!-- Task Modal -->
        <TaskModal
            :show="showTaskModal"
            :lead="lead"
            :task="editingTask"
            @close="closeTaskModal"
            @saved="onTaskSaved"
        />

        <!-- Lead Assign Modal (for Business panel) -->
        <LeadAssignModal
            v-if="panelType === 'business'"
            :show="showAssignModal"
            :lead="leadData || lead"
            @close="showAssignModal = false"
            @assigned="onLeadAssigned"
        />
    </div>
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
