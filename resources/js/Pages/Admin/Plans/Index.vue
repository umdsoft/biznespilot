<template>
  <AdminLayout title="Tarif Rejalari">
    <div class="max-w-[1800px] mx-auto">
      <!-- Premium Header -->
      <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 rounded-2xl mb-6">
        <div class="absolute inset-0 bg-grid-white/5"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl translate-y-1/2 -translate-x-1/2"></div>

        <div class="relative px-6 py-8 sm:px-8">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
              <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-white/10 backdrop-blur-sm rounded-xl flex items-center justify-center">
                  <svg class="w-5 h-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                </div>
                <span class="text-blue-300 text-sm font-medium">Tarif Boshqaruvi</span>
              </div>
              <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">Tarif Rejalari</h1>
              <p class="text-blue-200/70 text-sm max-w-md">
                Platformadagi barcha obuna rejalarini boshqaring, narxlar va limitlarni sozlang
              </p>
            </div>

            <Link
              href="/dashboard/plans/create"
              class="inline-flex items-center gap-2 px-5 py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-100 transition-all shadow-lg shadow-black/20 hover:shadow-xl hover:-translate-y-0.5"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Yangi tarif qo'shish
            </Link>
          </div>

          <!-- Mini Stats -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-8">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
              <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
              <div class="text-xs text-blue-200/70">Jami tariflar</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
              <div class="text-2xl font-bold text-emerald-400">{{ stats.active }}</div>
              <div class="text-xs text-blue-200/70">Faol tariflar</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
              <div class="text-2xl font-bold text-amber-400">{{ stats.inactive }}</div>
              <div class="text-xs text-blue-200/70">Nofaol</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
              <div class="text-2xl font-bold text-blue-400">{{ stats.total_subscribers }}</div>
              <div class="text-xs text-blue-200/70">Jami obunalar</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Plans Table -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        <!-- Table Header -->
        <div class="hidden lg:grid lg:grid-cols-12 gap-4 px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
          <div class="col-span-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarif</div>
          <div class="col-span-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Narx</div>
          <div class="col-span-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Asosiy Limitlar</div>
          <div class="col-span-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Xususiyatlar</div>
          <div class="col-span-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Amallar</div>
        </div>

        <!-- Plan Rows -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <div
            v-for="plan in plans"
            :key="plan.id"
            class="group relative"
            :class="{ 'bg-gradient-to-r from-blue-50/50 to-indigo-50/50 dark:from-blue-900/10 dark:to-indigo-900/10': plan.slug === 'business' }"
          >
            <!-- Popular indicator line -->
            <div v-if="plan.slug === 'business'" class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-indigo-500"></div>

            <div class="lg:grid lg:grid-cols-12 gap-4 px-6 py-5 items-center">
              <!-- Plan Name & Description -->
              <div class="col-span-3 mb-4 lg:mb-0">
                <div class="flex items-start gap-3">
                  <div
                    class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                    :class="getPlanColor(plan.slug).bg"
                  >
                    <component :is="getPlanIcon(plan.slug)" class="w-6 h-6" :class="getPlanColor(plan.slug).icon" />
                  </div>
                  <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ plan.name }}</h3>
                      <span
                        v-if="plan.slug === 'business'"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full"
                      >
                        Mashhur
                      </span>
                      <span
                        v-if="plan.slug === 'premium'"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-full"
                      >
                        VIP
                      </span>
                      <span
                        :class="[
                          'px-2 py-0.5 text-[10px] font-semibold rounded-full',
                          plan.is_active
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                        ]"
                      >
                        {{ plan.is_active ? 'Faol' : 'Nofaol' }}
                      </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-1">
                      {{ plan.description }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Pricing -->
              <div class="col-span-2 mb-4 lg:mb-0">
                <div class="lg:hidden text-xs text-gray-500 dark:text-gray-400 mb-1">Narx:</div>
                <div class="flex flex-col">
                  <div class="flex items-baseline gap-1">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                      {{ formatShortPrice(plan.price_monthly) }}
                    </span>
                    <span class="text-xs text-gray-400">/oy</span>
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ formatShortPrice(plan.price_yearly) }}/yil
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium ml-1">-{{ calculateSavings(plan) }}%</span>
                  </div>
                </div>
              </div>

              <!-- Key Limits -->
              <div class="col-span-3 mb-4 lg:mb-0">
                <div class="lg:hidden text-xs text-gray-500 dark:text-gray-400 mb-2">Limitlar:</div>
                <div class="flex flex-wrap gap-2">
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ formatLimit(plan.limits?.users) }}</span>
                  </div>
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">{{ formatLimit(plan.limits?.branches) }} fil</span>
                  </div>
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ formatNumber(plan.limits?.monthly_leads) }} lid</span>
                  </div>
                  <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span class="text-xs font-semibold text-orange-700 dark:text-orange-300">{{ formatLimit(plan.limits?.ai_call_minutes) }} daq</span>
                  </div>
                </div>
              </div>

              <!-- Features -->
              <div class="col-span-2 mb-4 lg:mb-0">
                <div class="lg:hidden text-xs text-gray-500 dark:text-gray-400 mb-2">Xususiyatlar:</div>
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="(enabled, key) in plan.features"
                    :key="key"
                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium"
                    :class="enabled
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                      : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500'"
                  >
                    <svg v-if="enabled" class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    {{ getFeatureLabel(key) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="col-span-2">
                <div class="flex items-center justify-end gap-2">
                  <!-- Subscribers count -->
                  <div class="hidden sm:flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg mr-2">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ plan.active_subscriptions_count }}</span>
                  </div>

                  <!-- Toggle Status -->
                  <button
                    @click="toggleStatus(plan)"
                    class="p-2 rounded-lg transition-all"
                    :class="plan.is_active
                      ? 'text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-900/30'
                      : 'text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'"
                    :title="plan.is_active ? 'O\'chirish' : 'Faollashtirish'"
                  >
                    <svg v-if="plan.is_active" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>

                  <!-- Edit -->
                  <Link
                    :href="`/dashboard/plans/${plan.id}/edit`"
                    class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-all"
                    title="Tahrirlash"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </Link>

                  <!-- Delete -->
                  <button
                    v-if="plan.active_subscriptions_count === 0"
                    @click="deletePlan(plan)"
                    class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-all"
                    title="O'chirish"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                  <div
                    v-else
                    class="p-2 text-gray-300 dark:text-gray-600 cursor-not-allowed"
                    title="Faol obunalar mavjud"
                  >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="plans.length === 0" class="text-center py-16">
          <div class="w-20 h-20 mx-auto bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Tarif rejalari topilmadi</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
            Mijozlaringiz uchun tarif rejalarini yarating va biznesingizni rivojlantiring
          </p>
          <Link
            href="/dashboard/plans/create"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Birinchi tarifni yarating
          </Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { h } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
  plans: Array,
  stats: Object,
  limitConfig: Object,
  featureConfig: Object,
});

