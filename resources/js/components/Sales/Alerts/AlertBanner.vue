<template>
  <Transition name="slide">
    <div
      v-if="alerts.length > 0"
      class="relative rounded-xl overflow-hidden"
      :class="bannerClass"
    >
      <!-- Animated background gradient -->
      <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent animate-shimmer" />
      </div>

      <div class="relative p-4">
        <div class="flex items-start gap-3">
          <!-- Icon -->
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="w-6 h-6" :class="iconClass" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="font-medium" :class="textClass">
              {{ primaryAlert.title }}
            </p>
            <p class="text-sm mt-0.5" :class="subtextClass">
              {{ primaryAlert.message }}
            </p>

            <!-- Multiple alerts indicator -->
            <p
              v-if="alerts.length > 1"
              class="text-xs mt-2"
              :class="subtextClass"
            >
              va yana {{ alerts.length - 1 }} ta ogohlantirish
            </p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2">
            <button
              @click="handleAction"
              class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
              :class="buttonClass"
            >
              Ko'rish
            </button>
            <button
              @click="dismiss"
              class="p-1.5 rounded-lg transition-colors"
              :class="dismissClass"
            >
              <XMarkIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  alerts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['dismiss', 'action'])

const primaryAlert = computed(() => props.alerts[0] || {})

const isUrgent = computed(() => primaryAlert.value?.priority === 'urgent')

const bannerClass = computed(() => {
  return isUrgent.value
    ? 'bg-gradient-to-r from-red-500/20 to-orange-500/20 border border-red-500/30'
    : 'bg-gradient-to-r from-orange-500/20 to-yellow-500/20 border border-orange-500/30'
})

const iconClass = computed(() => {
  return isUrgent.value ? 'text-red-400' : 'text-orange-400'
})

const textClass = computed(() => {
  return isUrgent.value ? 'text-red-100' : 'text-orange-100'
})

const subtextClass = computed(() => {
  return isUrgent.value ? 'text-red-200/70' : 'text-orange-200/70'
})

const buttonClass = computed(() => {
  return isUrgent.value
    ? 'bg-red-500 hover:bg-red-600 text-white'
    : 'bg-orange-500 hover:bg-orange-600 text-white'
})

const dismissClass = computed(() => {
  return isUrgent.value
    ? 'hover:bg-red-500/20 text-red-300'
    : 'hover:bg-orange-500/20 text-orange-300'
})

const handleAction = () => {
  emit('action', primaryAlert.value)
  router.visit(route('sales-head.alerts.index'))
}

const dismiss = () => {
  emit('dismiss', primaryAlert.value)
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}
</style>
