<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="p-5 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 bg-purple-500 rounded-xl flex items-center justify-center">
          <ArrowsRightLeftIcon class="w-6 h-6 text-white" />
        </div>
        <div>
          <h3 class="font-bold text-gray-900">Sabab - Natija</h3>
          <p class="text-sm text-gray-500">"X qilsam Y natija olaman"</p>
        </div>
      </div>
    </div>

    <!-- Matrix Items -->
    <div class="divide-y divide-gray-100">
      <div
        v-for="item in data"
        :key="item.id"
        class="p-4"
      >
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
          <!-- Problem -->
          <div class="lg:w-1/4">
            <div class="flex items-center gap-2">
              <XCircleIcon class="w-5 h-5 text-red-500 flex-shrink-0" />
              <span class="font-medium text-gray-900">{{ item.problem }}</span>
            </div>
            <p class="text-sm text-red-600 mt-1 ml-7">-{{ formatMoney(item.monthly_loss) }}/oy</p>
          </div>

          <!-- Arrow -->
          <div class="hidden lg:flex items-center justify-center">
            <ArrowRightIcon class="w-6 h-6 text-gray-300" />
          </div>

          <!-- Solution -->
          <div class="lg:w-1/4">
            <div class="flex items-center gap-2">
              <WrenchScrewdriverIcon class="w-5 h-5 text-blue-500 flex-shrink-0" />
              <span class="text-gray-700">{{ item.solution?.action }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-1 ml-7">{{ item.solution?.time }} | {{ item.solution?.difficulty }}</p>
          </div>

          <!-- Arrow -->
          <div class="hidden lg:flex items-center justify-center">
            <ArrowRightIcon class="w-6 h-6 text-gray-300" />
          </div>

          <!-- Result -->
          <div class="lg:w-1/4">
            <div class="flex items-center gap-2">
              <CheckCircleIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
              <span class="font-medium text-green-600">{{ item.expected_result?.improvement }}</span>
            </div>
            <p class="text-sm text-green-600 mt-1 ml-7">+{{ formatMoney(item.expected_result?.monthly_gain) }}/oy</p>
          </div>

          <!-- CTA -->
          <div class="lg:w-auto flex-shrink-0">
            <Link
              :href="item.solution?.module_route"
              class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all"
            >
              <span>{{ item.solution?.module }}</span>
              <ArrowRightIcon class="w-4 h-4" />
            </Link>
          </div>
        </div>

        <!-- Mobile flow visualization -->
        <div class="lg:hidden mt-4 flex items-center justify-center">
          <div class="flex items-center gap-2 text-gray-400 text-sm">
            <span class="text-red-500">Muammo</span>
            <ArrowRightIcon class="w-4 h-4" />
            <span class="text-blue-500">Yechim</span>
            <ArrowRightIcon class="w-4 h-4" />
            <span class="text-green-500">Natija</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Total -->
    <div v-if="data?.length" class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-t">
      <div class="flex items-center justify-between">
        <span class="text-gray-600">Barcha muammolarni hal qilsangiz:</span>
        <span class="text-2xl font-bold text-green-600">+{{ formatMoney(totalGain) }}/oy</span>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!data?.length" class="p-8 text-center">
      <ArrowsRightLeftIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">Sabab-natija tahlili mavjud emas</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  ArrowsRightLeftIcon,
  ArrowRightIcon,
  XCircleIcon,
  WrenchScrewdriverIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
});

const totalGain = computed(() => {
  return props.data?.reduce((sum, item) => sum + (item.expected_result?.monthly_gain || 0), 0) || 0;
});

function formatMoney(amount) {
  if (!amount) return '0';
  if (amount >= 1000000) return (amount / 1000000).toFixed(1) + 'M';
  return new Intl.NumberFormat('uz-UZ').format(amount);
}
</script>
