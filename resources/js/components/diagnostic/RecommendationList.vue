<template>
  <div class="bg-white rounded-lg border">
    <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
      <div>
        <h3 class="font-semibold text-gray-900">Tavsiyalar</h3>
        <p class="text-sm text-gray-500 mt-1">Biznesingizni yaxshilash uchun prioritetlangan qadam lar</p>
      </div>
      <span class="text-sm text-gray-500">{{ recommendations.length }} ta tavsiya</span>
    </div>

    <div class="divide-y">
      <div
        v-for="(rec, index) in recommendations"
        :key="rec.id || index"
        class="p-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex items-start space-x-3">
          <!-- Priority indicator -->
          <div
            class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
            :class="priorityBgClass(rec.priority)"
          >
            <span class="text-sm font-bold" :class="priorityTextClass(rec.priority)">
              {{ index + 1 }}
            </span>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center space-x-2">
              <h4 class="font-medium text-gray-900">{{ rec.title }}</h4>
              <span
                class="px-2 py-0.5 text-xs rounded-full"
                :class="priorityBadgeClass(rec.priority)"
              >
                {{ priorityLabel(rec.priority) }}
              </span>
              <span
                class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600"
              >
                {{ categoryLabel(rec.category) }}
              </span>
            </div>

            <p class="text-sm text-gray-600 mt-1">{{ rec.description }}</p>

            <!-- Actions -->
            <div v-if="rec.actions?.length" class="mt-3">
              <button
                @click="toggleActions(rec.id || index)"
                class="text-sm text-indigo-600 hover:text-indigo-700 flex items-center"
              >
                <ChevronDownIcon
                  class="w-4 h-4 mr-1 transition-transform"
                  :class="{ 'rotate-180': expandedActions.includes(rec.id || index) }"
                />
                Amaliy qadamlar ({{ rec.actions.length }})
              </button>

              <ul
                v-if="expandedActions.includes(rec.id || index)"
                class="mt-2 space-y-1 pl-4 border-l-2 border-gray-200"
              >
                <li
                  v-for="(action, actionIndex) in rec.actions"
                  :key="actionIndex"
                  class="text-sm text-gray-600 flex items-start"
                >
                  <span class="w-4 h-4 text-gray-400 mr-2 mt-0.5">{{ actionIndex + 1 }}.</span>
                  {{ action }}
                </li>
              </ul>
            </div>

            <!-- Meta info -->
            <div class="flex items-center space-x-4 mt-3 text-xs text-gray-500">
              <div class="flex items-center">
                <BoltIcon class="w-3.5 h-3.5 mr-1" />
                Ta'sir: {{ impactLabel(rec.impact) }}
              </div>
              <div class="flex items-center">
                <ClockIcon class="w-3.5 h-3.5 mr-1" />
                {{ rec.timeframe || 'Belgilanmagan' }}
              </div>
              <div class="flex items-center">
                <WrenchIcon class="w-3.5 h-3.5 mr-1" />
                Murakkablik: {{ effortLabel(rec.effort) }}
              </div>
            </div>

            <!-- Expected result -->
            <div
              v-if="rec.expected_result"
              class="mt-2 p-2 bg-green-50 rounded text-sm text-green-700"
            >
              <span class="font-medium">Kutilayotgan natija:</span> {{ rec.expected_result }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="!recommendations.length" class="p-8 text-center text-gray-500">
        Tavsiyalar mavjud emas
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import {
  ChevronDownIcon,
  BoltIcon,
  ClockIcon,
  WrenchIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  recommendations: {
    type: Array,
    default: () => [],
  },
});

const expandedActions = ref([]);

function toggleActions(id) {
  const index = expandedActions.value.indexOf(id);
  if (index === -1) {
    expandedActions.value.push(id);
  } else {
    expandedActions.value.splice(index, 1);
  }
}

function priorityBgClass(priority) {
  const classes = {
    critical: 'bg-red-100',
    high: 'bg-orange-100',
    medium: 'bg-yellow-100',
    low: 'bg-gray-100',
  };
  return classes[priority] || classes.medium;
}

function priorityTextClass(priority) {
  const classes = {
    critical: 'text-red-600',
    high: 'text-orange-600',
    medium: 'text-yellow-600',
    low: 'text-gray-600',
  };
  return classes[priority] || classes.medium;
}

function priorityBadgeClass(priority) {
  const classes = {
    critical: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-gray-100 text-gray-700',
  };
  return classes[priority] || classes.medium;
}

function priorityLabel(priority) {
  const labels = {
    critical: 'Kritik',
    high: 'Yuqori',
    medium: "O'rta",
    low: 'Past',
  };
  return labels[priority] || priority;
}

function categoryLabel(category) {
  const labels = {
    marketing: 'Marketing',
    sales: 'Sotuvlar',
    content: 'Kontent',
    funnel: 'Funnel',
    analytics: 'Analitika',
    infrastructure: 'Infrastruktura',
    operations: 'Operatsiyalar',
  };
  return labels[category] || category;
}

function impactLabel(impact) {
  const labels = {
    high: 'Yuqori',
    medium: "O'rta",
    low: 'Past',
  };
  return labels[impact] || impact;
}

function effortLabel(effort) {
  const labels = {
    high: 'Yuqori',
    medium: "O'rta",
    low: 'Past',
  };
  return labels[effort] || effort;
}
</script>
