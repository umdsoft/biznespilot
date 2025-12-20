<template>
  <BusinessLayout title="Raqib Ma'lumotlari">
    <div class="max-w-8xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <Link href="/competitors" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Raqobatchilar
        </Link>
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold text-gray-900">{{ competitor.name }}</h2>
          <div class="flex items-center space-x-3">
            <Link
              :href="`/competitors/${competitor.id}/edit`"
              class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors"
            >
              <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Tahrirlash
            </Link>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="space-y-6">
          <Card title="Asosiy Ma'lumot">
            <div class="space-y-3">
              <div>
                <p class="text-sm text-gray-600">Tahdid Darajasi</p>
                <div class="mt-1">
                  <span
                    :class="getThreatClass(competitor.threat_level)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getThreatLabel(competitor.threat_level) }}
                  </span>
                </div>
              </div>

              <div>
                <p class="text-sm text-gray-600">Holat</p>
                <div class="mt-1">
                  <span
                    :class="getStatusClass(competitor.is_active)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ competitor.is_active ? 'Faol' : 'Nofaol' }}
                  </span>
                </div>
              </div>

              <div v-if="competitor.website">
                <p class="text-sm text-gray-600">Veb-sayt</p>
                <a
                  :href="competitor.website"
                  target="_blank"
                  class="text-sm text-primary-600 hover:text-primary-700 mt-1 inline-flex items-center"
                >
                  {{ competitor.website }}
                  <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>
              </div>

              <div v-if="competitor.description">
                <p class="text-sm text-gray-600">Tavsif</p>
                <p class="text-sm text-gray-900 mt-1">{{ competitor.description }}</p>
              </div>
            </div>
          </Card>

          <Card title="Mahsulotlar" v-if="competitor.products && competitor.products.length">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(product, index) in competitor.products"
                :key="index"
                class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded"
              >
                {{ product }}
              </span>
            </div>
          </Card>

          <Card title="Marketing Strategiyalari" v-if="competitor.marketing_strategies && competitor.marketing_strategies.length">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(strategy, index) in competitor.marketing_strategies"
                :key="index"
                class="px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded"
              >
                {{ strategy }}
              </span>
            </div>
          </Card>
        </div>

        <!-- Main Panel -->
        <div class="lg:col-span-2 space-y-6">
          <!-- SWOT Analysis -->
          <Card title="SWOT Tahlil">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h4 class="text-sm font-semibold text-green-900 mb-2">Kuchli Tomonlar</h4>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ competitor.strengths || 'Ma\'lumot yo\'q' }}</p>
              </div>

              <div>
                <h4 class="text-sm font-semibold text-red-900 mb-2">Zaif Tomonlar</h4>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ competitor.weaknesses || 'Ma\'lumot yo\'q' }}</p>
              </div>
            </div>
          </Card>

          <!-- Pricing -->
          <Card title="Narxlash Strategiyalari" v-if="competitor.pricing && competitor.pricing.length">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(price, index) in competitor.pricing"
                :key="index"
                class="px-3 py-1.5 bg-green-100 text-green-700 text-sm rounded-lg"
              >
                {{ price }}
              </span>
            </div>
          </Card>

          <!-- Activity Timeline -->
          <Card title="Faoliyat Tarixi">
            <div class="flow-root">
              <ul class="-mb-8">
                <li v-for="(activity, index) in activities" :key="index">
                  <div class="relative pb-8">
                    <span
                      v-if="index !== activities.length - 1"
                      class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                      aria-hidden="true"
                    ></span>
                    <div class="relative flex space-x-3">
                      <div>
                        <span :class="[activity.iconBackground, 'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white']">
                          <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" :d="activity.iconPath" clip-rule="evenodd" />
                          </svg>
                        </span>
                      </div>
                      <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                        <div>
                          <p class="text-sm text-gray-900">{{ activity.content }}</p>
                        </div>
                        <div class="text-right text-sm whitespace-nowrap text-gray-500">
                          <time :datetime="activity.datetime">{{ activity.date }}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';

defineProps({
  competitor: {
    type: Object,
    required: true,
  },
  activities: {
    type: Array,
    default: () => [],
  },
});

const getThreatClass = (level) => {
  const classes = {
    0: 'bg-green-100 text-green-800',
    1: 'bg-green-100 text-green-800',
    2: 'bg-green-100 text-green-800',
    3: 'bg-green-100 text-green-800',
    4: 'bg-yellow-100 text-yellow-800',
    5: 'bg-yellow-100 text-yellow-800',
    6: 'bg-yellow-100 text-yellow-800',
    7: 'bg-orange-100 text-orange-800',
    8: 'bg-red-100 text-red-800',
    9: 'bg-red-100 text-red-800',
    10: 'bg-red-100 text-red-800',
  };
  return classes[level] || classes[5];
};

const getThreatLabel = (level) => {
  if (level <= 3) return 'Past';
  if (level <= 6) return 'O\'rta';
  if (level <= 7) return 'Yuqori';
  return 'Kritik';
};

const getStatusClass = (isActive) => {
  return isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
};
</script>
