<template>
  <div class="h-screen flex overflow-hidden">
    <!-- Left Side - Branding & Info -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 relative overflow-hidden">
      <!-- Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <!-- Floating Elements -->
      <div class="absolute top-20 right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-10 w-96 h-96 bg-teal-400/10 rounded-full blur-3xl"></div>

      <!-- Content -->
      <div class="relative z-10 flex flex-col justify-center px-10 py-8 text-white">
        <div class="mb-8">
          <div class="flex items-center mb-4">
            <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h1 class="ml-3 text-2xl font-bold">BiznesPilot AI</h1>
          </div>
          <h2 class="text-3xl font-bold leading-tight mb-3">
            {{ t('auth.grow_business') }}
          </h2>
          <p class="text-lg text-emerald-100 leading-relaxed">
            {{ t('auth.platform_desc') }}
          </p>
        </div>

        <!-- Benefits -->
        <div class="space-y-3">
          <div class="flex items-center space-x-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
            <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">{{ t('auth.free_start') }}</h3>
              <p class="text-emerald-100 text-sm">{{ t('auth.free_trial') }}</p>
            </div>
          </div>

          <div class="flex items-center space-x-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
            <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">{{ t('auth.ai_advice') }}</h3>
              <p class="text-emerald-100 text-sm">{{ t('auth.ai_advice_desc') }}</p>
            </div>
          </div>

          <div class="flex items-center space-x-3 bg-white/10 backdrop-blur-sm rounded-lg p-3">
            <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">{{ t('auth.support_247') }}</h3>
              <p class="text-emerald-100 text-sm">{{ t('auth.support_desc') }}</p>
            </div>
          </div>
        </div>

        <!-- Testimonials Carousel -->
        <div class="mt-6 group">
          <TestimonialCarousel :autoplay-interval="5000" />
        </div>
      </div>
    </div>

    <!-- Right Side - Register Form -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gray-50 overflow-y-auto">
      <div class="w-full max-w-md py-6">
        <!-- Mobile Logo -->
        <div class="lg:hidden text-center mb-6">
          <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-600 rounded-2xl mb-3">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h1 class="text-xl font-bold text-gray-900">BiznesPilot AI</h1>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-6">
          <div class="mb-5">
            <h2 class="text-xl font-bold text-gray-900 mb-1">{{ t('auth.register_title') }}</h2>
            <p class="text-sm text-gray-600">
              {{ t('auth.register_desc') }}
            </p>
          </div>

          <!-- Error Messages -->
          <div v-if="Object.keys(form.errors).length > 0" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
            <div class="flex items-start">
              <svg class="w-4 h-4 text-red-600 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div class="text-xs text-red-800">
                <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
              </div>
            </div>
          </div>

          <form @submit.prevent="form.post('/register')" class="space-y-4">
            <!-- Name Field -->
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                {{ t('auth.full_name') }} <span class="text-red-500">*</span>
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                :placeholder="t('auth.full_name_placeholder')"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                :class="{ 'border-red-300 focus:ring-red-500': form.errors.name }"
                required
              />
            </div>

            <!-- Login Field -->
            <div>
              <label for="login" class="block text-sm font-medium text-gray-700 mb-1">
                Login <span class="text-red-500">*</span>
              </label>
              <input
                id="login"
                v-model="form.login"
                type="text"
                :placeholder="t('auth.login_username')"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                :class="{ 'border-red-300 focus:ring-red-500': form.errors.login }"
                required
                autocomplete="username"
              />
            </div>

            <!-- Email & Phone Row -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('auth.email') }}
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  placeholder="email@example.com"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.email }"
                  autocomplete="email"
                />
              </div>
              <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('auth.phone') }}
                </label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  placeholder="+998901234567"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.phone }"
                  autocomplete="tel"
                />
              </div>
            </div>

            <!-- Password Row -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('auth.password') }} <span class="text-red-500">*</span>
                </label>
                <input
                  id="password"
                  v-model="form.password"
                  type="password"
                  :placeholder="t('auth.password_min')"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.password }"
                  required
                  autocomplete="new-password"
                />
              </div>
              <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                  {{ t('auth.password_confirm') }} <span class="text-red-500">*</span>
                </label>
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  :placeholder="t('auth.password_reenter')"
                  class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400 text-sm"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.password_confirmation }"
                  required
                  autocomplete="new-password"
                />
              </div>
            </div>

            <!-- Terms Checkbox -->
            <div class="flex items-start">
              <input
                id="terms"
                v-model="form.terms"
                type="checkbox"
                class="mt-0.5 w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 cursor-pointer"
              />
              <label for="terms" class="ml-2 text-xs text-gray-600">
                {{ t('auth.terms_agree') }}
                <a href="/terms" target="_blank" class="text-emerald-600 hover:text-emerald-500 font-medium hover:underline">{{ t('auth.terms_link') }}</a>
                {{ t('auth.and') }}
                <a href="/privacy-policy" target="_blank" class="text-emerald-600 hover:text-emerald-500 font-medium hover:underline">{{ t('auth.privacy_link') }}</a>{{ t('auth.agree_suffix') }}
              </label>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold py-2.5 px-4 rounded-lg hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transform transition-all duration-150 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl text-sm"
            >
              <span v-if="!form.processing" class="flex items-center justify-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                {{ t('auth.register_button') }}
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ t('auth.loading') }}
              </span>
            </button>
          </form>

          <!-- Divider -->
          <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
              {{ t('auth.have_account') }}
              <Link href="/login" class="font-semibold text-emerald-600 hover:text-emerald-500 transition-colors">
                {{ t('auth.login_link') }}
              </Link>
            </p>
          </div>
        </div>

        <!-- Footer -->
        <p class="mt-4 text-center text-xs text-gray-500">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. {{ t('auth.copyright') }}
        </p>
      </div>
    </div>

  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import TestimonialCarousel from '@/components/Auth/TestimonialCarousel.vue';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const form = useForm({
  name: '',
  login: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: false,
});
</script>
