<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-cyan-50 to-teal-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
            <FunnelIcon class="w-6 h-6 text-cyan-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Sotuv Funneli Tahlili</h3>
            <p class="text-sm text-gray-500">Mijozlar sayohati va konversiya</p>
          </div>
        </div>
        <div v-if="normalizedFunnel.overallConversion" class="text-right">
          <p class="text-sm text-gray-500">Umumiy konversiya</p>
          <p
            class="text-2xl font-bold"
            :class="normalizedFunnel.overallConversion >= 5 ? 'text-green-600' : normalizedFunnel.overallConversion >= 2 ? 'text-yellow-600' : 'text-red-600'"
          >
            {{ normalizedFunnel.overallConversion }}%
          </p>
        </div>
      </div>
    </div>

    <!-- Funnel Visualization -->
    <div class="p-6">
      <div class="relative space-y-3">
        <div
          v-for="(stage, i) in normalizedFunnel.stages"
          :key="i"
          class="relative group"
        >
          <div
            class="rounded-xl flex items-center justify-between px-5 py-4 transition-all hover:shadow-md"
            :class="getStageClass(stage.health)"
            :style="{ width: `${Math.max(100 - i * 12, 40)}%`, marginLeft: `${i * 6}%` }"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
                :class="getStageNumberClass(stage.health)"
              >
                {{ i + 1 }}
              </div>
              <div>
                <span class="font-medium text-gray-900">{{ stage.name }}</span>
                <p v-if="stage.count" class="text-xs text-gray-500">{{ stage.count.toLocaleString() }} kishi</p>
              </div>
            </div>
            <div class="text-right">
              <span
                class="text-lg font-bold"
                :class="getStageTextClass(stage.health)"
              >
                {{ stage.conversionRate }}%
              </span>
              <p v-if="stage.dropRate" class="text-xs text-red-500">-{{ stage.dropRate }}% tushum</p>
            </div>
          </div>

          <!-- Problem/Solution tooltip -->
          <div
            v-if="stage.problem"
            class="absolute left-full top-1/2 -translate-y-1/2 ml-4 opacity-0 group-hover:opacity-100 transition-opacity z-10 w-64"
          >
            <div class="bg-white rounded-lg shadow-lg border p-3">
              <p class="text-xs text-red-600 font-medium mb-1">Muammo:</p>
              <p class="text-sm text-gray-700 mb-2">{{ stage.problem }}</p>
              <p v-if="stage.solution" class="text-xs text-green-600 font-medium mb-1">Yechim:</p>
              <p v-if="stage.solution" class="text-sm text-gray-700">{{ stage.solution }}</p>
            </div>
          </div>
        </div>

        <!-- Connector Line -->
        <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200 -z-10" style="margin-left: 6%;"></div>
      </div>

      <!-- Biggest Leak -->
      <div v-if="normalizedFunnel.biggestLeak" class="mt-6 bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-200">
        <h4 class="font-medium text-red-900 mb-3 flex items-center gap-2">
          <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
          Eng katta yo'qotish joyi
        </h4>
        <div class="flex items-center justify-between">
          <div>
            <p class="text-lg font-semibold text-red-700">{{ normalizedFunnel.biggestLeak.stage }}</p>
            <p class="text-sm text-red-600">{{ normalizedFunnel.biggestLeak.loss_percent }}% mijozlar yo'qotilmoqda</p>
          </div>
          <div v-if="normalizedFunnel.biggestLeak.estimated_loss" class="text-right">
            <p class="text-sm text-gray-500">Taxminiy yo'qotish</p>
            <p class="text-xl font-bold text-red-600">{{ formatCurrency(normalizedFunnel.biggestLeak.estimated_loss) }}</p>
          </div>
        </div>
      </div>

      <!-- Bottlenecks -->
      <div v-if="normalizedFunnel.bottlenecks.length" class="mt-6 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-5 border border-yellow-200">
        <h4 class="font-medium text-yellow-900 mb-3 flex items-center gap-2">
          <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600" />
          Muammoli joylar
        </h4>
        <ul class="space-y-2">
          <li v-for="(bn, i) in normalizedFunnel.bottlenecks" :key="i" class="flex items-start gap-3">
            <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center text-xs font-medium text-yellow-800">{{ i + 1 }}</span>
            <span class="text-sm text-yellow-800">{{ bn }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { FunnelIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  funnel: {
    type: Object,
    required: true,
  },
});

const normalizedFunnel = computed(() => {
  const analysis = props.funnel;
  if (!analysis) return { stages: [], bottlenecks: [], overallConversion: 0, biggestLeak: null };

  const stages = (analysis.stages || []).map(stage => ({
    name: stage.name,
    conversionRate: stage.conversion_rate ?? stage.percent ?? 0,
    health: stage.health || (stage.drop_rate > 60 ? 'bad' : stage.drop_rate > 30 ? 'warning' : 'good'),
    count: stage.count || 0,
    dropRate: stage.drop_rate || 0,
    problem: stage.problem || '',
    solution: stage.solution || '',
  }));

  let bottlenecks = analysis.bottlenecks || [];
  if (!bottlenecks.length && stages.some(s => s.problem)) {
    bottlenecks = stages.filter(s => s.problem).map(s => `${s.name}: ${s.problem}`);
  }

  return {
    stages,
    bottlenecks,
    overallConversion: analysis.overall_conversion || 0,
    biggestLeak: analysis.biggest_leak || null,
  };
});

function getStageClass(health) {
  const classes = {
    good: 'bg-gradient-to-r from-green-50 to-green-100 border border-green-200',
    warning: 'bg-gradient-to-r from-yellow-50 to-yellow-100 border border-yellow-200',
    bad: 'bg-gradient-to-r from-red-50 to-red-100 border border-red-200',
  };
  return classes[health] || classes.warning;
}

function getStageNumberClass(health) {
  const classes = { good: 'bg-green-500', warning: 'bg-yellow-500', bad: 'bg-red-500' };
  return classes[health] || 'bg-gray-500';
}

function getStageTextClass(health) {
  const classes = { good: 'text-green-600', warning: 'text-yellow-600', bad: 'text-red-600' };
  return classes[health] || 'text-gray-600';
}

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}
</script>
