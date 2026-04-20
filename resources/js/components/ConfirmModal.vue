<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="state.show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="handleCancel"></div>

        <!-- Modal -->
        <Transition
          enter-active-class="duration-200 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
          appear
        >
          <div v-if="state.show" class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
            <!-- Icon + Content -->
            <div class="p-6 text-center">
              <!-- Icon -->
              <div :class="[
                'w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center',
                iconClasses.bg
              ]">
                <!-- Danger -->
                <svg v-if="state.type === 'danger'" :class="['w-7 h-7', iconClasses.text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                <!-- Warning -->
                <svg v-else-if="state.type === 'warning'" :class="['w-7 h-7', iconClasses.text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <!-- Info -->
                <svg v-else :class="['w-7 h-7', iconClasses.text]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
              </div>

              <!-- Title -->
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1.5">
                {{ state.title }}
              </h3>

              <!-- Message -->
              <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                {{ state.message }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 px-6 pb-6">
              <button
                @click="handleCancel"
                class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors"
              >
                {{ state.cancelText }}
              </button>
              <button
                @click="handleConfirm"
                :class="[
                  'flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-colors shadow-sm',
                  buttonClasses
                ]"
              >
                {{ state.confirmText }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

const { state, handleConfirm, handleCancel } = useConfirm();

const iconClasses = computed(() => {
  switch (state.type) {
    case 'danger': return { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400' };
    case 'warning': return { bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-600 dark:text-amber-400' };
    default: return { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400' };
  }
});

const buttonClasses = computed(() => {
  switch (state.type) {
    case 'danger': return 'bg-red-600 hover:bg-red-700';
    case 'warning': return 'bg-amber-600 hover:bg-amber-700';
    default: return 'bg-blue-600 hover:bg-blue-700';
  }
});
</script>
