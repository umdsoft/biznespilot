<template>
    <SalesHeadLayout title="Voronka Bosqichlari">
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

defineProps({
    stages: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});
</script>
