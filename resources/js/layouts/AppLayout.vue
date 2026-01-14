<template>
  <BaseLayout :title="title" :config="layoutConfig">
    <template #navigation>
      <template v-for="(section, sectionIndex) in layoutConfig.navigation" :key="sectionIndex">
        <!-- Section Divider -->
        <div v-if="sectionIndex > 0" class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
          <p v-if="section.title" class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ section.title }}
          </p>
        </div>
        <div v-else class="mb-4">
          <p v-if="section.title" class="px-3 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ section.title }}
          </p>
        </div>

        <!-- Navigation Items -->
        <NavLink
          v-for="item in section.items"
          :key="item.href"
          :href="item.href"
          :active="isActive(item)"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          {{ item.label }}
        </NavLink>
      </template>

      <!-- Admin Panel Link (Only for admins) -->
      <div v-if="isAdmin" class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
        <NavLink href="/admin" :active="$page.url.startsWith('/admin')">
          <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <span class="font-semibold text-red-600">Admin Paneli</span>
        </NavLink>
      </div>
    </template>

    <slot />
  </BaseLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BaseLayout from './BaseLayout.vue';
import NavLink from '@/components/NavLink.vue';
import { appLayoutConfig } from '@/composables/useLayoutConfig';

defineProps({
  title: {
    type: String,
    default: 'Bosh sahifa',
  },
});

const page = usePage();
const layoutConfig = appLayoutConfig;

// Check if user has admin role
const isAdmin = computed(() => {
  const user = page.props.auth?.user;
  const roles = user?.roles || [];
  return roles.some(role => role.name === 'admin' || role.name === 'super_admin');
});

// Check if nav item is active
const isActive = (item) => {
  const url = page.url;
  if (item.exact) {
    return url === item.href || url === item.href + '/';
  }
  if (item.activeMatch) {
    return item.activeMatch(url);
  }
  return url.startsWith(item.href);
};
</script>
