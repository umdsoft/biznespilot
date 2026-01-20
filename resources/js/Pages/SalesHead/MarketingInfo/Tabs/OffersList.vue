<template>
  <div class="space-y-4">
    <!-- Empty State -->
    <div v-if="!offers || offers.length === 0" class="text-center py-12">
      <TagIcon class="w-12 h-12 mx-auto text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Takliflar yo'q</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Biznes egasi hali takliflarni yaratmagan.
      </p>
    </div>

    <!-- Offers Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="offer in offers"
        :key="offer.id"
        class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-4">
          <div>
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded-full',
                offer.is_active
                  ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
              ]"
            >
              {{ offer.is_active ? 'Faol' : 'Nofaol' }}
            </span>
          </div>
          <span v-if="offer.offer_type" class="text-xs text-gray-500 dark:text-gray-400">
            {{ getOfferTypeLabel(offer.offer_type) }}
          </span>
        </div>

        <!-- Title -->
        <h3 class="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
          {{ offer.title || offer.name }}
        </h3>

        <!-- Description -->
        <p v-if="offer.description" class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-3">
          {{ offer.description }}
        </p>

        <!-- Price Info -->
        <div v-if="offer.price || offer.original_price" class="flex items-baseline gap-2 mb-4">
          <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
            {{ formatPrice(offer.price) }}
          </span>
          <span v-if="offer.original_price && offer.original_price > offer.price" class="text-sm text-gray-400 line-through">
            {{ formatPrice(offer.original_price) }}
          </span>
        </div>

        <!-- Benefits -->
        <div v-if="offer.benefits && offer.benefits.length > 0" class="mb-4">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Afzalliklar:</p>
          <ul class="space-y-1">
            <li
              v-for="(benefit, idx) in offer.benefits.slice(0, 3)"
              :key="idx"
              class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400"
            >
              <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ benefit }}
            </li>
          </ul>
        </div>

        <!-- View Button -->
        <a
          :href="route('sales-head.offers.show', offer.id)"
          class="block w-full text-center py-2 px-4 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors text-sm font-medium"
        >
          Batafsil ko'rish
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { TagIcon } from '@heroicons/vue/24/outline';

defineProps({
  offers: { type: Array, default: () => [] },
  panelType: { type: String, default: 'saleshead' },
  readOnly: { type: Boolean, default: true },
});

function getOfferTypeLabel(type) {
  const types = {
    product: 'Mahsulot',
    service: 'Xizmat',
    bundle: 'Paket',
    subscription: 'Obuna',
  };
  return types[type] || type;
}

function formatPrice(price) {
  if (!price) return '';
  return new Intl.NumberFormat('uz-UZ').format(price) + ' so\'m';
}
</script>
