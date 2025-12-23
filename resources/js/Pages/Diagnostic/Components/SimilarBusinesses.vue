<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
            <BuildingStorefrontIcon class="w-6 h-6 text-emerald-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">O'xshash Bizneslar</h3>
            <p class="text-sm text-gray-500">Sohangizdagi muvaffaqiyat tarixi</p>
          </div>
        </div>
        <div v-if="similarBusinesses.total_count" class="text-right">
          <p class="text-sm text-gray-500">Sohadagi bizneslar</p>
          <p class="text-xl font-bold text-emerald-600">{{ similarBusinesses.total_count }}+</p>
        </div>
      </div>
    </div>

    <!-- Success Stories -->
    <div v-if="similarBusinesses.success_stories?.length" class="p-6 border-b border-gray-100">
      <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
        <TrophyIcon class="w-5 h-5 text-yellow-500" />
        Muvaffaqiyat tarixi
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="(story, i) in similarBusinesses.success_stories"
          :key="i"
          class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-5 border border-green-200"
        >
          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <BuildingStorefrontIcon class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <h5 class="font-medium text-gray-900">{{ story.display_name }}</h5>
                <p class="text-xs text-gray-500">{{ story.industry }}</p>
              </div>
            </div>
            <div v-if="story.is_verified" class="flex items-center gap-1 text-xs text-green-600">
              <CheckBadgeIcon class="w-4 h-4" />
              Tasdiqlangan
            </div>
          </div>

          <!-- Score Progress -->
          <div class="mb-4">
            <div class="flex items-center justify-between text-sm mb-2">
              <span class="text-gray-500">Ball o'sishi</span>
              <span class="font-medium text-green-600">+{{ story.growth_percent }}%</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="flex-1">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="text-red-600">{{ story.initial_score }}</span>
                  <span class="text-green-600">{{ story.final_score }}</span>
                </div>
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-gradient-to-r from-red-400 via-yellow-400 to-green-500 rounded-full"
                    :style="{ width: '100%' }"
                  ></div>
                </div>
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ story.duration_months }} oy ichida</p>
          </div>

          <!-- Key Actions -->
          <div v-if="story.key_actions?.length">
            <p class="text-xs text-gray-500 mb-2">Asosiy harakatlar:</p>
            <ul class="space-y-1">
              <li
                v-for="(action, j) in story.key_actions.slice(0, 3)"
                :key="j"
                class="text-sm text-gray-700 flex items-start gap-2"
              >
                <CheckIcon class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                {{ action }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Proven Tactics -->
    <div v-if="similarBusinesses.proven_tactics?.length" class="p-6">
      <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
        <LightBulbIcon class="w-5 h-5 text-yellow-500" />
        Isbotlangan taktikalar
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div
          v-for="(tactic, i) in similarBusinesses.proven_tactics"
          :key="i"
          class="flex items-start gap-3 bg-yellow-50 rounded-xl p-4 border border-yellow-200"
        >
          <span class="flex-shrink-0 w-6 h-6 bg-yellow-200 rounded-full flex items-center justify-center text-xs font-medium text-yellow-800">
            {{ i + 1 }}
          </span>
          <span class="text-sm text-yellow-800">{{ tactic }}</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!similarBusinesses.success_stories?.length && !similarBusinesses.proven_tactics?.length" class="p-12 text-center">
      <BuildingStorefrontIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
      <p class="text-gray-500">O'xshash bizneslar ma'lumoti mavjud emas</p>
    </div>
  </div>
</template>

<script setup>
import {
  BuildingStorefrontIcon,
  TrophyIcon,
  CheckBadgeIcon,
  CheckIcon,
  LightBulbIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  similarBusinesses: {
    type: Object,
    required: true,
    default: () => ({
      total_count: 0,
      success_stories: [],
      proven_tactics: [],
    }),
  },
});
</script>
