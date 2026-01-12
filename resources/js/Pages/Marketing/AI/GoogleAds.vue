<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import AIChannelPage from '@/components/ai-analysis/AIChannelPage.vue';
import { h } from 'vue';

defineProps({
    channel: Object,
    metrics: Array,
});

// Google icon component
const GoogleIcon = {
    render() {
        return h('svg', { class: 'w-8 h-8', viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z' })
        ]);
    }
};

const platformConfig = {
    title: 'Google Ads',
    subtitle: 'Google reklama kampaniyalaringiz tahlili',
    headerGradient: 'bg-gradient-to-br from-yellow-400 via-red-500 to-blue-600',
    icon: GoogleIcon,
    emptyIconBg: 'bg-gradient-to-br from-yellow-100 via-red-100 to-blue-100 dark:from-yellow-900/30 dark:via-red-900/30 dark:to-blue-900/30',
    emptyIconColor: 'text-blue-600 dark:text-blue-400',
    connectTitle: 'Google Ads Ulang',
    connectDescription: 'Google Ads akkauntingizni ulang va reklama kampaniyalaringizni tahlil qiling.',
    connectButtonClass: 'bg-blue-600 hover:bg-blue-700',
    connectButtonText: 'Google Ads Ulash',
    metricsConfig: [
        { key: 'spend', label: 'Xarajat', icon: '💰', iconColor: 'text-red-600', prefix: '$' },
        { key: 'clicks', label: 'Kliklar', icon: '👆', iconColor: 'text-blue-600' },
        { key: 'impressions', label: 'Ko\'rishlar', icon: '👁️', iconColor: 'text-green-600' },
        { key: 'conversions', label: 'Konversiya', icon: '🎯', iconColor: 'text-yellow-600' },
    ],
    tableTitle: 'Kampaniya Statistikasi',
    tableColumns: [
        { key: 'metric_date', label: 'Sana' },
        { key: 'spend', label: 'Xarajat', prefix: '$' },
        { key: 'clicks', label: 'Kliklar' },
        { key: 'ctr', label: 'CTR', suffix: '%' },
    ],
};
</script>

<template>
    <MarketingLayout title="Google Ads">
        <Head title="Google Ads" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="marketing"
            :platform-config="platformConfig"
        />
    </MarketingLayout>
</template>
