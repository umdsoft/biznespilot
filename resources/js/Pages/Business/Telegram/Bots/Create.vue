<template>
  <BusinessLayout title="Yangi Bot Qo'shish">
    <div class="max-w-4xl mx-auto">
      <!-- Back Button -->
      <Link
        :href="route('business.telegram-funnels.index')"
        class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-6 group"
      >
        <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Orqaga
      </Link>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-3">
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- Header -->
            <div class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-8 text-white">
              <div class="absolute inset-0 bg-grid-white/10"></div>
              <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>

              <div class="relative flex items-center gap-4">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                  <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                  </svg>
                </div>
                <div>
                  <h1 class="text-2xl font-bold">Telegram Bot Qo'shish</h1>
                  <p class="text-blue-100 mt-1">BotFather dan olingan tokenni kiriting</p>
                </div>
              </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submitForm" class="p-8 space-y-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                  Bot Token
                </label>
                <div class="relative">
                  <input
                    v-model="form.bot_token"
                    :type="showToken ? 'text' : 'password'"
                    placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                    class="w-full px-4 py-4 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:text-white text-lg font-mono transition-all"
                    :class="{ 'border-red-500 focus:ring-red-500': errors.bot_token }"
                  />
                  <button
                    type="button"
                    @click="showToken = !showToken"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                  >
                    <svg v-if="showToken" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                </div>
                <p v-if="errors.bot_token" class="text-red-500 text-sm mt-2">{{ errors.bot_token }}</p>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                  Token formatidan ko'rinishi: <code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">123456789:ABC...</code>
                </p>
              </div>

              <button
                type="submit"
                :disabled="isLoading || !form.bot_token"
                class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 text-white font-semibold rounded-xl transition-all duration-200 flex items-center justify-center shadow-lg shadow-blue-500/30 disabled:shadow-none"
              >
                <svg v-if="isLoading" class="animate-spin w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ isLoading ? 'Tekshirilmoqda...' : 'Bot Qo\'shish' }}
              </button>

              <!-- Error Message -->
              <div v-if="errorMessage" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-semibold text-red-700 dark:text-red-400">Xatolik yuz berdi</p>
                    <p class="text-red-600 dark:text-red-400 text-sm mt-1">{{ errorMessage }}</p>
                  </div>
                </div>
              </div>

              <!-- Success Message -->
              <div v-if="successMessage" class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-semibold text-green-700 dark:text-green-400">Muvaffaqiyatli!</p>
                    <p class="text-green-600 dark:text-green-400 text-sm mt-1">{{ successMessage }}</p>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Instructions Sidebar -->
        <div class="lg:col-span-2 space-y-6">
          <!-- How to get token -->
          <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-4">
              <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="font-semibold text-gray-900 dark:text-white">Bot Token olish</h3>
            </div>

            <ol class="space-y-4">
              <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-xs font-bold text-blue-600 dark:text-blue-400">1</span>
                <div>
                  <p class="text-gray-700 dark:text-gray-300">Telegram da <a href="https://t.me/BotFather" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">@BotFather</a> ga o'ting</p>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-xs font-bold text-blue-600 dark:text-blue-400">2</span>
                <div>
                  <p class="text-gray-700 dark:text-gray-300"><code class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-sm">/newbot</code> buyrug'ini yuboring</p>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-xs font-bold text-blue-600 dark:text-blue-400">3</span>
                <div>
                  <p class="text-gray-700 dark:text-gray-300">Bot nomini kiriting</p>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-xs font-bold text-blue-600 dark:text-blue-400">4</span>
                <div>
                  <p class="text-gray-700 dark:text-gray-300">Username kiriting (<code class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-sm">bot</code> bilan tugashi kerak)</p>
                </div>
              </li>
              <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center text-xs font-bold text-green-600 dark:text-green-400">5</span>
                <div>
                  <p class="text-gray-700 dark:text-gray-300">Olingan tokenni yuqoridagi maydonga kiriting</p>
                </div>
              </li>
            </ol>

            <a
              href="https://t.me/BotFather"
              target="_blank"
              class="mt-6 w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium rounded-xl transition-colors"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
              </svg>
              BotFather ga o'tish
            </a>
          </div>

          <!-- Security Notice -->
          <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-200 dark:border-amber-800 p-6">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              </div>
              <div>
                <h4 class="font-semibold text-amber-800 dark:text-amber-300 mb-1">Xavfsizlik</h4>
                <p class="text-amber-700 dark:text-amber-400 text-sm">
                  Bot tokeningizni hech kim bilan ulashmang. Token sizning botingizga to'liq ruxsat beradi.
                </p>
              </div>
            </div>
          </div>

          <!-- Features -->
          <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-6">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Bot imkoniyatlari</h4>
            <ul class="space-y-3">
              <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Avtomatik funnellar yaratish
              </li>
              <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Trigger asosida javoblar
              </li>
              <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Ommaviy xabar yuborish
              </li>
              <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Foydalanuvchi segmentatsiyasi
              </li>
              <li class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Batafsil statistika
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import BusinessLayout from '@/Layouts/BusinessLayout.vue'

const form = reactive({
  bot_token: ''
})

const errors = reactive({
  bot_token: null
})

const isLoading = ref(false)
const errorMessage = ref(null)
const successMessage = ref(null)
const showToken = ref(false)

const submitForm = async () => {
  isLoading.value = true
  errorMessage.value = null
  successMessage.value = null
  errors.bot_token = null

  try {
    const response = await fetch(route('business.telegram-funnels.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(form)
    })

    const data = await response.json()

    if (data.success) {
      successMessage.value = 'Bot muvaffaqiyatli qo\'shildi!'
      setTimeout(() => {
        router.visit(route('business.telegram-funnels.show', data.bot.id))
      }, 1000)
    } else {
      errorMessage.value = data.message || 'Xatolik yuz berdi'
    }
  } catch (error) {
    errorMessage.value = 'Server bilan bog\'lanishda xatolik'
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.bg-grid-white\/10 {
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
</style>
