<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 -translate-y-4"
    enter-to-class="opacity-100 translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0"
    leave-to-class="opacity-0 -translate-y-4"
  >
    <!-- Sinov davri tugagan -->
    <div
      v-if="isExpired"
      class="relative overflow-hidden mb-6 bg-gradient-to-r from-red-600 to-red-700 rounded-2xl shadow-lg"
    >
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <pattern id="trial-expired-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="currentColor" />
          </pattern>
          <rect x="0" y="0" width="100" height="100" fill="url(#trial-expired-pattern)" />
        </svg>
      </div>

      <div class="relative p-5 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="text-white">
            <h3 class="text-lg font-bold">{{ isTrial ? 'Sinov davri' : 'Obuna muddati' }} tugadi!</h3>
            <p class="text-white/80 text-sm">
              Ma'lumotlaringiz saqlanadi, lekin tizimdan foydalanish uchun tarifni tanlang.
            </p>
          </div>
        </div>

        <Link
          href="/business/subscription"
          class="inline-flex items-center px-6 py-2.5 bg-white text-red-600 rounded-xl font-semibold text-sm hover:bg-white/90 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
          Hozir sotib olish
        </Link>
      </div>
    </div>

    <!-- Xavfli holat: 1-3 kun qoldi -->
    <div
      v-else-if="isUrgent"
      class="relative overflow-hidden mb-6 bg-gradient-to-r from-red-500 to-orange-500 rounded-2xl shadow-lg"
    >
      <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <pattern id="trial-urgent-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
            <circle cx="10" cy="10" r="2" fill="currentColor" />
          </pattern>
          <rect x="0" y="0" width="100" height="100" fill="url(#trial-urgent-pattern)" />
        </svg>
      </div>

      <div class="relative p-5 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="text-white">
            <h3 class="text-lg font-bold">
              {{ isTrial ? 'Sinov' : 'Obuna' }} tugashiga {{ daysRemaining }} kun qoldi!
            </h3>
            <p class="text-white/80 text-sm">
              Ma'lumotlaringiz o'chib ketmasligi uchun tarifni tanlang.
            </p>
          </div>
        </div>

        <Link
          href="/business/subscription"
          class="inline-flex items-center px-6 py-2.5 bg-white text-red-600 rounded-xl font-semibold text-sm hover:bg-white/90 transition-all duration-200 shadow-lg hover:shadow-xl"
        >
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
          Tarif tanlash
        </Link>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'

const page = usePage()

// Subscription ma'lumotlari (HandleInertiaRequests dan keladi)
const subscriptionProp = computed(() => page.props.subscription)
const subscription = computed(() => subscriptionProp.value?.subscription)
const hasSubscription = computed(() => subscriptionProp.value?.has_subscription === true)
const isTrial = computed(() => subscription.value?.is_trial === true)
const daysRemaining = computed(() => Math.round(subscription.value?.days_remaining ?? 0))

// Holatlar — trial va pullik obuna uchun bir xil
const isExpired = computed(() => {
  // Obuna umuman yo'q
  if (!hasSubscription.value) return true
  // Muddati tugagan
  return daysRemaining.value <= 0
})

const isUrgent = computed(() => {
  if (!hasSubscription.value) return false
  return daysRemaining.value > 0 && daysRemaining.value <= 3
})

// Banner faqat 3 kun qolganda yoki muddati tugaganda ko'rinadi
// Dismiss kerak emas — banner 3 kundan ko'p qolganda umuman ko'rinmaydi
</script>
