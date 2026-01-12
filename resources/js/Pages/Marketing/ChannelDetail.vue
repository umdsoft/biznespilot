<template>
    <MarketingLayout :title="`${channel.name} - Analytics`">
        <MarketingChannelDetail
            panel-type="marketing"
            :channel="channel"
            :metrics="metrics"
            :chart-data="chartData"
            :summary="summary"
            :period="period"
        />
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import MarketingChannelDetail from '@/components/marketing/MarketingChannelDetail.vue';

defineProps({
    channel: {
        type: Object,
        required: true
    },
    metrics: {
        type: Array,
        default: () => []
    },
    chartData: {
        type: Object,
        default: () => ({ labels: [], datasets: [] })
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    period: {
        type: Number,
        default: 30
    }
});
</script>
