<template>
  <div class="min-h-screen flex">
    <!-- Left Side - Branding & Info -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 flex flex-col justify-center px-12 py-12 text-white">
        <div class="mb-12">
          <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h1 class="ml-4 text-3xl font-bold">BiznesPilot AI</h1>
          </div>
          <p class="text-xl text-blue-100 leading-relaxed">
            O'zbekiston bizneslari uchun AI-powered marketing va sotuvni boshqarish platformasi
          </p>
        </div>

        <!-- Features -->
        <div class="space-y-6">
          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg">AI Ideal Mijoz Tahlili</h3>
              <p class="text-blue-100 text-sm">Ideal mijozingizni aniqlang va ular bilan gaplashing</p>
            </div>
          </div>

          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg">Marketing Avtomatizatsiya</h3>
              <p class="text-blue-100 text-sm">WhatsApp, Instagram, Telegram orqali avtomatik marketing</p>
            </div>
          </div>

          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg">Real-time Analytics</h3>
              <p class="text-blue-100 text-sm">Sotuvlar, mijozlar va marketing tahlili bir joyda</p>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="mt-12 grid grid-cols-3 gap-6 pt-8 border-t border-white/20">
          <div>
            <div class="text-3xl font-bold">500+</div>
            <div class="text-blue-200 text-sm">Faol bizneslar</div>
          </div>
          <div>
            <div class="text-3xl font-bold">50K+</div>
            <div class="text-blue-200 text-sm">Mijozlar</div>
          </div>
          <div>
            <div class="text-3xl font-bold">99%</div>
            <div class="text-blue-200 text-sm">Qoniqish</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side - Login Form -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gray-50">
      <div class="w-full max-w-md">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">BiznesPilot AI</h1>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8">
          <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2">Xush kelibsiz!</h2>
            <p class="text-gray-600">
              Akkauntingizga kirish uchun ma'lumotlaringizni kiriting
            </p>
          </div>

          <!-- Error Message -->
          <div v-if="form.errors.login || form.errors.password" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-sm text-red-800">{{ form.errors.login || form.errors.password }}</p>
            </div>
          </div>

          <form @submit.prevent="form.post('/login')" class="space-y-6">
            <!-- Login Field -->
            <div>
              <label for="login" class="block text-sm font-semibold text-gray-700 mb-2">
                Login yoki Telefon
              </label>
              <input
                id="login"
                v-model="form.login"
                type="text"
                placeholder="username yoki +998901234567"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400"
                :class="{ 'border-red-300 focus:ring-red-500': form.errors.login }"
                required
                autocomplete="username"
              />
            </div>

            <!-- Password Field -->
            <div>
              <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                Parol
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="••••••••"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400"
                :class="{ 'border-red-300 focus:ring-red-500': form.errors.password }"
                required
                autocomplete="current-password"
              />
            </div>

            <!-- Remember & Forgot -->
            <div class="flex items-center justify-between">
              <label class="flex items-center cursor-pointer">
                <input
                  v-model="form.remember"
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                />
                <span class="ml-2 text-sm text-gray-700">Eslab qolish</span>
              </label>
              <!-- <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                Parolni unutdingizmi?
              </a> -->
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transform transition-all duration-150 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
            >
              <span v-if="!form.processing" class="flex items-center justify-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Tizimga kirish
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Yuklanmoqda...
              </span>
            </button>
          </form>

          <!-- Divider -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
              Akkauntingiz yo'qmi?
              <Link href="/register" class="font-semibold text-blue-600 hover:text-blue-500 transition-colors">
                Ro'yxatdan o'tish
              </Link>
            </p>
          </div>

          <!-- Demo Credentials -->
          <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-700 mb-2">Demo hisoblar:</p>
            <div class="text-xs text-gray-600 space-y-1">
              <div>Admin: <code class="bg-white px-2 py-0.5 rounded text-blue-600">admin / password</code></div>
              <div>User: <code class="bg-white px-2 py-0.5 rounded text-blue-600">user1 / password</code></div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <p class="mt-8 text-center text-sm text-gray-500">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. Barcha huquqlar himoyalangan.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  login: '',
  password: '',
  remember: false,
});
</script>
