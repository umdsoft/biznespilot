<template>
  <Head title="Diagnostika jarayonda..." />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12">
    <div class="max-w-2xl w-full mx-auto px-4">
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-6 text-center text-white">
          <h1 class="text-xl font-bold">AI Diagnostika</h1>
          <p class="text-indigo-100 mt-1">Versiya #{{ diagnostic.version }}</p>
        </div>

        <!-- Processing animation -->
        <ProcessingAnimation
          :currentStep="processingStep"
          @cancel="cancelDiagnostic"
        />

        <!-- Error state -->
        <div
          v-if="error"
          class="p-6 bg-red-50 border-t border-red-200"
        >
          <div class="flex items-center space-x-3">
            <ExclamationCircleIcon class="w-6 h-6 text-red-500" />
            <div>
              <h3 class="font-medium text-red-800">Xatolik yuz berdi</h3>
              <p class="text-sm text-red-600 mt-1">{{ error }}</p>
            </div>
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              @click="retryDiagnostic"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
            >
              Qayta urinish
            </button>
            <Link
              href="/business/diagnostic"
              class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm"
            >
              Orqaga
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import ProcessingAnimation from '@/Components/diagnostic/ProcessingAnimation.vue';
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostic: {
    type: Object,
    required: true,
  },
});

const store = useDiagnosticStore();
const processingStep = ref(props.diagnostic.processing_step || 'aggregating_data');
const error = ref(null);

let pollInterval = null;

async function pollStatus() {
  try {
    const status = await store.fetchStatus(props.diagnostic.id);
    processingStep.value = status.processing_step;

    if (status.status === 'completed') {
      stopPolling();
      router.visit(`/business/diagnostic/${props.diagnostic.id}`);
    } else if (status.status === 'failed') {
      stopPolling();
      error.value = status.error_message || 'Diagnostika muvaffaqiyatsiz tugadi';
    }
  } catch (err) {
    console.error('Polling error:', err);
  }
}

function startPolling() {
  pollStatus(); // Initial call
  pollInterval = setInterval(pollStatus, 2000);
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
}

function cancelDiagnostic() {
  stopPolling();
  router.visit('/business/diagnostic');
}

function retryDiagnostic() {
  error.value = null;
  store.startDiagnostic();
}

onMounted(() => {
  if (props.diagnostic.status === 'pending' || props.diagnostic.status === 'processing') {
    startPolling();
  } else if (props.diagnostic.status === 'completed') {
    router.visit(`/business/diagnostic/${props.diagnostic.id}`);
  } else if (props.diagnostic.status === 'failed') {
    error.value = 'Diagnostika muvaffaqiyatsiz tugadi';
  }
});

onUnmounted(() => {
  stopPolling();
});
</script>
