<template>
  <BusinessLayout title="Bizneslar">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Bizneslar</h2>
        <p class="mt-1 text-sm text-gray-600">
          Sizning barcha bizneslaringizni boshqaring
        </p>
      </div>
      <Link
        :href="route('business.business.create')"
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Yangi Biznes
      </Link>
    </div>

    <!-- Empty State -->
    <div v-if="businesses.length === 0" class="text-center py-12">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Bizneslar topilmadi</h3>
      <p class="text-gray-600 mb-6">
        Boshlash uchun birinchi biznesingizni yarating
      </p>
      <Link
        :href="route('business.business.create')"
        class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Yangi Biznes Yaratish
      </Link>
    </div>

    <!-- Business Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <Card v-for="business in businesses" :key="business.id" class="hover:shadow-lg transition-shadow">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">
              {{ business.name }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ business.industry }}
            </p>
          </div>
          <span
            :class="[
              'px-2 py-1 text-xs font-medium rounded',
              business.role === 'owner' ? 'bg-purple-100 text-purple-700' :
              business.role === 'admin' ? 'bg-blue-100 text-blue-700' :
              'bg-gray-100 text-gray-700'
            ]"
          >
            {{ getRoleLabel(business.role) }}
          </span>
        </div>

        <p v-if="business.description" class="text-sm text-gray-600 mb-4 line-clamp-2">
          {{ business.description }}
        </p>

        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
          <div class="flex items-center text-sm text-gray-500">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ business.users_count }} a'zo
          </div>
          <div class="flex items-center space-x-2">
            <Link
              :href="route('business.business.show', business.id)"
              class="text-primary-600 hover:text-primary-700 font-medium text-sm"
            >
              Ko'rish
            </Link>
            <Link
              v-if="business.role === 'owner' || business.role === 'admin'"
              :href="route('business.business.edit', business.id)"
              class="text-gray-600 hover:text-gray-700 font-medium text-sm"
            >
              Tahrirlash
            </Link>
          </div>
        </div>
      </Card>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import BusinessLayout from '@/Layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';

defineProps({
  businesses: {
    type: Array,
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
