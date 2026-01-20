<template>
    <div class="space-y-3">
        <div v-if="displayMembers.length === 0" class="text-center py-8">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">{{ t('dashboard.team.no_members') }}</p>
        </div>
        <div
            v-for="(member, index) in displayMembers"
            :key="member.id || index"
            :class="[
                'flex items-center gap-3 p-3 rounded-xl transition-all duration-200',
                'bg-gray-50 dark:bg-gray-700/50',
                'hover:bg-gray-100 dark:hover:bg-gray-700',
                'border border-transparent hover:border-gray-200 dark:hover:border-gray-600'
            ]"
        >
            <!-- Rank Badge -->
            <div v-if="showRank && index < 3" :class="[
                'w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0',
                index === 0 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' :
                index === 1 ? 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' :
                'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400'
            ]">
                {{ index + 1 }}
            </div>

            <!-- Avatar -->
            <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0',
                avatarColors[index % avatarColors.length]
            ]">
                <span class="font-semibold text-sm">
                    {{ getInitials(member.name) }}
                </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <p class="font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ member.leads_handled || member.leads_count || 0 }} {{ t('dashboard.team.lead') }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ member.leads_won || member.won_count || 0 }} {{ t('dashboard.team.won') }}</span>
                    <span :class="[
                        'font-medium',
                        (member.conversion_rate || 0) >= 30 ? 'text-emerald-600 dark:text-emerald-400' :
                        (member.conversion_rate || 0) >= 15 ? 'text-yellow-600 dark:text-yellow-400' :
                        'text-gray-500 dark:text-gray-400'
                    ]">
                        {{ member.conversion_rate || 0 }}% CR
                    </span>
                </div>
            </div>

            <!-- Revenue -->
            <div class="text-right flex-shrink-0">
                <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(member.revenue || 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('dashboard.team.revenue') }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    members: {
        type: Array,
        default: () => [],
    },
    limit: {
        type: Number,
        default: 5,
    },
    showRank: {
        type: Boolean,
        default: true,
    },
});

const displayMembers = computed(() => {
    const safeMembers = Array.isArray(props.members) ? props.members : [];
    return props.limit > 0 ? safeMembers.slice(0, props.limit) : safeMembers;
});

const avatarColors = [
    'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400',
    'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
    'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
    'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400',
    'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400',
];

const getInitials = (name) => {
    if (!name) return 'U';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2);
};

const formatCurrency = (value) => {
    if (value >= 1000000) {
        return (value / 1000000).toFixed(1) + 'M';
    }
    if (value >= 1000) {
        return (value / 1000).toFixed(0) + 'K';
    }
    return new Intl.NumberFormat('uz-UZ').format(value || 0);
};
</script>
