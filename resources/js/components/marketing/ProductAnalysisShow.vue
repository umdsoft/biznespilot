<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div class="flex items-center gap-4">
        <Link :href="getHref('/product-analysis')" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
          <ArrowLeftIcon class="w-5 h-5" />
        </Link>
        <div>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ product.name }}</h2>
          <div class="flex items-center gap-2 mt-1">
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ product.category }}</span>
            <span :class="statusClass" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-md">
              <span :class="statusDotClass" class="w-1.5 h-1.5 rounded-full"></span>
              {{ statusLabel }}
            </span>
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400">
              {{ lifeCycleLabel }}
            </span>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button @click="showEditModal = true" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
          <PencilSquareIcon class="w-4 h-4 mr-1.5" />
          Tahrirlash
        </button>
      </div>
    </div>

    <!-- Score Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <ScoreCard label="USP bali" :value="scores.usp" suffix="%" :color="getScoreColor(scores.usp)" />
      <ScoreCard label="Sog'lik" :value="scores.health" suffix="%" :color="getScoreColor(scores.health)" />
      <ScoreCard label="Margin" :value="scores.margin" suffix="%" :color="scores.margin >= 30 ? 'emerald' : scores.margin >= 15 ? 'amber' : 'red'" />
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Narx</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatPrice(product.price) }}</p>
        <p v-if="product.cost" class="text-[10px] text-gray-400">Tannarx: {{ formatPrice(product.cost) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Raqobatchilar</p>
        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ competitors.length }}</p>
        <p class="text-[10px] text-gray-400">bog'langan</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="border-b border-gray-200 dark:border-gray-700 px-5">
        <nav class="flex gap-6 -mb-px">
          <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
            'py-3 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
          ]">{{ tab.label }}</button>
        </nav>
      </div>

      <div class="p-5">
        <!-- Umumiy Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Asosiy ma'lumotlar -->
            <div class="space-y-4">
              <div v-if="product.short_desc" class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tavsif</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ product.short_desc }}</p>
              </div>
              <div v-if="product.target_audience" class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1">Maqsadli auditoriya</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ product.target_audience }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
                  <p class="text-xs text-gray-500 mb-0.5">Narxlash modeli</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ pricingModelLabel }}</p>
                </div>
                <div class="p-3 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
                  <p class="text-xs text-gray-500 mb-0.5">Hayot sikli</p>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ lifeCycleLabel }}</p>
                </div>
              </div>
            </div>

            <!-- Kuchli/zaif tomonlar -->
            <div class="space-y-4">
              <div v-if="product.advantages" class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg">
                <p class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 mb-2">Afzalliklar ({{ product.advantages_count }})</p>
                <ul class="space-y-1.5">
                  <li v-for="(adv, i) in advantagesList" :key="i" class="flex items-start gap-2 text-sm text-gray-900 dark:text-white">
                    <CheckCircleIcon class="w-4 h-4 text-emerald-500 mt-0.5 flex-shrink-0" />
                    {{ adv }}
                  </li>
                </ul>
              </div>
              <div v-if="product.weaknesses" class="p-4 bg-red-50 dark:bg-red-900/10 rounded-lg">
                <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-2">Kamchiliklar ({{ product.weaknesses_count }})</p>
                <ul class="space-y-1.5">
                  <li v-for="(w, i) in weaknessesList" :key="i" class="flex items-start gap-2 text-sm text-gray-900 dark:text-white">
                    <XCircleIcon class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
                    {{ w }}
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Xususiyatlar -->
          <div v-if="product.features && product.features.length > 0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Xususiyatlar</p>
            <div class="flex flex-wrap gap-2">
              <span v-for="(feat, i) in product.features" :key="i" class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 rounded-lg border border-indigo-200/50 dark:border-indigo-500/20">
                {{ feat }}
              </span>
            </div>
          </div>
        </div>

        <!-- Raqobatchilar Tab -->
        <div v-if="activeTab === 'competitors'" class="space-y-4">
          <div v-if="competitors.length > 0">
            <div v-for="comp in competitors" :key="comp.mapping_id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/30 rounded-lg mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                  <BuildingStorefrontIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ comp.product_name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ comp.competitor_name }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="text-right">
                  <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatPrice(comp.current_price) }}</p>
                  <p :class="comp.price_gap_percent > 0 ? 'text-red-500' : 'text-emerald-500'" class="text-xs font-medium">
                    {{ comp.price_gap_percent > 0 ? '+' : '' }}{{ comp.price_gap_percent }}%
                  </p>
                </div>
                <div class="w-12 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                  <div class="h-full bg-indigo-500 rounded-full" :style="{ width: comp.similarity_score + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <BuildingStorefrontIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400">Raqobatchi mahsulotlari bog'lanmagan</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Raqobatchilar bo'limidan mahsulotlarni bog'lang</p>
          </div>
        </div>

        <!-- Tavsiyalar Tab -->
        <div v-if="activeTab === 'insights'" class="space-y-3">
          <div v-if="insights.length > 0">
            <div v-for="insight in insights" :key="insight.id" :class="[
              'p-4 rounded-lg border-l-4',
              insight.priority === 'high' ? 'bg-red-50 dark:bg-red-900/10 border-red-500' :
              insight.priority === 'medium' ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-500' :
              'bg-blue-50 dark:bg-blue-900/10 border-blue-500'
            ]">
              <div class="flex items-start justify-between">
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ insight.title }}</p>
                  <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ insight.description }}</p>
                </div>
                <span :class="[
                  'px-2 py-0.5 text-[10px] font-bold rounded uppercase',
                  insight.priority === 'high' ? 'bg-red-100 text-red-700' :
                  insight.priority === 'medium' ? 'bg-amber-100 text-amber-700' :
                  'bg-blue-100 text-blue-700'
                ]">{{ insight.priority === 'high' ? 'Muhim' : insight.priority === 'medium' ? "O'rta" : 'Past' }}</span>
              </div>
              <p v-if="insight.action_text" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium mt-2">{{ insight.action_text }}</p>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <LightBulbIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-500">Hozircha tavsiyalar yo'q</p>
          </div>
        </div>

        <!-- AI Tab -->
        <div v-if="activeTab === 'ai'" class="space-y-4">
          <div v-if="ai_analysis" class="space-y-4">
            <div v-for="(value, key) in ai_analysis" :key="key" class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-lg">
              <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-1 uppercase">{{ key }}</p>
              <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ value }}</p>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <SparklesIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">AI tahlil hali o'tkazilmagan</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">Tez orada bu funksiya ishga tushadi</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
  ArrowLeftIcon, PencilSquareIcon, CheckCircleIcon, XCircleIcon,
  LightBulbIcon, SparklesIcon, BuildingStorefrontIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  panelType: { type: String, required: true },
  product: { type: Object, required: true },
  sales: { type: Object, default: () => ({}) },
  competitors: { type: Array, default: () => [] },
  insights: { type: Array, default: () => [] },
  scores: { type: Object, default: () => ({}) },
  ai_analysis: { type: Object, default: null },
  ai_stale: { type: Boolean, default: true },
});

