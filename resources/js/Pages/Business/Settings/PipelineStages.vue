<template>
    <BusinessLayout :title="t('settings.pipeline_stages')">
        <PipelineStagesManager
            :stages="stages"
            :colors="colors"
            panel-type="business"
        />
    </BusinessLayout>
</template>

<script setup>
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import PipelineStagesManager from '@/components/settings/PipelineStagesManager.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stages: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});
</script>
