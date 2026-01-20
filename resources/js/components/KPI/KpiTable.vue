<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <!-- Table Header -->
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
      <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
    </div>

    <!-- Table Content -->
    <div class="overflow-x-auto">
      <div v-for="section in sections" :key="section.name" class="border-b border-gray-200 last:border-b-0">
        <!-- Section Header -->
        <div class="bg-gray-100 px-6 py-3 border-b border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
            {{ section.name }}
          </h3>
        </div>

        <!-- Section Rows -->
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('kpi.kpi') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('kpi.current') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('kpi.target') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('kpi.status') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('kpi.trend') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="row in section.rows"
              :key="row.kpi_code"
              class="hover:bg-gray-50 transition-colors"
            >
              <!-- KPI Name -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <span class="text-xl">{{ row.icon }}</span>
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ row.name }}</div>
                    <div v-if="row.description" class="text-xs text-gray-500">{{ row.description }}</div>
                  </div>
                </div>
              </td>

              <!-- Current Value -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-gray-900">{{ row.current_value }}</div>
              </td>

              <!-- Target Value -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-600">{{ row.target_value }}</div>
              </td>

              <!-- Performance Status -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex flex-col gap-2">
                  <span
                    :class="getStatusBadgeClass(row.performance_color)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium w-fit"
                  >
                    {{ row.performance_status }} ({{ row.performance_percent }}%)
                  </span>
                  <div class="w-32 bg-gray-200 rounded-full h-1.5">
                    <div
                      :class="getProgressBarClass(row.performance_color)"
                      :style="{ width: `${Math.min(row.performance_percent, 100)}%` }"
                      class="h-1.5 rounded-full transition-all duration-300"
                    ></div>
                  </div>
                </div>
              </td>

              <!-- Trend -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <Sparkline
                    v-if="row.trend_data && row.trend_data.length > 0"
                    :data="row.trend_data"
                    :color="row.performance_color"
                    class="w-20 h-10"
                  />
                  <span v-else class="text-xs text-gray-400">N/A</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import Sparkline from './Sparkline.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: null,
  },
  sections: {
    type: Array,
    required: true,
  },
});

const getStatusBadgeClass = (color) => {
  const colorMap = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-800',
  };
  return colorMap[color] || 'bg-gray-100 text-gray-800';
};

const getProgressBarClass = (color) => {
  const colorMap = {
    green: 'bg-green-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
  };
  return colorMap[color] || 'bg-gray-500';
};
</script>
