<template>
  <BusinessLayout title="AI Yordamchi">
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">AI Yordamchi</h1>

    <!-- API Key Warning -->
    <div v-if="!hasApiKey" class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
      <div class="flex items-start">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600 mr-3 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <h3 class="text-sm font-medium text-yellow-800">API Kaliti Kerak</h3>
          <p class="mt-1 text-sm text-yellow-700">
            AI funksiyalaridan foydalanish uchun Settings sahifasida OpenAI yoki Claude API kalitini qo'shing.
          </p>
          <Link :href="route('business.settings.index')" class="mt-3 inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900">
            Settings ga o'tish
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
      </div>
    </div>

    <!-- AI Features Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <!-- Dream Buyer Analysis -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="activeFeature = 'dream-buyer'">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">Dream Buyer Tahlili</h3>
            <p class="text-sm text-gray-600 mt-1">Ideal mijozingizni chuqur tahlil qiling va marketing strategiyalari oling</p>
            <p class="text-xs text-gray-500 mt-2">{{ stats.dream_buyers }} ta Dream Buyer</p>
          </div>
        </div>
      </Card>

      <!-- Content Generation -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="activeFeature = 'content'">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">Kontent Yaratish</h3>
            <p class="text-sm text-gray-600 mt-1">AI yordamida marketing va ijtimoiy tarmoq kontenti yarating</p>
          </div>
        </div>
      </Card>

      <!-- Competitor Analysis -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="activeFeature = 'competitor'">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">Raqobatchi Tahlili</h3>
            <p class="text-sm text-gray-600 mt-1">Raqobatchilaringizni tahlil qilib, SWOT tavsiyalari oling</p>
            <p class="text-xs text-gray-500 mt-2">{{ stats.competitors }} ta raqobatchi</p>
          </div>
        </div>
      </Card>

      <!-- Offer Optimization -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="activeFeature = 'offer'">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">Taklif Optimallashtirish</h3>
            <p class="text-sm text-gray-600 mt-1">Takliflaringizni yaxshilash bo'yicha AI tavsiyalari</p>
            <p class="text-xs text-gray-500 mt-2">{{ stats.offers }} ta taklif</p>
          </div>
        </div>
      </Card>

      <!-- Business Advice -->
      <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="activeFeature = 'advice'">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">Biznes Maslahat</h3>
            <p class="text-sm text-gray-600 mt-1">Biznes savollari bo'yicha AI yordamchi bilan suhbatlashing</p>
          </div>
        </div>
      </Card>
    </div>

    <!-- Feature Content -->
    <div v-if="activeFeature && hasApiKey" class="mt-8">
      <Card>
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">{{ getFeatureTitle() }}</h2>
          <button @click="activeFeature = null" class="text-gray-400 hover:text-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Dream Buyer Feature -->
        <div v-if="activeFeature === 'dream-buyer'">
          <p class="text-sm text-gray-600 mb-4">
            Dream Buyer profilingizni tanlang va AI tahlil qilsin.
          </p>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Dream Buyer</label>
              <select v-model="selectedDreamBuyer" class="input">
                <option value="">Dream Buyer tanlang...</option>
                <!-- Options will be loaded from backend -->
              </select>
            </div>
            <button
              @click="analyzeDreamBuyer"
              :disabled="!selectedDreamBuyer || isLoading"
              class="btn-primary"
            >
              <span v-if="isLoading">Tahlil qilinmoqda...</span>
              <span v-else>Tahlil Qilish</span>
            </button>
          </div>

          <div v-if="result" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Tahlil Natijasi:</h3>
            <div class="prose prose-sm max-w-none whitespace-pre-line">{{ result }}</div>
          </div>
        </div>

        <!-- Content Generation Feature -->
        <div v-if="activeFeature === 'content'">
          <form @submit.prevent="generateContent" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kontent Turi</label>
              <select v-model="contentForm.content_type" class="input" required>
                <option value="social_post">Ijtimoiy tarmoq posti</option>
                <option value="blog_article">Blog maqolasi</option>
                <option value="email">Email</option>
                <option value="ad_copy">Reklama matni</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mavzu</label>
              <input v-model="contentForm.topic" type="text" class="input" required />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Maqsadli Auditoriya</label>
              <input v-model="contentForm.target_audience" type="text" class="input" />
            </div>

            <button type="submit" :disabled="isLoading" class="btn-primary">
              <span v-if="isLoading">Yaratilmoqda...</span>
              <span v-else>Kontent Yaratish</span>
            </button>
          </form>

          <div v-if="result" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Yaratilgan Kontent:</h3>
            <div class="prose prose-sm max-w-none whitespace-pre-line">{{ result }}</div>
          </div>
        </div>

        <!-- Business Advice Feature -->
        <div v-if="activeFeature === 'advice'">
          <form @submit.prevent="getAdvice" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Savolingiz</label>
              <textarea
                v-model="adviceQuestion"
                rows="4"
                class="input"
                placeholder="Biznes haqida savolingizni yozing..."
                required
              ></textarea>
            </div>

            <button type="submit" :disabled="isLoading" class="btn-primary">
              <span v-if="isLoading">Javob kutilmoqda...</span>
              <span v-else>Javob Olish</span>
            </button>
          </form>

          <div v-if="result" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">AI Javobi:</h3>
            <div class="prose prose-sm max-w-none whitespace-pre-line">{{ result }}</div>
          </div>
        </div>
      </Card>
    </div>
  </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/Components/Card.vue';
import axios from 'axios';

const props = defineProps({
  stats: Object,
  hasApiKey: Boolean,
});

const activeFeature = ref(null);
const isLoading = ref(false);
const result = ref(null);
const selectedDreamBuyer = ref('');
const adviceQuestion = ref('');

const contentForm = ref({
  content_type: 'social_post',
  topic: '',
  target_audience: '',
  tone: '',
  keywords: [],
});

const getFeatureTitle = () => {
  const titles = {
    'dream-buyer': 'Dream Buyer AI Tahlili',
    'content': 'AI Kontent Generatori',
    'competitor': 'Raqobatchi AI Tahlili',
    'offer': 'Taklif AI Optimallashtirish',
    'advice': 'Biznes AI Maslahatchi',
  };
  return titles[activeFeature.value] || '';
};

const analyzeDreamBuyer = async () => {
  if (!selectedDreamBuyer.value) return;

  isLoading.value = true;
  result.value = null;

  try {
    const response = await axios.post(route('business.ai.analyze-dream-buyer'), {
      dream_buyer_id: selectedDreamBuyer.value,
    });

    if (response.data.success) {
      result.value = response.data.analysis.insights;
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Xatolik yuz berdi');
  } finally {
    isLoading.value = false;
  }
};

const generateContent = async () => {
  isLoading.value = true;
  result.value = null;

  try {
    const response = await axios.post(route('business.ai.generate-content'), contentForm.value);

    if (response.data.success) {
      result.value = response.data.content;
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Xatolik yuz berdi');
  } finally {
    isLoading.value = false;
  }
};

const getAdvice = async () => {
  if (!adviceQuestion.value) return;

  isLoading.value = true;
  result.value = null;

  try {
    const response = await axios.post(route('business.ai.get-advice'), {
      question: adviceQuestion.value,
    });

    if (response.data.success) {
      result.value = response.data.advice;
    }
  } catch (error) {
    alert(error.response?.data?.error || 'Xatolik yuz berdi');
  } finally {
    isLoading.value = false;
  }
};
</script>
