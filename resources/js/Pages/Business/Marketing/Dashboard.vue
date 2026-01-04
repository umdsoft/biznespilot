<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    channels: {
        type: Array,
        default: () => []
    },
    analytics: {
        type: Object,
        default: () => ({
            total_channels: 0,
            active_channels: 0,
            total_reach: 0,
            total_engagement: 0,
            average_engagement_rate: 0
        })
    },
    lazyLoad: {
        type: Boolean,
        default: false
    }
});

// Lazy loading state
const isLoading = ref(false);
const loadedData = ref({
    channels: null,
    analytics: null,
});

// Default values
const defaultAnalytics = {
    total_channels: 0,
    active_channels: 0,
    total_reach: 0,
    total_engagement: 0,
    average_engagement_rate: 0
};

// Computed properties with null handling
const channels = computed(() => loadedData.value.channels || props.channels || []);
const analytics = computed(() => loadedData.value.analytics || props.analytics || defaultAnalytics);

// Fetch data via API
const fetchData = async () => {
    if (!props.lazyLoad) return;

    isLoading.value = true;
    try {
        const response = await axios.get('/business/marketing/api/dashboard');
        if (response.data) {
            loadedData.value = {
                channels: response.data.channels || [],
                analytics: response.data.analytics || defaultAnalytics,
            };
        }
    } catch (error) {
        console.error('Marketing dashboard data loading error:', error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    if (props.lazyLoad) {
        fetchData();
    }
});

// Format numbers with commas
const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('uz-UZ').format(num);
};

// Marketing Fundamentals sections
const marketingFundamentals = [
    {
        title: 'Mijoz Portreti',
        description: 'Ideal mijozingiz profili va xarid qilish xulq-atvori',
        icon: 'user-heart',
        href: '/business/dream-buyer',
        color: 'from-pink-500 to-rose-600',
        bgColor: 'bg-pink-500/10',
        iconColor: 'text-pink-400'
    },
    {
        title: 'Raqobatchilar',
        description: 'Raqobatchilar tahlili va bozor pozitsiyasi',
        icon: 'users',
        href: '/business/competitors',
        color: 'from-orange-500 to-amber-600',
        bgColor: 'bg-orange-500/10',
        iconColor: 'text-orange-400'
    },
    {
        title: 'SWOT Tahlil',
        description: 'Kuchli tomonlar, zaif tomonlar, imkoniyatlar va xavflar',
        icon: 'grid',
        href: '/business/swot',
        color: 'from-blue-500 to-cyan-600',
        bgColor: 'bg-blue-500/10',
        iconColor: 'text-blue-400'
    },
    {
        title: 'Takliflar',
        description: 'Mahsulot va xizmatlaringiz qiymati',
        icon: 'gift',
        href: '/business/offers',
        color: 'from-emerald-500 to-teal-600',
        bgColor: 'bg-emerald-500/10',
        iconColor: 'text-emerald-400'
    }
];

// Content and Channels sections
const contentAndChannels = [
    {
        title: 'Kontent Kalendar',
        description: 'Kontent rejasi va nashr jadvali',
        icon: 'calendar',
        href: '/business/marketing/content',
        color: 'from-violet-500 to-purple-600',
        bgColor: 'bg-violet-500/10',
        iconColor: 'text-violet-400'
    },
    {
        title: 'Marketing Kanallari',
        description: 'Instagram, Telegram, Facebook va boshqalar',
        icon: 'share',
        href: '/business/marketing/channels',
        color: 'from-indigo-500 to-blue-600',
        bgColor: 'bg-indigo-500/10',
        iconColor: 'text-indigo-400'
    },
    {
        title: 'Reklama Kampaniyalari',
        description: 'Google Ads va ijtimoiy tarmoq reklamalari',
        icon: 'megaphone',
        href: '/business/marketing/campaigns',
        color: 'from-red-500 to-rose-600',
        bgColor: 'bg-red-500/10',
        iconColor: 'text-red-400'
    }
];

// AI and Analytics sections
const aiAndAnalytics = [
    {
        title: 'AI Strategiya',
        description: 'Sun\'iy intellekt yordamida marketing strategiyasi',
        icon: 'sparkles',
        href: '/business/ai/marketing-strategy',
        color: 'from-purple-500 to-indigo-600',
        bgColor: 'bg-purple-500/10',
        iconColor: 'text-purple-400'
    },
    {
        title: 'AI Chat',
        description: 'Marketing bo\'yicha AI bilan suhbat',
        icon: 'chat',
        href: '/business/ai/chat',
        color: 'from-cyan-500 to-blue-600',
        bgColor: 'bg-cyan-500/10',
        iconColor: 'text-cyan-400'
    },
    {
        title: 'Hisobotlar',
        description: 'Marketing samaradorligi hisobotlari',
        icon: 'chart',
        href: '/business/reports',
        color: 'from-green-500 to-emerald-600',
        bgColor: 'bg-green-500/10',
        iconColor: 'text-green-400'
    },
    {
        title: 'KPI Reja',
        description: 'Asosiy ko\'rsatkichlar va maqsadlar',
        icon: 'target',
        href: '/business/kpi',
        color: 'from-amber-500 to-yellow-600',
        bgColor: 'bg-amber-500/10',
        iconColor: 'text-amber-400'
    }
];
</script>

