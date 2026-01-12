<script setup>
import { Head } from '@inertiajs/vue3';
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import AIChannelPage from '@/components/ai-analysis/AIChannelPage.vue';
import { h } from 'vue';

defineProps({
    channel: Object,
    metrics: Array,
});

// Telegram icon component
const TelegramIcon = {
    render() {
        return h('svg', { class: 'w-8 h-8', viewBox: '0 0 24 24', fill: 'currentColor' }, [
            h('path', { d: 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z' })
        ]);
    }
};

const platformConfig = {
    title: 'Telegram Funnel',
    subtitle: 'Telegram kanal va botlaringiz tahlili',
    headerGradient: 'bg-gradient-to-br from-sky-400 to-blue-600',
    icon: TelegramIcon,
    emptyIconBg: 'bg-sky-100 dark:bg-sky-900/30',
    emptyIconColor: 'text-sky-600 dark:text-sky-400',
    connectTitle: 'Telegram Ulang',
    connectDescription: 'Telegram kanal yoki botingizni ulang va batafsil tahlillarni ko\'ring.',
    connectButtonClass: 'bg-sky-600 hover:bg-sky-700',
    connectButtonText: 'Telegram Ulash',
    metricsConfig: [
        { key: 'subscribers', label: 'A\'zolar', icon: '👥', iconColor: 'text-sky-600' },
        { key: 'views', label: 'Ko\'rishlar', icon: '👁️', iconColor: 'text-blue-600' },
        { key: 'growth', label: 'O\'sish', icon: '📈', iconColor: 'text-green-600', prefix: '+', suffix: '%' },
    ],
};
</script>

<template>
    <MarketingLayout title="Telegram Funnel">
        <Head title="Telegram Funnel" />
        <AIChannelPage
            :channel="channel"
            :metrics="metrics"
            panel-type="marketing"
            :platform-config="platformConfig"
        />
    </MarketingLayout>
</template>
