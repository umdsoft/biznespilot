<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentIndex from '@/components/content/ContentIndex.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    posts: { type: Array, default: () => [] },
    activeOffers: { type: Array, default: () => [] },
    aiRemaining: { type: [Number, null], default: null },
    painPoints: { type: Array, default: () => [] },
});
</script>

<template>
    <MarketingLayout :title="t('nav.content_plan')">
        <Head :title="t('nav.content_plan')" />
        <ContentIndex :posts="posts" :active-offers="activeOffers" :ai-remaining="aiRemaining" :pain-points="painPoints" panel-type="marketing" />
    </MarketingLayout>
</template>
