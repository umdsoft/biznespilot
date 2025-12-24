<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-blue-50 to-cyan-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
            <MegaphoneIcon class="w-6 h-6 text-blue-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Marketing Kanallari Tahlili</h3>
            <p class="text-sm text-gray-500">Faol kanallar va samaradorligi</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Yuqori</span>
          <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">O'rta</span>
          <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full">Past</span>
        </div>
      </div>
    </div>

    <!-- Channels Grid -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="(channel, i) in normalizedChannels.channels"
          :key="i"
          class="bg-white rounded-xl border p-4 hover:shadow-md transition-shadow"
          :class="channel.connected ? 'border-gray-200' : 'border-red-200 bg-red-50/30'"
        >
          <!-- Channel Header -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-lg flex items-center justify-center"
                :class="getChannelBgClass(channel.name)"
              >
                <span class="text-white text-lg font-bold">{{ channel.name.charAt(0) }}</span>
              </div>
              <div>
                <h4 class="font-medium text-gray-900">{{ channel.name }}</h4>
                <span
                  class="text-xs"
                  :class="channel.connected ? 'text-green-600' : 'text-red-600'"
                >
                  {{ channel.connected ? 'Ulangan' : 'Ulanmagan' }}
                </span>
              </div>
            </div>
            <span
              class="px-2 py-1 text-xs rounded-full font-medium"
              :class="getEffectivenessClass(channel.effectiveness)"
            >
              {{ getEffectivenessLabel(channel.effectiveness) }}
            </span>
          </div>

          <!-- Stats -->
          <div v-if="channel.followers || channel.engagement_rate" class="flex items-center gap-4 mb-3 text-sm">
            <div v-if="channel.followers" class="text-gray-600">
              <span class="font-medium text-gray-900">{{ formatNumber(channel.followers) }}</span> obunachilar
            </div>
            <div v-if="channel.engagement_rate" class="text-gray-600">
              <span class="font-medium text-gray-900">{{ channel.engagement_rate }}%</span> engagement
            </div>
          </div>

          <!-- Score bar -->
          <div v-if="channel.score !== undefined" class="mb-3">
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="text-gray-500">Samaradorlik</span>
              <span class="font-medium" :class="getScoreClass(channel.score)">{{ channel.score }}/100</span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="getScoreBarClass(channel.score)"
                :style="{ width: `${channel.score}%` }"
              ></div>
            </div>
          </div>

          <!-- Problems -->
          <div v-if="channel.problems?.length" class="mb-3">
            <p class="text-xs text-red-600 font-medium mb-1">Muammolar:</p>
            <ul class="text-xs text-red-600 space-y-0.5">
              <li v-for="(problem, j) in channel.problems" :key="j">• {{ problem }}</li>
            </ul>
          </div>

          <!-- Recommendation -->
          <div v-if="channel.recommendation" class="pt-3 border-t border-gray-100">
            <p class="text-sm text-gray-600">
              <span class="font-medium text-indigo-600">Tavsiya:</span> {{ channel.recommendation }}
            </p>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!normalizedChannels.channels.length" class="text-center py-8 bg-gray-50 rounded-xl">
        <MegaphoneIcon class="w-12 h-12 text-gray-400 mx-auto mb-3" />
        <p class="text-gray-600">Hech qanday kanal ulanmagan</p>
      </div>

      <!-- Recommended Channels -->
      <div v-if="normalizedChannels.recommendedChannels.length" class="mt-6 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-5 border border-indigo-100">
        <h4 class="font-medium text-indigo-900 mb-3 flex items-center gap-2">
          <SparklesIcon class="w-5 h-5 text-indigo-600" />
          Tavsiya etilgan yangi kanallar
        </h4>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="(ch, i) in normalizedChannels.recommendedChannels"
            :key="i"
            class="px-4 py-2 bg-white border border-indigo-200 text-indigo-700 text-sm rounded-lg"
          >
            {{ ch }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { MegaphoneIcon, SparklesIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  channels: {
    type: Object,
    required: true,
  },
});

const normalizedChannels = computed(() => {
  const analysis = props.channels;
  if (!analysis) return { channels: [], recommendedChannels: [] };

  // New format
  if (Array.isArray(analysis.channels)) {
    return {
      channels: analysis.channels.map(ch => ({
        name: ch.name,
        effectiveness: ch.effectiveness || 'low',
        recommendation: ch.recommendation || '',
        connected: ch.connected !== false,
        score: ch.score || 0,
        followers: ch.followers,
        engagement_rate: ch.engagement_rate,
        problems: ch.problems,
      })),
      recommendedChannels: analysis.recommended_channels || [],
    };
  }

  // Old format
  const channels = [];
  const channelNames = {
    instagram: 'Instagram',
    telegram: 'Telegram',
    whatsapp: 'WhatsApp',
    facebook: 'Facebook',
    tiktok: 'TikTok',
  };

  for (const [key, value] of Object.entries(analysis)) {
    if (typeof value === 'object' && value !== null && key !== 'recommended_channels') {
      const effectiveness = value.connected ? (value.score >= 70 ? 'high' : value.score >= 40 ? 'medium' : 'low') : 'low';
      channels.push({
        name: channelNames[key] || key,
        effectiveness,
        recommendation: value.recommendation || (value.recommendations ? value.recommendations.join('. ') : ''),
        connected: value.connected || false,
        score: value.score || 0,
        followers: value.followers || 0,
        engagement_rate: value.engagement_rate || 0,
        problems: value.problems || [],
      });
    }
  }

  return {
    channels,
    recommendedChannels: analysis.recommended_channels || [],
  };
});

function getChannelBgClass(name) {
  const classes = {
    Instagram: 'bg-gradient-to-br from-purple-500 to-pink-500',
    Telegram: 'bg-blue-500',
    WhatsApp: 'bg-green-500',
    Facebook: 'bg-blue-600',
    TikTok: 'bg-black',
  };
  return classes[name] || 'bg-gray-500';
}

function getEffectivenessClass(effectiveness) {
  const classes = {
    high: 'bg-green-100 text-green-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-red-100 text-red-700',
  };
  return classes[effectiveness] || 'bg-gray-100 text-gray-700';
}

function getEffectivenessLabel(effectiveness) {
  const labels = { high: 'Yuqori', medium: "O'rta", low: 'Past' };
  return labels[effectiveness] || effectiveness;
}

function getScoreClass(score) {
  if (score >= 70) return 'text-green-600';
  if (score >= 40) return 'text-yellow-600';
  return 'text-red-600';
}

function getScoreBarClass(score) {
  if (score >= 70) return 'bg-green-500';
  if (score >= 40) return 'bg-yellow-500';
  return 'bg-red-500';
}

function formatNumber(num) {
  if (!num) return '0';
  return new Intl.NumberFormat('uz-UZ').format(num);
}
</script>
