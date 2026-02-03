<script setup>
/**
 * SubscriptionWidget - Dashboard uchun obuna holati va limit vidjeti
 *
 * Foydalanuvchiga joriy tarif, to'lov holati va limitlar qancha
 * qolganini vizual ko'rsatadi.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    subscriptionStatus: {
        type: Object,
        default: () => null,
    },
});

// Has subscription
const hasSubscription = computed(() => props.subscriptionStatus?.has_subscription ?? false);

// Plan info
const planName = computed(() => props.subscriptionStatus?.plan_name || 'Bepul');
const planSlug = computed(() => props.subscriptionStatus?.plan_slug || 'free');
const planPrice = computed(() => props.subscriptionStatus?.price || 0);

// Status info
const status = computed(() => props.subscriptionStatus?.status || 'no_subscription');
const statusLabel = computed(() => props.subscriptionStatus?.status_label || 'Obuna yo\'q');
const statusColor = computed(() => props.subscriptionStatus?.status_color || 'gray');
const isTrial = computed(() => props.subscriptionStatus?.is_trial || false);

// Days remaining
const daysRemaining = computed(() => props.subscriptionStatus?.days_remaining || 0);
const renewsAt = computed(() => props.subscriptionStatus?.renews_at || null);

// Usage data
const usage = computed(() => props.subscriptionStatus?.usage || {});

// Is premium plan (not free or starter)
const isPremium = computed(() => {
    const slug = planSlug.value?.toLowerCase();
    return slug !== 'free' && slug !== 'starter' && slug !== 'bepul';
});

// Days remaining color
const daysRemainingColor = computed(() => {
    if (daysRemaining.value <= 3) return 'text-red-600 dark:text-red-400';
    if (daysRemaining.value <= 7) return 'text-amber-600 dark:text-amber-400';
    return 'text-green-600 dark:text-green-400';
});

// Progress bar color based on percentage
const getProgressColor = (percent, isExceeded, isWarning) => {
    if (isExceeded) return 'bg-red-500';
    if (isWarning || percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-amber-500';
    return 'bg-blue-500';
};

// Format number with K/M suffix
const formatNumber = (num) => {
    if (num === null || num === undefined) return 'Cheksiz';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
};

// Format price
const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('uz-UZ').format(price);
};

// Check if any limit is critical (>90%)
const hasCriticalLimit = computed(() => {
    return Object.values(usage.value).some(item => item.percent >= 90 && !item.is_unlimited);
});

// Sorted usage entries (show critical first)
const sortedUsage = computed(() => {
    return Object.entries(usage.value)
        .filter(([key, value]) => !value.is_unlimited) // Only show limited items
        .sort((a, b) => b[1].percent - a[1].percent)
        .slice(0, 4); // Show max 4 items
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <!-- Header -->
        <div class="relative overflow-hidden">
            <!-- Background gradient -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAwIDEwIEwgNDAgMTAgTSAxMCAwIEwgMTAgNDAgTSAwIDIwIEwgNDAgMjAgTSAyMCAwIEwgMjAgNDAgTSAwIDMwIEwgNDAgMzAgTSAzMCAwIEwgMzAgNDAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-30"></div>

            <div class="relative px-5 py-4">
                <div class="flex items-center justify-between">
                    <!-- Plan Name & Price -->
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-bold text-white">{{ planName }}</h3>
                            <span v-if="isTrial"
                                  class="px-2 py-0.5 text-xs font-medium bg-white/20 text-white rounded-full">
                                Sinov
                            </span>
                        </div>
                        <p v-if="planPrice" class="text-blue-100 text-sm mt-0.5">
                            {{ formatPrice(planPrice) }} so'm/oy
                        </p>
                    </div>

                    <!-- Upgrade Button -->
                    <Link v-if="!isPremium"
                          href="/business/billing/plans"
                          class="flex items-center gap-1.5 px-4 py-2 bg-white text-indigo-600 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        Upgrade
                    </Link>
                </div>
            </div>
        </div>

        <!-- Status Bar -->
        <div class="px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <!-- Status Dot -->
                    <span class="relative flex h-2.5 w-2.5">
                        <span :class="[
                            'animate-ping absolute inline-flex h-full w-full rounded-full opacity-75',
                            statusColor === 'green' ? 'bg-green-400' : '',
                            statusColor === 'blue' ? 'bg-blue-400' : '',
                            statusColor === 'red' ? 'bg-red-400' : '',
                            statusColor === 'gray' ? 'bg-gray-400' : '',
                        ]"></span>
                        <span :class="[
                            'relative inline-flex rounded-full h-2.5 w-2.5',
                            statusColor === 'green' ? 'bg-green-500' : '',
                            statusColor === 'blue' ? 'bg-blue-500' : '',
                            statusColor === 'red' ? 'bg-red-500' : '',
                            statusColor === 'gray' ? 'bg-gray-500' : '',
                        ]"></span>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ statusLabel }}</span>
                </div>

                <!-- Days Remaining -->
                <div v-if="hasSubscription && renewsAt" class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Keyingi to'lov</p>
                    <p :class="['text-sm font-semibold', daysRemainingColor]">
                        {{ daysRemaining }} kundan keyin
                    </p>
                </div>
            </div>
        </div>

        <!-- Usage Progress Bars -->
        <div class="px-5 py-4 space-y-4">
            <template v-if="sortedUsage.length > 0">
                <div v-for="[key, item] in sortedUsage" :key="key" class="space-y-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ item.label }}</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">
                            {{ formatNumber(item.used) }} / {{ formatNumber(item.limit) }}
                        </span>
                    </div>
                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div
                            :class="[
                                'h-full rounded-full transition-all duration-500',
                                getProgressColor(item.percent, item.is_exceeded, item.is_warning)
                            ]"
                            :style="{ width: Math.min(item.percent, 100) + '%' }"
                        ></div>
                    </div>
                </div>
            </template>

            <!-- No limits message -->
            <template v-else-if="hasSubscription">
                <div class="text-center py-2 text-gray-500 dark:text-gray-400 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Barcha limitlar cheksiz
                </div>
            </template>

            <!-- No subscription -->
            <template v-else>
                <div class="text-center py-4">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">Obuna mavjud emas</p>
                    <Link href="/business/billing/plans"
                          class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Tariflarni ko'rish
                    </Link>
                </div>
            </template>
        </div>

        <!-- Critical Warning CTA -->
        <div v-if="hasCriticalLimit && hasSubscription"
             class="px-5 py-3 bg-red-50 dark:bg-red-900/20 border-t border-red-100 dark:border-red-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-medium text-red-700 dark:text-red-300">Limit tugamoqda!</span>
                </div>
                <Link href="/business/billing/plans"
                      class="px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                    Limitni oshirish
                </Link>
            </div>
        </div>
    </div>
</template>
