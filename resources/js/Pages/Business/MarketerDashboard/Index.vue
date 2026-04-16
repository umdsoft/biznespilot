<template>
  <BusinessLayout title="Marketing Komanda Punkti">
    <Head title="Marketer Dashboard" />
    <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">

      <!-- Greeting + Health -->
      <div v-if="briefing" class="bg-gradient-to-br from-blue-500 to-indigo-600 text-white rounded-2xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
          <div>
            <h1 class="text-2xl font-bold">{{ briefing.greeting }}</h1>
            <p class="text-blue-100 mt-1 text-sm">Marketer komanda punkti — bitta joyda hammasi</p>
          </div>
          <div class="text-right">
            <div class="text-4xl font-black">{{ snapshot?.health?.overall || 0 }}<span class="text-xl opacity-75">/100</span></div>
            <div class="text-sm font-medium px-3 py-1 bg-white/20 rounded-full inline-block mt-1">{{ snapshot?.health?.grade || 'N/A' }}</div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12 text-gray-500">Yuklanmoqda...</div>

      <!-- Content -->
      <template v-else-if="snapshot">
        <!-- Health breakdown -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <HealthCard label="Setup" :score="snapshot.health.setup" />
          <HealthCard label="Kontent" :score="snapshot.health.content" />
          <HealthCard label="Takliflar" :score="snapshot.health.offers" />
          <HealthCard label="Kampaniyalar" :score="snapshot.health.campaigns" />
          <HealthCard label="Samara" :score="snapshot.health.performance" />
        </div>

        <!-- Top 3 actions + Alerts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Top priorities -->
          <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
            <h2 class="font-semibold mb-3 flex items-center gap-2">
              <span>🔥</span>
              <span>Bugun eng muhim ishlar</span>
            </h2>
            <div v-if="briefing?.top_3_actions?.length" class="space-y-2">
              <div v-for="(action, i) in briefing.top_3_actions" :key="i"
                class="flex items-start gap-3 p-3 rounded-lg border"
                :class="severityClass(action.severity)">
                <span class="w-7 h-7 rounded-full bg-white dark:bg-gray-900 flex items-center justify-center text-sm font-bold flex-shrink-0">
                  {{ i + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                  <div class="font-semibold text-sm">{{ action.title }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">{{ action.description }}</div>
                  <div class="flex items-center gap-3 mt-2 text-[11px]">
                    <span class="font-medium">📍 {{ action.action }}</span>
                  </div>
                </div>
              </div>
            </div>
            <p v-else class="text-sm text-gray-500 text-center py-4">Ustuvor ishlar yo'q — hammasi joyida!</p>
          </div>

          <!-- Stats -->
          <div class="space-y-3">
            <StatCard icon="📝" label="Kontent (30k)" :value="snapshot.data_summary.content_published_30d" />
            <StatCard icon="💰" label="ROAS (30k)" :value="snapshot.data_summary.roas_30d + 'x'" />
            <StatCard icon="🎯" label="Kampaniyalar" :value="snapshot.data_summary.campaigns_active + ' faol'" />
            <StatCard icon="👀" label="Raqobatchilar" :value="snapshot.data_summary.competitors_tracked + ' ta'" />
          </div>
        </div>

        <!-- Cross Insights -->
        <div v-if="snapshot.insights?.length" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <h2 class="font-semibold mb-3 flex items-center gap-2">
            <span>💡</span>
            <span>Tizim bog'liqliklari (Cross-module insights)</span>
          </h2>
          <div class="space-y-2">
            <div v-for="(ins, i) in snapshot.insights" :key="i"
              class="rounded-lg p-3 border text-sm"
              :class="severityClass(ins.severity)">
              <div class="font-semibold">{{ ins.title }}</div>
              <p class="text-xs mt-1">{{ ins.message }}</p>
              <p class="text-xs mt-1.5 opacity-75">💡 {{ ins.recommendation }}</p>
            </div>
          </div>
        </div>

        <!-- Competitor digest -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <h2 class="font-semibold mb-3 flex items-center gap-2">
            <span>👀</span>
            <span>Raqobatchi radari (7 kun)</span>
          </h2>
          <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold">{{ competitorDigest?.total_activities || 0 }}</div>
              <div class="text-[10px] text-gray-500">Faoliyatlar</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold">{{ competitorCompare?.our_content_30d || 0 }}</div>
              <div class="text-[10px] text-gray-500">Siz (30k)</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3 text-center">
              <div class="text-2xl font-bold" :class="verdictColor(competitorCompare?.verdict)">
                {{ verdictIcon(competitorCompare?.verdict) }}
              </div>
              <div class="text-[10px] text-gray-500">{{ verdictLabel(competitorCompare?.verdict) }}</div>
            </div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400">{{ competitorCompare?.recommendation }}</p>
        </div>

        <!-- Quick actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <button @click="generateWeekPlan" :disabled="planLoading"
            class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl p-4 text-left transition-colors disabled:opacity-50">
            <div class="text-lg mb-1">📅 Haftalik reja</div>
            <div class="text-xs opacity-90">AI 7 kunlik kontent rejasini avtomatik tuzadi</div>
          </button>
          <button @click="loadFeedback"
            class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-4 text-left transition-colors">
            <div class="text-lg mb-1">📊 Content feedback</div>
            <div class="text-xs opacity-90">Top va failed postlardan o'rganish</div>
          </button>
          <Link :href="route('business.marketing.campaigns.index')"
            class="bg-green-600 hover:bg-green-700 text-white rounded-xl p-4 text-left transition-colors block">
            <div class="text-lg mb-1">🚀 Kampaniyalar</div>
            <div class="text-xs opacity-90">Yangi kampaniya ishga tushirish</div>
          </Link>
        </div>

        <!-- Generated Week Plan -->
        <div v-if="weekPlan" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold">📅 Haftalik reja ({{ weekPlan.start_date }} → {{ weekPlan.end_date }})</h2>
            <button @click="saveWeekPlan" :disabled="savingPlan"
              class="px-3 py-1.5 text-xs bg-green-600 hover:bg-green-700 text-white rounded-lg">
              ✅ Saqlash ({{ weekPlan.total_posts }} post)
            </button>
          </div>
          <div class="space-y-2">
            <div v-for="day in weekPlan.plan" :key="day.date"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">
                {{ day.day_name }} — {{ day.date }}
              </div>
              <div v-for="(post, i) in day.posts" :key="i" class="flex items-center gap-2 text-sm py-1">
                <span class="text-[11px] font-mono bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ post.time }}</span>
                <span class="text-[11px] font-semibold px-1.5 py-0.5 rounded"
                  :class="channelClass(post.channel)">{{ post.channel }}</span>
                <span class="flex-1 truncate">{{ post.title }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Feedback Result -->
        <div v-if="feedbackData" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
          <h2 class="font-semibold mb-3">📊 Content feedback (7 kun)</h2>
          <div class="grid grid-cols-2 gap-3 mb-3">
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
              <div class="text-xs text-green-700 dark:text-green-300 font-semibold">Top performers</div>
              <div class="text-sm mt-1">{{ feedbackData.top_performers?.top_posts_analyzed || 0 }} post tahlil qilindi</div>
              <div class="text-xs mt-0.5">{{ feedbackData.top_performers?.ideas_created || 0 }} yangi g'oya yaratildi</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
              <div class="text-xs text-red-700 dark:text-red-300 font-semibold">Yomon postlar</div>
              <div class="text-sm mt-1">{{ feedbackData.failures?.failed_posts_count || 0 }} ta</div>
            </div>
          </div>
          <div v-if="feedbackData.failures?.recommendations?.length" class="space-y-1">
            <p v-for="(r, i) in feedbackData.failures.recommendations" :key="i" class="text-xs text-gray-700 dark:text-gray-300">
              ⚠️ {{ r.message }}
            </p>
          </div>
        </div>
      </template>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';

const HealthCard = {
  props: ['label', 'score'],
  template: `<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 text-center">
    <div class="text-2xl font-bold" :style="{ color: score >= 70 ? '#10B981' : score >= 40 ? '#F59E0B' : '#EF4444' }">{{ score }}</div>
    <div class="text-[10px] text-gray-500 mt-0.5">{{ label }}</div>
  </div>`,
};

const StatCard = {
  props: ['icon', 'label', 'value'],
  template: `<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 flex items-center gap-3">
    <div class="text-2xl">{{ icon }}</div>
    <div class="flex-1 min-w-0">
      <div class="text-sm font-semibold">{{ value }}</div>
      <div class="text-[10px] text-gray-500">{{ label }}</div>
    </div>
  </div>`,
};

const loading = ref(true);
const snapshot = ref(null);
const briefing = ref(null);
const competitorDigest = ref(null);
const competitorCompare = ref(null);
const weekPlan = ref(null);
const planLoading = ref(false);
const savingPlan = ref(false);
const feedbackData = ref(null);

const loadData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/business/marketer/data');
    snapshot.value = res.data.snapshot;
    briefing.value = res.data.briefing;
    competitorDigest.value = res.data.competitor_digest;
    competitorCompare.value = res.data.competitor_compare;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const generateWeekPlan = async () => {
  planLoading.value = true;
  try {
    const res = await axios.post('/business/marketer/week-plan/generate');
    if (res.data.success) weekPlan.value = res.data;
  } catch (e) {
    console.error(e);
  } finally {
    planLoading.value = false;
  }
};

const saveWeekPlan = async () => {
  if (!weekPlan.value) return;
  savingPlan.value = true;
  try {
    const res = await axios.post('/business/marketer/week-plan/save', { plan: weekPlan.value.plan });
    alert(`${res.data.saved} ta post calendar'ga qo'shildi`);
    weekPlan.value = null;
  } catch (e) {
    alert('Xato: ' + (e.response?.data?.message || 'Noma\'lum'));
  } finally {
    savingPlan.value = false;
  }
};

const loadFeedback = async () => {
  try {
    const res = await axios.get('/business/marketer/content-feedback');
    feedbackData.value = res.data;
  } catch (e) {
    console.error(e);
  }
};

const severityClass = (s) => ({
  critical: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
  high: 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
  medium: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
  low: 'bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-700',
}[s] || 'bg-gray-50 border-gray-200');

const verdictColor = (v) => ({ ahead: 'text-green-600', behind: 'text-red-600', neutral: 'text-yellow-600' }[v]);
const verdictIcon = (v) => ({ ahead: '🏆', behind: '⚠️', neutral: '≈' }[v] || '—');
const verdictLabel = (v) => ({ ahead: 'Oldindasiz', behind: 'Orqadasiz', neutral: 'Bir xil' }[v] || '—');

const channelClass = (c) => ({
  instagram: 'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300',
  telegram: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  facebook: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
}[c] || 'bg-gray-100 text-gray-700');

onMounted(() => loadData());
</script>
