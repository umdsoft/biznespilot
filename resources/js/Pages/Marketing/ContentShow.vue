<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentShowPage from '@/components/content/ContentShowPage.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <MarketingLayout :title="t('marketing.content_details')">
        <Head :title="t('marketing.content_details')" />
        <ContentShowPage :post="post" panel-type="marketing" />
    </MarketingLayout>
</template>
