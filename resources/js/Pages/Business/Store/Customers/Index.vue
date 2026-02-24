<template>
  <Head title="Mijozlar" />
  <component :is="layoutComponent" title="Mijozlar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mijozlar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Jami: {{ customers?.total || 0 }} ta mijoz
          </p>
        </div>
      </div>

      <!-- Search -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="relative max-w-md">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Ism yoki telefon raqam bo'yicha qidirish..."
            class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            @input="debouncedSearch"
          />
        </div>
      </div>

      <!-- Customers Table -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="customers.data && customers.data.length > 0" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50">
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Mijoz</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Telefon</th>
                <th class="px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Buyurtmalar</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Jami xarid</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Oxirgi buyurtma</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="customer in customers.data"
                :key="customer.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors cursor-pointer"
                @click="router.visit(storeRoute('customers.show', customer.id))"
              >
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                      :class="getAvatarColor(customer.name)"
                    >
                      <span class="text-sm font-bold text-white">{{ getInitials(customer.name) }}</span>
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium text-slate-900 dark:text-white">{{ customer.name }}</p>
                      <p v-if="customer.telegram_username" class="text-xs text-blue-500">@{{ customer.telegram_username }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-600 dark:text-slate-400">{{ customer.phone || '-' }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-center">
                  <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 text-sm font-semibold rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    {{ customer.orders_count || 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(customer.total_spent || 0) }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ customer.last_order_date ? formatDate(customer.last_order_date) : '-' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-16">
          <UsersIcon class="w-16 h-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Mijozlar yo'q</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ search ? "Qidiruv bo'yicha natija topilmadi" : "Birinchi buyurtma berilganda mijozlar avtomatik qo'shiladi" }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="customers.links && customers.links.length > 3"
        :links="customers.links"
        :from="customers.from"
        :to="customers.to"
        :total="customers.total"
      />
    </div>
  </component>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useStorePanel } from '@/composables/useStorePanel';
import Pagination from '@/components/Pagination.vue';
import {
  MagnifyingGlassIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  customers: { type: Object, default: () => ({ data: [], links: [] }) },
  filters: { type: Object, default: () => ({}) },
  panelType: { type: String, default: 'business' },
});

const { layoutComponent, storeRoute } = useStorePanel(props.panelType);

const search = ref(props.filters?.search || '');

let searchTimeout = null;

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getInitials = (name) => {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
};

const avatarColors = [
  'bg-emerald-500',
  'bg-blue-500',
  'bg-purple-500',
  'bg-amber-500',
  'bg-rose-500',
  'bg-cyan-500',
  'bg-indigo-500',
  'bg-orange-500',
];

const getAvatarColor = (name) => {
  if (!name) return avatarColors[0];
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash);
  }
  return avatarColors[Math.abs(hash) % avatarColors.length];
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(storeRoute('customers.index'), {
      search: search.value || undefined,
    }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }, 400);
};
</script>
