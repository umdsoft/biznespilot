<template>
  <BusinessLayout :title="business.name">
    <div class="max-w-5xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <Link href="/business" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
          </Link>
          <h2 class="text-2xl font-bold text-gray-900">{{ business.name }}</h2>
          <p class="mt-1 text-sm text-gray-600">{{ business.industry }}</p>
        </div>
        <Link
          :href="`/business/${business.id}/edit`"
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
          <!-- Business Details -->
          <Card title="Biznes Tafsilotlari">
            <div class="space-y-4">
              <div v-if="business.description">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
                <p class="text-gray-900">{{ business.description }}</p>
              </div>

              <div v-if="business.website">
                <label class="block text-sm font-medium text-gray-700 mb-1">Veb-sayt</label>
                <a :href="business.website" target="_blank" class="text-primary-600 hover:text-primary-700">
                  {{ business.website }}
                </a>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div v-if="business.phone">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                  <p class="text-gray-900">{{ business.phone }}</p>
                </div>

                <div v-if="business.email">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <p class="text-gray-900">{{ business.email }}</p>
                </div>
              </div>

              <div v-if="business.address || business.city">
                <label class="block text-sm font-medium text-gray-700 mb-1">Manzil</label>
                <p class="text-gray-900">
                  {{ [business.address, business.city, business.country].filter(Boolean).join(', ') }}
                </p>
              </div>
            </div>
          </Card>

          <!-- Team Members -->
          <Card title="Jamoa A'zolari">
            <div class="space-y-3">
              <div
                v-for="user in business.users"
                :key="user.id"
                class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50"
              >
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-primary-700">
                      {{ user.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <p class="font-medium text-gray-900">{{ user.name }}</p>
                    <p class="text-sm text-gray-600">@{{ user.login }}</p>
                  </div>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded',
                    user.role === 'owner' ? 'bg-purple-100 text-purple-700' :
                    user.role === 'admin' ? 'bg-blue-100 text-blue-700' :
                    'bg-gray-100 text-gray-700'
                  ]"
                >
                  {{ getRoleLabel(user.role) }}
                </span>
              </div>
            </div>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Stats -->
          <Card title="Statistika">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Jamoa a'zolari</span>
                <span class="font-semibold text-gray-900">{{ business.users.length }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Yaratilgan</span>
                <span class="font-semibold text-gray-900">{{ business.created_at }}</span>
              </div>
            </div>
          </Card>

          <!-- Quick Actions -->
          <Card title="Tezkor Harakatlar">
            <div class="space-y-2">
              <Link
                href="/dream-buyer"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                Ideal Mijoz yaratish
              </Link>
              <Link
                href="/marketing"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                Marketing kampaniyasi
              </Link>
              <Link
                href="/sales"
                class="block w-full px-4 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
              >
                Lead qo'shish
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
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';

defineProps({
  business: {
    type: Object,
    required: true,
  },
});

const getRoleLabel = (role) => {
  const labels = {
    owner: 'Egasi',
    admin: 'Admin',
    manager: 'Menejer',
    member: 'A\'zo',
    viewer: 'Ko\'ruvchi',
  };
  return labels[role] || role;
};
</script>
