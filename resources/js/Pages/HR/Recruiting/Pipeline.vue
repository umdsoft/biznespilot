<template>
  <HRLayout title="Arizalar">
    <Head title="Arizalar" />
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Arizalar</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Nomzodlar arizalari va ishga qabul bosqichlari</p>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden sm:flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
            <span>Jami: <strong class="text-gray-900 dark:text-gray-100">{{ localApps.length }}</strong></span>
          </div>
          <select v-model="selectedPosting" @change="filterByPosting" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500">
            <option :value="null">Barcha vakansiyalar</option>
            <option v-for="jp in jobPostings" :key="jp.id" :value="jp.id">{{ jp.title }}</option>
          </select>
        </div>
      </div>

      <!-- Empty state — hech qanday nomzod yo'q -->
      <div v-if="localApps.length === 0" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
        <div class="py-20 px-6 text-center">
          <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Arizalar yo'q</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
            Nomzodlar arizalari bu yerda ko'rinadi. Vakansiya yarating, public linkni ulashing — nomzodlar ariza topshirganda avtomatik shu yerga tushadi.
          </p>
          <div class="flex items-center justify-center gap-3">
            <a :href="route('hr.recruiting.index')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
              Vakansiya yaratish
            </a>
            <a :href="route('hr.talent-pool.index')" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
              Kadrlar zaxirasi
            </a>
          </div>

          <!-- Qanday ishlaydi -->
          <div class="mt-10 grid sm:grid-cols-3 gap-4 max-w-2xl mx-auto text-left">
            <div class="flex gap-3">
              <div class="w-8 h-8 bg-sky-50 dark:bg-sky-900/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-sky-600">1</span>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-900 dark:text-gray-100">Vakansiya oching</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Lavozim, talablar va ish haqi belgilang</p>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="w-8 h-8 bg-violet-50 dark:bg-violet-900/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-violet-600">2</span>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-900 dark:text-gray-100">Arizalar qabul qiling</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Nomzodlar avtomatik pipeline'ga tushadi</p>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="w-8 h-8 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-bold text-emerald-600">3</span>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-900 dark:text-gray-100">Bosqichlarni boshqaring</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Drag-and-drop bilan nomzodlarni suring</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Kanban Board (faqat nomzodlar bo'lganda) -->
      <div v-else class="overflow-x-auto pb-4 -mx-6 px-6">
        <div class="flex gap-4 min-w-max">
          <div
            v-for="(label, stageKey) in stages"
            :key="stageKey"
            class="w-64 flex-shrink-0"
          >
            <!-- Column Header -->
            <div class="flex items-center justify-between px-3 py-2.5 mb-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full" :class="stageColor(stageKey)"></div>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">{{ label }}</span>
              </div>
              <span class="text-xs font-bold min-w-[1.5rem] text-center py-0.5 rounded-md" :class="stageApps(stageKey).length > 0 ? 'text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700' : 'text-gray-400'">{{ stageApps(stageKey).length }}</span>
            </div>

            <!-- Drop Zone -->
            <div
              class="min-h-[calc(100vh-280px)] space-y-2.5 p-2 rounded-xl transition-all duration-200"
              :class="[
                dragOverStage === stageKey
                  ? 'bg-blue-50 dark:bg-blue-900/10 ring-2 ring-blue-300 dark:ring-blue-700 ring-dashed'
                  : 'bg-gray-50/50 dark:bg-gray-800/30'
              ]"
              @dragover.prevent="dragOverStage = stageKey"
              @dragleave="dragOverStage = null"
              @drop="onDrop(stageKey)"
            >
              <!-- Cards -->
              <div
                v-for="app in stageApps(stageKey)"
                :key="app.id"
                draggable="true"
                @dragstart="onDragStart(app)"
                @dragend="dragItem = null; dragOverStage = null"
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3.5 cursor-grab active:cursor-grabbing hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm transition-all group"
              >
                <!-- Avatar + Name -->
                <div class="flex items-start gap-2.5">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" :class="avatarColor(app.candidate_name)">
                    {{ app.candidate_name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ app.candidate_name }}</p>
                    <p v-if="app.job_posting" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ app.job_posting }}</p>
                  </div>
                </div>

                <!-- Meta row -->
                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700/50">
                  <!-- Rating -->
                  <div v-if="app.rating" class="flex items-center gap-0.5">
                    <svg v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= app.rating ? 'text-amber-400' : 'text-gray-200 dark:text-gray-700'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                  </div>
                  <div v-else></div>

                  <div class="flex items-center gap-2">
                    <!-- Assigned -->
                    <span v-if="app.assigned_to" class="text-[10px] text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-1.5 py-0.5 rounded">{{ app.assigned_to }}</span>
                    <!-- Days -->
                    <span class="text-[10px] font-medium" :class="app.days_in_stage > 5 ? 'text-red-500' : app.days_in_stage > 2 ? 'text-amber-500' : 'text-gray-400'">
                      {{ app.days_in_stage > 0 ? app.days_in_stage + 'k' : 'Yangi' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="stageApps(stageKey).length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700/50 flex items-center justify-center mb-2">
                  <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" /></svg>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Bu yerga suring</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </HRLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';

const props = defineProps({
  applications: Array,
  stages: Object,
  jobPostings: Array,
  selectedJobPosting: String,
});

const selectedPosting = ref(props.selectedJobPosting || null);
const dragItem = ref(null);
const dragOverStage = ref(null);
const localApps = ref([...props.applications]);

const stageApps = (stage) => localApps.value.filter(a => a.pipeline_stage === stage);

const stageColor = (stage) => ({
  new: 'bg-sky-500', screening: 'bg-amber-500', phone_screen: 'bg-orange-500',
  interview_scheduled: 'bg-violet-500', interview_done: 'bg-indigo-500',
  assessment: 'bg-cyan-500', offer: 'bg-emerald-500', hired: 'bg-green-600', rejected: 'bg-red-500',
}[stage] || 'bg-gray-400');

const avatarColors = [
  'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
  'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
  'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
  'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
];

const avatarColor = (name) => {
  const idx = (name || '').charCodeAt(0) % avatarColors.length;
  return avatarColors[idx];
};

const onDragStart = (app) => { dragItem.value = app; };

const onDrop = (newStage) => {
  if (!dragItem.value || dragItem.value.pipeline_stage === newStage) {
    dragOverStage.value = null;
    return;
  }

  const app = localApps.value.find(a => a.id === dragItem.value.id);
  if (app) {
    app.pipeline_stage = newStage;
    app.days_in_stage = 0;
  }

  dragOverStage.value = null;

  router.post(route('hr.recruiting.pipeline.move', dragItem.value.id), {
    stage: newStage,
  }, { preserveState: true, preserveScroll: true });

  dragItem.value = null;
};

const filterByPosting = () => {
  router.get(route('hr.recruiting.pipeline'), {
    job_posting_id: selectedPosting.value || undefined,
  }, { preserveState: true, replace: true });
};
</script>
