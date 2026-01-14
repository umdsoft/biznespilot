<template>
    <div
        class="p-3 rounded-lg border transition-all hover:shadow-md cursor-pointer"
        :class="statusBorderClass"
        @click="$emit('click', item)"
    >
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 truncate">{{ item.title }}</h4>
                <p v-if="item.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                    {{ item.description }}
                </p>
            </div>
            <span :class="statusClass" class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">
                {{ statusLabel }}
            </span>
        </div>
        <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
            <span v-if="item.channel" class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
                {{ item.channel }}
            </span>
            <span v-if="item.scheduled_date" class="flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(item.scheduled_date) }}
            </span>
            <span v-if="item.content_type" class="flex items-center gap-1">
                {{ contentTypeLabel }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

defineEmits(['click']);

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

const statusClass = computed(() => {
    const status = props.item.status;
    const classes = {
        published: 'bg-green-100 text-green-800',
        scheduled: 'bg-blue-100 text-blue-800',
        draft: 'bg-gray-100 text-gray-800',
        pending_review: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-indigo-100 text-indigo-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
});

const statusBorderClass = computed(() => {
    const status = props.item.status;
    const classes = {
        published: 'border-green-200',
        scheduled: 'border-blue-200',
        draft: 'border-gray-200',
        pending_review: 'border-yellow-200',
        approved: 'border-indigo-200',
    };
    return classes[status] || 'border-gray-200';
});

const statusLabel = computed(() => {
    const status = props.item.status;
    const labels = {
        published: 'Nashr qilindi',
        scheduled: 'Rejalashtirilgan',
        draft: 'Qoralama',
        pending_review: 'Ko\'rib chiqilmoqda',
        approved: 'Tasdiqlangan',
    };
    return labels[status] || status;
});

const contentTypeLabel = computed(() => {
    const type = props.item.content_type;
    const labels = {
        post: 'Post',
        story: 'Story',
        reel: 'Reel',
        video: 'Video',
        carousel: 'Carousel',
        article: 'Maqola',
    };
    return labels[type] || type;
});
</script>
