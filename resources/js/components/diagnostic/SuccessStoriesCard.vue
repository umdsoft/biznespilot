<template>
  <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
          <TrophyIcon class="w-6 h-6 text-purple-600" />
        </div>
        <div>
          <h3 class="font-semibold text-gray-900">O'xshash bizneslar muvaffaqiyati</h3>
          <p class="text-sm text-gray-500">Sizning sohangizda</p>
        </div>
      </div>
    </div>

    <!-- Stories -->
    <div class="divide-y divide-gray-100">
      <div
        v-for="(story, index) in stories"
        :key="index"
        class="p-4 hover:bg-gray-50 transition-colors"
      >
        <div class="flex items-start gap-4">
          <!-- Avatar -->
          <div class="flex-shrink-0 w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold">
            {{ story.display_name?.charAt(0) || '?' }}
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h4 class="font-medium text-gray-900">{{ story.display_name || 'Anonim biznes' }}</h4>
              <span v-if="story.is_verified" class="text-green-500">
                <CheckBadgeIcon class="w-4 h-4" />
              </span>
            </div>
            <p class="text-sm text-gray-500 mb-2">{{ story.industry }}</p>

            <!-- Results -->
            <div class="flex items-center gap-4 mb-2">
              <div class="flex items-center gap-1">
                <span class="text-sm text-gray-500">Ball:</span>
                <span class="text-sm font-medium text-red-500">{{ story.initial_score }}</span>
                <ArrowRightIcon class="w-3 h-3 text-gray-400" />
                <span class="text-sm font-medium text-green-600">{{ story.final_score }}</span>
              </div>
              <span class="text-sm font-medium text-green-600">
                +{{ story.growth_percent }}% o'sish
              </span>
            </div>

            <!-- Actions taken -->
            <div v-if="story.key_actions?.length" class="flex flex-wrap gap-1">
              <span
                v-for="(action, i) in story.key_actions.slice(0, 3)"
                :key="i"
                class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full"
              >
                {{ action }}
              </span>
            </div>
          </div>

          <!-- Duration -->
          <div class="flex-shrink-0 text-right">
            <p class="text-sm text-gray-500">{{ story.duration_months }} oy</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Proven tactics -->
    <div v-if="provenTactics?.length" class="p-4 bg-purple-50/50 border-t border-gray-100">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Isbot qilingan taktikalar:</h4>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="(tactic, index) in provenTactics"
          :key="index"
          class="px-3 py-1.5 bg-white border border-purple-200 text-purple-700 text-sm rounded-lg"
        >
          {{ tactic }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { TrophyIcon, CheckBadgeIcon, ArrowRightIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  similarBusinesses: {
    type: Object,
    default: () => ({}),
  },
});

// Normalize success stories to handle both old and new field names
const stories = computed(() => {
  const rawStories = props.similarBusinesses?.success_stories || [];
  return rawStories.map(story => ({
    display_name: story.display_name || story.name || 'Anonim biznes',
    industry: story.industry || 'Noma\'lum',
    initial_score: story.initial_score || story.before_score || 0,
    final_score: story.final_score || story.after_score || 0,
    growth_percent: story.growth_percent || 0,
    duration_months: story.duration_months || 0,
    key_actions: story.key_actions || story.actions || [],
    is_verified: story.is_verified || false,
  }));
});

// Handle both string array and object array formats for proven_tactics
const provenTactics = computed(() => {
  const tactics = props.similarBusinesses?.proven_tactics || [];
  return tactics.map(tactic => {
    if (typeof tactic === 'string') {
      return tactic;
    }
    if (typeof tactic === 'object' && tactic !== null) {
      // Format: { tactic: "...", success_rate: 85, avg_impact: "..." }
      let text = tactic.tactic || '';
      if (tactic.avg_impact) {
        text += ` (${tactic.avg_impact})`;
      }
      return text;
    }
    return String(tactic);
  });
});
</script>
