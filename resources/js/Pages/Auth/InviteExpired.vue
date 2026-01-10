<template>
  <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-red-50 via-orange-50 to-yellow-50">
    <div class="w-full max-w-md">
      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <!-- Icon -->
        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <!-- Content -->
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Taklif muddati o'tgan</h1>
        <p class="text-gray-600 mb-6">{{ message }}</p>

        <!-- Actions -->
        <div class="space-y-3">
          <a
            :href="route('login')"
            class="block w-full py-3 px-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all"
          >
            Tizimga kirish
          </a>
          <a
            :href="route('register')"
            class="block w-full py-3 px-4 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors"
          >
            Ro'yxatdan o'tish
          </a>
        </div>
      </div>

      <!-- Footer -->
      <p class="mt-8 text-center text-xs text-gray-500">
        &copy; {{ new Date().getFullYear() }} BiznesPilot. Barcha huquqlar himoyalangan.
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  message: String,
});
</script>
