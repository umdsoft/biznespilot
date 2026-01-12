<template>
    <BusinessLayout :title="`${channel.name} - Analytics`">
        <MarketingChannelDetail
            panel-type="business"
            :channel="channel"
            :metrics="metrics"
            :chart-data="chartData"
            :summary="summary"
            :period="period"
        />
    </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
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