const getHref = (path) => (props.panelType === 'business' ? '/business' : '/marketing') + path;

const activeTab = ref('overview');
const showEditModal = ref(false);

const tabs = [
  { id: 'overview', label: 'Umumiy' },
  { id: 'competitors', label: 'Raqobatchilar' },
  { id: 'insights', label: 'Tavsiyalar' },
  { id: 'ai', label: 'AI Tahlil' },
];

const advantagesList = computed(() => props.product.advantages ? props.product.advantages.split('\n').filter(Boolean) : []);
const weaknessesList = computed(() => props.product.weaknesses ? props.product.weaknesses.split('\n').filter(Boolean) : []);

const statusLabels = { active: 'Aktiv', planned: 'Rejalashtirilgan', paused: "To'xtatilgan", none: "Yo'q" };
const statusLabel = computed(() => statusLabels[props.product.marketing_status] || "—");
const statusClass = computed(() => ({
  active: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50',
  planned: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200/50',
  paused: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200/50',
  none: 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200/50',
})[props.product.marketing_status] || '');
const statusDotClass = computed(() => ({
  active: 'bg-emerald-500', planned: 'bg-blue-500', paused: 'bg-gray-400', none: 'bg-red-500',
})[props.product.marketing_status] || 'bg-gray-400');

const lifeCycleLabels = { introduction: 'Kiritish', growth: "O'sish", maturity: 'Yetuklik', decline: 'Pasayish' };
const lifeCycleLabel = computed(() => lifeCycleLabels[props.product.life_cycle_stage] || "O'sish");

const pricingModelLabels = { one_time: 'Bir martalik', subscription: 'Obuna', freemium: 'Freemium' };
const pricingModelLabel = computed(() => pricingModelLabels[props.product.pricing_model] || 'Bir martalik');

const formatPrice = (price) => price ? new Intl.NumberFormat('uz-UZ').format(price) + " so'm" : "—";
const getScoreColor = (score) => score >= 70 ? 'emerald' : score >= 40 ? 'amber' : 'red';

const colorMap = {
  emerald: { text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-500' },
  amber: { text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-500' },
  red: { text: 'text-red-600 dark:text-red-400', bg: 'bg-red-500' },
  blue: { text: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-500' },
};

// ScoreCard inline component — static Tailwind classes
const ScoreCard = {
  props: ['label', 'value', 'suffix', 'color'],
  setup(props) {
    const colors = colorMap[props.color] || colorMap.blue;
    return { colors };
  },
  template: `
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ label }}</p>
      <div class="flex items-end gap-1">
        <p class="text-lg font-bold" :class="colors.text">{{ value || 0 }}</p>
        <span class="text-xs text-gray-400 mb-0.5">{{ suffix }}</span>
      </div>
      <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-2">
        <div class="h-full rounded-full transition-all duration-500" :class="colors.bg" :style="{ width: Math.min(value || 0, 100) + '%' }"></div>
      </div>
    </div>
  `,
};
</script>
