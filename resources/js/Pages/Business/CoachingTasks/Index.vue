<template>
  <BusinessLayout title="Coaching Vazifalari">
    <Head title="Coaching Vazifalari" />
    <div class="p-4 sm:p-6 max-w-6xl mx-auto">

      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold">🎓 Operator Coaching</h1>
        <p class="text-sm text-gray-500 mt-1">
          AI past ball aniqlaganda avtomatik yaratilgan mashq vazifalari
        </p>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
          <div class="text-2xl font-bold">{{ stats.total }}</div>
          <div class="text-xs text-gray-500 mt-1">Jami</div>
        </div>
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800 p-4 text-center">
          <div class="text-2xl font-bold text-orange-600">{{ stats.pending }}</div>
          <div class="text-xs text-gray-500 mt-1">Kutilmoqda</div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4 text-center">
          <div class="text-2xl font-bold text-red-600">{{ stats.urgent }}</div>
          <div class="text-xs text-gray-500 mt-1">Shoshilinch</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800 p-4 text-center">
          <div class="text-2xl font-bold text-green-600">{{ stats.completed }}</div>
          <div class="text-xs text-gray-500 mt-1">Bajarilgan</div>
        </div>
      </div>

      <!-- Filter -->
      <div class="flex items-center gap-2 mb-4 flex-wrap">
        <button v-for="f in filters" :key="f.value"
          @click="statusFilter = f.value; loadData()"
          :class="statusFilter === f.value ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'"
          class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
          {{ f.label }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-8 text-gray-500">Yuklanmoqda...</div>

      <!-- Empty -->
      <div v-else-if="tasks.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="text-5xl mb-3">🎯</div>
        <h3 class="font-semibold mb-1">{{ statusFilter === 'completed' ? 'Bajarilgan vazifalar yo\'q' : 'Hozircha vazifalar yo\'q' }}</h3>
        <p class="text-sm text-gray-500">Qo'ng'iroq tahlili past ball olganda bu yerda paydo bo'ladi</p>
      </div>

      <!-- Tasks list -->
      <div v-else class="space-y-3">
        <div v-for="task in tasks" :key="task.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
          <div class="flex items-start gap-3">
            <!-- Priority indicator -->
            <div class="flex-shrink-0 mt-1">
              <span :class="priorityClass(task.priority)" class="inline-block w-2 h-10 rounded-full"></span>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-3 mb-1">
                <h3 class="font-semibold text-sm" :class="{ 'line-through text-gray-400': task.status === 'completed' }">
                  {{ task.title }}
                </h3>
                <span :class="priorityBadgeClass(task.priority)" class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full flex-shrink-0">
                  {{ priorityLabel(task.priority) }}
                </span>
              </div>

              <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">{{ task.description }}</p>

              <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                  👤 {{ task.operator_name || 'Noma\'lum' }}
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                  🎯 {{ areaLabel(task.weak_area) }}
                </span>
                <span v-if="task.score_at_creation" class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded">
                  📉 Ball: {{ Math.round(task.score_at_creation) }}
                </span>
                <span v-if="task.due_date">
                  ⏰ {{ formatDate(task.due_date) }}
                </span>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex-shrink-0 flex items-center gap-1">
              <button v-if="task.status === 'pending'"
                @click="startTask(task)"
                class="px-2 py-1 text-xs bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded">
                Boshlash
              </button>
              <button v-if="task.status !== 'completed'"
                @click="completeTask(task)"
                class="px-2 py-1 text-xs bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:hover:bg-green-900/50 text-green-700 dark:text-green-300 rounded">
                ✓ Bajarildi
              </button>
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

const loading = ref(false);
const tasks = ref([]);
const stats = ref(null);
const statusFilter = ref('pending');

const filters = [
  { value: 'all', label: 'Hammasi' },
  { value: 'pending', label: 'Kutilmoqda' },
  { value: 'in_progress', label: 'Jarayonda' },
  { value: 'completed', label: 'Bajarilgan' },
];

const loadData = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/business/coaching-tasks/list', { params: { status: statusFilter.value } });
    tasks.value = res.data.tasks || [];
    stats.value = res.data.stats;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const startTask = async (task) => {
  try {
    await axios.post(`/business/coaching-tasks/${task.id}/status`, { status: 'in_progress' });
    loadData();
  } catch (e) { console.error(e); }
};

const completeTask = async (task) => {
  try {
    await axios.post(`/business/coaching-tasks/${task.id}/complete`);
    loadData();
  } catch (e) { console.error(e); }
};

const priorityClass = (p) => ({
  urgent: 'bg-red-500',
  high: 'bg-orange-500',
  medium: 'bg-yellow-500',
  low: 'bg-gray-400',
}[p] || 'bg-gray-400');

const priorityBadgeClass = (p) => ({
  urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
  medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  low: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
}[p]);

const priorityLabel = (p) => ({
  urgent: 'Shoshilinch',
  high: 'Yuqori',
  medium: "O'rta",
  low: 'Past',
}[p] || p);

const areaLabel = (area) => ({
  greeting: 'Salomlashish',
  discovery: 'Ehtiyoj aniqlash',
  presentation: 'Taqdimot',
  objection_handling: "E'tiroz",
  closing: 'Yopish',
  rapport: 'Munosabat',
  cta: 'Keyingi qadam',
  script_compliance: 'Skript bajarish',
  talk_ratio: 'Gaplashish balansi',
  sentiment: 'Mijoz kayfiyati',
}[area] || area);

const formatDate = (iso) => {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short' });
};

onMounted(() => loadData());
</script>
