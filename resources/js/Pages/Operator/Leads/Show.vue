<script setup>
import OperatorLayout from '@/layouts/OperatorLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    ArrowLeftIcon,
    PhoneIcon,
    EnvelopeIcon,
    MapPinIcon,
    CalendarIcon,
    ChatBubbleLeftIcon,
    PlusIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    lead: Object,
    activities: Array,
});

const showNoteForm = ref(false);
const showCallForm = ref(false);

const noteForm = useForm({
    note: '',
});

const callForm = useForm({
    duration: '',
    outcome: 'answered',
    notes: '',
});

const statusForm = useForm({
    status: props.lead?.status || 'new',
});

const statuses = [
    { value: 'new', label: 'Yangi' },
    { value: 'contacted', label: 'Bog\'lanildi' },
    { value: 'qualified', label: 'Kvalifikatsiya' },
    { value: 'lost', label: 'Yo\'qotilgan' },
];

const callOutcomes = [
    { value: 'answered', label: 'Javob berdi' },
    { value: 'no_answer', label: 'Javob bermadi' },
    { value: 'busy', label: 'Band' },
    { value: 'callback', label: 'Qayta qo\'ng\'iroq' },
];

const getStatusClass = (status) => {
    const classes = {
        new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        contacted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        qualified: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        lost: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
    const labels = {
        new: 'Yangi',
        contacted: 'Bog\'lanildi',
        qualified: 'Kvalifikatsiya',
        lost: 'Yo\'qotilgan',
    };
    return labels[status] || status;
};

const getActivityIcon = (type) => {
    const icons = {
        call: PhoneIcon,
        note: ChatBubbleLeftIcon,
        status_change: CheckCircleIcon,
    };
    return icons[type] || ChatBubbleLeftIcon;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('uz-UZ', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const submitNote = () => {
    noteForm.post(`/operator/leads/${props.lead.id}/notes`, {
        onSuccess: () => {
            noteForm.reset();
            showNoteForm.value = false;
        },
    });
};

const submitCall = () => {
    callForm.post(`/operator/leads/${props.lead.id}/calls`, {
        onSuccess: () => {
            callForm.reset();
            showCallForm.value = false;
        },
    });
};

const updateStatus = () => {
    statusForm.patch(`/operator/leads/${props.lead.id}/status`);
};

const makeCall = () => {
    window.location.href = `tel:${props.lead.phone}`;
};
</script>

<template>
    <OperatorLayout title="Lead">
        <Head :title="lead?.name || 'Lead'" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/operator/leads" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ lead?.name }}</h1>
                        <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', getStatusClass(lead?.status)]">
                            {{ getStatusLabel(lead?.status) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Lead Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aloqa Ma'lumotlari</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <PhoneIcon class="w-5 h-5 text-blue-600" />
                                <div>
                                    <p class="text-sm text-gray-500">Telefon</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ lead?.phone }}</p>
                                </div>
                            </div>
                            <div v-if="lead?.email" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <EnvelopeIcon class="w-5 h-5 text-green-600" />
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ lead?.email }}</p>
                                </div>
                            </div>
                            <div v-if="lead?.address" class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <MapPinIcon class="w-5 h-5 text-red-600" />
                                <div>
                                    <p class="text-sm text-gray-500">Manzil</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ lead?.address }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <CalendarIcon class="w-5 h-5 text-purple-600" />
                                <div>
                                    <p class="text-sm text-gray-500">Yaratilgan</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(lead?.created_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="flex gap-3 mt-6">
                            <button
                                @click="makeCall"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors"
                            >
                                <PhoneIcon class="w-5 h-5" />
                                Qo'ng'iroq qilish
                            </button>
                            <button
                                @click="showNoteForm = true"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
                            >
                                <ChatBubbleLeftIcon class="w-5 h-5" />
                                Izoh qo'shish
                            </button>
                        </div>
                    </div>

                    <!-- Note Form -->
                    <div v-if="showNoteForm" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Yangi Izoh</h3>
                        <form @submit.prevent="submitNote">
                            <textarea
                                v-model="noteForm.note"
                                rows="4"
                                placeholder="Izohni kiriting..."
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                            <div class="flex gap-3 mt-4">
                                <button
                                    type="submit"
                                    :disabled="noteForm.processing"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50"
                                >
                                    Saqlash
                                </button>
                                <button
                                    type="button"
                                    @click="showNoteForm = false"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Call Log Form -->
                    <div v-if="showCallForm" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Qo'ng'iroq Qayd Qilish</h3>
                        <form @submit.prevent="submitCall" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Davomiyligi (daqiqa)</label>
                                    <input
                                        v-model="callForm.duration"
                                        type="number"
                                        min="0"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Natija</label>
                                    <select
                                        v-model="callForm.outcome"
                                        class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option v-for="o in callOutcomes" :key="o.value" :value="o.value">{{ o.label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Izohlar</label>
                                <textarea
                                    v-model="callForm.notes"
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                                ></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button
                                    type="submit"
                                    :disabled="callForm.processing"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50"
                                >
                                    Saqlash
                                </button>
                                <button
                                    type="button"
                                    @click="showCallForm = false"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                                >
                                    Bekor qilish
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Faoliyat Tarixi</h3>
                        <div class="space-y-4">
                            <div
                                v-for="activity in activities"
                                :key="activity.id"
                                class="flex gap-4"
                            >
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <component :is="getActivityIcon(activity.type)" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div class="flex-1 pb-4 border-b border-gray-100 dark:border-gray-700">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ activity.title }}</p>
                                    <p v-if="activity.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ activity.description }}</p>
                                    <p class="text-xs text-gray-400 mt-2">{{ formatDate(activity.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="!activities?.length" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                Hozircha faoliyat yo'q
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Update -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statusni O'zgartirish</h3>
                        <form @submit.prevent="updateStatus">
                            <select
                                v-model="statusForm.status"
                                class="w-full px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            >
                                <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                            </select>
                            <button
                                type="submit"
                                :disabled="statusForm.processing"
                                class="w-full mt-4 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                Saqlash
                            </button>
                        </form>
                    </div>

                    <!-- Lead Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Qo'shimcha Ma'lumot</h3>
                        <div class="space-y-3">
                            <div v-if="lead?.source">
                                <p class="text-sm text-gray-500">Manba</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ lead.source }}</p>
                            </div>
                            <div v-if="lead?.company">
                                <p class="text-sm text-gray-500">Kompaniya</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ lead.company }}</p>
                            </div>
                            <div v-if="lead?.budget">
                                <p class="text-sm text-gray-500">Byudjet</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ lead.budget }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Log Call Button -->
                    <button
                        @click="showCallForm = true"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Qo'ng'iroq qayd qilish
                    </button>
                </div>
            </div>
        </div>
    </OperatorLayout>
</template>
