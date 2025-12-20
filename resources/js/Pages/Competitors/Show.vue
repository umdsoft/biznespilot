<template>
  <BusinessLayout :title="competitor.name">
    <div class="max-w-5xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <Link href="/competitors" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Raqobatchilar
          </Link>
          <div class="flex items-center space-x-3">
            <div class="w-16 h-16 bg-gradient-to-br from-red-100 to-orange-100 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900">{{ competitor.name }}</h2>
              <a
                v-if="competitor.website"
                :href="competitor.website"
                target="_blank"
                class="mt-1 text-sm text-primary-600 hover:text-primary-700 inline-flex items-center"
              >
                {{ getDomain(competitor.website) }}
                <svg class="w-3 h-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </div>
          </div>
        </div>
        <Link
          :href="`/competitors/${competitor.id}/edit`"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Tahrirlash
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <Card v-if="competitor.description" title="Tavsif">
            <p class="text-gray-700">{{ competitor.description }}</p>
          </Card>

          <Card title="SWOT Tahlili">
            <div class="space-y-4">
              <div v-if="competitor.strengths">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Kuchli Tomonlar</h4>
                <p class="text-gray-600 whitespace-pre-wrap">{{ competitor.strengths }}</p>
              </div>
              <div v-if="competitor.weaknesses" class="pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Zaif Tomonlar</h4>
                <p class="text-gray-600 whitespace-pre-wrap">{{ competitor.weaknesses }}</p>
              </div>
            </div>
          </Card>

          <Card v-if="competitor.products?.length" title="Mahsulotlar">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(product, index) in competitor.products"
                :key="index"
                class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-lg"
              >
                {{ product }}
              </span>
            </div>
          </Card>

          <Card v-if="competitor.pricing?.length" title="Narxlash Strategiyalari">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(price, index) in competitor.pricing"
                :key="index"
                class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-lg"
              >
                {{ price }}
              </span>
            </div>
          </Card>

          <Card v-if="competitor.marketing_strategies?.length" title="Marketing Strategiyalari">
            <div class="flex flex-wrap gap-2">
              <span
                v-for="(strategy, index) in competitor.marketing_strategies"
                :key="index"
                class="px-3 py-1 bg-purple-100 text-purple-700 text-sm rounded-lg"
              >
                {{ strategy }}
              </span>
            </div>
          </Card>

          <Card v-if="activities.length" title="Oxirgi Faoliyatlar">
            <div class="space-y-3">
              <div
                v-for="activity in activities"
                :key="activity.id"
                class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex items-start justify-between mb-1">
                  <h4 class="font-medium text-gray-900">{{ activity.title }}</h4>
                  <span :class="getActivityTypeClass(activity.activity_type)" class="text-xs px-2 py-0.5 rounded">
                    {{ getActivityTypeLabel(activity.activity_type) }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 mb-2">{{ activity.description }}</p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>{{ activity.detected_at }}</span>
                  <a v-if="activity.source_url" :href="activity.source_url" target="_blank" class="text-primary-600 hover:text-primary-700">
                    Manbaga o'tish →
                  </a>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <div class="space-y-6">
          <Card title="Tahdid Darajasi">
            <div class="mb-4">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Daraja</span>
                <span class="text-2xl font-bold" :class="getThreatColor(competitor.threat_level)">
                  {{ competitor.threat_level }}/10
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div
                  :class="getThreatBarColor(competitor.threat_level)"
                  class="h-3 rounded-full transition-all"
                  :style="{ width: (competitor.threat_level * 10) + '%' }"
                ></div>
              </div>
            </div>
            <p class="text-sm text-gray-600">
              {{ getThreatDescription(competitor.threat_level) }}
            </p>
          </Card>

          <Card title="Holat">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Faollik</span>
              <span
                :class="competitor.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ competitor.is_active ? 'Faol' : 'Faol emas' }}
              </span>
            </div>
          </Card>

          <Card title="Ma'lumot">
            <div class="text-sm text-gray-600">
              <p>Qo'shilgan: {{ competitor.created_at }}</p>
            </div>
          </Card>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';

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

const getDomain = (url) => {
  try {
    return new URL(url).hostname.replace('www.', '');
  } catch {
    return url;
  }
};

const getThreatColor = (level) => {
  if (level >= 7) return 'text-red-600';
  if (level >= 4) return 'text-yellow-600';
  return 'text-green-600';
};

const getThreatBarColor = (level) => {
  if (level >= 7) return 'bg-red-500';
  if (level >= 4) return 'bg-yellow-500';
  return 'bg-green-500';
};

const getThreatDescription = (level) => {
  if (level >= 7) return 'Yuqori tahdid - Jiddiy diqqat talab qiladi';
  if (level >= 4) return 'O\'rtacha tahdid - Kuzatib borish kerak';
  return 'Past tahdid - Oddiy monitoring';
};

const getActivityTypeClass = (type) => {
  const classes = {
    product_launch: 'bg-blue-100 text-blue-800',
    pricing_change: 'bg-green-100 text-green-800',
    marketing_campaign: 'bg-purple-100 text-purple-800',
    content: 'bg-yellow-100 text-yellow-800',
    social_media: 'bg-pink-100 text-pink-800',
    other: 'bg-gray-100 text-gray-800',
  };
  return classes[type] || classes.other;
};

const getActivityTypeLabel = (type) => {
  const labels = {
    product_launch: 'Mahsulot',
    pricing_change: 'Narx',
    marketing_campaign: 'Kampaniya',
    content: 'Kontent',
    social_media: 'Ijtimoiy',
    other: 'Boshqa',
  };
  return labels[type] || type;
};
</script>
