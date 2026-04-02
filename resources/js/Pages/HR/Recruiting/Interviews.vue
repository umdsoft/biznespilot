<template>
  <HRLayout title="Intervyular">
    <Head title="Intervyular" />
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Intervyular</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400">Suhbatlarni rejalashtirish va boshqarish</p>
        </div>
        <button @click="showSchedule = true" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Intervyu rejalashtirish
        </button>
      </div>

      <!-- Interviews List -->
      <div v-if="interviews.data && interviews.data.length > 0" class="space-y-3">
        <div v-for="iv in interviews.data" :key="iv.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" :class="typeColor(iv.interview_type)">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="typeIcon(iv.interview_type)" /></svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ iv.application?.candidate_name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ iv.application?.job_posting?.title }} &middot; {{ typeLabel(iv.interview_type) }}</p>
            </div>
            <div class="text-right flex-shrink-0">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatDate(iv.scheduled_at) }}</p>
              <p class="text-xs text-gray-500">{{ iv.duration_minutes }} daq &middot; {{ iv.interviewer?.name || 'Tayinlanmagan' }}</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusClass(iv.status)">{{ statusLabel(iv.status) }}</span>
            <div class="flex gap-1">
              <button v-if="iv.status === 'scheduled'" @click="completeInterview(iv)" class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition-colors" title="Yakunlash">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              </button>
              <button v-if="iv.status === 'scheduled'" @click="cancelInterview(iv.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors" title="Bekor qilish">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center">
        <p class="text-sm text-gray-500">Rejalashtirilgan intervyular yo'q</p>
      </div>
    </div>

    <!-- Schedule Modal -->
    <Teleport to="body">
      <div v-if="showSchedule" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40" @click="showSchedule = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Intervyu rejalashtirish</h3>
          </div>
          <form @submit.prevent="submitSchedule" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomzod *</label>
              <select v-model="scheduleForm.job_application_id" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500">
                <option value="">Tanlang</option>
                <option v-for="app in applications" :key="app.id" :value="app.id">{{ app.candidate_name }} — {{ app.job_posting?.title }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Turi *</label>
                <select v-model="scheduleForm.interview_type" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                  <option value="phone">Telefon</option>
                  <option value="video">Video</option>
                  <option value="in_person">Yuzma-yuz</option>
                  <option value="technical">Texnik</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Davomiyligi *</label>
                <select v-model="scheduleForm.duration_minutes" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                  <option :value="30">30 daqiqa</option>
                  <option :value="45">45 daqiqa</option>
                  <option :value="60">1 soat</option>
                  <option :value="90">1.5 soat</option>
                  <option :value="120">2 soat</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sana va vaqt *</label>
              <input v-model="scheduleForm.scheduled_at" type="datetime-local" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Intervyuer</label>
              <select v-model="scheduleForm.interviewer_id" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
                <option :value="null">Tayinlanmagan</option>
                <option v-for="u in interviewers" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uchrashuv linki</label>
              <input v-model="scheduleForm.meeting_link" type="url" placeholder="https://meet.google.com/..." class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="showSchedule = false" class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Bekor qilish</button>
              <button type="submit" :disabled="scheduleForm.processing" class="flex-1 px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50">Rejalashtirish</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </HRLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';

const props = defineProps({ interviews: Object, applications: Array, interviewers: Array });

const showSchedule = ref(false);
const scheduleForm = useForm({
  job_application_id: '',
  interview_type: 'video',
  scheduled_at: '',
  duration_minutes: 60,
  interviewer_id: null,
  meeting_link: '',
  notes: '',
});

const submitSchedule = () => {
  scheduleForm.post(route('hr.recruiting.interviews.store'), {
    onSuccess: () => { showSchedule.value = false; scheduleForm.reset(); },
  });
};

const completeInterview = (iv) => {
  if (confirm('Intervyuni yakunlaysizmi?')) {
    router.post(route('hr.recruiting.interviews.complete', iv.id), { rating: iv.rating }, { preserveScroll: true });
  }
};

const cancelInterview = (id) => {
  if (confirm('Intervyuni bekor qilasizmi?')) {
    router.post(route('hr.recruiting.interviews.cancel', id), {}, { preserveScroll: true });
  }
};

const formatDate = (d) => new Date(d).toLocaleString('uz-UZ', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
const typeLabel = (t) => ({ phone: 'Telefon', video: 'Video', in_person: 'Yuzma-yuz', technical: 'Texnik' }[t] || t);
const typeIcon = (t) => ({ phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', video: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', in_person: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', technical: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' }[t] || '');
const typeColor = (t) => ({ phone: 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400', video: 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400', in_person: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400', technical: 'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400' }[t] || 'bg-gray-100 text-gray-600');
const statusLabel = (s) => ({ scheduled: 'Rejalashtirilgan', completed: 'Tugallangan', cancelled: 'Bekor qilingan', no_show: 'Kelmadi' }[s] || s);
const statusClass = (s) => ({ scheduled: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400', completed: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400', cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', no_show: 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400' }[s] || 'bg-gray-100 text-gray-600');
</script>
