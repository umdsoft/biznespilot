<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import AIChannelPage from '@/components/ai-analysis/AIChannelPage.vue';
import { h } from 'vue';

defineProps({
    channel: Object,
    metrics: Array,
});

// Instagram icon component
const InstagramIcon = {
    render() {
        return h('svg', { class: 'w-8 h-8', viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z' })
        ]);
    }
};

const platformConfig = {
    title: 'Instagram Tahlili',
    subtitle: 'Instagram akkauntingiz statistikasi',
    headerGradient: 'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500',
    icon: InstagramIcon,
    emptyIconBg: 'bg-gradient-to-br from-purple-100 via-pink-100 to-orange-100 dark:from-purple-900/30 dark:via-pink-900/30 dark:to-orange-900/30',
    emptyIconColor: 'text-pink-600 dark:text-pink-400',
    connectTitle: 'Instagram Ulang',
    connectDescription: 'Instagram akkauntingizni ulang va batafsil tahlillarni ko\'ring.',
    connectButtonClass: 'bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700',
    connectButtonText: 'Instagram Ulash',
    metricsConfig: [
        { key: 'followers_count', label: 'Followers', icon: '👥', iconColor: 'text-pink-600' },
        { key: 'following_count', label: 'Following', icon: '➡️', iconColor: 'text-purple-600' },
        { key: 'media_count', label: 'Postlar', icon: '📷', iconColor: 'text-orange-600' },
        { key: 'engagement_rate', label: 'Engagement', icon: '📈', iconColor: 'text-green-600', suffix: '%' },
    ],
};
</script>

<template>
    <MarketingLayout title="Instagram Tahlili">
        <Head title="Instagram Tahlili" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="marketing"
            :platform-config="platformConfig"
        />
    </MarketingLayout>
</template>
