<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[9999] space-y-3 max-w-sm w-full pointer-events-none">
      <TransitionGroup
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-x-full opacity-0"
        enter-to-class="transform translate-x-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-x-0 opacity-100"
        leave-to-class="transform translate-x-full opacity-0"
      >
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'pointer-events-auto w-full rounded-xl shadow-lg overflow-hidden',
            'border backdrop-blur-sm',
            typeClasses[toast.type]
          ]"
        >
          <div class="p-4">
            <div class="flex items-start gap-3">
              <!-- Icon -->
              <div :class="['flex-shrink-0 w-6 h-6', iconColorClasses[toast.type]]">
                <component :is="icons[toast.type]" class="w-6 h-6" />
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p :class="['text-sm font-semibold', titleColorClasses[toast.type]]">
                  {{ toast.title }}
                </p>
                <p v-if="toast.message" :class="['mt-1 text-sm', messageColorClasses[toast.type]]">
                  {{ toast.message }}
                </p>
              </div>

              <!-- Close button -->
              <button
                @click="removeToast(toast.id)"
                :class="['flex-shrink-0 p-1 rounded-lg transition-colors', closeButtonClasses[toast.type]]"
              >
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Progress bar -->
            <div v-if="toast.duration > 0" class="mt-3 h-1 rounded-full overflow-hidden bg-black/10">
              <div
                :class="['h-full rounded-full', progressClasses[toast.type]]"
                :style="{
                  animation: `toast-progress ${toast.duration}ms linear forwards`
                }"
              />
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, h } from 'vue';
import { useToastStore } from '@/stores/toast';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/solid';

const toastStore = useToastStore();
const toasts = computed(() => toastStore.toasts);
const removeToast = toastStore.removeToast;

const icons = {
  success: CheckCircleIcon,
  error: ExclamationCircleIcon,
  warning: ExclamationTriangleIcon,
  info: InformationCircleIcon,
};

const typeClasses = {
  success: 'bg-emerald-50/95 border-emerald-200',
  error: 'bg-red-50/95 border-red-200',
  warning: 'bg-amber-50/95 border-amber-200',
  info: 'bg-blue-50/95 border-blue-200',
};

const iconColorClasses = {
  success: 'text-emerald-500',
  error: 'text-red-500',
  warning: 'text-amber-500',
  info: 'text-blue-500',
};

const titleColorClasses = {
  success: 'text-emerald-900',
  error: 'text-red-900',
  warning: 'text-amber-900',
  info: 'text-blue-900',
};

const messageColorClasses = {
  success: 'text-emerald-700',
  error: 'text-red-700',
  warning: 'text-amber-700',
  info: 'text-blue-700',
};

const closeButtonClasses = {
  success: 'text-emerald-500 hover:bg-emerald-100',
  error: 'text-red-500 hover:bg-red-100',
  warning: 'text-amber-500 hover:bg-amber-100',
  info: 'text-blue-500 hover:bg-blue-100',
};

const progressClasses = {
  success: 'bg-emerald-500',
  error: 'bg-red-500',
  warning: 'bg-amber-500',
  info: 'bg-blue-500',
};
</script>

<style>
@keyframes toast-progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
</style>
