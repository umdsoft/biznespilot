<template>
  <div class="flex flex-col lg:flex-row gap-0 min-h-[calc(100vh-220px)]">

    <!-- ═══ CHAP PANEL: VORONKA ═══ -->
    <div class="lg:w-[45%] p-5 border-r border-gray-100 dark:border-gray-700">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-5">📊 Sotuv Voronkasi</h3>

      <!-- Funnel bars -->
      <div class="space-y-1.5">
        <div
          v-for="(stage, index) in funnelData"
          :key="stage.slug"
          class="relative mx-auto transition-all duration-300"
          :style="{ width: stage.widthPercent + '%', minWidth: '160px' }"
        >
          <div
            class="w-full py-2.5 px-3 rounded-lg flex items-center justify-between relative overflow-hidden"
            :style="{ backgroundColor: stage.color + '18', borderLeft: '3px solid ' + stage.color }"
          >
            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 z-10">{{ stage.name }}</span>
            <div class="flex items-center gap-2 z-10">
              <span class="text-sm font-bold" :style="{ color: stage.color }">{{ stage.count }}</span>
              <span class="text-[10px] text-gray-400">{{ formatCurrency(stage.value) }}</span>
              <span v-if="index > 0" class="text-[10px] font-semibold px-1 py-0.5 rounded"
                :class="stage.conversionRate >= 50 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-300'">
                {{ stage.conversionRate }}%
              </span>
            </div>
            <div class="absolute inset-y-0 left-0 opacity-10 rounded-lg" :style="{ width: stage.fillPercent + '%', backgroundColor: stage.color }"></div>
          </div>
        </div>
      </div>

      <!-- Summary row -->
      <div class="grid grid-cols-4 gap-2 mt-5">
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2.5 text-center">
          <p class="text-lg font-bold text-gray-900 dark:text-white">{{ totalLeads }}</p>
          <p class="text-[10px] text-gray-500">Jami</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2.5 text-center">
          <p class="text-lg font-bold text-green-600">{{ wonCount }}</p>
          <p class="text-[10px] text-gray-500">Yutilgan</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2.5 text-center">
          <p class="text-lg font-bold text-blue-600">{{ overallConversion }}%</p>
          <p class="text-[10px] text-gray-500">Konversiya</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2.5 text-center">
          <p class="text-lg font-bold text-purple-600">{{ formatCurrency(totalPipelineValue) }}</p>
          <p class="text-[10px] text-gray-500">Pipeline</p>
        </div>
      </div>

      <!-- Conversion chain -->
      <div class="mt-5 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-3 uppercase tracking-wide">Bosqichlar konversiyasi</p>
        <div class="space-y-2">
          <div v-for="(conv, i) in stageConversions" :key="i"
            class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg px-3 py-2.5 border border-gray-100 dark:border-gray-700">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ conv.from }}</span>
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ conv.to }}</span>
            <span class="ml-auto text-sm font-bold px-2 py-0.5 rounded"
              :class="conv.rate >= 50 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : conv.rate >= 30 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'">
              {{ conv.rate }}%
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ O'NG PANEL: INSIGHTS ═══ -->
    <div class="lg:w-[55%] p-5 overflow-y-auto space-y-5">
      <h3 class="text-base font-semibold text-gray-900 dark:text-white">🔍 Sotuv Insaydlari</h3>

      <!-- 1. Eng ko'p lid yo'qotilgan bosqich -->
      <div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-red-500 text-lg">⚠️</span>
          <h4 class="text-sm font-semibold text-red-700 dark:text-red-400">Eng ko'p lid yo'qotilgan joy</h4>
        </div>
        <div v-if="biggestDropoff" class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-red-600">{{ biggestDropoff.dropped }}</span>
            <span class="text-sm text-red-600/80">ta lid</span>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              {{ biggestDropoff.from }} → {{ biggestDropoff.to }}
            </span>
            <span class="text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 px-1.5 py-0.5 rounded font-semibold">
              -{{ biggestDropoff.dropPercent }}%
            </span>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400">
            Bu bosqichda {{ biggestDropoff.dropped }} ta lid keyingi bosqichga o'tmagan.
            {{ biggestDropoff.dropPercent >= 50 ? 'Bu jiddiy muammo — tezkor harakat kerak.' : 'Yaxshilash imkoniyati bor.' }}
          </p>
        </div>
        <p v-else class="text-sm text-gray-500">Ma'lumot yetarli emas</p>
      </div>

      <!-- 2. Eng tez sotuv bo'layotgan bosqich -->
      <div class="bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-900/30 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-green-500 text-lg">🚀</span>
          <h4 class="text-sm font-semibold text-green-700 dark:text-green-400">Eng samarali bosqich</h4>
        </div>
        <div v-if="bestConversion" class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-green-600">{{ bestConversion.rate }}%</span>
            <span class="text-sm text-green-600/80">konversiya</span>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              {{ bestConversion.from }} → {{ bestConversion.to }}
            </span>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400">
            Bu bosqichda lidlar eng tez keyingi bosqichga o'tmoqda. Shu strategiyani boshqa bosqichlarga ham qo'llang.
          </p>
        </div>
      </div>

      <!-- 3. Sifatsiz lidlar tahlili -->
      <div class="bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-orange-500 text-lg">📉</span>
          <h4 class="text-sm font-semibold text-orange-700 dark:text-orange-400">Sifatsiz lidlar tahlili</h4>
        </div>
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <span class="text-2xl font-bold text-orange-600">{{ lostCount }}</span>
            <span class="text-sm text-orange-600/80">ta lid yo'qotilgan</span>
            <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 px-1.5 py-0.5 rounded font-semibold">
              {{ lostPercent }}% umumiy
            </span>
          </div>
          <!-- Lost from which stage -->
          <div v-if="lostByPreviousStage.length" class="space-y-1.5 mt-2">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Qaysi bosqichdan rad etilgan:</p>
            <div v-for="item in lostByPreviousStage" :key="item.stage" class="flex items-center gap-2">
              <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-5 overflow-hidden">
                <div class="h-full rounded-full flex items-center px-2"
                  :style="{ width: item.percent + '%', backgroundColor: item.color + '40', minWidth: '40px' }">
                  <span class="text-[10px] font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ item.stage }}</span>
                </div>
              </div>
              <span class="text-xs font-bold text-gray-700 dark:text-gray-300 w-8 text-right">{{ item.count }}</span>
              <span class="text-[10px] text-gray-400 w-8">{{ item.percent }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Bosqichdagi kutish vaqti -->
      <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
          <span class="text-blue-500 text-lg">⏱️</span>
          <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-400">Bosqichlardagi o'rtacha muddat</h4>
        </div>
        <div class="space-y-1.5">
          <div v-for="stage in stageAging" :key="stage.name" class="flex items-center gap-2">
            <span class="text-xs text-gray-600 dark:text-gray-400 w-24 truncate">{{ stage.name }}</span>
            <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-4 overflow-hidden">
              <div class="h-full rounded-full transition-all"
                :style="{ width: stage.barWidth + '%', backgroundColor: stage.barColor }"
              ></div>
            </div>
            <span class="text-xs font-bold w-14 text-right" :style="{ color: stage.barColor }">{{ stage.avgDays }} kun</span>
          </div>
        </div>
        <p v-if="slowestStage" class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          ⚠️ <strong>{{ slowestStage.name }}</strong> bosqichida lidlar eng uzoq kutmoqda ({{ slowestStage.avgDays }} kun). Tezlashtirish kerak.
        </p>
      </div>

      <!-- 5. Tavsiyalar -->
      <div class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-900/30 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-indigo-500 text-lg">💡</span>
          <h4 class="text-sm font-semibold text-indigo-700 dark:text-indigo-400">Tavsiyalar</h4>
        </div>
        <ul class="space-y-2">
          <li v-for="(tip, i) in recommendations" :key="i" class="flex items-start gap-2">
            <span class="text-xs mt-0.5" :class="tip.priority === 'high' ? 'text-red-500' : tip.priority === 'medium' ? 'text-yellow-500' : 'text-blue-500'">●</span>
            <span class="text-xs text-gray-700 dark:text-gray-300">{{ tip.text }}</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stages: { type: Array, default: () => [] },
  columnTotals: { type: Object, default: () => ({}) },
  leads: { type: Array, default: () => [] },
});

