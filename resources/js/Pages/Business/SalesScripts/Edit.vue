<template>
  <BusinessLayout title="Skript Tahrirlash">
    <Head title="Skript Tahrirlash" />
    <ScriptForm
      :script="script"
      :stage-labels="stageLabels"
      mode="edit"
      @saved="onSaved"
    />
  </BusinessLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ScriptForm from '@/components/SalesScripts/ScriptForm.vue';

defineProps({
  script: { type: Object, required: true },
  stageLabels: { type: Object, default: () => ({}) },
});

const onSaved = () => router.visit(route('business.sales-scripts.index'));
</script>
