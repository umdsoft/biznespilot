<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-2 mb-6">
    <div class="flex overflow-x-auto gap-1">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="$emit('update:activeTab', tab.id)"
        class="flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium whitespace-nowrap transition-all"
        :class="activeTab === tab.id
          ? 'bg-indigo-600 text-white shadow-sm'
          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
      >
        <component :is="tab.icon" class="w-4 h-4" />
        {{ tab.label }}
        <span
          v-if="tab.badge"
          class="ml-1 px-1.5 py-0.5 text-xs rounded-full"
          :class="activeTab === tab.id
            ? 'bg-white/20 text-white'
            : 'bg-red-100 text-red-600'"
        >
          {{ tab.badge }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import {
  ChartBarIcon,
  CurrencyDollarIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline';

defineProps({
  activeTab: {
    type: String,
    required: true,
  },
  tabs: {
    type: Array,
    default: () => [
      { id: 'summary', label: 'Umumiy', icon: ChartBarIcon },
      { id: 'financial', label: 'Moliyaviy', icon: CurrencyDollarIcon },
      { id: 'action', label: 'Harakat Rejasi', icon: ClipboardDocumentListIcon },
      { id: 'platform', label: 'Platformalar', icon: CubeIcon },
      { id: 'customer', label: 'Mijoz', icon: UserGroupIcon },
      { id: 'offer', label: 'Taklif', icon: GiftIcon },
      { id: 'channels', label: 'Kanallar', icon: MegaphoneIcon },
      { id: 'funnel', label: 'Funnel', icon: FunnelIcon },
      { id: 'risks', label: 'Xavflar', icon: ExclamationTriangleIcon },
      { id: 'swot', label: 'SWOT', icon: Squares2X2Icon },
    ],
  },
});

defineEmits(['update:activeTab']);
</script>
