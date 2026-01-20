<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!dreamBuyers || dreamBuyers.length === 0" class="text-center py-12">
      <UserGroupIcon class="w-12 h-12 mx-auto text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Ideal mijozlar yo'q</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Biznes egasi hali ideal mijoz profillarini yaratmagan.
      </p>
    </div>

    <!-- Dream Buyers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="buyer in dreamBuyers"
        :key="buyer.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-lg">
              {{ buyer.name?.charAt(0)?.toUpperCase() || 'M' }}
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">{{ buyer.name }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ buyer.age_range || '25-45 yosh' }}</p>
            </div>
          </div>
        </div>

        <!-- Demographics -->
        <div class="space-y-2 mb-4">
          <div v-if="buyer.gender" class="flex items-center text-sm">
            <span class="text-gray-500 dark:text-gray-400 w-24">Jinsi:</span>
            <span class="text-gray-900 dark:text-white">{{ buyer.gender === 'male' ? 'Erkak' : buyer.gender === 'female' ? 'Ayol' : 'Barchasi' }}</span>
          </div>
          <div v-if="buyer.location" class="flex items-center text-sm">
            <span class="text-gray-500 dark:text-gray-400 w-24">Joylashuv:</span>
            <span class="text-gray-900 dark:text-white">{{ buyer.location }}</span>
          </div>
          <div v-if="buyer.income_level" class="flex items-center text-sm">
            <span class="text-gray-500 dark:text-gray-400 w-24">Daromad:</span>
            <span class="text-gray-900 dark:text-white">{{ buyer.income_level }}</span>
          </div>
        </div>

        <!-- Pain Points -->
        <div v-if="buyer.pain_points && buyer.pain_points.length > 0" class="mb-4">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Muammolari:</p>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="(pain, idx) in buyer.pain_points.slice(0, 3)"
              :key="idx"
              class="px-2 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded-full"
            >
              {{ pain }}
            </span>
          </div>
        </div>

        <!-- View Button -->
        <a
          :href="route('sales-head.dream-buyer.show', buyer.id)"
          class="block w-full text-center py-2 px-4 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors text-sm font-medium"
        >
          Batafsil ko'rish
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { UserGroupIcon } from '@heroicons/vue/24/outline';

defineProps({
  dreamBuyers: { type: Array, default: () => [] },
  panelType: { type: String, default: 'saleshead' },
  readOnly: { type: Boolean, default: true },
});
</script>
