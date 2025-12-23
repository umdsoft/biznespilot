<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
    <!-- Header -->
    <div class="p-6 bg-gradient-to-r from-purple-50 to-indigo-50 border-b border-gray-100">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
            <CubeIcon class="w-6 h-6 text-purple-600" />
          </div>
          <div>
            <h3 class="font-semibold text-gray-900">Platforma Tavsiyalari</h3>
            <p class="text-sm text-gray-500">Biznesingiz uchun tavsiya etilgan modullar</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full font-medium">Yuqori</span>
          <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full font-medium">O'rta</span>
          <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">Past</span>
        </div>
      </div>
    </div>

    <!-- Priority Filter -->
    <div class="p-4 border-b border-gray-100 bg-gray-50">
      <div class="flex gap-2">
        <button
          v-for="filter in priorityFilters"
          :key="filter.value"
          @click="activeFilter = filter.value"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
          :class="activeFilter === filter.value
            ? 'bg-purple-600 text-white'
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

    <!-- Recommendations Grid -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="(rec, index) in filteredRecommendations"
          :key="index"
          class="group relative bg-gradient-to-br rounded-xl p-5 border transition-all hover:shadow-lg cursor-pointer"
          :class="getCardClass(rec.priority)"
          @click="$emit('navigate', rec.route)"
        >
          <!-- Priority Badge -->
          <div class="absolute top-4 right-4">
            <span
              class="px-2 py-0.5 text-xs rounded-full font-medium"
              :class="getPriorityBadgeClass(rec.priority)"
            >
              {{ getPriorityLabel(rec.priority) }}
            </span>
          </div>

          <!-- Icon -->
          <div
            class="w-12 h-12 rounded-xl flex items-center justify-center mb-4"
            :class="getIconClass(rec.priority)"
          >
            <component :is="getModuleIcon(rec.module)" class="w-6 h-6" />
          </div>

          <!-- Content -->
          <h4 class="font-semibold text-gray-900 mb-2 pr-16">{{ rec.module }}</h4>
          <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ rec.reason }}</p>

          <!-- Action -->
          <div class="flex items-center text-sm font-medium" :class="getActionClass(rec.priority)">
            <span>Batafsil ko'rish</span>
            <ArrowRightIcon class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!filteredRecommendations.length" class="text-center py-12">
        <CubeIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
        <p class="text-gray-500">Bu ustuvorlikda tavsiyalar yo'q</p>
      </div>
    </div>

    <!-- Video Recommendations -->
    <div v-if="videos?.length" class="p-6 border-t border-gray-100 bg-gray-50">
      <h4 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
        <PlayCircleIcon class="w-5 h-5 text-purple-600" />
        Tavsiya etilgan videolar
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <a
          v-for="(video, index) in videos"
          :key="index"
          :href="video.url"
          target="_blank"
          class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200 hover:border-purple-300 hover:shadow-md transition-all"
        >
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <PlayIcon class="w-5 h-5 text-purple-600" />
          </div>
          <div class="flex-1 min-w-0">
            <h5 class="text-sm font-medium text-gray-900 truncate">{{ video.title }}</h5>
            <p class="text-xs text-gray-500">{{ video.duration }} | {{ video.related_module }}</p>
          </div>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
  CubeIcon,
  ArrowRightIcon,
  PlayCircleIcon,
  PlayIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ChatBubbleLeftRightIcon,
  DocumentTextIcon,
  CogIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  recommendations: {
    type: Array,
    required: true,
    default: () => [],
  },
  videos: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['navigate']);

const activeFilter = ref('all');

const priorityFilters = [
  { value: 'all', label: 'Hammasi' },
  { value: 'yuqori', label: 'Yuqori' },
  { value: "o'rta", label: "O'rta" },
  { value: 'past', label: 'Past' },
];

const normalizedRecommendations = computed(() => {
  return props.recommendations.map(rec => {
    // Handle both old and new formats
    if (typeof rec.module === 'object' && rec.module !== null) {
      return {
        module: rec.module.name || 'Modul',
        reason: rec.module.description || rec.reason || '',
        priority: rec.priority || "o'rta",
        route: rec.module.route || rec.route || null,
      };
    }
    return {
      module: rec.module || 'Modul',
      reason: rec.reason || '',
      priority: rec.priority || "o'rta",
      route: rec.route || null,
    };
  });
});

const filteredRecommendations = computed(() => {
  if (activeFilter.value === 'all') return normalizedRecommendations.value;
  return normalizedRecommendations.value.filter(rec => rec.priority === activeFilter.value);
});

function getFilterCount(filter) {
  if (filter === 'all') return normalizedRecommendations.value.length;
  return normalizedRecommendations.value.filter(rec => rec.priority === filter).length;
}

function getCardClass(priority) {
  const classes = {
    yuqori: 'from-red-50 to-orange-50 border-red-200 hover:border-red-300',
    "o'rta": 'from-yellow-50 to-orange-50 border-yellow-200 hover:border-yellow-300',
    past: 'from-green-50 to-emerald-50 border-green-200 hover:border-green-300',
  };
  return classes[priority] || 'from-gray-50 to-white border-gray-200';
}

function getIconClass(priority) {
  const classes = {
    yuqori: 'bg-red-100 text-red-600',
    "o'rta": 'bg-yellow-100 text-yellow-600',
    past: 'bg-green-100 text-green-600',
  };
  return classes[priority] || 'bg-gray-100 text-gray-600';
}

function getPriorityBadgeClass(priority) {
  const classes = {
    yuqori: 'bg-red-100 text-red-700',
    "o'rta": 'bg-yellow-100 text-yellow-700',
    past: 'bg-green-100 text-green-700',
  };
  return classes[priority] || 'bg-gray-100 text-gray-700';
}

function getPriorityLabel(priority) {
  const labels = {
    yuqori: 'Yuqori',
    "o'rta": "O'rta",
    past: 'Past',
  };
  return labels[priority] || priority;
}

function getActionClass(priority) {
  const classes = {
    yuqori: 'text-red-600',
    "o'rta": 'text-yellow-600',
    past: 'text-green-600',
  };
  return classes[priority] || 'text-gray-600';
}

const moduleIcons = {
  'Dream Buyer': UserGroupIcon,
  'Ideal Mijoz': UserGroupIcon,
  'Taklif': GiftIcon,
  'Offer': GiftIcon,
  'Kanallar': MegaphoneIcon,
  'Channels': MegaphoneIcon,
  'Funnel': FunnelIcon,
  'Voronka': FunnelIcon,
  'Chatbot': ChatBubbleLeftRightIcon,
  'Bot': ChatBubbleLeftRightIcon,
  'Kontent': DocumentTextIcon,
  'Content': DocumentTextIcon,
  'Avtomatlashtirish': CogIcon,
  'Automation': CogIcon,
  'Analytics': ChartBarIcon,
  'Tahlil': ChartBarIcon,
};

function getModuleIcon(moduleName) {
  for (const [key, icon] of Object.entries(moduleIcons)) {
    if (moduleName?.toLowerCase().includes(key.toLowerCase())) {
      return icon;
    }
  }
  return CubeIcon;
}
</script>
