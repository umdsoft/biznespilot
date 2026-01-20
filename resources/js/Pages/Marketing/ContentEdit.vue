<script setup>
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import ContentEditPage from '@/components/content/ContentEditPage.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    post: { type: Object, required: true }
});
</script>

<template>
    <MarketingLayout :title="t('marketing.edit_content')">
        <Head :title="t('marketing.edit_content')" />
        <ContentEditPage :post="post" panel-type="marketing" />
    </MarketingLayout>
</template>
