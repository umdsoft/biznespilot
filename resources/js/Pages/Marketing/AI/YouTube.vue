<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import AIChannelPage from '@/components/ai-analysis/AIChannelPage.vue';
import { h, computed } from 'vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    channel: Object,
    metrics: Array,
});

// YouTube icon component
const YouTubeIcon = {
    render() {
        return h('svg', { class: 'w-8 h-8', viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z' })
        ]);
    }
};

const platformConfig = computed(() => ({
    title: t('nav.youtube_analytics'),
    subtitle: t('marketing.youtube_stats'),
    headerGradient: 'bg-gradient-to-br from-red-500 to-red-700',
    icon: YouTubeIcon,
    emptyIconBg: 'bg-red-100 dark:bg-red-900/30',
    emptyIconColor: 'text-red-600 dark:text-red-400',
    connectTitle: t('marketing.connect_youtube'),
    connectDescription: t('marketing.connect_youtube_desc'),
    connectButtonClass: 'bg-red-600 hover:bg-red-700',
    connectButtonText: t('marketing.connect_youtube_btn'),
    metricsConfig: [
        { key: 'subscribers', label: t('marketing.subscribers'), icon: '👥', iconColor: 'text-red-600' },
        { key: 'video_count', label: t('marketing.videos'), icon: '🎬', iconColor: 'text-orange-600' },
        { key: 'total_views', label: t('marketing.views'), icon: '👁️', iconColor: 'text-blue-600' },
        { key: 'watch_hours', label: t('marketing.hours'), icon: '⏱️', iconColor: 'text-green-600' },
    ],
}));
</script>

<template>
    <MarketingLayout :title="t('nav.youtube_analytics')">
        <Head :title="t('nav.youtube_analytics')" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="marketing"
            :platform-config="platformConfig"
        />
    </MarketingLayout>
</template>
