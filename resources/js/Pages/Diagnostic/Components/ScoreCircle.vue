<template>
  <div class="relative" :style="{ width: `${size}px`, height: `${size}px` }">
    <!-- Background circle -->
    <svg class="w-full h-full transform -rotate-90">
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        stroke-width="8"
        :stroke="trackColor"
        fill="none"
      />
      <!-- Progress circle -->
      <circle
        :cx="size / 2"
        :cy="size / 2"
        :r="radius"
        stroke-width="8"
        :stroke="progressColor"
        fill="none"
        stroke-linecap="round"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="strokeDashoffset"
        class="transition-all duration-1000 ease-out"
      />
    </svg>

    <!-- Center content -->
    <div class="absolute inset-0 flex flex-col items-center justify-center">
      <span class="text-3xl font-bold" :class="textColorClass">{{ animatedScore }}</span>
      <span class="text-sm text-gray-500">{{ label }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';

const props = defineProps({
  score: {
    type: Number,
    default: 0,
  },
  size: {
    type: Number,
    default: 120,
  },
  label: {
    type: String,
    default: 'ball',
  },
  animate: {
    type: Boolean,
    default: true,
  },
});

const animatedScore = ref(0);

const radius = computed(() => (props.size - 16) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const strokeDashoffset = computed(() => {
  const progress = animatedScore.value / 100;
  return circumference.value * (1 - progress);
});

const progressColor = computed(() => {
  if (animatedScore.value >= 80) return '#3B82F6'; // blue
  if (animatedScore.value >= 60) return '#22C55E'; // green
  if (animatedScore.value >= 40) return '#F59E0B'; // yellow
  return '#EF4444'; // red
});

const trackColor = computed(() => '#E5E7EB');

const textColorClass = computed(() => {
  if (animatedScore.value >= 80) return 'text-blue-600';
  if (animatedScore.value >= 60) return 'text-green-600';
  if (animatedScore.value >= 40) return 'text-yellow-600';
  return 'text-red-600';
});

function animateScore() {
  if (!props.animate) {
    animatedScore.value = props.score;
    return;
  }

  const duration = 1000;
  const start = animatedScore.value;
  const end = props.score;
  const startTime = Date.now();

  function update() {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);

    // Easing function
    const eased = 1 - Math.pow(1 - progress, 3);
    animatedScore.value = Math.round(start + (end - start) * eased);

    if (progress < 1) {
      requestAnimationFrame(update);
    }
  }

  requestAnimationFrame(update);
}

onMounted(() => {
  animateScore();
});

watch(() => props.score, () => {
  animateScore();
});
</script>
