<template>
  <BusinessLayout :title="lead.name">
    <div class="max-w-5xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <Link href="/sales" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Sotuv
          </Link>
          <div class="flex items-center space-x-3">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-900">{{ lead.name }}</h2>
              <div class="flex items-center space-x-2 mt-1">
                <span
                  :class="getStatusClass(lead.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(lead.status) }}
                </span>
                <span class="text-sm text-gray-600">UUID: {{ lead.uuid }}</span>
              </div>
            </div>
          </div>
        </div>
        <Link
          :href="`/sales/${lead.id}/edit`"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
          Tahrirlash
        </Link>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Kontakt Ma'lumotlari -->
          <Card title="Kontakt Ma'lumotlari">
            <div class="grid grid-cols-2 gap-4">
              <InfoItem label="Email" :value="lead.email" />
              <InfoItem label="Telefon" :value="lead.phone" />
              <InfoItem label="Kompaniya" :value="lead.company" />
            </div>
          </Card>

          <!-- Lead Batafsil -->
          <Card title="Lead Ma'lumotlari">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lead Ball</label>
                <div class="flex items-center">
                  <div class="flex-1 bg-gray-200 rounded-full h-3 mr-3">
                    <div
                      :class="getScoreColor(lead.score)"
                      class="h-3 rounded-full transition-all"
                      :style="{ width: lead.score + '%' }"
                    ></div>
                  </div>
                  <span class="text-lg font-semibold text-gray-900">{{ lead.score }}/100</span>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <InfoItem label="Taxminiy qiymat" :value="lead.estimated_value ? formatCurrency(lead.estimated_value) : null" />
                <InfoItem label="Manba" :value="lead.source?.name" />
              </div>

              <div v-if="lead.assigned_to">
                <label class="block text-sm font-medium text-gray-700 mb-1">Javobgar</label>
                <div class="flex items-center space-x-2">
                  <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ lead.assigned_to.name }}</p>
                    <p class="text-xs text-gray-600">{{ lead.assigned_to.email }}</p>
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Izohlar -->
          <Card v-if="lead.notes" title="Izohlar">
            <p class="text-gray-700 whitespace-pre-wrap">{{ lead.notes }}</p>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Meta Info -->
          <Card title="Ma'lumot">
            <div class="space-y-3">
              <InfoItem label="Yaratilgan" :value="lead.created_at" />
              <InfoItem label="Oxirgi aloqa" :value="lead.last_contacted_at" />
              <InfoItem v-if="lead.converted_at" label="O'tkazilgan" :value="lead.converted_at" />
            </div>
          </Card>

          <!-- Quick Stats -->
          <Card title="Tezkor Ko'rinish">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Holat</span>
                <span
                  :class="getStatusClass(lead.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(lead.status) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Lead Ball</span>
                <span class="text-sm font-medium text-gray-900">{{ lead.score }}/100</span>
              </div>
              <div v-if="lead.estimated_value" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Qiymat</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(lead.estimated_value) }}</span>
              </div>
            </div>
          </Card>

          <!-- Quick Actions -->
          <Card title="Tezkor Harakatlar">
            <div class="space-y-2">
              <a
                v-if="lead.email"
                :href="`mailto:${lead.email}`"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Email Yuborish
                </div>
              </a>
              <a
                v-if="lead.phone"
                :href="`tel:${lead.phone}`"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  Qo'ng'iroq Qilish
                </div>
              </a>
              <Link
                :href="`/sales/${lead.id}/edit`"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Tahrirlash
                </div>
              </Link>
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
  lead: {
    type: Object,
    required: true,
  },
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('uz-UZ', {
    style: 'decimal',
    minimumFractionDigits: 0,
  }).format(amount) + ' so\'m';
};

const getStatusClass = (status) => {
  const classes = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-cyan-100 text-cyan-800',
    qualified: 'bg-purple-100 text-purple-800',
    proposal: 'bg-yellow-100 text-yellow-800',
    negotiation: 'bg-orange-100 text-orange-800',
    won: 'bg-green-100 text-green-800',
    lost: 'bg-red-100 text-red-800',
  };
  return classes[status] || classes.new;
};

const getStatusLabel = (status) => {
  const labels = {
    new: 'Yangi',
    contacted: 'Bog\'lanildi',
    qualified: 'Qualified',
    proposal: 'Taklif',
    negotiation: 'Muzokara',
    won: 'Yutildi',
    lost: 'Yo\'qoldi',
  };
  return labels[status] || status;
};

const getScoreColor = (score) => {
  if (score >= 75) return 'bg-green-500';
  if (score >= 50) return 'bg-yellow-500';
  if (score >= 25) return 'bg-orange-500';
  return 'bg-red-500';
};
</script>

<script>
// Helper Components
const InfoItem = {
  props: ['label', 'value'],
  template: `
    <div v-if="value">
      <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
      <p class="text-gray-900">{{ value }}</p>
    </div>
  `,
};

export default {
  components: {
    InfoItem,
  },
};
</script>