const defaultColors = ['#3B82F6', '#6366F1', '#8B5CF6', '#F59E0B', '#EC4899', '#10B981', '#EF4444'];

const getColor = (stage, i) => (stage.color && stage.color.startsWith('#')) ? stage.color : defaultColors[i % defaultColors.length];

// ═══ FUNNEL DATA ═══
const funnelData = computed(() => {
  if (!props.stages.length) return [];
  const maxCount = Math.max(...props.stages.map(s => props.columnTotals[s.value]?.count || 0), 1);

  return props.stages.map((stage, i) => {
    const count = props.columnTotals[stage.value]?.count || 0;
    const value = props.columnTotals[stage.value]?.value || 0;
    const prevCount = i > 0 ? (props.columnTotals[props.stages[i - 1].value]?.count || 1) : count;

    return {
      slug: stage.value,
      name: stage.label,
      count, value,
      color: getColor(stage, i),
      widthPercent: Math.max(25, 100 - (i * (65 / Math.max(props.stages.length - 1, 1)))),
      fillPercent: maxCount > 0 ? Math.round(count / maxCount * 100) : 0,
      conversionRate: i > 0 && prevCount > 0 ? Math.round(count / prevCount * 100) : 100,
    };
  });
});

// ═══ SUMMARY ═══
const totalLeads = computed(() => funnelData.value.reduce((s, d) => s + d.count, 0));
const wonCount = computed(() => (props.columnTotals[props.stages.find(s => s.is_won)?.value]?.count) || 0);
const lostCount = computed(() => (props.columnTotals[props.stages.find(s => s.is_lost)?.value]?.count) || 0);
const overallConversion = computed(() => totalLeads.value > 0 ? Math.round(wonCount.value / totalLeads.value * 100) : 0);
const lostPercent = computed(() => totalLeads.value > 0 ? Math.round(lostCount.value / totalLeads.value * 100) : 0);
const totalPipelineValue = computed(() => props.stages.filter(s => !s.is_won && !s.is_lost).reduce((s, st) => s + (props.columnTotals[st.value]?.value || 0), 0));

