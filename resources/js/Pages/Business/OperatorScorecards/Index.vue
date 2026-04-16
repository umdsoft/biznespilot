<template>
  <BusinessLayout title="Operator Scorecards">
    <Head title="Operator Scorecards" />
    <div class="p-4 sm:p-6 max-w-7xl mx-auto">

      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎓 Operator Reyting</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Qaysi operator eng yaxshi ishlayapti, qaysi biri mijozlarni yo'qotyapti?
          </p>
        </div>
        <div class="flex items-center gap-2">
          <select v-model="days" @change="onDaysChange"
            class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm">
            <option value="7">So'nggi 7 kun</option>
            <option value="30">So'nggi 30 kun</option>
            <option value="90">So'nggi 90 kun</option>
          </select>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex items-center gap-1 mb-4 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg w-fit">
        <button @click="activeTab = 'leaderboard'"
          :class="activeTab === 'leaderboard' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-600'"
          class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
          🏆 Reyting
        </button>
        <button @click="activeTab = 'lost_matrix'; loadLostMatrix()"
          :class="activeTab === 'lost_matrix' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-600'"
          class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
          ❌ Lost Matrix
        </button>
        <button @click="activeTab = 'anti_patterns'; loadLostMatrix()"
          :class="activeTab === 'anti_patterns' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-600'"
          class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
          ⚠️ Anti-Patterns
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12 text-gray-500">
        Yuklanmoqda...
      </div>

      <!-- LEADERBOARD TAB -->
      <div v-else-if="activeTab === 'leaderboard'">
        <div v-if="operators.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
          <div class="text-6xl mb-4">📊</div>
          <h3 class="text-lg font-semibold mb-2">Hozircha ma'lumot yo'q</h3>
          <p class="text-sm text-gray-500">Qo'ng'iroqlar tahlil qilingandan keyin bu yerda reyting paydo bo'ladi</p>
        </div>
        <div v-else class="space-y-3">
        <div v-for="op in operators" :key="op.operator_id"
          @click="openDetail(op)"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow cursor-pointer">

          <div class="flex items-center gap-4">
            <!-- Rank -->
            <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold"
              :class="rankClass(op.rank)">
              {{ rankIcon(op.rank) }}
            </div>

            <!-- Name + grade -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ op.operator_name }}</h3>
                <span class="text-xs font-bold px-2 py-0.5 rounded" :class="gradeClass(op.grade)">
                  {{ op.grade }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                <span>📞 {{ op.total_calls }} qo'ng'iroq</span>
                <span>✅ {{ op.sales }} sotuv</span>
                <span v-if="op.lost > 0" class="text-red-600">❌ {{ op.lost }} yo'qotish</span>
                <span>💬 {{ op.conversion_rate }}% konversiya</span>
              </div>
            </div>

            <!-- Scores -->
            <div class="grid grid-cols-4 gap-3 text-center">
              <div>
                <div class="text-lg font-bold" :style="{ color: scoreColor(op.avg_score) }">
                  {{ op.avg_score }}
                </div>
                <div class="text-[10px] text-gray-500">Ball</div>
              </div>
              <div>
                <div class="text-lg font-bold" :style="{ color: scoreColor(op.avg_compliance) }">
                  {{ op.avg_compliance }}%
                </div>
                <div class="text-[10px] text-gray-500">Skript</div>
              </div>
              <div>
                <div class="text-lg font-bold" :style="{ color: ratioColor(op.avg_talk_ratio) }">
                  {{ op.avg_talk_ratio }}%
                </div>
                <div class="text-[10px] text-gray-500">Gapirish</div>
              </div>
              <div>
                <div class="text-lg font-bold" :style="{ color: scoreColor(op.avg_win_prob) }">
                  {{ op.avg_win_prob }}%
                </div>
                <div class="text-[10px] text-gray-500">Win prob</div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>

      <!-- LOST MATRIX TAB -->
      <div v-else-if="activeTab === 'lost_matrix'">
        <div v-if="!lostData || lostData.matrix.operator_totals.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
          <div class="text-6xl mb-4">❌</div>
          <h3 class="text-lg font-semibold mb-2">Hozircha lost matrix yo'q</h3>
          <p class="text-sm text-gray-500">Qo'ng'iroqlar va lost lidlar bog'langanda ma'lumot paydo bo'ladi</p>
        </div>
        <div v-else>
          <!-- Insights -->
          <div v-if="lostData.matrix.insights.length > 0" class="space-y-2 mb-4">
            <div v-for="(insight, i) in lostData.matrix.insights" :key="i"
              class="rounded-xl p-4 border"
              :class="insight.severity === 'high' || insight.severity === 'critical' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'">
              <div class="font-semibold text-sm">{{ insight.title }}</div>
              <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ insight.message }}</div>
            </div>
          </div>

          <!-- Matrix -->
          <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                <tr>
                  <th class="text-left px-4 py-3 font-semibold">Operator</th>
                  <th v-for="reason in lostData.matrix.reasons" :key="reason" class="text-center px-3 py-3 font-semibold text-xs">
                    {{ reason }}
                  </th>
                  <th class="text-center px-3 py-3 font-semibold">Jami</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(opId, idx) in Object.keys(lostData.matrix.operator_totals)" :key="opId"
                  class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                  <td class="px-4 py-3 font-medium">{{ lostData.matrix.operators[opId] }}</td>
                  <td v-for="reason in lostData.matrix.reasons" :key="reason" class="text-center px-3 py-3">
                    <span v-if="lostData.matrix.matrix[opId]?.[reason]"
                      class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                      :class="cellColor(lostData.matrix.matrix[opId][reason].count)">
                      {{ lostData.matrix.matrix[opId][reason].count }}
                    </span>
                    <span v-else class="text-gray-300">—</span>
                  </td>
                  <td class="text-center px-3 py-3 font-bold text-red-600">
                    {{ lostData.matrix.operator_totals[opId] }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ANTI-PATTERNS TAB -->
      <div v-else-if="activeTab === 'anti_patterns'">
        <div v-if="!lostData || lostData.anti_patterns.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
          <div class="text-6xl mb-4">⚠️</div>
          <h3 class="text-lg font-semibold mb-2">Hozircha anti-patterns yo'q</h3>
          <p class="text-sm text-gray-500">Tahlil qilingan qo'ng'iroqlar xatolarni ko'rsatadi</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="op in lostData.anti_patterns" :key="op.operator_id"
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-semibold">{{ op.operator_name }}</h3>
              <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 px-2 py-0.5 rounded font-bold">
                Jami: {{ op.total_patterns }}
              </span>
            </div>
            <div class="space-y-2">
              <div v-for="(count, pattern) in op.top_patterns" :key="pattern" class="flex items-center gap-3">
                <div class="flex-1">
                  <div class="flex items-center justify-between mb-1">
                    <span class="text-sm">{{ patternLabel(pattern) }}</span>
                    <span class="text-xs font-bold text-red-600">{{ count }}</span>
                  </div>
                  <div class="bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                    <div class="bg-red-500 h-full rounded-full" :style="{ width: Math.min(100, count * 20) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail Modal -->
      <div v-if="selectedOp" @click.self="selectedOp = null"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
            <h2 class="font-semibold">👤 {{ selectedOp.operator_name }}</h2>
            <button @click="selectedOp = null" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-6" v-if="detail">
            <!-- Stats grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
              <StatCard label="Jami qo'ng'iroq" :value="detail.stats.total_calls" />
              <StatCard label="O'rtacha ball" :value="detail.stats.avg_score" suffix="" :color="scoreColor(detail.stats.avg_score)" />
              <StatCard label="Sotuvlar" :value="detail.stats.sales" color="#10B981" />
              <StatCard label="Yo'qotishlar" :value="detail.stats.lost" color="#EF4444" />
            </div>

            <!-- Weakest stage -->
            <div v-if="detail.weakest_stage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
              <p class="text-xs font-semibold text-red-700 dark:text-red-300 mb-1">⚠️ ENG ZAIF BOSQICH</p>
              <p class="text-base font-bold">{{ detail.weakest_stage.label }}: {{ detail.weakest_stage.score }}/100</p>
            </div>

            <!-- Stage averages -->
            <div class="mb-4">
              <h3 class="text-sm font-semibold mb-2">Bosqichlar bo'yicha o'rtacha ball</h3>
              <div class="space-y-2">
                <div v-for="(score, stage) in detail.stage_avgs" :key="stage" class="flex items-center gap-2">
                  <span class="text-xs w-40">{{ stageLabel(stage) }}</span>
                  <div class="flex-1 bg-gray-100 dark:bg-gray-700 h-5 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all" :style="{ width: score + '%', background: scoreColor(score) }"></div>
                  </div>
                  <span class="text-sm font-bold w-12 text-right" :style="{ color: scoreColor(score) }">{{ score }}</span>
                </div>
              </div>
            </div>

            <!-- Sentiments -->
            <div class="grid grid-cols-3 gap-2 mb-4">
              <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-green-700">{{ detail.sentiments.positive }}</div>
                <div class="text-[10px] text-gray-500">Ijobiy mijoz</div>
              </div>
              <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-center">
                <div class="text-lg font-bold">{{ detail.sentiments.neutral }}</div>
                <div class="text-[10px] text-gray-500">Neytral</div>
              </div>
              <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
                <div class="text-lg font-bold text-red-700">{{ detail.sentiments.negative }}</div>
                <div class="text-[10px] text-gray-500">Norozi mijoz</div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';

const StatCard = {
  props: ['label', 'value', 'suffix', 'color'],
  template: `<div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-center">
    <div class="text-xl font-bold" :style="{ color: color || '#374151' }">{{ value }}{{ suffix ?? '' }}</div>
    <div class="text-[10px] text-gray-500 mt-1">{{ label }}</div>
  </div>`,
};

const days = ref(30);
const loading = ref(false);
const operators = ref([]);
const selectedOp = ref(null);
const detail = ref(null);
const activeTab = ref('leaderboard');
const lostData = ref(null);

const onDaysChange = () => {
  if (activeTab.value === 'leaderboard') loadData();
  else loadLostMatrix();
};

const loadLostMatrix = async () => {
  if (lostData.value && !loading.value) return;
  loading.value = true;
  try {
    const res = await axios.get('/business/operator-scorecards/lost-matrix', { params: { days: days.value } });
    lostData.value = res.data;
  } catch (e) { console.error(e); }
  finally { loading.value = false; }
};

const cellColor = (count) => {
  if (count >= 10) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
  if (count >= 5) return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300';
  if (count >= 2) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300';
  return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

const patternLabel = (key) => ({
  no_discovery: "Savolsiz sotish",
  price_early: "Narx erta aytildi",
  weak_closing: "Zaif yakunlash",
  no_objection_handle: "E'tirozga javob yo'q",
  interruption: "Gapni bo'lish",
  monologue: "Monolog",
  no_followup: "Keyingi qadam yo'q",
  negative_language: "Salbiy til",
  rushing: "Shoshilish",
}[key] || key);

const loadData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/business/operator-scorecards/leaderboard', { params: { days: days.value } });
    operators.value = res.data.operators || [];
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const openDetail = async (op) => {
  selectedOp.value = op;
  detail.value = null;
  try {
    const res = await axios.get(`/business/operator-scorecards/${op.operator_id}`, { params: { days: days.value } });
    detail.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const stageLabel = (key) => ({
  greeting: 'Salomlashish', discovery: 'Ehtiyoj aniqlash', presentation: 'Taqdimot',
  objection_handling: "E'tiroz", closing: 'Yopish', rapport: 'Munosabat', cta: 'Keyingi qadam',
}[key] || key);

const scoreColor = (s) => s >= 80 ? '#10B981' : s >= 60 ? '#F59E0B' : s >= 40 ? '#EF4444' : '#6B7280';
const ratioColor = (r) => (r >= 30 && r <= 70) ? '#10B981' : '#F59E0B';

const gradeClass = (g) => ({
  A: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  B: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  C: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  D: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
  F: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[g] || 'bg-gray-100');

const rankIcon = (r) => r === 1 ? '🥇' : r === 2 ? '🥈' : r === 3 ? '🥉' : `#${r}`;
const rankClass = (r) => r <= 3 ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-gray-100 dark:bg-gray-700';

onMounted(() => loadData());
</script>
