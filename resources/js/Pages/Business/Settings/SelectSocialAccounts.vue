<template>
  <BusinessLayout title="Akkaunt Tanlash">
    <div class="max-w-4xl mx-auto py-8 px-4">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Akkaunt Tanlang
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Quyidagi akkauntlardan <strong>faqat bittasini</strong> tanlang. Keyinchalik o'zgartirish uchun avval mavjud akkauntni uzishingiz kerak bo'ladi.
        </p>
      </div>

      <!-- Warning -->
      <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-8">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <h3 class="font-medium text-amber-800 dark:text-amber-300">Muhim!</h3>
            <p class="text-sm text-amber-700 dark:text-amber-400">
              Har bir kategoriyadan faqat <strong>1 ta</strong> akkaunt ulash mumkin.
              Tanlangan akkauntning <strong>oxirgi 6 oylik</strong> ma'lumotlari avtomatik yuklanadi.
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="saveAccounts">
        <!-- Ad Accounts Section -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </span>
            Facebook Ads Account
          </h2>

          <div v-if="adAccounts.length === 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">Hech qanday Ad Account topilmadi</p>
          </div>

          <div v-else class="space-y-3">
            <label
              v-for="account in adAccounts"
              :key="account.id"
              class="block cursor-pointer"
            >
              <div
                :class="[
                  'border rounded-xl p-4 transition-all',
                  selectedAdAccount === account.id
                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 ring-2 ring-blue-500'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-center gap-4">
                  <input
                    type="radio"
                    name="ad_account"
                    :value="account.id"
                    v-model="selectedAdAccount"
                    class="w-4 h-4 text-blue-600"
                  />
                  <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">{{ account.name }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      ID: {{ account.id }} · {{ account.currency }}
                    </p>
                  </div>
                  <div v-if="account.business_name" class="text-right">
                    <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded">
                      {{ account.business_name }}
                    </span>
                  </div>
                </div>
              </div>
            </label>

            <!-- Option to skip -->
            <label class="block cursor-pointer">
              <div
                :class="[
                  'border rounded-xl p-4 transition-all',
                  selectedAdAccount === null
                    ? 'border-gray-500 bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-400'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center gap-4">
                  <input
                    type="radio"
                    name="ad_account"
                    :value="null"
                    v-model="selectedAdAccount"
                    class="w-4 h-4 text-gray-600"
                  />
                  <p class="text-gray-600 dark:text-gray-400">Ad Account ulamaslik</p>
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Instagram Accounts Section -->
        <div class="mb-8">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
              <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
              </svg>
            </span>
            Instagram Business Account
          </h2>

          <div v-if="instagramAccounts.length === 0" class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6 text-center">
            <p class="text-gray-500 dark:text-gray-400">Hech qanday Instagram Business Account topilmadi</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
              Instagram Business Account ulash uchun Facebook Page bilan bog'langan bo'lishi kerak.
            </p>
          </div>

          <div v-else class="space-y-3">
            <label
              v-for="account in instagramAccounts"
              :key="account.id"
              class="block cursor-pointer"
            >
              <div
                :class="[
                  'border rounded-xl p-4 transition-all',
                  selectedInstagram === account.id
                    ? 'border-pink-500 bg-pink-50 dark:bg-pink-900/20 ring-2 ring-pink-500'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
              >
                <div class="flex items-center gap-4">
                  <input
                    type="radio"
                    name="instagram"
                    :value="account.id"
                    v-model="selectedInstagram"
                    class="w-4 h-4 text-pink-600"
                  />
                  <img
                    v-if="account.profile_picture_url"
                    :src="account.profile_picture_url"
                    :alt="account.username"
                    class="w-12 h-12 rounded-full object-cover"
                  />
                  <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center" v-else>
                    <span class="text-lg font-bold text-gray-500">{{ account.username?.charAt(0)?.toUpperCase() }}</span>
                  </div>
                  <div class="flex-1">
                    <p class="font-medium text-gray-900 dark:text-white">@{{ account.username }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatNumber(account.followers_count) }} followers · {{ formatNumber(account.media_count) }} posts
                    </p>
                    <p v-if="account.linked_page_name" class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                      Ulangan: {{ account.linked_page_name }}
                    </p>
                  </div>
                </div>
              </div>
            </label>

            <!-- Option to skip -->
            <label class="block cursor-pointer">
              <div
                :class="[
                  'border rounded-xl p-4 transition-all',
                  selectedInstagram === null
                    ? 'border-gray-500 bg-gray-50 dark:bg-gray-800 ring-2 ring-gray-400'
                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                ]"
              >
                <div class="flex items-center gap-4">
                  <input
                    type="radio"
                    name="instagram"
                    :value="null"
                    v-model="selectedInstagram"
                    class="w-4 h-4 text-gray-600"
                  />
                  <p class="text-gray-600 dark:text-gray-400">Instagram ulamaslik</p>
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
          <Link
            :href="route('business.settings.index')"
            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
          >
            Bekor qilish
          </Link>

          <button
            type="submit"
            :disabled="isSubmitting || (!selectedAdAccount && !selectedInstagram)"
            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isSubmitting ? 'Saqlanmoqda...' : 'Ulash va Sinxronlash' }}</span>
          </button>
        </div>
      </form>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';

const props = defineProps({
  adAccounts: {
    type: Array,
    default: () => [],
  },
  instagramAccounts: {
    type: Array,
    default: () => [],
  },
  facebookPages: {
    type: Array,
    default: () => [],
  },
  currentBusiness: {
    type: Object,
    required: true,
  },
});

const selectedAdAccount = ref(props.adAccounts[0]?.id || null);
const selectedInstagram = ref(props.instagramAccounts[0]?.id || null);
const isSubmitting = ref(false);

const formatNumber = (num) => {
  if (!num) return '0';
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
  return num.toString();
};

const saveAccounts = async () => {
  if (!selectedAdAccount.value && !selectedInstagram.value) {
    alert('Kamida bitta akkaunt tanlashingiz kerak');
    return;
  }

  isSubmitting.value = true;

  try {
    const response = await fetch(route('business.settings.social.save-accounts'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
      },
      body: JSON.stringify({
        selected_ad_account_id: selectedAdAccount.value,
        selected_instagram_id: selectedInstagram.value,
      }),
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Redirect to settings with success message
      router.visit(route('business.settings.index'), {
        preserveState: false,
        onSuccess: () => {
          // Show success notification
        },
      });
    } else {
      alert(data.error || 'Xatolik yuz berdi');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Tarmoq xatoligi. Qayta urinib ko\'ring.');
  } finally {
    isSubmitting.value = false;
  }
};
</script>
