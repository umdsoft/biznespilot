<template>
  <BusinessLayout title="Sozlamalar">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 mb-6">Sozlamalar</h1>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
            ]"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Profile Tab -->
    <div v-show="activeTab === 'profile'" class="space-y-6">
      <!-- Profile Information -->
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Profil Ma'lumotlari</h2>
        <form @submit.prevent="updateProfile">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ism</label>
              <input
                v-model="profileForm.name"
                type="text"
                class="input"
                :class="{ 'border-red-500': profileForm.errors.name }"
                required
              />
              <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600">{{ profileForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Login</label>
              <input
                v-model="user.login"
                type="text"
                class="input bg-gray-50"
                disabled
              />
              <p class="mt-1 text-xs text-gray-500">Login o'zgartirib bo'lmaydi</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                v-model="profileForm.email"
                type="email"
                class="input"
                :class="{ 'border-red-500': profileForm.errors.email }"
                required
              />
              <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600">{{ profileForm.errors.email }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
              <input
                v-model="profileForm.phone"
                type="tel"
                class="input"
                :class="{ 'border-red-500': profileForm.errors.phone }"
                placeholder="+998 XX XXX XX XX"
              />
              <p v-if="profileForm.errors.phone" class="mt-1 text-sm text-red-600">{{ profileForm.errors.phone }}</p>
            </div>

            <div class="pt-4">
              <button type="submit" :disabled="profileForm.processing" class="btn-primary">
                <span v-if="profileForm.processing">Saqlanmoqda...</span>
                <span v-else>Saqlash</span>
              </button>
            </div>
          </div>
        </form>
      </Card>

      <!-- Change Password -->
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Parolni O'zgartirish</h2>
        <form @submit.prevent="updatePassword">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Joriy Parol</label>
              <input
                v-model="passwordForm.current_password"
                type="password"
                class="input"
                :class="{ 'border-red-500': passwordForm.errors.current_password }"
                required
              />
              <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Yangi Parol</label>
              <input
                v-model="passwordForm.password"
                type="password"
                class="input"
                :class="{ 'border-red-500': passwordForm.errors.password }"
                required
              />
              <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Parolni Tasdiqlash</label>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                class="input"
                required
              />
            </div>

            <div class="pt-4">
              <button type="submit" :disabled="passwordForm.processing" class="btn-primary">
                <span v-if="passwordForm.processing">Saqlanmoqda...</span>
                <span v-else>Parolni O'zgartirish</span>
              </button>
            </div>
          </div>
        </form>
      </Card>
    </div>

    <!-- Preferences Tab -->
    <div v-show="activeTab === 'preferences'" class="space-y-6">
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bildirishnomalar</h2>
        <form @submit.prevent="updatePreferences">
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Email Bildirishnomalar</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Muhim yangiliklarni email orqali qabul qiling</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferencesForm.email_notifications"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Brauzer Bildirishnomalar</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Real vaqt bildirishnomalarini yoqish</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferencesForm.browser_notifications"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
              </label>
            </div>

            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl border border-pink-100 dark:border-pink-800">
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">Marketing Xabarlari</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Yangi funksiyalar va takliflar haqida xabar olish</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input
                  v-model="preferencesForm.marketing_emails"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-pink-300 dark:peer-focus:ring-pink-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-600"></div>
              </label>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Til</label>
              <select v-model="preferencesForm.language" class="input max-w-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="uz">O'zbekcha</option>
                <option value="ru">Русский</option>
                <option value="en">English</option>
              </select>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tema</label>
              <select v-model="preferencesForm.theme" class="input max-w-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                <option value="light">Yorug'</option>
                <option value="dark">Qorong'i</option>
                <option value="auto">Avtomatik</option>
              </select>
            </div>

            <div class="pt-4">
              <button type="submit" :disabled="preferencesForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-500 dark:to-pink-500 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-pink-700 dark:hover:from-purple-600 dark:hover:to-pink-600 focus:outline-none focus:ring-2 focus:ring-purple-500 dark:focus:ring-purple-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg">
                <span v-if="preferencesForm.processing">Saqlanmoqda...</span>
                <span v-else>O'zgarishlarni Saqlash</span>
              </button>
            </div>
          </div>
        </form>
      </Card>
    </div>

    <!-- AI Settings Tab -->
    <div v-show="activeTab === 'ai'" class="space-y-6">
      <!-- API Keys -->
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">API Kalitlari</h2>
        <p class="text-sm text-gray-600 mb-6">
          AI funksiyalaridan foydalanish uchun OpenAI yoki Claude API kalitlarini qo'shing.
        </p>

        <form @submit.prevent="updateApiKeys">
          <div class="space-y-4">
            <!-- OpenAI API Key -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                  OpenAI API Kaliti
                  <span v-if="settings.has_openai_key" class="ml-2 text-xs text-green-600">(Qo'shilgan)</span>
                </label>
                <button
                  v-if="settings.has_openai_key"
                  type="button"
                  @click="deleteKey('openai')"
                  class="text-xs text-red-600 hover:text-red-700"
                >
                  O'chirish
                </button>
              </div>
              <input
                v-model="apiKeysForm.openai_api_key"
                type="password"
                class="input"
                :placeholder="settings.has_openai_key ? '••••••••••••••••••••' : 'sk-...'"
              />
              <p class="mt-1 text-xs text-gray-500">
                <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 hover:text-blue-700">
                  OpenAI Platform
                </a>
                dan API kalitini oling
              </p>
            </div>

            <!-- Claude API Key -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700">
                  Claude API Kaliti
                  <span v-if="settings.has_claude_key" class="ml-2 text-xs text-green-600">(Qo'shilgan)</span>
                </label>
                <button
                  v-if="settings.has_claude_key"
                  type="button"
                  @click="deleteKey('claude')"
                  class="text-xs text-red-600 hover:text-red-700"
                >
                  O'chirish
                </button>
              </div>
              <input
                v-model="apiKeysForm.claude_api_key"
                type="password"
                class="input"
                :placeholder="settings.has_claude_key ? '••••••••••••••••••••' : 'sk-ant-...'"
              />
              <p class="mt-1 text-xs text-gray-500">
                <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-blue-600 hover:text-blue-700">
                  Anthropic Console
                </a>
                dan API kalitini oling
              </p>
            </div>

            <div class="pt-4">
              <button type="submit" :disabled="apiKeysForm.processing" class="btn-primary">
                <span v-if="apiKeysForm.processing">Saqlanmoqda...</span>
                <span v-else>API Kalitlarini Saqlash</span>
              </button>
            </div>
          </div>
        </form>
      </Card>

      <!-- AI Preferences -->
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">AI Sozlamalari</h2>
        <form @submit.prevent="updatePreferences">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Afzal AI Model
              </label>
              <select v-model="preferencesForm.preferred_ai_model" class="input">
                <option value="gpt-4">GPT-4 (OpenAI)</option>
                <option value="gpt-3.5-turbo">GPT-3.5 Turbo (OpenAI)</option>
                <option value="claude-3-opus">Claude 3 Opus (Anthropic)</option>
                <option value="claude-3-sonnet">Claude 3 Sonnet (Anthropic)</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Ijodkorlik Darajasi: {{ preferencesForm.ai_creativity_level }}/10
              </label>
              <input
                v-model="preferencesForm.ai_creativity_level"
                type="range"
                min="1"
                max="10"
                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600"
              />
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>Konservativ (1)</span>
                <span>Muvozanatli (5)</span>
                <span>Ijodiy (10)</span>
              </div>
              <p class="mt-2 text-xs text-gray-500">
                Yuqori qiymat - ko'proq ijodiy va noodatiy javoblar. Past qiymat - aniqroq va konservativ javoblar.
              </p>
            </div>

            <div class="pt-4">
              <button type="submit" :disabled="preferencesForm.processing" class="btn-primary">
                <span v-if="preferencesForm.processing">Saqlanmoqda...</span>
                <span v-else>Saqlash</span>
              </button>
            </div>
          </div>
        </form>
      </Card>
    </div>

    <!-- Integrations Tab -->
    <div v-show="activeTab === 'integrations'" class="space-y-6">
      <Card>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Messaging Platformalar</h2>
        <p class="text-sm text-gray-600 mb-6">
          WhatsApp, Instagram va boshqa messaging platformalar bilan AI integratsiyalarini sozlang.
        </p>

        <div class="space-y-4">
          <!-- WhatsApp AI -->
          <a
            :href="route('business.settings.whatsapp-ai')"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all group"
          >
            <div class="flex items-center">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-200 transition-colors">
                <span class="text-2xl">💬</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">WhatsApp AI</h3>
                <p class="text-sm text-gray-600">AI-powered WhatsApp chat automation va sozlamalari</p>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>

          <!-- Instagram AI -->
          <a
            :href="route('business.settings.instagram-ai')"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-pink-500 hover:bg-pink-50 transition-all group"
          >
            <div class="flex items-center">
              <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg flex items-center justify-center mr-4 group-hover:shadow-lg transition-all">
                <span class="text-2xl">📸</span>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">Instagram AI</h3>
                <p class="text-sm text-gray-600">AI-powered Instagram DM va Story reply automation</p>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>

          <!-- WhatsApp Integration -->
          <a
            :href="route('business.settings.whatsapp')"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all group"
          >
            <div class="flex items-center">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-blue-200 transition-colors">
                <svg class="w-6 h-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">WhatsApp Ulanishi</h3>
                <p class="text-sm text-gray-600">WhatsApp Business API ni ulash va sozlash</p>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </a>
        </div>
      </Card>

    </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';

const props = defineProps({
  user: Object,
  settings: Object,
});

const activeTab = ref('profile');

const tabs = [
  { id: 'profile', name: 'Profil' },
  { id: 'preferences', name: 'Sozlamalar' },
  { id: 'ai', name: 'AI Sozlamalari' },
  { id: 'integrations', name: 'Integratsiyalar' },
];

// Profile Form
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
});

