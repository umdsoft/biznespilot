<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[800px]">
      <thead>
        <tr class="border-b border-slate-200">
          <th class="text-left py-4 px-4 font-semibold text-slate-900 w-1/3">Imkoniyat</th>
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
              <span class="text-sm font-normal text-slate-500 mt-1">{{ formatPrice(plan.monthlyPrice) }} so'm</span>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <!-- Section: Instagram Bot -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-pink-500 to-rose-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
            </div>
            Instagram & Facebook Bot
          </td>
        </tr>
        <tr v-for="feature in instagramFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: CRM -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            CRM va Lids Boshqaruvi
          </td>
        </tr>
        <tr v-for="feature in crmFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Marketing -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            Kontent Marketing
          </td>
        </tr>
        <tr v-for="feature in marketingFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Call Center -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
            </div>
            Call Center va AI
          </td>
        </tr>
        <tr v-for="feature in callCenterFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Team -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-violet-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            Jamoa va Boshqaruv
          </td>
        </tr>
        <tr v-for="feature in teamFeatures" :key="feature.name" class="border-b border-slate-100 hover:bg-slate-50">
          <td class="py-3 px-4 text-slate-600">{{ feature.name }}</td>
          <td v-for="plan in plans" :key="plan.id" class="py-3 px-4 text-center">
            <FeatureValue :value="feature[plan.id]" :highlight="plan.id === 'business'" />
          </td>
        </tr>

        <!-- Section: Support -->
        <tr class="bg-slate-50">
          <td colspan="5" class="py-3 px-4 font-bold text-slate-800 flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            Texnik Yordam
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
import { computed, h } from 'vue';

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

// Instagram Bot Features
const instagramFeatures = [
  { name: 'DM avto-javob', start: true, standard: true, business: true, premium: true },
  { name: 'Tugmali menyular', start: '3 ta', standard: '7 ta', business: '10 ta', premium: 'Cheksiz' },
  { name: 'Comment avto-javob', start: false, standard: true, business: true, premium: true },
  { name: 'Story reply/mention', start: false, standard: true, business: true, premium: true },
  { name: 'Intent aniqlash', start: '3 ta', standard: '5 ta', business: '7 ta', premium: 'Custom' },
  { name: 'Media yuborish', start: true, standard: true, business: true, premium: true },
  { name: 'Human handoff', start: true, standard: true, business: true, premium: true },
  { name: 'Instagram accountlar soni', start: '1', standard: '2', business: '3', premium: '10' },
  { name: 'Oylik xabarlar limiti', start: '1,000', standard: '5,000', business: '10,000', premium: 'Cheksiz' },
];

// CRM Features
const crmFeatures = [
  { name: 'Lidlar bazasi', start: true, standard: true, business: true, premium: true },
  { name: 'Kanban doska', start: true, standard: true, business: true, premium: true },
  { name: 'Custom pipeline bosqichlari', start: '3 ta', standard: '7 ta', business: '10 ta', premium: 'Cheksiz' },
  { name: 'Lead scoring', start: false, standard: true, business: true, premium: true },
  { name: 'MQL/SQL qualification', start: false, standard: false, business: true, premium: true },
  { name: 'UTM tracking', start: false, standard: true, business: true, premium: true },
  { name: 'Yo\'qotilgan lidlar tahlili', start: false, standard: false, business: true, premium: true },
  { name: 'Lead import/export', start: false, standard: true, business: true, premium: true },
  { name: 'Lidlar soni limiti', start: '500', standard: '2,000', business: '5,000', premium: 'Cheksiz' },
];

// Marketing Features
const marketingFeatures = [
  { name: 'Kontent kalendar', start: false, standard: true, business: true, premium: true },
  { name: 'Multi-kanal rejalashtirish', start: false, standard: '2 kanal', business: '3 kanal', premium: 'Barcha' },
  { name: 'Instagram statistika sync', start: false, standard: true, business: true, premium: true },
  { name: 'Engagement rate hisoblash', start: false, standard: true, business: true, premium: true },
  { name: 'Top performers aniqlash', start: false, standard: false, business: true, premium: true },
  { name: 'AI caption tavsiyalari', start: false, standard: false, business: false, premium: true },
  { name: 'Export (PDF/Excel)', start: false, standard: true, business: true, premium: true },
];

// Call Center Features
const callCenterFeatures = [
  { name: 'Qo\'ng\'iroqlar tarixi', start: false, standard: false, business: true, premium: true },
  { name: 'Audio yozib olish', start: false, standard: false, business: false, premium: true },
  { name: 'Speech-to-Text (transkripsiya)', start: false, standard: false, business: false, premium: '50 ta/oy' },
  { name: 'AI qo\'ng\'iroq tahlili', start: false, standard: false, business: false, premium: '50 ta/oy' },
  { name: 'Operator baholash (0-100)', start: false, standard: false, business: false, premium: true },
  { name: 'Tavsiyalar generatsiyasi', start: false, standard: false, business: false, premium: true },
  { name: 'Qo\'shimcha tahlil (add-on)', start: false, standard: false, business: false, premium: '1,000 so\'m/ta' },
];

// Team Features
const teamFeatures = [
  { name: 'Foydalanuvchilar soni', start: '2', standard: '5', business: '10', premium: 'Cheksiz' },
  { name: 'Rollar (Admin/Manager/Operator)', start: 'Asosiy', standard: true, business: true, premium: 'Custom' },
  { name: 'Filiallar boshqaruvi', start: false, standard: '2 ta', business: '3 ta', premium: 'Cheksiz' },
  { name: 'Activity log', start: false, standard: true, business: true, premium: true },
  { name: 'API access', start: false, standard: false, business: false, premium: true },
];

// Support Features
const supportFeatures = [
  { name: 'Telegram support', start: true, standard: true, business: true, premium: true },
  { name: 'Javob vaqti', start: '24 soat', standard: '12 soat', business: '8 soat', premium: '2 soat' },
  { name: 'Video qo\'llanmalar', start: true, standard: true, business: true, premium: true },
  { name: 'Onboarding yordam', start: false, standard: true, business: true, premium: 'Shaxsiy' },
  { name: 'Dedicated manager', start: false, standard: false, business: false, premium: true },
];
</script>
