<template>
  <Head :title="survey ? survey.title : 'Baholash'" />
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
      <!-- Expired -->
      <div v-if="expired" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Muddat tugagan</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Bu baholash havolasining muddati tugagan.</p>
      </div>

      <!-- Completed -->
      <div v-else-if="completed || submitted" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rahmat!</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Javoblaringiz muvaffaqiyatli qabul qilindi.</p>
      </div>

      <!-- Assessment Form -->
      <div v-else-if="survey" class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
          <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ survey.title }}</h1>
          <p v-if="survey.description" class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ survey.description }}</p>
          <p class="text-xs text-gray-400 mt-3">Nomzod: {{ link.candidate_name }}</p>
        </div>

        <form @submit.prevent="submitAssessment">
          <div v-for="(question, qi) in (survey.questions || [])" :key="qi" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-4">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
              {{ qi + 1 }}. {{ question.question }}
              <span v-if="question.is_required" class="text-red-500">*</span>
            </p>

            <!-- Text -->
            <input v-if="question.type === 'text'" v-model="answers[qi]" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500" />

            <!-- Textarea -->
            <textarea v-else-if="question.type === 'textarea'" v-model="answers[qi]" rows="3" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 resize-none"></textarea>

            <!-- Rating 1-5 -->
            <div v-else-if="question.type === 'rating'" class="flex gap-2">
              <button v-for="i in 5" :key="i" type="button" @click="answers[qi] = i" class="w-10 h-10 rounded-lg border-2 text-sm font-medium transition-colors" :class="answers[qi] === i ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300'">{{ i }}</button>
            </div>

            <!-- Scale 1-10 -->
            <div v-else-if="question.type === 'scale'" class="flex gap-1.5 flex-wrap">
              <button v-for="i in 10" :key="i" type="button" @click="answers[qi] = i" class="w-9 h-9 rounded-lg border text-xs font-medium transition-colors" :class="answers[qi] === i ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 dark:border-gray-600 text-gray-600 hover:border-gray-300'">{{ i }}</button>
            </div>

            <!-- Select -->
            <div v-else-if="question.type === 'select'" class="space-y-2">
              <label v-for="(opt, oi) in (question.options || [])" :key="oi" class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :name="'q'+qi" :value="opt" v-model="answers[qi]" class="text-emerald-600 focus:ring-emerald-500" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ opt }}</span>
              </label>
            </div>

            <!-- Multiselect -->
            <div v-else-if="question.type === 'multiselect'" class="space-y-2">
              <label v-for="(opt, oi) in (question.options || [])" :key="oi" class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" :value="opt" v-model="answers[qi]" class="text-emerald-600 focus:ring-emerald-500 rounded" />
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ opt }}</span>
              </label>
            </div>
          </div>

          <button type="submit" :disabled="submitting" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors disabled:opacity-50">
            {{ submitting ? 'Yuborilmoqda...' : 'Javoblarni yuborish' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
  link: Object,
  survey: Object,
  expired: Boolean,
  completed: Boolean,
});

const questions = props.survey?.questions || [];
const answers = reactive(questions.map(q => q.type === 'multiselect' ? [] : null));
const submitting = ref(false);
const submitted = ref(false);

const submitAssessment = () => {
  submitting.value = true;
  router.post(`/assessment/${props.link.token}`, { answers }, {
    onSuccess: () => { submitted.value = true; },
    onFinish: () => { submitting.value = false; },
  });
};
</script>