<template>
    <Head title="Marketing Markazi" />

    <BusinessLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-white leading-tight">
                        Marketing Markazi
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">
                        Barcha marketing vositalaringiz bir joyda
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <!-- Hero Stats Section -->
            <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl p-6 mb-8 shadow-lg">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-white text-lg font-semibold mb-1">Marketing Dashboard</h3>
                        <p class="text-white/80 text-sm">Umumiy marketing ko'rsatkichlari</p>
                    </div>
                    <div class="flex flex-wrap gap-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-white">{{ analytics?.total_channels || 0 }}</p>
                            <p class="text-white/80 text-sm">Kanallar</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-white">{{ formatNumber(analytics?.total_reach) }}</p>
                            <p class="text-white/80 text-sm">Reach</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-white">{{ formatNumber(analytics?.total_engagement) }}</p>
                            <p class="text-white/80 text-sm">Engagement</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Marketing Fundamentals Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <span class="w-2 h-6 bg-gradient-to-b from-pink-500 to-rose-600 rounded-full mr-3"></span>
                        Marketing Asoslari
                    </h3>
                    <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded-full">Strategiya poydevori</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="item in marketingFundamentals"
                        :key="item.title"
                        :href="item.href"
                        class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-5 hover:border-slate-600 hover:bg-slate-800/80 transition-all duration-300"
                    >
                        <div :class="[item.bgColor, 'w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform']">
                            <!-- User Heart Icon -->
                            <svg v-if="item.icon === 'user-heart'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <!-- Users Icon -->
                            <svg v-else-if="item.icon === 'users'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Grid Icon -->
                            <svg v-else-if="item.icon === 'grid'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            <!-- Gift Icon -->
                            <svg v-else-if="item.icon === 'gift'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            </svg>
                        </div>
                        <h4 class="text-white font-semibold mb-1 group-hover:text-indigo-400 transition-colors">{{ item.title }}</h4>
                        <p class="text-slate-400 text-sm">{{ item.description }}</p>
                    </Link>
                </div>
            </div>

            <!-- Content and Channels Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <span class="w-2 h-6 bg-gradient-to-b from-violet-500 to-purple-600 rounded-full mr-3"></span>
                        Kontent va Kanallar
                    </h3>
                    <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded-full">Ijro va tarqatish</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <Link
                        v-for="item in contentAndChannels"
                        :key="item.title"
                        :href="item.href"
                        class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-5 hover:border-slate-600 hover:bg-slate-800/80 transition-all duration-300"
                    >
                        <div :class="[item.bgColor, 'w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform']">
                            <!-- Calendar Icon -->
                            <svg v-if="item.icon === 'calendar'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <!-- Share Icon -->
                            <svg v-else-if="item.icon === 'share'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            <!-- Megaphone Icon -->
                            <svg v-else-if="item.icon === 'megaphone'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <h4 class="text-white font-semibold mb-1 group-hover:text-indigo-400 transition-colors">{{ item.title }}</h4>
                        <p class="text-slate-400 text-sm">{{ item.description }}</p>
                    </Link>
                </div>
            </div>

            <!-- AI and Analytics Section -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <span class="w-2 h-6 bg-gradient-to-b from-purple-500 to-indigo-600 rounded-full mr-3"></span>
                        AI va Tahlil
                    </h3>
                    <span class="text-xs text-slate-500 bg-slate-800 px-2 py-1 rounded-full">Sun'iy intellekt</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="item in aiAndAnalytics"
                        :key="item.title"
                        :href="item.href"
                        class="group bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-5 hover:border-slate-600 hover:bg-slate-800/80 transition-all duration-300"
                    >
                        <div :class="[item.bgColor, 'w-12 h-12 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform']">
                            <!-- Sparkles Icon -->
                            <svg v-if="item.icon === 'sparkles'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                            <!-- Chat Icon -->
                            <svg v-else-if="item.icon === 'chat'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <!-- Chart Icon -->
                            <svg v-else-if="item.icon === 'chart'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <!-- Target Icon -->
                            <svg v-else-if="item.icon === 'target'" :class="[item.iconColor, 'w-6 h-6']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h4 class="text-white font-semibold mb-1 group-hover:text-indigo-400 transition-colors">{{ item.title }}</h4>
                        <p class="text-slate-400 text-sm">{{ item.description }}</p>
                    </Link>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Tezkor Amallar</h3>
                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/business/dream-buyer"
                        class="inline-flex items-center px-4 py-2 bg-pink-600 hover:bg-pink-500 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Mijoz Portretini Yaratish
                    </Link>
                    <Link
                        href="/business/swot"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        SWOT Tahlil Qilish
                    </Link>
                    <Link
                        href="/business/competitors"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Raqobatchi Qo'shish
                    </Link>
                    <Link
                        href="/business/marketing/content"
                        class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Kontent Rejalashtirish
                    </Link>
                </div>
            </div>
        </div>
    </BusinessLayout>
</template>
