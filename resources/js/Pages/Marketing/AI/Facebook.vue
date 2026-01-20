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

// Facebook icon component
const FacebookIcon = {
    render() {
        return h('svg', { class: 'w-8 h-8', viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z' })
        ]);
    }
};

const platformConfig = computed(() => ({
    title: t('nav.facebook_analysis'),
    subtitle: t('marketing.facebook_stats'),
    headerGradient: 'bg-gradient-to-br from-blue-500 to-blue-700',
    icon: FacebookIcon,
    emptyIconBg: 'bg-blue-100 dark:bg-blue-900/30',
    emptyIconColor: 'text-blue-600 dark:text-blue-400',
    connectTitle: t('marketing.connect_facebook'),
    connectDescription: t('marketing.connect_facebook_desc'),
    connectButtonClass: 'bg-blue-600 hover:bg-blue-700',
    connectButtonText: t('marketing.connect_facebook_btn'),
    metricsConfig: [
        { key: 'followers_count', label: 'Followers', icon: '👥', iconColor: 'text-blue-600' },
        { key: 'page_likes', label: 'Page Likes', icon: '👍', iconColor: 'text-green-600' },
        { key: 'reach', label: 'Reach', icon: '📈', iconColor: 'text-purple-600' },
        { key: 'engagement_rate', label: 'Engagement', icon: '💬', iconColor: 'text-orange-600', suffix: '%' },
    ],
    tableTitle: t('marketing.weekly_stats'),
    tableColumns: [
        { key: 'date', label: t('common.date') },
        { key: 'reach', label: 'Reach' },
        { key: 'impressions', label: t('marketing.views') },
        { key: 'engagement', label: 'Engagement' },
    ],
}));
</script>

<template>
    <MarketingLayout :title="t('nav.facebook_analysis')">
        <Head :title="t('nav.facebook_analysis')" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="marketing"
            :platform-config="platformConfig"
        />
    </MarketingLayout>
</template>