// ═══ CONVERSIONS ═══
const stageConversions = computed(() => {
  const result = [];
  for (let i = 0; i < funnelData.value.length - 1; i++) {
    const from = funnelData.value[i], to = funnelData.value[i + 1];
    if (from.count > 0) result.push({ from: from.name, to: to.name, rate: Math.round(to.count / from.count * 100) });
  }
  return result;
});

// ═══ INSIGHTS: BIGGEST DROPOFF ═══
const biggestDropoff = computed(() => {
  let worst = null;
  for (let i = 0; i < funnelData.value.length - 1; i++) {
    const from = funnelData.value[i], to = funnelData.value[i + 1];
    if (from.count === 0) continue;
    const dropped = from.count - to.count;
    const dropPercent = Math.round((dropped / from.count) * 100);
    if (dropped > 0 && (!worst || dropped > worst.dropped)) {
      worst = { from: from.name, to: to.name, dropped, dropPercent };
    }
  }
  return worst;
});

// ═══ INSIGHTS: BEST CONVERSION ═══
const bestConversion = computed(() => {
  let best = null;
  for (const conv of stageConversions.value) {
    if (!best || conv.rate > best.rate) best = conv;
  }
  return best;
});

// ═══ INSIGHTS: LOST BY PREVIOUS STAGE ═══
const lostByPreviousStage = computed(() => {
  // Taqribiy hisoblash — lost lidlarning created_at vaqtiga qarab qaysi bosqichda ekanini aniqlash
  // Aslida — lost lidlar "lost" statusiga o'tgan, lekin ular ilgari qaysi bosqichda edi?
  // Hozircha har bosqichdagi kamayishni "yo'qotilgan" deb hisoblaymiz
  const stages = funnelData.value.filter(s => !props.stages.find(st => st.value === s.slug)?.is_won && !props.stages.find(st => st.value === s.slug)?.is_lost);
  if (stages.length < 2) return [];

  const result = [];
  const totalDrop = stages.reduce((sum, s, i) => {
    if (i === 0) return sum;
    const prev = stages[i - 1];
    return sum + Math.max(0, prev.count - s.count);
  }, 0) || 1;

  for (let i = 0; i < stages.length - 1; i++) {
    const drop = Math.max(0, stages[i].count - stages[i + 1].count);
    if (drop > 0) {
      result.push({
        stage: stages[i].name,
        count: drop,
        percent: Math.round(drop / totalDrop * 100),
        color: stages[i].color,
      });
    }
  }
  return result.sort((a, b) => b.count - a.count);
});

