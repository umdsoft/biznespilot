<template>
  <BusinessLayout title="Sotuv va Leadlar">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Sotuv va Leadlar</h2>
          <p class="mt-1 text-sm text-gray-600">
            Potensial mijozlaringizni boshqaring va kuzatib boring
          </p>
        </div>
        <Link
          href="/sales/create"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Lead Qo'shish
        </Link>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Jami Leadlar</p>
              <p class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_leads }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Yangi Leadlar</p>
              <p class="mt-2 text-3xl font-semibold text-orange-600">{{ stats.new_leads }}</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Qualified</p>
              <p class="mt-2 text-3xl font-semibold text-purple-600">{{ stats.qualified_leads }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Pipeline Qiymati</p>
              <p class="mt-2 text-3xl font-semibold text-green-600">{{ formatCurrency(stats.pipeline_value) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4">
          <div class="flex items-center space-x-4">
            <div class="flex-1">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Lead qidirish..."
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              />
            </div>
            <div class="w-48">
              <select
                v-model="statusFilter"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">Barcha holatlar</option>
                <option value="new">Yangi</option>
                <option value="contacted">Bog'lanildi</option>
                <option value="qualified">Qualified</option>
                <option value="proposal">Taklif</option>
                <option value="negotiation">Muzokara</option>
                <option value="won">Yutildi</option>
                <option value="lost">Yo'qoldi</option>
              </select>
            </div>
            <div class="w-48">
              <select
                v-model="sourceFilter"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">Barcha manbalar</option>
                <option
                  v-for="channel in channels"
                  :key="channel.id"
                  :value="channel.id"
                >
                  {{ channel.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Leads Table -->
      <div v-if="filteredLeads.length > 0" class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Lead
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Kompaniya
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Holat
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ball
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Qiymat
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Manba
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sana
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amallar
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="lead in filteredLeads"
              :key="lead.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ lead.name }}</div>
                  <div class="text-sm text-gray-500">{{ lead.email }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ lead.company || '-' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="getStatusClass(lead.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ getStatusLabel(lead.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                    <div
                      :class="getScoreColor(lead.score)"
                      class="h-2 rounded-full"
                      :style="{ width: lead.score + '%' }"
                    ></div>
                  </div>
                  <span class="text-sm text-gray-900">{{ lead.score }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ lead.estimated_value ? formatCurrency(lead.estimated_value) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ lead.source?.name || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ lead.created_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <Link
                    :href="`/sales/${lead.id}`"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    Ko'rish
                  </Link>
                  <Link
                    :href="`/sales/${lead.id}/edit`"
                    class="text-gray-600 hover:text-gray-900"
                  >
                    Tahrirlash
                  </Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Hech qanday lead mavjud emas</h3>
        <p class="text-gray-600 mb-6">Birinchi leadingizni qo'shing</p>
        <Link
          href="/sales/create"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Lead Qo'shish
        </Link>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  leads: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    required: true,
  },
  channels: {
    type: Array,
    default: () => [],
  },
  currentBusiness: {
    type: Object,
    required: true,
  },
});

const searchQuery = ref('');
const statusFilter = ref('');
const sourceFilter = ref('');

const filteredLeads = computed(() => {
  let filtered = props.leads;

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(lead =>
      lead.name.toLowerCase().includes(query) ||
      lead.email?.toLowerCase().includes(query) ||
      lead.company?.toLowerCase().includes(query)
    );
  }

  if (statusFilter.value) {
    filtered = filtered.filter(lead => lead.status === statusFilter.value);
  }

  if (sourceFilter.value) {
    filtered = filtered.filter(lead => lead.source?.id === parseInt(sourceFilter.value));
  }

  return filtered;
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
