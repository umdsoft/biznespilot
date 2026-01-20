<template>
    <SalesHeadLayout :title="t('settings.pipeline_stages')">
        <PipelineStagesManager
            :stages="stages"
            :colors="colors"
            panel-type="saleshead"
        />
    </SalesHeadLayout>
</template>

<script setup>
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import PipelineStagesManager from '@/components/settings/PipelineStagesManager.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    stages: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});
</script>
