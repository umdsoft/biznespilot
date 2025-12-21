<template>
  <div class="relative flex items-center justify-center">
    <!-- Background circle -->
    <svg :width="size" :height="size" class="transform -rotate-90">
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        fill="none"
        :stroke="bgColor"
        :stroke-width="strokeWidth"
      />
      <!-- Progress circle -->
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        fill="none"
        :stroke="scoreColor"
        :stroke-width="strokeWidth"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        stroke-linecap="round"
        class="transition-all duration-1000 ease-out"
      />
    </svg>

    <!-- Center content -->
    <div class="absolute inset-0 flex flex-col items-center justify-center">
      <span :class="['font-bold', scoreFontSize]" :style="{ color: scoreColor }">
        {{ animatedScore }}
      </span>
      <span class="text-gray-500 text-sm">{{ statusLabel }}</span>
      <span v-if="showTrend && trend" class="flex items-center text-xs mt-1" :class="trendColor">
        <component :is="trendIcon" class="w-3 h-3 mr-1" />
        {{ trendLabel }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { ArrowUpIcon, ArrowDownIcon, MinusIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  score: {
    type: Number,
    default: 0,
  },
  size: {
    type: Number,
    default: 200,
  },
  strokeWidth: {
    type: Number,
    default: 12,
  },
  trend: {
    type: Object,
    default: null,
  },
  showTrend: {
    type: Boolean,
    default: true,
  },
  animate: {
    type: Boolean,
    default: true,
  },
});

const animatedScore = ref(0);

const center = computed(() => props.size / 2);
const radius = computed(() => (props.size - props.strokeWidth) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const dashOffset = computed(() => circumference.value * (1 - animatedScore.value / 100));

const bgColor = computed(() => '#E5E7EB');

const scoreColor = computed(() => {
  const s = props.score;
  if (s >= 80) return '#3B82F6'; // blue
  if (s >= 60) return '#22C55E'; // green
  if (s >= 40) return '#EAB308'; // yellow
  return '#EF4444'; // red
});

const statusLabel = computed(() => {
  const s = props.score;
  if (s >= 80) return 'Ajoyib';
  if (s >= 60) return 'Yaxshi';
  if (s >= 40) return "O'rtacha";
  return 'Zaif';
});

const scoreFontSize = computed(() => {
  if (props.size >= 200) return 'text-4xl';
  if (props.size >= 150) return 'text-3xl';
  return 'text-2xl';
});

const trendIcon = computed(() => {
  if (!props.trend) return MinusIcon;
  if (props.trend.trend === 'up') return ArrowUpIcon;
  if (props.trend.trend === 'down') return ArrowDownIcon;
  return MinusIcon;
});

const trendColor = computed(() => {
  if (!props.trend) return 'text-gray-500';
  if (props.trend.trend === 'up') return 'text-green-500';
  if (props.trend.trend === 'down') return 'text-red-500';
  return 'text-gray-500';
});

const trendLabel = computed(() => {
  if (!props.trend) return '';
  return props.trend.label || '';
});

// Animate score
function animateScore() {
  if (!props.animate) {
    animatedScore.value = props.score;
    return;
  }

  const duration = 1500;
  const start = animatedScore.value;
  const end = props.score;
  const startTime = Date.now();

  const animate = () => {
    const now = Date.now();
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);

    // Easing function (ease-out)
    const eased = 1 - Math.pow(1 - progress, 3);
    animatedScore.value = Math.round(start + (end - start) * eased);

    if (progress < 1) {
      requestAnimationFrame(animate);
    }
  };

  animate();
}

watch(() => props.score, () => {
  animateScore();
});

onMounted(() => {
  animateScore();
});
</script>
