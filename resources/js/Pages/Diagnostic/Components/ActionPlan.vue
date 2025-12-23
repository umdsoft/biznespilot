<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
            <ClipboardDocumentListIcon class="w-6 h-6 text-indigo-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">90 Kunlik Harakat Rejasi</h3>
            <p class="text-sm text-gray-500">Qadam-baqadam o'sish yo'li</p>
          </div>
        </div>
        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-sm text-gray-500">Jami vaqt</p>
            <p class="text-xl font-bold text-indigo-600">
              {{ actionPlan.total_time_hours || 0 }} soat
            </p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-500">Tejash</p>
            <p class="text-xl font-bold text-green-600">
              {{ formatCurrency(actionPlan.total_potential_savings) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Filter -->
    <div class="p-4 border-b border-gray-100 bg-gray-50">
      <div class="flex gap-2">
        <button
          v-for="filter in timelineFilters"
          :key="filter.value"
          @click="activeFilter = filter.value"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
          :class="activeFilter === filter.value
            ? 'bg-indigo-600 text-white'
            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'"
        >
          {{ filter.label }}
          <span
            v-if="getFilterCount(filter.value) > 0"
            class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full"
            :class="activeFilter === filter.value ? 'bg-white/20' : 'bg-gray-100'"
          >
            {{ getFilterCount(filter.value) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Action Steps -->
    <div class="p-6">
      <div class="space-y-4">
        <div
          v-for="step in filteredSteps"
          :key="step.order"
          class="relative"
        >
          <!-- Step Card -->
          <div
            class="bg-gray-50 rounded-xl p-4 md:p-5 hover:bg-gray-100 transition-all border border-gray-100 hover:border-indigo-200 hover:shadow-md"
            :class="{ 'ring-2 ring-indigo-500 ring-offset-2': step.timeline === 'today' }"
          >
            <div class="flex items-start gap-4">
              <!-- Order Number -->
              <div
                class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white"
                :class="getTimelineClass(step.timeline)"
              >
                {{ step.order }}
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap mb-2">
                  <!-- Timeline Badge -->
                  <span
                    class="px-2 py-0.5 text-xs rounded-full font-medium"
                    :class="getTimelineBadgeClass(step.timeline)"
                  >
                    {{ getTimelineLabel(step.timeline) }}
                  </span>
                  <!-- Impact Stars -->
                  <div class="flex items-center gap-0.5">
                    <StarIcon
                      v-for="i in 5"
                      :key="i"
                      class="w-3.5 h-3.5"
                      :class="i <= step.impact_stars ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                    />
                  </div>
                  <!-- Time -->
                  <span class="text-xs text-gray-500 flex items-center gap-1">
                    <ClockIcon class="w-3.5 h-3.5" />
                    {{ step.time_minutes }} daqiqa
                  </span>
                </div>

                <h4 class="font-semibold text-gray-900 mb-1">{{ step.title }}</h4>
                <p class="text-sm text-gray-600 mb-3">{{ step.why }}</p>

                <!-- Module Info -->
                <div class="flex items-center gap-2 text-sm">
                  <CubeIcon class="w-4 h-4 text-indigo-500" />
                  <span class="text-indigo-600 font-medium">{{ step.module_name }}</span>
                </div>

                <!-- Similar Business Result -->
                <div
                  v-if="step.similar_business_result"
                  class="mt-3 p-3 bg-green-50 rounded-lg border border-green-100"
                >
                  <div class="flex items-center gap-2 text-sm text-green-700">
                    <CheckCircleIcon class="w-4 h-4 text-green-500" />
                    <span>{{ step.similar_business_result }}</span>
                  </div>
                </div>
              </div>

              <!-- Action Button -->
              <div class="flex-shrink-0">
                <button
                  @click="$emit('start-action', step)"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium flex items-center gap-2 transition-colors"
                >
                  <PlayIcon class="w-4 h-4" />
                  Boshlash
                </button>
              </div>
            </div>
          </div>

          <!-- Connector Line -->
          <div
            v-if="step.order < filteredSteps.length"
            class="absolute left-5 top-full h-4 w-0.5 bg-gray-200"
          ></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!filteredSteps.length" class="text-center py-12">
        <ClipboardDocumentListIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500">Bu davrda harakat rejalari yo'q</p>
      </div>
    </div>

    <!-- Footer Progress -->
    <div class="p-4 bg-gray-50 border-t border-gray-100">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm text-gray-600">Jami qadamlar</span>
        <span class="text-sm font-medium text-gray-900">
          0 / {{ actionPlan.steps?.length || 0 }} bajarildi
        </span>
      </div>
      <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full bg-indigo-500 rounded-full" style="width: 0%"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeIcon,
  StarIcon,
  CheckCircleIcon,
  PlayIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  actionPlan: {
    type: Object,
    required: true,
    default: () => ({
      total_time_hours: 0,
      total_potential_savings: 0,
      steps: [],
    }),
  },
});

defineEmits(['start-action']);

const activeFilter = ref('all');

const timelineFilters = [
  { value: 'all', label: 'Hammasi' },
  { value: 'today', label: 'Bugun' },
  { value: 'this_week', label: 'Shu hafta' },
  { value: 'next_week', label: 'Keyingi hafta' },
];

const filteredSteps = computed(() => {
  if (!props.actionPlan.steps) return [];
  if (activeFilter.value === 'all') return props.actionPlan.steps;
  return props.actionPlan.steps.filter(step => step.timeline === activeFilter.value);
});

function getFilterCount(filter) {
  if (!props.actionPlan.steps) return 0;
  if (filter === 'all') return props.actionPlan.steps.length;
  return props.actionPlan.steps.filter(step => step.timeline === filter).length;
}

function getTimelineClass(timeline) {
  const classes = {
    today: 'bg-red-500',
    this_week: 'bg-orange-500',
    next_week: 'bg-blue-500',
  };
  return classes[timeline] || 'bg-gray-500';
}

function getTimelineBadgeClass(timeline) {
  const classes = {
    today: 'bg-red-100 text-red-700',
    this_week: 'bg-orange-100 text-orange-700',
    next_week: 'bg-blue-100 text-blue-700',
  };
  return classes[timeline] || 'bg-gray-100 text-gray-700';
}

function getTimelineLabel(timeline) {
  const labels = {
    today: 'Bugun',
    this_week: 'Shu hafta',
    next_week: 'Keyingi hafta',
  };
  return labels[timeline] || timeline;
}

function formatCurrency(amount) {
  if (!amount) return '0 UZS';
  return new Intl.NumberFormat('uz-UZ').format(amount) + ' UZS';
}
</script>
