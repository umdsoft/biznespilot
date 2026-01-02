<template>
  <svg :width="width" :height="height" class="sparkline">
    <polyline
      :points="points"
      :stroke="strokeColor"
      stroke-width="2"
      fill="none"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <circle
      v-if="points"
      :cx="lastPoint.x"
      :cy="lastPoint.y"
      r="3"
      :fill="strokeColor"
    />
  </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  data: {
    type: Array,
    required: true,
  },
  color: {
    type: String,
    default: 'green',
  },
  width: {
    type: Number,
    default: 80,
  },
  height: {
    type: Number,
    default: 40,
  },
});

const strokeColor = computed(() => {
  const colorMap = {
    green: '#10b981',
    yellow: '#f59e0b',
    red: '#ef4444',
  };
  return colorMap[props.color] || '#6b7280';
});

const points = computed(() => {
  if (!props.data || props.data.length === 0) return '';

  const values = props.data;
  const max = Math.max(...values);
  const min = Math.min(...values);
  const range = max - min || 1;

  const padding = 5;
  const chartWidth = props.width - 2 * padding;
  const chartHeight = props.height - 2 * padding;

  return values
    .map((value, index) => {
      const x = padding + (index / (values.length - 1 || 1)) * chartWidth;
      const y = padding + chartHeight - ((value - min) / range) * chartHeight;
      return `${x},${y}`;
    })
    .join(' ');
});

const lastPoint = computed(() => {
  if (!props.data || props.data.length === 0) return { x: 0, y: 0 };

  const pointsArray = points.value.split(' ');
  const lastPointStr = pointsArray[pointsArray.length - 1];
  const [x, y] = lastPointStr.split(',').map(Number);

  return { x, y };
});
</script>

<style scoped>
.sparkline {
  display: block;
}
</style>
