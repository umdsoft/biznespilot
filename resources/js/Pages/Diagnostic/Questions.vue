<template>
  <Head title="AI Savollari" />

  <div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
          <Link href="/business/diagnostic" class="hover:text-gray-700">Diagnostika</Link>
          <ChevronRightIcon class="w-4 h-4" />
          <Link :href="`/business/diagnostic/${diagnostic_id}`" class="hover:text-gray-700">
            Natija
          </Link>
          <ChevronRightIcon class="w-4 h-4" />
          <span>Savollar</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">AI Savollari</h1>
        <p class="text-gray-500 mt-1">
          Biznesingizni yaxshiroq tushunish uchun quyidagi savollarga javob bering
        </p>
      </div>

      <!-- Progress -->
      <div class="bg-white rounded-lg border p-4 mb-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-600">Progress</span>
          <span class="text-sm font-medium text-gray-900">
            {{ answered_count }}/{{ total_count }} javob berildi
          </span>
        </div>
        <div class="relative h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="absolute left-0 top-0 h-full bg-indigo-500 rounded-full transition-all duration-500"
            :style="{ width: `${progressPercent}%` }"
          ></div>
        </div>
      </div>

      <!-- Questions list -->
      <div class="space-y-4">
        <QuestionCard
          v-for="(question, index) in questions"
          :key="question.id"
          :question="question"
          :index="index"
          :loading="answeringQuestionId === question.id"
          @answer="handleAnswer"
          @edit="editQuestion(question)"
        />
      </div>

      <!-- Empty state -->
      <div
        v-if="!questions.length"
        class="bg-white rounded-lg border p-8 text-center"
      >
        <ChatBubbleLeftRightIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <h3 class="font-medium text-gray-900">Savollar mavjud emas</h3>
        <p class="text-gray-500 text-sm mt-1">
          AI hozircha qo'shimcha savol bermadi
        </p>
      </div>

      <!-- All answered -->
      <div
        v-if="questions.length && answered_count === total_count"
        class="mt-6 bg-green-50 rounded-lg border border-green-200 p-6 text-center"
      >
        <CheckCircleIcon class="w-12 h-12 text-green-500 mx-auto mb-4" />
        <h3 class="font-medium text-green-800">Barcha savollarga javob berildi!</h3>
        <p class="text-green-700 text-sm mt-1">
          Javoblaringiz keyingi diagnostikada hisobga olinadi
        </p>
        <Link
          :href="`/business/diagnostic/${diagnostic_id}`"
          class="mt-4 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
        >
          Natijaga qaytish
          <ArrowRightIcon class="w-4 h-4 ml-2" />
        </Link>
      </div>

      <!-- Back button -->
      <div class="mt-6">
        <Link
          :href="`/business/diagnostic/${diagnostic_id}`"
          class="text-gray-500 hover:text-gray-700 text-sm flex items-center"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-1" />
          Natijaga qaytish
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import QuestionCard from '@/Components/diagnostic/QuestionCard.vue';
import {
  ChevronRightIcon,
  ChatBubbleLeftRightIcon,
  CheckCircleIcon,
  ArrowRightIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostic_id: {
    type: [Number, String],
    required: true,
  },
  questions: {
    type: Array,
    default: () => [],
  },
  answered_count: {
    type: Number,
    default: 0,
  },
  total_count: {
    type: Number,
    default: 0,
  },
});

const store = useDiagnosticStore();
const answeringQuestionId = ref(null);

const progressPercent = computed(() => {
  if (props.total_count === 0) return 0;
  return Math.round((props.answered_count / props.total_count) * 100);
});

async function handleAnswer({ questionId, answer }) {
  answeringQuestionId.value = questionId;
  try {
    await store.answerQuestion(questionId, answer);
    // Update local data
    const question = props.questions.find(q => q.id === questionId);
    if (question) {
      question.answer = answer;
      question.answered_at = new Date().toLocaleString('uz-UZ');
    }
  } catch (error) {
    console.error('Failed to submit answer:', error);
  } finally {
    answeringQuestionId.value = null;
  }
}

function editQuestion(question) {
  // For now, just clear the answer to allow re-answering
  question.answer = null;
  question.answered_at = null;
}
</script>