const getPlanColor = (slug) => {
  const colors = {
    start: { bg: 'bg-blue-100 dark:bg-blue-900/30', icon: 'text-blue-600 dark:text-blue-400' },
    standard: { bg: 'bg-emerald-100 dark:bg-emerald-900/30', icon: 'text-emerald-600 dark:text-emerald-400' },
    business: { bg: 'bg-indigo-100 dark:bg-indigo-900/30', icon: 'text-indigo-600 dark:text-indigo-400' },
    premium: { bg: 'bg-amber-100 dark:bg-amber-900/30', icon: 'text-amber-600 dark:text-amber-400' },
    enterprise: { bg: 'bg-purple-100 dark:bg-purple-900/30', icon: 'text-purple-600 dark:text-purple-400' },
  };
  return colors[slug] || { bg: 'bg-gray-100 dark:bg-gray-700', icon: 'text-gray-600 dark:text-gray-400' };
};

const getPlanIcon = (slug) => {
  const icons = {
    start: {
      render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 10V3L4 14h7v7l9-11h-7z' })
      ])
    },
    standard: {
      render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' })
      ])
    },
    business: {
      render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
      ])
    },
    premium: {
      render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' })
      ])
    },
    enterprise: {
      render: () => h('svg', { fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9' })
      ])
    },
  };
  return icons[slug] || icons.start;
};

const formatShortPrice = (price) => {
  if (!price || price === 0) return 'Bepul';
  if (price >= 1000000) return (price / 1000000).toFixed(1).replace('.0', '') + 'M';
  if (price >= 1000) return (price / 1000).toFixed(0) + 'K';
  return price.toLocaleString('uz-UZ');
};

const formatLimit = (value) => {
  if (value === null || value === undefined) return '∞';
  if (value === 0) return '0';
  return value.toLocaleString('uz-UZ');
};

const formatNumber = (num) => {
  if (num === null || num === undefined) return '∞';
  if (num === 0) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(0) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(0) + 'K';
  return num.toLocaleString('uz-UZ');
};

const calculateSavings = (plan) => {
  if (!plan.price_monthly || plan.price_monthly === 0) return 0;
  const yearlyIfMonthly = plan.price_monthly * 12;
  const savings = ((yearlyIfMonthly - plan.price_yearly) / yearlyIfMonthly) * 100;
  return Math.round(savings);
};

const getFeatureLabel = (key) => {
  const labels = {
    flow_builder: 'Flow',
    marketing_roi: 'ROI',
    hr_tasks: 'Tasks',
    hr_bot: 'HR',
    anti_fraud: 'Fraud',
    api_access: 'API',
    amocrm: 'Amo',
    instagram: 'IG',
  };
  return labels[key] || key;
};

const toggleStatus = async (plan) => {
  try {
    await axios.post(`/dashboard/plans/${plan.id}/toggle-status`);
    router.reload();
  } catch (error) {
    alert('Xatolik yuz berdi');
  }
};

const deletePlan = async (plan) => {
  if (!confirm(`"${plan.name}" tarifini o'chirishni tasdiqlaysizmi?`)) return;

  try {
    await axios.delete(`/dashboard/plans/${plan.id}`);
    router.reload();
  } catch (error) {
    alert(error.response?.data?.message || 'Xatolik yuz berdi');
  }
};
</script>

<style scoped>
.bg-grid-white\/5 {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(255 255 255 / 0.05)'%3e%3cpath d='M0 .5H31.5V32'/%3e%3c/svg%3e");
}
</style>
