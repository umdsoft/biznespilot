<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-red-50 to-orange-50 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
          <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">Xavflar va Imkoniyatlar</h3>
          <p class="text-sm text-gray-500">Biznes oldidagi xavflar va imkoniyatlar</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Timeline Risks (old format) -->
      <div v-if="normalizedRisks.timeline.length" class="mb-6">
        <h4 class="font-medium text-gray-900 mb-4">Vaqt bo'yicha xavflar</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="(item, i) in normalizedRisks.timeline"
            :key="i"
            class="bg-gradient-to-br rounded-xl p-5 border"
            :class="getTimelineClass(i)"
          >
            <div class="flex items-center gap-2 mb-3">
              <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="getTimelineIconClass(i)">
                <ClockIcon class="w-5 h-5" :class="getTimelineIconTextClass(i)" />
              </div>
              <span class="font-medium" :class="getTimelineLabelClass(i)">{{ item.period }}</span>
            </div>
            <p class="text-sm text-gray-700 mb-3">{{ item.description }}</p>
            <div v-if="item.estimatedLoss" class="pt-3 border-t" :class="getTimelineBorderClass(i)">
              <p class="text-xs text-gray-500">Taxminiy yo'qotish</p>
              <p class="text-lg font-bold" :class="getTimelineLabelClass(i)">{{ formatCurrency(item.estimatedLoss) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Threats and Opportunities Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Threats -->
        <div v-if="normalizedRisks.threats.length" class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-5 border border-red-200">
          <h4 class="font-medium text-red-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
              <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
            </div>
            Xavflar
          </h4>
          <ul class="space-y-3">
            <li v-for="(threat, i) in normalizedRisks.threats" :key="i" class="flex items-start gap-3">
              <span class="flex-shrink-0 w-6 h-6 bg-red-200 rounded-full flex items-center justify-center text-xs font-medium text-red-800">{{ i + 1 }}</span>
              <span class="text-sm text-red-800">{{ threat }}</span>
            </li>
          </ul>
        </div>

        <!-- Opportunities -->
        <div v-if="normalizedRisks.opportunities.length" class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200">
          <h4 class="font-medium text-green-900 mb-4 flex items-center gap-2">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
              <LightBulbIcon class="w-5 h-5 text-green-600" />
            </div>
            Imkoniyatlar
          </h4>
          <ul class="space-y-3">
            <li v-for="(opp, i) in normalizedRisks.opportunities" :key="i" class="flex items-start gap-3">
              <span class="flex-shrink-0 w-6 h-6 bg-green-200 rounded-full flex items-center justify-center text-xs font-medium text-green-800">{{ i + 1 }}</span>
              <span class="text-sm text-green-800">{{ opp }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!normalizedRisks.threats.length && !normalizedRisks.opportunities.length && !normalizedRisks.timeline.length" class="text-center py-8 bg-gray-50 rounded-xl">
        <ExclamationTriangleIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
        <p class="text-gray-600">Xavflar va imkoniyatlar tahlili mavjud emas</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ExclamationTriangleIcon, LightBulbIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  risks: {
    type: Object,
    required: true,
  },
});

const normalizedRisks = computed(() => {
  const risks = props.risks;
  if (!risks) return { threats: [], opportunities: [], timeline: [] };

  // New format
  if (Array.isArray(risks.threats) || Array.isArray(risks.opportunities)) {
    return {
      threats: risks.threats || [],
      opportunities: risks.opportunities || [],
      timeline: [],
    };
  }

  // Old format
  const timeline = [];
  const threats = [];
  const timeLabels = { '3_months': '3 oy', '6_months': '6 oy', '12_months': '12 oy' };

  for (const [key, value] of Object.entries(risks)) {
    if (typeof value === 'object' && value !== null) {
      timeline.push({
        period: timeLabels[key] || key,
        description: value.description || '',
        estimatedLoss: value.estimated_loss || 0,
      });
      if (value.description) {
        threats.push(`${timeLabels[key] || key} ichida: ${value.description}`);
      }
    }
  }

  return { threats, opportunities: [], timeline };
});

function getTimelineClass(i) {
  const classes = [
    'from-yellow-50 to-orange-50 border-yellow-200',
    'from-orange-50 to-red-50 border-orange-200',
    'from-red-50 to-red-100 border-red-200',
  ];
  return classes[i] || classes[2];
}

function getTimelineIconClass(i) {
  const classes = ['bg-yellow-100', 'bg-orange-100', 'bg-red-100'];
  return classes[i] || 'bg-red-100';
}

function getTimelineIconTextClass(i) {
  const classes = ['text-yellow-600', 'text-orange-600', 'text-red-600'];
  return classes[i] || 'text-red-600';
}

function getTimelineLabelClass(i) {
  const classes = ['text-yellow-700', 'text-orange-700', 'text-red-700'];
  return classes[i] || 'text-red-700';
}

function getTimelineBorderClass(i) {
  const classes = ['border-yellow-200', 'border-orange-200', 'border-red-200'];
  return classes[i] || 'border-red-200';
}

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}
</script>
