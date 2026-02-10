<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[800px]">
      <thead>
        <tr class="border-b border-slate-200">
          <th class="text-left py-4 px-4 font-semibold text-slate-900 w-1/3">{{ t.table.feature_col }}</th>
          <th
            v-for="plan in plans"
            :key="plan.id"
            class="text-center py-4 px-4 font-semibold"
            :class="{
              'text-indigo-600': plan.id === 'business',
              'text-slate-900': plan.id !== 'business'
            }"
          >
            <div class="flex flex-col items-center">
              <span>{{ plan.name }}</span>
              <span class="text-sm font-normal text-slate-500 mt-1">{{ formatPrice(plan.monthlyPrice) }} {{ t.table.price_suffix }}</span>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- Section: Asosiy limitlar -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            {{ t.table.sections.basic_limits }}
          </td>
        </tr>
        <tr v-for="feature in basicLimits" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Bot va kanallar -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-rose-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            {{ t.table.sections.bot_channels }}
          </td>
        </tr>
        <tr v-for="feature in botChannels" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: AI imkoniyatlari -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-violet-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            {{ t.table.sections.ai_features }}
          </td>
        </tr>
        <tr v-for="feature in aiFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Qo'shimcha funksiyalar -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
              </svg>
            </div>
            {{ t.table.sections.additional }}
          </td>
        </tr>
        <tr v-for="feature in additionalFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Barcha tariflarda mavjud -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            {{ t.table.sections.included_all }}
          </td>
        </tr>
        <tr v-for="feature in includedInAll" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Texnik yordam -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            {{ t.table.sections.support }}
          </td>
        </tr>
        <tr v-for="feature in supportFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { h, computed } from 'vue';
import { useLandingLocale } from '@/i18n/landing/locale';
import translations from '@/i18n/landing/pricing';

const { locale, t } = useLandingLocale(translations);

// Feature Value Component using render function (Vue 3 compatible)
const FeatureValue = {
  props: {
    value: [Boolean, String, Number],
    highlight: Boolean
  },
  setup(props) {
    return () => {
      if (props.value === true) {
        return h('span', {
          class: ['inline-flex items-center justify-center w-6 h-6 rounded-full', props.highlight ? 'bg-indigo-100' : 'bg-green-100']
        }, [
          h('svg', {
            class: ['w-4 h-4', props.highlight ? 'text-indigo-600' : 'text-green-600'],
            fill: 'currentColor',
            viewBox: '0 0 20 20'
          }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z',
              'clip-rule': 'evenodd'
            })
          ])
        ]);
      } else if (props.value === false) {
        return h('span', {
          class: 'inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100'
        }, [
          h('svg', {
            class: 'w-4 h-4 text-slate-400',
            fill: 'currentColor',
            viewBox: '0 0 20 20'
          }, [
            h('path', {
              'fill-rule': 'evenodd',
              d: 'M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z',
              'clip-rule': 'evenodd'
            })
          ])
        ]);
      } else {
        return h('span', {
          class: ['text-sm font-medium', props.highlight ? 'text-indigo-600' : 'text-slate-700']
        }, props.value);
      }
    };
  }
};

const props = defineProps({
  plans: {
    type: Array,
    required: true
  }
});

const formatPrice = (price) => {
  return price.toLocaleString('uz-UZ');
};

// Computed data arrays using translations
const v = computed(() => t.value.table.values);
const tb = computed(() => t.value.table);

// Asosiy limitlar - from PlanSeeder
const basicLimits = computed(() => [
  { name: tb.value.basic.users, start: v.value.pcs_2, standard: v.value.pcs_5, business: v.value.pcs_10, premium: v.value.pcs_15 },
  { name: tb.value.basic.branches, start: v.value.pcs_1, standard: v.value.pcs_1, business: v.value.pcs_2, premium: v.value.pcs_5 },
  { name: tb.value.basic.monthly_leads, start: v.value.leads_500, standard: v.value.leads_2000, business: v.value.leads_10000, premium: v.value.unlimited },
  { name: tb.value.basic.storage, start: v.value.storage_500mb, standard: v.value.storage_1gb, business: v.value.storage_5gb, premium: v.value.storage_50gb },
]);

// Bot va kanallar - from PlanSeeder
const botChannels = computed(() => [
  { name: tb.value.bots.instagram_accounts, start: v.value.pcs_1, standard: v.value.pcs_2, business: v.value.pcs_3, premium: v.value.pcs_10 },
  { name: tb.value.bots.chatbot_channels, start: v.value.pcs_2, standard: v.value.pcs_3, business: v.value.pcs_5, premium: v.value.pcs_20 },
  { name: tb.value.bots.telegram_bots, start: v.value.pcs_2, standard: v.value.pcs_3, business: v.value.pcs_5, premium: v.value.pcs_20 },
]);

// AI imkoniyatlari - from PlanSeeder
const aiFeatures = computed(() => [
  { name: tb.value.ai.call_analysis, start: v.value.min_60, standard: v.value.min_150, business: v.value.min_400, premium: v.value.min_1000 },
  { name: tb.value.ai.extra_minute_price, start: v.value.price_500, standard: v.value.price_450, business: v.value.price_400, premium: v.value.price_300 },
  { name: tb.value.ai.ai_requests, start: v.value.requests_500, standard: v.value.requests_2000, business: v.value.requests_10000, premium: v.value.requests_50000 },
]);

// Qo'shimcha funksiyalar - from PlanSeeder features
const additionalFeatures = computed(() => [
  { name: tb.value.additional.hr_tasks, start: false, standard: true, business: true, premium: true },
  { name: tb.value.additional.hr_bot, start: false, standard: false, business: true, premium: true },
  { name: tb.value.additional.anti_fraud, start: false, standard: false, business: false, premium: true },
]);

// Barcha tariflarda mavjud (cheklovsiz)
const includedInAll = computed(() => [
  { name: tb.value.included.instagram_facebook, start: true, standard: true, business: true, premium: true },
  { name: tb.value.included.flow_builder, start: true, standard: true, business: true, premium: true },
  { name: tb.value.included.marketing_roi, start: true, standard: true, business: true, premium: true },
  { name: tb.value.included.crm, start: true, standard: true, business: true, premium: true },
  { name: tb.value.included.kanban, start: true, standard: true, business: true, premium: true },
]);

// Texnik yordam
const supportFeatures = computed(() => [
  { name: tb.value.support.telegram, start: true, standard: true, business: true, premium: true },
  { name: tb.value.support.response_time, start: v.value.hours_24, standard: v.value.hours_12, business: v.value.hours_8, premium: v.value.hours_2 },
  { name: tb.value.support.video_guides, start: true, standard: true, business: true, premium: true },
  { name: tb.value.support.onboarding, start: false, standard: true, business: true, premium: v.value.personal },
  { name: tb.value.support.personal_manager, start: false, standard: false, business: false, premium: true },
]);
</script>
