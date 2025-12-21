<template>
  <Head title="Diagnostika Tarixi" />

  <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
            <Link href="/business/diagnostic" class="hover:text-gray-700">Diagnostika</Link>
            <ChevronRightIcon class="w-4 h-4" />
            <span>Tarix</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Diagnostika Tarixi</h1>
          <p class="text-gray-500 mt-1">Barcha o'tkazilgan diagnostikalar</p>
        </div>

        <Link
          href="/business/diagnostic"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
        >
          Yangi diagnostika
        </Link>
      </div>

      <!-- Diagnostics list -->
      <div v-if="diagnostics.length" class="bg-white rounded-lg border overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Versiya
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ball
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sana
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amallar
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="diagnostic in diagnostics"
              :key="diagnostic.id"
              class="hover:bg-gray-50"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="font-medium text-gray-900">#{{ diagnostic.version }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div
                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium"
                    :class="scoreColorClass(diagnostic.overall_score)"
                  >
                    {{ diagnostic.overall_score }}
                  </div>
                  <span class="ml-2 text-sm" :class="scoreTextClass(diagnostic.overall_score)">
                    {{ scoreLabel(diagnostic.overall_score) }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="statusBadgeClass(diagnostic.status)"
                >
                  {{ statusLabel(diagnostic.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ diagnostic.completed_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <div class="flex items-center justify-end space-x-2">
                  <Link
                    :href="`/business/diagnostic/${diagnostic.id}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Ko'rish
                  </Link>
                  <button
                    v-if="diagnostics.length > 1 && diagnostic.id !== diagnostics[0].id"
                    @click="compareDiagnostics(diagnostic.id)"
                    class="text-gray-600 hover:text-gray-900"
                  >
                    Taqqoslash
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty state -->
      <div
        v-else
        class="bg-white rounded-lg border p-8 text-center"
      >
        <ClockIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
        <h3 class="font-medium text-gray-900">Diagnostika tarixi bo'sh</h3>
        <p class="text-gray-500 text-sm mt-1">
          Hali diagnostika o'tkazilmagan
        </p>
        <Link
          href="/business/diagnostic"
          class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm"
        >
          Birinchi diagnostikani boshlash
        </Link>
      </div>

      <!-- Score trend chart placeholder -->
      <div v-if="diagnostics.length > 1" class="mt-8 bg-white rounded-lg border p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Ball dinamikasi</h3>
        <div class="h-48 flex items-end justify-between space-x-2">
          <div
            v-for="diagnostic in [...diagnostics].reverse().slice(-10)"
            :key="diagnostic.id"
            class="flex-1 flex flex-col items-center"
          >
            <div
              class="w-full rounded-t transition-all"
              :class="scoreColorClass(diagnostic.overall_score)"
              :style="{ height: `${diagnostic.overall_score * 1.5}px` }"
            ></div>
            <span class="text-xs text-gray-500 mt-2">#{{ diagnostic.version }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronRightIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  diagnostics: {
    type: Array,
    default: () => [],
  },
});

function scoreColorClass(score) {
  if (score >= 80) return 'bg-blue-100 text-blue-700';
  if (score >= 60) return 'bg-green-100 text-green-700';
  if (score >= 40) return 'bg-yellow-100 text-yellow-700';
  return 'bg-red-100 text-red-700';
}

function scoreTextClass(score) {
  if (score >= 80) return 'text-blue-600';
  if (score >= 60) return 'text-green-600';
  if (score >= 40) return 'text-yellow-600';
  return 'text-red-600';
}

function scoreLabel(score) {
  if (score >= 80) return 'Ajoyib';
  if (score >= 60) return 'Yaxshi';
  if (score >= 40) return "O'rtacha";
  return 'Zaif';
}

function statusBadgeClass(status) {
  const classes = {
    completed: 'bg-green-100 text-green-700',
    processing: 'bg-blue-100 text-blue-700',
    pending: 'bg-yellow-100 text-yellow-700',
    failed: 'bg-red-100 text-red-700',
  };
  return classes[status] || classes.pending;
}

function statusLabel(status) {
  const labels = {
    completed: 'Tugallandi',
    processing: 'Jarayonda',
    pending: 'Kutilmoqda',
    failed: 'Xatolik',
  };
  return labels[status] || status;
}

function compareDiagnostics(diagnosticId) {
  const latestId = props.diagnostics[0].id;
  router.visit(`/business/diagnostic/${latestId}/compare/${diagnosticId}`);
}
</script>
