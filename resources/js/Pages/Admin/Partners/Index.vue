<template>
  <AdminLayout title="Partner Program">
    <div class="min-h-screen">
      <!-- Header -->
      <div class="border-b border-gray-200 dark:border-gray-700/50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm sticky top-0 z-10">
        <div class="max-w-[1600px] mx-auto px-6 py-5 flex items-center justify-between">
          <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Partner Program</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ stats.total_partners }} ta partner</p>
          </div>
          <Link
            :href="route('admin.partners.payouts')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg"
          >
            <BanknotesIcon class="w-4 h-4" />
            Payouts queue
          </Link>
        </div>
      </div>

      <div class="max-w-[1600px] mx-auto px-6 py-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Jami</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total_partners }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Faol</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ stats.active_partners }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pending_partners }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Payouts kutilmoqda</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ formatMoney(stats.total_payouts_pending) }}</p>
          </div>
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700/50">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-medium">Komissiya to'langan</p>
            <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ formatMoney(stats.total_commissions_paid) }}</p>
          </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
          <div class="relative flex-1">
            <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="localFilters.search"
              @input="debouncedApply"
              type="text"
              placeholder="Ism, kod yoki telefon..."
              class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
            />
          </div>
          <select v-model="localFilters.status" @change="apply" class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white min-w-[160px]">
            <option value="">Barcha statuslar</option>
            <option value="pending">Pending</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
            <option value="terminated">Terminated</option>
          </select>
          <select v-model="localFilters.tier" @change="apply" class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white min-w-[160px]">
            <option value="">Barcha tierlar</option>
            <option value="bronze">Bronze</option>
            <option value="silver">Silver</option>
            <option value="gold">Gold</option>
            <option value="platinum">Platinum</option>
          </select>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/50 overflow-hidden">
          <div v-if="partners.data.length" class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/30">
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Code / Ism</th>
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden md:table-cell">Email</th>
                  <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Status</th>
                  <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Tier</th>
                  <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">Turi</th>
                  <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Ref</th>
                  <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5 hidden lg:table-cell">Lifetime</th>
                  <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3.5">Available</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                <tr
                  v-for="p in partners.data"
                  :key="p.id"
                  @click="goShow(p.id)"
                  class="cursor-pointer hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors"
                >
                  <td class="px-5 py-4">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ p.full_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ p.code }}</p>
                  </td>
                  <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ p.email || '—' }}</td>
                  <td class="px-5 py-4 text-center">
                    <span :class="statusBadge(p.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                      {{ p.status }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-center">
                    <span :class="tierBadge(p.tier)" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-md">
                      {{ tierIcon(p.tier) }} {{ p.tier }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300 hidden lg:table-cell">{{ p.partner_type }}</td>
                  <td class="px-5 py-4 text-center text-sm text-gray-700 dark:text-gray-200">
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ p.active_referrals }}</span>
                    <span class="text-gray-400"> / {{ p.referrals_count }}</span>
                  </td>
                  <td class="px-5 py-4 text-right text-sm text-gray-700 dark:text-gray-200 hidden lg:table-cell">{{ formatMoney(p.lifetime_earned) }}</td>
                  <td class="px-5 py-4 text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ formatMoney(p.available_balance) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-16">
            <UserGroupIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Hozircha partner yo'q</p>
          </div>
        </div>

        <div v-if="partners.data.length && partners.links" class="mt-4">
          <Pagination :links="partners.links" :from="partners.from" :to="partners.to" :total="partners.total" />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/components/Pagination.vue';
import {
  MagnifyingGlassIcon,
  UserGroupIcon,
  BanknotesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  partners: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
});

const localFilters = ref({
  search: props.filters.search || '',
  status: props.filters.status || '',
  tier: props.filters.tier || '',
});

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

const apply = () => {
  router.get(
    route('admin.partners.index'),
    {
      search: localFilters.value.search || undefined,
      status: localFilters.value.status || undefined,
      tier: localFilters.value.tier || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true }
  );
};

let debounceTimer;
const debouncedApply = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(apply, 400);
};

const goShow = (id) => {
  router.visit(route('admin.partners.show', id));
};

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  suspended: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  terminated: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

const tierBadge = (t) => ({
  bronze: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
  silver: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
  gold: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  platinum: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
}[t] || 'bg-gray-100 text-gray-700');

const tierIcon = (t) => ({
  bronze: '🥉', silver: '🥈', gold: '🥇', platinum: '💎',
}[t] || '');
</script>
