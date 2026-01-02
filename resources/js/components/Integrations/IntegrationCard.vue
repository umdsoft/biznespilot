<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="p-6 border-b border-gray-100">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
          <!-- Icon -->
          <div
            :class="[
              'w-16 h-16 rounded-lg flex items-center justify-center text-3xl',
              isConnected ? 'bg-green-50' : 'bg-gray-50'
            ]"
          >
            {{ icon }}
          </div>

          <!-- Info -->
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ name }}</h3>
            <p class="text-sm text-gray-600">{{ description }}</p>
          </div>
        </div>

        <!-- Status Badge -->
        <span
          :class="[
            'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
            isConnected
              ? 'bg-green-100 text-green-800'
              : 'bg-gray-100 text-gray-600'
          ]"
        >
          <span
            :class="[
              'w-2 h-2 rounded-full mr-2',
              isConnected ? 'bg-green-500' : 'bg-gray-400'
            ]"
          ></span>
          {{ isConnected ? 'Ulangan' : 'Ulanmagan' }}
        </span>
      </div>
    </div>

    <!-- Body -->
    <div class="p-6">
      <!-- Features -->
      <div v-if="features && features.length > 0" class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Imkoniyatlar:</h4>
        <ul class="space-y-1">
          <li
            v-for="(feature, index) in features"
            :key="index"
            class="flex items-start gap-2 text-sm text-gray-600"
          >
            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ feature }}
          </li>
        </ul>
      </div>

      <!-- Connected Info -->
      <div v-if="isConnected && connectionInfo" class="mb-4 p-3 bg-green-50 rounded-lg">
        <div class="text-sm">
          <div v-if="connectionInfo.account" class="flex items-center gap-2 text-gray-700 mb-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="font-medium">{{ connectionInfo.account }}</span>
          </div>
          <div v-if="connectionInfo.lastSync" class="text-xs text-gray-500">
            Oxirgi sinxronlash: {{ connectionInfo.lastSync }}
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="isConnected && stats" class="grid grid-cols-3 gap-3 mb-4">
        <div
          v-for="(stat, key) in stats"
          :key="key"
          class="text-center p-2 bg-gray-50 rounded"
        >
          <div class="text-lg font-semibold text-gray-900">{{ stat.value }}</div>
          <div class="text-xs text-gray-600">{{ stat.label }}</div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <button
          v-if="!isConnected"
          @click="$emit('connect')"
          :disabled="loading"
          class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="!loading" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          {{ loading ? 'Ulanmoqda...' : 'Ulash' }}
        </button>

        <template v-else>
          <button
            @click="$emit('sync')"
            :disabled="loading"
            class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
          >
            <svg
              :class="{ 'animate-spin': loading }"
              class="w-4 h-4 mr-2"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Sinxronlash
          </button>

          <button
            @click="$emit('settings')"
            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            title="Sozlamalar"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>

          <button
            @click="$emit('disconnect')"
            class="px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
            title="Uzish"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon: {
    type: String,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    required: true,
  },
  isConnected: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  features: {
    type: Array,
    default: () => [],
  },
  connectionInfo: {
    type: Object,
    default: null,
  },
  stats: {
    type: Object,
    default: null,
  },
});

defineEmits(['connect', 'disconnect', 'sync', 'settings']);
</script>
