<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseLayout from './BaseLayout.vue';
import BusinessLayout from './BusinessLayout.vue';
import { hrLayoutConfig } from '@/composables/useLayoutConfig';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
    title: String,
});

const page = usePage();

// Business owner bo'lsa — BusinessLayout ko'rsatish (qaysi URL dan kirishidan qat'i nazar)
const isBusinessOwner = computed(() => {
    return page.props?.currentBusiness?.is_owner === true;
});
</script>

<template>
    <BusinessLayout v-if="isBusinessOwner" :title="title || 'HR'">
        <slot />
    </BusinessLayout>

    <BaseLayout v-else :config="hrLayoutConfig" :title="title || t('layout.home')" panel-type="hr">
        <slot />
    </BaseLayout>
</template>