// ═══ INSIGHTS: STAGE AGING ═══
const stageAging = computed(() => {
  const now = new Date();
  const activeStages = props.stages.filter(s => !s.is_won && !s.is_lost);

  const data = activeStages.map((stage, i) => {
    const stageLeads = props.leads.filter(l => l.status === stage.value);
    if (stageLeads.length === 0) return { name: stage.label, avgDays: 0, barWidth: 0, barColor: getColor(stage, i) };

    const totalDays = stageLeads.reduce((sum, lead) => {
      const created = new Date(lead.created_at);
      return sum + Math.max(0, Math.floor((now - created) / 86400000));
    }, 0);
    const avgDays = Math.round(totalDays / stageLeads.length);

    return { name: stage.label, avgDays, barWidth: 0, barColor: getColor(stage, i) };
  });

  const maxDays = Math.max(...data.map(d => d.avgDays), 1);
  data.forEach(d => { d.barWidth = Math.round(d.avgDays / maxDays * 100); });
  return data;
});

const slowestStage = computed(() => {
  if (!stageAging.value.length) return null;
  return [...stageAging.value].sort((a, b) => b.avgDays - a.avgDays)[0];
});

// ═══ RECOMMENDATIONS ═══
const recommendations = computed(() => {
  const tips = [];

  if (biggestDropoff.value && biggestDropoff.value.dropPercent >= 40) {
    tips.push({
      priority: 'high',
      text: `"${biggestDropoff.value.from}" bosqichida ${biggestDropoff.value.dropped} ta lid yo'qotilmoqda. Bu bosqichdagi jarayonni tekshiring — skript, javob tezligi yoki taklif sifati muammo bo'lishi mumkin.`
    });
  }

  if (slowestStage.value && slowestStage.value.avgDays > 7) {
    tips.push({
      priority: 'high',
      text: `"${slowestStage.value.name}" bosqichida lidlar o'rtacha ${slowestStage.value.avgDays} kun kutmoqda. Follow-up tezligini oshiring.`
    });
  }

  if (lostPercent.value > 20) {
    tips.push({
      priority: 'medium',
      text: `Umumiy yo'qotish ${lostPercent.value}%. Sifatsiz lidlar sababini aniqlash uchun rad sabablari yozib boring.`
    });
  }

  if (overallConversion.value < 15) {
    tips.push({
      priority: 'medium',
      text: `Konversiya ${overallConversion.value}% — past. Ideal mijoz portretini yangilang va lead kvalifikatsiya jarayonini kuchaytiring.`
    });
  }

  if (bestConversion.value && bestConversion.value.rate >= 80) {
    tips.push({
      priority: 'low',
      text: `"${bestConversion.value.from} → ${bestConversion.value.to}" bosqichida ${bestConversion.value.rate}% konversiya — ajoyib! Bu strategiyani boshqa bosqichlarga ham qo'llang.`
    });
  }

  if (tips.length === 0) {
    tips.push({ priority: 'low', text: 'Voronka ma\'lumotlari yig\'ilmoqda. Ko\'proq lid qo\'shilganda aniqroq tavsiyalar chiqadi.' });
  }

  return tips;
});

const formatCurrency = (value) => {
  if (!value) return '0';
  if (value >= 1e9) return Math.round(value / 1e9) + ' mlrd';
  if (value >= 1e6) return Math.round(value / 1e6) + ' mln';
  if (value >= 1e3) return Math.round(value / 1e3) + ' ming';
  return Math.round(value).toLocaleString();
};
</script>
