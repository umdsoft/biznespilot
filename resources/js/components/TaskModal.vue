<script setup>
import { ref, computed, watch } from 'vue';
import {
    XMarkIcon,
    PhoneIcon,
    CalendarIcon,
    UserIcon,
    FlagIcon,
    ClockIcon,
    ChatBubbleLeftRightIcon,
    EnvelopeIcon,
    ArrowPathIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    lead: {
        type: Object,
        default: null,
    },
    task: {
        type: Object,
        default: null,
    },
    leads: {
        type: Array,
        default: () => [],
    },
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead'].includes(value),
    },
});

const emit = defineEmits(['close', 'saved']);

// Route prefix based on panel type
const routePrefix = computed(() => props.panelType === 'saleshead' ? 'sales-head' : 'business');

// Form state
const form = ref({
    title: '',
    description: '',
    type: 'task',
    priority: 'medium',
    due_date: '',
    due_time: '10:00',
    lead_id: null,
});

const isLoading = ref(false);
const error = ref('');

// Task types with icons
const taskTypes = [
    { value: 'call', label: 'Qo\'ng\'iroq', icon: PhoneIcon, color: 'text-green-500' },
    { value: 'meeting', label: 'Uchrashuv', icon: UserIcon, color: 'text-blue-500' },
    { value: 'email', label: 'Email', icon: EnvelopeIcon, color: 'text-purple-500' },
    { value: 'task', label: 'Vazifa', icon: CheckCircleIcon, color: 'text-orange-500' },
    { value: 'follow_up', label: 'Qayta aloqa', icon: ArrowPathIcon, color: 'text-indigo-500' },
    { value: 'other', label: 'Boshqa', icon: ChatBubbleLeftRightIcon, color: 'text-gray-500' },
];

// Priority levels
const priorities = [
    { value: 'low', label: 'Past', color: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' },
    { value: 'medium', label: 'O\'rtacha', color: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400' },
    { value: 'high', label: 'Yuqori', color: 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400' },
    { value: 'urgent', label: 'Shoshilinch', color: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' },
];

// Quick date options
const quickDates = [
    { label: 'Bugun', days: 0 },
    { label: 'Ertaga', days: 1 },
    { label: '3 kun', days: 3 },
    { label: 'Hafta', days: 7 },
];

// Set quick date
const setQuickDate = (days) => {
    const date = new Date();
    date.setDate(date.getDate() + days);
    form.value.due_date = date.toISOString().split('T')[0];
};

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.task) {
            // Edit mode
            form.value = {
                title: props.task.title,
                description: props.task.description || '',
                type: props.task.type,
                priority: props.task.priority,
                due_date: props.task.due_date?.split(' ')[0] || '',
                due_time: props.task.due_date?.split(' ')[1]?.slice(0, 5) || '10:00',
                lead_id: props.task.lead_id,
            };
        } else {
            // Create mode
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            form.value = {
                title: '',
                description: '',
                type: 'call',
                priority: 'medium',
                due_date: tomorrow.toISOString().split('T')[0],
                due_time: '10:00',
                lead_id: props.lead?.id || null,
            };
        }
        error.value = '';
    }
});

// Check if form is valid
const isValid = computed(() => {
    return form.value.title.trim() && form.value.due_date;
});

// Submit form
const submit = async () => {
    if (!isValid.value || isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const payload = {
            title: form.value.title,
            description: form.value.description || null,
            type: form.value.type,
            priority: form.value.priority,
            due_date: `${form.value.due_date} ${form.value.due_time}:00`,
            lead_id: form.value.lead_id,
        };

        const url = props.task
            ? route(`${routePrefix.value}.tasks.update`, props.task.id)
            : route(`${routePrefix.value}.tasks.store`);

        const method = props.task ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            emit('saved', data.task);
            emit('close');
        } else {
            error.value = data.error || data.message || 'Xatolik yuz berdi';
        }
    } catch (err) {
        console.error('Failed to save task:', err);
        error.value = 'Tarmoq xatosi';
    } finally {
        isLoading.value = false;
    }
};

// Close modal
const close = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50" @click="close"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg z-10 overflow-hidden">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ task ? 'Vazifani tahrirlash' : 'Yangi vazifa' }}
                            </h3>
                            <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-5">
                            <!-- Lead info (if attached) -->
                            <div v-if="lead" class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                    {{ lead.name?.charAt(0)?.toUpperCase() || '?' }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ lead.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ lead.phone }}</p>
                                </div>
                            </div>

                            <!-- Lead selection (if no lead attached and leads provided) -->
                            <div v-if="!lead && leads.length > 0">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lead (ixtiyoriy)</label>
                                <select
                                    v-model="form.lead_id"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option :value="null">Lead tanlamang</option>
                                    <option v-for="l in leads" :key="l.id" :value="l.id">
                                        {{ l.name }} - {{ l.phone }}
                                    </option>
                                </select>
                            </div>

                            <!-- Task Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Vazifa turi</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="type in taskTypes"
                                        :key="type.value"
                                        @click="form.type = type.value"
                                        :class="[
                                            'flex flex-col items-center gap-1 p-3 rounded-xl border-2 transition-all',
                                            form.type === type.value
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                        ]"
                                    >
                                        <component :is="type.icon" :class="['w-5 h-5', type.color]" />
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ type.label }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Title -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sarlavha</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Masalan: Telefon qilish, Uchrashuv..."
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Muddat</label>
                                <div class="flex gap-2 mb-2">
                                    <button
                                        v-for="quick in quickDates"
                                        :key="quick.days"
                                        @click="setQuickDate(quick.days)"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
                                    >
                                        {{ quick.label }}
                                    </button>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex-1">
                                        <input
                                            v-model="form.due_date"
                                            type="date"
                                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div class="w-28">
                                        <input
                                            v-model="form.due_time"
                                            type="time"
                                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Priority -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Muhimlik</label>
                                <div class="flex gap-2">
                                    <button
                                        v-for="p in priorities"
                                        :key="p.value"
                                        @click="form.priority = p.value"
                                        :class="[
                                            'flex-1 py-2 px-3 text-sm font-medium rounded-xl transition-all',
                                            form.priority === p.value
                                                ? p.color + ' ring-2 ring-offset-2 ring-blue-500'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600'
                                        ]"
                                    >
                                        {{ p.label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Izoh (ixtiyoriy)</label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Qo'shimcha ma'lumot..."
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                                ></textarea>
                            </div>

                            <!-- Error -->
                            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <button
                                @click="close"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="submit"
                                :disabled="!isValid || isLoading"
                                :class="[
                                    'px-6 py-2 font-medium rounded-xl transition-colors',
                                    isValid && !isLoading
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                        : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                ]"
                            >
                                {{ isLoading ? 'Saqlanmoqda...' : (task ? 'Saqlash' : 'Yaratish') }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
