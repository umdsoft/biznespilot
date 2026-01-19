<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import { formatDateTime, formatDuration, getInitials, getAvatarColor } from '@/utils/formatting';
import {
    ArrowLeftIcon,
    PhoneIcon,
    PlayIcon,
    ClockIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    call: {
        type: Object,
        default: () => ({
            id: 1,
            phone: '+998 90 123 45 67',
            caller_name: 'Jasur Aliyev',
            direction: 'outgoing',
            status: 'completed',
            duration: 245,
            started_at: new Date().toISOString(),
            notes: "Mijoz bilan mahsulot haqida gaplashdik. Keyingi haftada yana qo'ng'iroq qilish kerak.",
            recording_url: null,
        }),
    },
    lead: {
        type: Object,
        default: () => null,
    },
});

const getStatusColor = (status) => {
    const colors = {
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        missed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        busy: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        no_answer: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return colors[status] || colors.no_answer;
};

const getStatusLabel = (status) => {
    const labels = {
        completed: 'Tugallandi',
        missed: "O'tkazib yuborildi",
        busy: 'Band',
        no_answer: 'Javob yo\'q',
    };
    return labels[status] || status;
};

const getDirectionLabel = (direction) => {
    return direction === 'incoming' ? 'Kiruvchi' : 'Chiquvchi';
};
</script>

<template>
    <SalesHeadLayout title="Qo'ng'iroq Tafsilotlari">
        <Head title="Qo'ng'iroq Tafsilotlari" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    href="/sales-head/calls"
                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <ArrowLeftIcon class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Qo'ng'iroq Tafsilotlari</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ call.phone }}</p>
                </div>
            </div>

            <!-- Call Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                            <PhoneIcon class="w-8 h-8 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ call.phone }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ getDirectionLabel(call.direction) }} qo'ng'iroq</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium" :class="getStatusColor(call.status)">
                        {{ getStatusLabel(call.status) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                            <ClockIcon class="w-4 h-4" />
                            <span class="text-sm">Davomiyligi</span>
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ formatDuration(call.duration) }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                            <UserIcon class="w-4 h-4" />
                            <span class="text-sm">Operator</span>
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ call.caller_name }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 col-span-2">
                        <div class="flex items-center gap-2 text-gray-500 dark:text-gray-400 mb-1">
                            <span class="text-sm">Vaqti</span>
                        </div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ formatDateTime(call.started_at) }}</p>
                    </div>
                </div>
            </div>

            <!-- Recording -->
            <div v-if="call.recording_url" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Yozuv</h3>
                <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <button class="w-12 h-12 bg-emerald-600 hover:bg-emerald-700 rounded-full flex items-center justify-center text-white transition-colors">
                        <PlayIcon class="w-6 h-6" />
                    </button>
                    <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-600 rounded-full">
                        <div class="w-0 h-full bg-emerald-500 rounded-full"></div>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ formatDuration(call.duration) }}</span>
                </div>
            </div>

            <!-- Notes -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Izohlar</h3>
                <p v-if="call.notes" class="text-gray-700 dark:text-gray-300">{{ call.notes }}</p>
                <p v-else class="text-gray-500 dark:text-gray-400 italic">Izoh yo'q</p>
            </div>

            <!-- Related Lead -->
            <div v-if="lead" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Bog'langan lid</h3>
                <Link
                    :href="`/leads/${lead.id}`"
                    class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                    <div
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold bg-gradient-to-br"
                        :class="getAvatarColor(lead.name)"
                    >
                        {{ getInitials(lead.name) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ lead.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.phone }}</p>
                    </div>
                </Link>
            </div>
        </div>
    </SalesHeadLayout>
</template>
