<template>
  <BusinessLayout title="Yangi Skript">
    <Head title="Yangi Skript" />
    <ScriptForm
      :default-template="defaultTemplate"
      :stage-labels="stageLabels"
      mode="create"
      @saved="onSaved"
    />
  </BusinessLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import ScriptForm from '@/components/SalesScripts/ScriptForm.vue';

defineProps({
  defaultTemplate: { type: Object, default: () => ({}) },
  stageLabels: { type: Object, default: () => ({}) },
});

const onSaved = () => router.visit(route('business.sales-scripts.index'));
</script>
