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
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useDiagnosticStore } from '@/stores/diagnostic';
import ProcessingAnimation from '@/Components/diagnostic/ProcessingAnimation.vue';
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
  diagnostic: {
    type: Object,
    required: true,
  },
});

const store = useDiagnosticStore();
const processingStep = ref(props.diagnostic.processing_step || 'aggregating_data');
const error = ref(null);
const isRunning = ref(false);

let pollInterval = null;

async function runDiagnostic() {
  if (isRunning.value) return;
  isRunning.value = true;
  error.value = null;

  try {
    // Start polling IMMEDIATELY before the POST request
    // This ensures we catch all step updates
    startPolling();

    // Trigger the actual processing (this returns quickly now)
    const response = await axios.post(`/business/diagnostic/${props.diagnostic.id}/run`);

    // Update step from response if available
    if (response.data.processing_step) {
      processingStep.value = response.data.processing_step;
    }

  } catch (err) {
    console.error('Run error:', err);
    stopPolling();
    error.value = err.response?.data?.message || 'Diagnostika ishga tushirib bo\'lmadi';
    isRunning.value = false;
  }
}

async function pollStatus() {
  try {
    const response = await axios.get(`/business/diagnostic/${props.diagnostic.id}/status`);
    const status = response.data;

    // Update processing step
    if (status.processing_step) {
      processingStep.value = status.processing_step;
    }

    // Check if completed or failed
    if (status.status === 'completed') {
      stopPolling();
      isRunning.value = false;
      // Small delay before redirect for UX
      setTimeout(() => {
        router.visit(`/business/diagnostic/${props.diagnostic.id}`);
      }, 500);
    } else if (status.status === 'failed') {
      stopPolling();
      isRunning.value = false;
      error.value = status.error_message || 'Diagnostika muvaffaqiyatsiz tugadi';
    }
  } catch (err) {
    console.error('Polling error:', err);
    // Don't stop polling on network error, might be temporary
  }
}

function startPolling() {
  if (pollInterval) return; // Already polling

  // Poll every 1 second for real-time updates
  pollInterval = setInterval(pollStatus, 1000);

  // Also do an immediate poll
  pollStatus();
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

async function retryDiagnostic() {
  error.value = null;
  isRunning.value = false;
  await runDiagnostic();
}

onMounted(async () => {
  if (props.diagnostic.status === 'pending') {
    // Trigger the processing
    await runDiagnostic();
  } else if (props.diagnostic.status === 'processing') {
    // Already processing, just start polling
    isRunning.value = true;
    startPolling();
  } else if (props.diagnostic.status === 'completed') {
    router.visit(`/business/diagnostic/${props.diagnostic.id}`);
  } else if (props.diagnostic.status === 'failed') {
    error.value = props.diagnostic.error_message || 'Diagnostika muvaffaqiyatsiz tugadi';
  }
});

onUnmounted(() => {
  stopPolling();
});
</script>
