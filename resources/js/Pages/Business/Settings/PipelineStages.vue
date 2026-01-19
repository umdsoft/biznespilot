<template>
    <BusinessLayout title="Voronka Bosqichlari">
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

defineProps({
    stages: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
});
</script>
