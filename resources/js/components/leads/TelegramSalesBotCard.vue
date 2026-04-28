<script setup>
import { computed } from 'vue';
import { SparklesIcon, CurrencyDollarIcon, TagIcon, ExclamationCircleIcon, BoltIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    lead: {
        type: Object,
        required: true,
    },
});

// AI Sales Bot orqali kelgan lid uchun customer_profile data.lead.customer_profile da
const profile = computed(() => {
    const data = props.lead?.data || {};
    return data.customer_profile || null;
});

const isFromSalesBot = computed(() => {
    const source = props.lead?.data?.source;
    return source === 'telegram_sales_bot';
});

const constraints = computed(() => profile.value?.constraints || {});

const formatBudget = computed(() => {
    const min = constraints.value.budget_min;
    const max = constraints.value.budget_max;
    if (!min && !max) return null;
    if (min && max) return `${formatNumber(min)} – ${formatNumber(max)} so'm`;
    if (max) return `≤ ${formatNumber(max)} so'm`;
    return `≥ ${formatNumber(min)} so'm`;
});

const completenessPercent = computed(() => {
    const c = parseFloat(profile.value?.info_completeness || 0);
    return Math.round(c * 100);
});

const completenessColor = computed(() => {
    const p = completenessPercent.value;
    if (p >= 70) return 'bg-emerald-500';
    if (p >= 40) return 'bg-amber-500';
    return 'bg-gray-400';
});

const formatNumber = (n) => {
    if (!n && n !== 0) return '0';
    return new Intl.NumberFormat('uz-UZ').format(n);
};

const telegramInfo = computed(() => {
    const data = props.lead?.data || {};
    return {
        username: data.telegram_username,
        telegram_id: data.telegram_id,
    };
});
</script>

<template>
    <div v-if="isFromSalesBot && profile" class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6 mb-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-200 flex items-center gap-2">
                <SparklesIcon class="w-5 h-5" />
                AI Sotuvchi Bot — Mijoz Portreti
            </h3>
            <a v-if="telegramInfo.username" :href="`https://t.me/${telegramInfo.username}`" target="_blank" rel="noopener"
                class="text-sm text-purple-700 dark:text-purple-300 hover:underline flex items-center gap-1">
                @{{ telegramInfo.username }} ↗
            </a>
        </div>

        <!-- Info Completeness -->
        <div class="mb-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ma'lumot to'liqligi</span>
                <span class="text-sm font-bold text-purple-700 dark:text-purple-300">{{ completenessPercent }}%</span>
            </div>
            <div class="w-full bg-white dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                <div :class="['h-full transition-all', completenessColor]" :style="{ width: completenessPercent + '%' }"></div>
            </div>
        </div>

        <!-- Profile Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Asosiy ehtiyoj -->
            <div v-if="profile.primary_intent" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <BoltIcon class="w-4 h-4 text-purple-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Asosiy ehtiyoj</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ profile.primary_intent }}</div>
                    </div>
                </div>
            </div>

            <!-- Foydalanish maqsadi -->
            <div v-if="profile.use_case" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <CheckBadgeIcon class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Foydalanish maqsadi</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ profile.use_case }}</div>
                    </div>
                </div>
            </div>

            <!-- Byudjet -->
            <div v-if="formatBudget" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <CurrencyDollarIcon class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Byudjet</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatBudget }}</div>
                    </div>
                </div>
            </div>

            <!-- O'lcham/Razmer -->
            <div v-if="constraints.size" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <TagIcon class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">O'lcham</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ constraints.size }}</div>
                    </div>
                </div>
            </div>

            <!-- Rang -->
            <div v-if="constraints.color" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <TagIcon class="w-4 h-4 text-pink-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Rang</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ constraints.color }}</div>
                    </div>
                </div>
            </div>

            <!-- Brand -->
            <div v-if="constraints.brand" class="bg-white dark:bg-gray-800 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <TagIcon class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Brand</div>
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ constraints.brand }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preferences (afzalliklar) -->
        <div v-if="constraints.preferences && constraints.preferences.length" class="mt-4">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Afzalliklar</div>
            <div class="flex flex-wrap gap-2">
                <span v-for="(pref, i) in constraints.preferences" :key="i"
                    class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-medium rounded-full">
                    ✓ {{ pref }}
                </span>
            </div>
        </div>

        <!-- Avoid (qochmoqchi) -->
        <div v-if="constraints.avoid && constraints.avoid.length" class="mt-3">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Qochmoqchi</div>
            <div class="flex flex-wrap gap-2">
                <span v-for="(item, i) in constraints.avoid" :key="i"
                    class="px-2.5 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-xs font-medium rounded-full">
                    ✕ {{ item }}
                </span>
            </div>
        </div>

        <!-- Marketing maslahat -->
        <div class="mt-5 pt-4 border-t border-purple-200 dark:border-purple-800">
            <div class="flex items-start gap-2 text-xs text-purple-800 dark:text-purple-300">
                <ExclamationCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
                <div>
                    <strong>Marketing uchun:</strong> Bu ma'lumot AI Sotuvchi Bot orqali tabiiy dialogda to'plangan.
                    Mijoz xohish-istaklarini bilgan holda — keyingi kampaniyangizni shaxsiylashtiring.
                </div>
            </div>
        </div>
    </div>
</template>
