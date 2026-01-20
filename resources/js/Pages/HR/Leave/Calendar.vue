<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    CalendarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    requests: { type: Array, default: () => [] },
    month: { type: Number, required: true },
    year: { type: Number, required: true },
});

const monthNames = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'
];

const dayNames = ['Du', 'Se', 'Cho', 'Pa', 'Ju', 'Sha', 'Ya'];

const currentMonthName = computed(() => monthNames[props.month - 1]);

const calendarDays = computed(() => {
    const firstDay = new Date(props.year, props.month - 1, 1);
    const lastDay = new Date(props.year, props.month, 0);
    const daysInMonth = lastDay.getDate();
    const startDayOfWeek = firstDay.getDay();

    const days = [];

    // Add empty cells for days before month starts
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({ day: null, date: null, leaves: [] });
    }

    // Add all days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const date = new Date(props.year, props.month - 1, day);
        const dateStr = formatDate(date);

        // Find leaves for this day
        const dayLeaves = props.requests.filter(request => {
            const startDate = new Date(request.start_date);
            const endDate = new Date(request.end_date);
            return date >= startDate && date <= endDate;
        });

        days.push({
            day,
            date: dateStr,
            dateObj: date,
            leaves: dayLeaves,
            isToday: isToday(date),
            isWeekend: date.getDay() === 0 || date.getDay() === 6,
        });
    }

    return days;
});

const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const isToday = (date) => {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
};

const navigateMonth = (delta) => {
    let newMonth = props.month + delta;
    let newYear = props.year;

    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }

    router.get(route('hr.leave.calendar'), {
        month: newMonth,
        year: newYear,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const goToToday = () => {
    const today = new Date();
    router.get(route('hr.leave.calendar'), {
        month: today.getMonth() + 1,
        year: today.getFullYear(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getLeaveTypeColor = (code) => {
    const colors = {
        'annual': 'bg-blue-500',
        'sick': 'bg-red-500',
        'family': 'bg-purple-500',
        'unpaid': 'bg-gray-500',
    };
    return colors[code] || 'bg-green-500';
};

// Group leaves by user for the legend
const leavesByUser = computed(() => {
    const users = {};
    props.requests.forEach(request => {
        if (!users[request.user_id]) {
            users[request.user_id] = {
                name: request.user_name,
                leaves: [],
            };
        }
        users[request.user_id].leaves.push(request);
    });
    return Object.values(users);
});
</script>

<template>
    <HRLayout :title="t('hr.leave_calendar')">
        <Head :title="t('hr.leave_calendar')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.leave_calendar') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.team_members') }} {{ t('hr.leave').toLowerCase() }}</p>
                </div>

                <!-- Month Navigation -->
                <div class="flex items-center gap-3">
                    <button
                        @click="navigateMonth(-1)"
                        class="p-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <ChevronLeftIcon class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                    </button>

                    <button
                        @click="goToToday"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Bugun
                    </button>

                    <div class="px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold min-w-[180px] text-center">
                        {{ currentMonthName }} {{ year }}
                    </div>

                    <button
                        @click="navigateMonth(1)"
                        class="p-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        <ChevronRightIcon class="w-5 h-5 text-gray-700 dark:text-gray-300" />
                    </button>
                </div>
            </div>

            <!-- Calendar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Day Names -->
                <div class="grid grid-cols-7 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                    <div
                        v-for="dayName in dayNames"
                        :key="dayName"
                        class="p-3 text-center text-sm font-semibold text-gray-600 dark:text-gray-400"
                    >
                        {{ dayName }}
                    </div>
                </div>

                <!-- Calendar Days -->
                <div class="grid grid-cols-7 auto-rows-fr">
                    <div
                        v-for="(dayData, index) in calendarDays"
                        :key="index"
                        :class="[
                            'min-h-[120px] p-2 border-r border-b border-gray-200 dark:border-gray-700',
                            dayData.isWeekend ? 'bg-gray-50 dark:bg-gray-900/30' : 'bg-white dark:bg-gray-800',
                            dayData.isToday ? 'ring-2 ring-inset ring-purple-500' : '',
                        ]"
                    >
                        <div v-if="dayData.day" class="h-full flex flex-col">
                            <!-- Day Number -->
                            <div
                                :class="[
                                    'text-sm font-medium mb-2',
                                    dayData.isToday
                                        ? 'w-7 h-7 flex items-center justify-center rounded-full bg-purple-600 text-white'
                                        : 'text-gray-900 dark:text-gray-100'
                                ]"
                            >
                                {{ dayData.day }}
                            </div>

                            <!-- Leaves -->
                            <div class="flex-1 space-y-1 overflow-y-auto">
                                <div
                                    v-for="leave in dayData.leaves"
                                    :key="leave.id"
                                    :class="[
                                        'px-2 py-1 rounded text-xs text-white truncate cursor-pointer hover:opacity-80 transition-opacity',
                                        getLeaveTypeColor(leave.leave_type_code)
                                    ]"
                                    :title="`${leave.user_name} - ${leave.leave_type}`"
                                >
                                    {{ leave.user_name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Legend -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Leave Types Legend -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Ta'til Turlari</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-blue-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Yillik Ta'til</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-red-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Kasallik Ta'tili</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-purple-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Oilaviy Ta'til</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded bg-gray-500"></div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">To'lovdan Tashqari</span>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Statistika</h3>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Jami Ta'tillar:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ requests.length }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Xodimlar:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ leavesByUser.length }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Jami Kunlar:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ requests.reduce((sum, r) => sum + r.total_days, 0) }} kun
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Leaves List -->
            <div v-if="requests.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Ushbu Oyda Ta'tillar</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="leave in requests"
                        :key="leave.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    :class="[
                                        'w-3 h-3 rounded-full',
                                        getLeaveTypeColor(leave.leave_type_code)
                                    ]"
                                ></div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ leave.user_name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ leave.leave_type }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ new Date(leave.start_date).toLocaleDateString('uz-UZ') }} -
                                    {{ new Date(leave.end_date).toLocaleDateString('uz-UZ') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ leave.total_days }} kun</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <CalendarIcon class="w-12 h-12 mx-auto mb-4 text-gray-400 opacity-50" />
                <p class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Ushbu oyda ta'tillar yo'q</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Hozircha hech kim ta'tilda emas</p>
            </div>
        </div>
    </HRLayout>
</template>