const updateProfile = () => {
  profileForm.put(route('business.settings.profile.update'), {
    preserveScroll: true,
  });
};

// Password Form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  passwordForm.put(route('business.settings.password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
};

// Preferences Form
const preferencesForm = useForm({
  email_notifications: props.settings.email_notifications,
  browser_notifications: props.settings.browser_notifications,
  marketing_emails: props.settings.marketing_emails,
  preferred_ai_model: props.settings.preferred_ai_model,
  ai_creativity_level: props.settings.ai_creativity_level,
  theme: props.settings.theme,
  language: props.settings.language,
});

const updatePreferences = () => {
  preferencesForm.put(route('business.settings.preferences.update'), {
    preserveScroll: true,
  });
};

// API Keys Form
const apiKeysForm = useForm({
  openai_api_key: '',
  claude_api_key: '',
});

const updateApiKeys = () => {
  apiKeysForm.put(route('business.settings.api-keys.update'), {
    preserveScroll: true,
    onSuccess: () => {
      apiKeysForm.reset();
    },
  });
};

const deleteKey = (keyType) => {
  if (confirm('Haqiqatan ham bu API kalitini o\'chirmoqchimisiz?')) {
    useForm({ key_type: keyType }).delete(route('business.settings.api-keys.delete'), {
      preserveScroll: true,
    });
  }
};
</script>
