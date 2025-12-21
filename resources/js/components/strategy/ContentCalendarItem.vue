<template>
  <div
    class="p-2 rounded-lg cursor-pointer transition-all hover:shadow-md"
    :class="[statusBgClass, isDragging ? 'opacity-50 scale-95' : '']"
    draggable="true"
    @dragstart="onDragStart"
    @dragend="onDragEnd"
    @click="$emit('click')"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-1">
      <div class="flex items-center space-x-1.5">
        <component
          :is="channelIcon"
          class="w-4 h-4"
          :class="channelColor"
        />
        <span class="text-xs font-medium" :class="channelColor">
          {{ channelLabel }}
        </span>
      </div>
      <span class="text-xs text-gray-500">{{ item.scheduled_time || '--:--' }}</span>
    </div>

    <!-- Title -->
    <h4 class="text-sm font-medium text-gray-900 line-clamp-2">
      {{ item.title }}
    </h4>

    <!-- Type badge -->
    <div class="flex items-center justify-between mt-2">
      <span
        class="px-1.5 py-0.5 text-xs rounded"
        :class="typeClass"
      >
        {{ typeLabel }}
      </span>
      <span
        class="px-1.5 py-0.5 text-xs rounded-full"
        :class="statusClass"
      >
        {{ statusLabel }}
      </span>
    </div>

    <!-- AI badge -->
    <div
      v-if="item.is_ai_generated"
      class="mt-2 flex items-center text-xs text-purple-600"
    >
      <SparklesIcon class="w-3.5 h-3.5 mr-1" />
      AI yaratgan
    </div>

    <!-- Metrics (if published) -->
    <div
      v-if="item.status === 'published' && (item.likes > 0 || item.views > 0)"
      class="mt-2 flex items-center space-x-3 text-xs text-gray-500"
    >
      <span v-if="item.views > 0" class="flex items-center">
        <EyeIcon class="w-3.5 h-3.5 mr-0.5" />
        {{ formatNumber(item.views) }}
      </span>
      <span v-if="item.likes > 0" class="flex items-center">
        <HeartIcon class="w-3.5 h-3.5 mr-0.5" />
        {{ formatNumber(item.likes) }}
      </span>
      <span v-if="item.comments > 0" class="flex items-center">
        <ChatBubbleLeftIcon class="w-3.5 h-3.5 mr-0.5" />
        {{ formatNumber(item.comments) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  SparklesIcon,
  EyeIcon,
  HeartIcon,
  ChatBubbleLeftIcon,
} from '@heroicons/vue/24/outline';

// Channel icons (simplified - you can use actual brand icons)
const InstagramIcon = {
  template: '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2" fill="none"/><circle cx="18" cy="6" r="1.5"/></svg>'
};

const TelegramIcon = {
  template: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .37z"/></svg>'
};

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['click', 'drag-start', 'drag-end']);

const isDragging = ref(false);

const channelIcon = computed(() => {
  const icons = {
    instagram: InstagramIcon,
    telegram: TelegramIcon,
  };
  return icons[props.item.channel] || InstagramIcon;
});

const channelLabel = computed(() => {
  const labels = {
    instagram: 'Instagram',
    telegram: 'Telegram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube',
  };
  return labels[props.item.channel] || props.item.channel;
});

const channelColor = computed(() => {
  const colors = {
    instagram: 'text-pink-600',
    telegram: 'text-sky-600',
    facebook: 'text-blue-600',
    tiktok: 'text-gray-800',
    youtube: 'text-red-600',
  };
  return colors[props.item.channel] || 'text-gray-600';
});

const typeLabel = computed(() => {
  const labels = {
    post: 'Post',
    story: 'Story',
    reel: 'Reel',
    video: 'Video',
    article: 'Maqola',
    carousel: 'Carousel',
    live: 'Live',
    poll: 'So\'rovnoma',
  };
  return labels[props.item.content_type] || props.item.content_type;
});

const typeClass = computed(() => {
  const classes = {
    post: 'bg-blue-100 text-blue-700',
    story: 'bg-purple-100 text-purple-700',
    reel: 'bg-pink-100 text-pink-700',
    video: 'bg-red-100 text-red-700',
    article: 'bg-gray-100 text-gray-700',
    carousel: 'bg-indigo-100 text-indigo-700',
  };
  return classes[props.item.content_type] || 'bg-gray-100 text-gray-700';
});

const statusLabel = computed(() => {
  const labels = {
    idea: 'G\'oya',
    draft: 'Qoralama',
    pending_review: 'Tekshiruvda',
    approved: 'Tasdiqlangan',
    scheduled: 'Rejalashtirilgan',
    published: 'Joylashtirilgan',
    failed: 'Xato',
  };
  return labels[props.item.status] || props.item.status;
});

const statusClass = computed(() => {
  const classes = {
    idea: 'bg-gray-100 text-gray-600',
    draft: 'bg-yellow-100 text-yellow-700',
    pending_review: 'bg-orange-100 text-orange-700',
    approved: 'bg-blue-100 text-blue-700',
    scheduled: 'bg-indigo-100 text-indigo-700',
    published: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
  };
  return classes[props.item.status] || 'bg-gray-100 text-gray-600';
});

const statusBgClass = computed(() => {
  const classes = {
    idea: 'bg-gray-50 border border-gray-200',
    draft: 'bg-yellow-50 border border-yellow-200',
    pending_review: 'bg-orange-50 border border-orange-200',
    approved: 'bg-blue-50 border border-blue-200',
    scheduled: 'bg-indigo-50 border border-indigo-200',
    published: 'bg-green-50 border border-green-200',
    failed: 'bg-red-50 border border-red-200',
  };
  return classes[props.item.status] || 'bg-gray-50 border border-gray-200';
});

function onDragStart(event) {
  isDragging.value = true;
  event.dataTransfer.setData('text/plain', JSON.stringify(props.item));
  emit('drag-start', props.item);
}

function onDragEnd() {
  isDragging.value = false;
  emit('drag-end');
}

function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toString();
}
</script>
