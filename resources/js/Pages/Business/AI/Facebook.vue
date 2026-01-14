<script setup>
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import AIChannelPage from '@/components/ai-analysis/AIChannelPage.vue';
import { h } from 'vue';

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

const platformConfig = {
    title: 'Facebook Tahlili',
    subtitle: 'Facebook sahifangiz statistikasi',
    headerGradient: 'bg-gradient-to-br from-blue-500 to-blue-700',
    icon: FacebookIcon,
    emptyIconBg: 'bg-blue-100 dark:bg-blue-900/30',
    emptyIconColor: 'text-blue-600 dark:text-blue-400',
    connectTitle: 'Facebook Ulang',
    connectDescription: 'Facebook sahifangizni ulang va batafsil tahlillarni ko\'ring.',
    connectButtonClass: 'bg-blue-600 hover:bg-blue-700',
    connectButtonText: 'Facebook Ulash',
    metricsConfig: [
        { key: 'followers_count', label: 'Followers', icon: '👥', iconColor: 'text-blue-600' },
        { key: 'page_likes', label: 'Page Likes', icon: '👍', iconColor: 'text-green-600' },
        { key: 'reach', label: 'Reach', icon: '📈', iconColor: 'text-purple-600' },
        { key: 'engagement_rate', label: 'Engagement', icon: '💬', iconColor: 'text-orange-600', suffix: '%' },
    ],
    tableTitle: 'Haftalik Statistika',
    tableColumns: [
        { key: 'date', label: 'Sana' },
        { key: 'reach', label: 'Reach' },
        { key: 'impressions', label: 'Ko\'rishlar' },
        { key: 'engagement', label: 'Engagement' },
    ],
};
</script>

<template>
    <BusinessLayout title="Facebook Tahlili">
        <Head title="Facebook Tahlili" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="business"
            :platform-config="platformConfig"
        />
    </BusinessLayout>
</template>
