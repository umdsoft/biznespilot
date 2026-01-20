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
            {{ t('auth.platform_desc') }}
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
              <h3 class="font-semibold text-lg">{{ t('auth.feature_ai_analysis') }}</h3>
              <p class="text-blue-100 text-sm">{{ t('auth.feature_ai_analysis_desc') }}</p>
            </div>
          </div>

          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg">{{ t('auth.feature_marketing') }}</h3>
              <p class="text-blue-100 text-sm">{{ t('auth.feature_marketing_desc') }}</p>
            </div>
          </div>

          <div class="flex items-start space-x-4">
            <div class="flex-shrink-0 w-10 h-10 bg-white/10 backdrop-blur-sm rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold text-lg">{{ t('auth.feature_analytics') }}</h3>
              <p class="text-blue-100 text-sm">{{ t('auth.feature_analytics_desc') }}</p>
            </div>
          </div>
        </div>

        <!-- Testimonials Carousel -->
        <div class="mt-8 group">
          <TestimonialCarousel :autoplay-interval="5000" />
        </div>

        <!-- Stats -->
        <div class="mt-6 grid grid-cols-3 gap-6 pt-6 border-t border-white/20">
          <div>
            <div class="text-3xl font-bold">50+</div>
            <div class="text-blue-200 text-sm">{{ t('auth.stats_businesses') }}</div>
          </div>
          <div>
            <div class="text-3xl font-bold">1K+</div>
            <div class="text-blue-200 text-sm">{{ t('auth.stats_customers') }}</div>
          </div>
          <div>
            <div class="text-3xl font-bold">98%</div>
            <div class="text-blue-200 text-sm">{{ t('auth.stats_satisfaction') }}</div>
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
            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ t('auth.welcome') }}</h2>
            <p class="text-gray-600">
              {{ t('auth.login_desc') }}
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

          <form @submit.prevent="handleLogin" class="space-y-6">
            <!-- Login Field -->
            <div>
              <label for="login" class="block text-sm font-semibold text-gray-700 mb-2">
                {{ t('auth.login_or_phone') }}
              </label>
              <input
                id="login"
                v-model="form.login"
                type="text"
                :placeholder="t('auth.login_placeholder')"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-gray-900 placeholder-gray-400"
                :class="{ 'border-red-300 focus:ring-red-500': form.errors.login }"
                required
                autocomplete="username"
              />
            </div>

            <!-- Password Field -->
            <div>
              <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                {{ t('auth.password') }}
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
                <span class="ml-2 text-sm text-gray-700">{{ t('auth.remember_me') }}</span>
              </label>
              <!-- <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                {{ t('auth.forgot_password') }}
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
                {{ t('auth.login_button') }}
              </span>
              <span v-else class="flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ t('auth.loading') }}
              </span>
            </button>
          </form>

          <!-- Divider -->
          <div class="mt-8 pt-6 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
              {{ t('auth.no_account') }}
              <Link href="/register" class="font-semibold text-blue-600 hover:text-blue-500 transition-colors">
                {{ t('auth.register_link') }}
              </Link>
            </p>
          </div>

        </div>

        <!-- Footer -->
        <p class="mt-8 text-center text-sm text-gray-500">
          &copy; {{ new Date().getFullYear() }} BiznesPilot AI. {{ t('auth.copyright') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import TestimonialCarousel from '@/components/Auth/TestimonialCarousel.vue';
import { refreshCsrfToken, isCsrfError } from '@/utils/csrf';
import { useI18n } from '@/i18n';

const { t } = useI18n();

// Use reactive form instead of Inertia useForm to have full control
const form = reactive({
    login: '',
    password: '',
    remember: false,
    processing: false,
    errors: {},
});

const isReady = ref(false);

// Initialize CSRF token when login page loads (after logout)
onMounted(async () => {
    await refreshCsrfToken();
    isReady.value = true;
});

// Handle login with axios directly to avoid Inertia CSRF issues
const handleLogin = async () => {
    if (!isReady.value || form.processing) return;

    form.errors = {};
    form.processing = true;

    try {
        // Refresh CSRF token right before login
        await refreshCsrfToken();

        // Use axios directly for login
        const response = await window.axios.post('/login', {
            login: form.login,
            password: form.password,
            remember: form.remember,
        });

        // If login successful, redirect to the intended URL or dashboard
        if (response.data.redirect) {
            window.location.href = response.data.redirect;
        } else {
            window.location.href = '/';
        }
    } catch (error) {
        form.password = '';

        if (isCsrfError(error)) {
            await refreshCsrfToken();
            form.errors.login = t('auth.session_expired');
        } else if (error.response?.status === 422) {
            const errors = error.response.data.errors || {};
            form.errors = {
                login: errors.login?.[0] || error.response.data.message,
                password: errors.password?.[0],
            };
        } else if (error.response?.data?.message) {
            form.errors.login = error.response.data.message;
        } else {
            form.errors.login = t('auth.login_error');
        }
    } finally {
        form.processing = false;
    }
};
</script>
