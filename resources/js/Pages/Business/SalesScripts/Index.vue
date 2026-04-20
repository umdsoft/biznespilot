<template>
  <BusinessLayout title="Sotuv Skriptlari">
    <Head title="Sotuv Skriptlari" />
    <div class="p-4 sm:p-6 max-w-7xl mx-auto">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📞 Sotuv Skriptlari</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Operatorlar gaplashish uchun skriptlar. AI skript bajarilishini tekshiradi.
          </p>
        </div>
        <Link :href="route('business.sales-scripts.create')"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Yangi skript
        </Link>
      </div>

      <!-- Empty state -->
      <div v-if="scripts.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="text-6xl mb-4">📋</div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Hali skriptlar yo'q</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Birinchi skriptingizni yarating — operatorlar unga amal qiladi</p>
        <Link :href="route('business.sales-scripts.create')"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg">
          Birinchi skript yaratish
        </Link>
      </div>

      <!-- Scripts list -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="script in scripts" :key="script.id"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ script.name }}</h3>
              <p v-if="script.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ script.description }}</p>
            </div>
            <span v-if="script.is_default" class="text-[10px] bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full flex-shrink-0 ml-2">
              Standart
            </span>
          </div>

          <div class="flex items-center gap-2 mb-3 text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
              {{ typeLabel(script.script_type) }}
            </span>
            <span :class="script.is_active ? 'text-green-600' : 'text-gray-400'" class="text-xs">
              ● {{ script.is_active ? 'Faol' : 'Nofaol' }}
            </span>
          </div>

          <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
            <span>🕐 {{ script.ideal_duration_min }}-{{ script.ideal_duration_max }}s</span>
            <span>💬 {{ script.ideal_talk_ratio_min }}%-{{ script.ideal_talk_ratio_max }}%</span>
          </div>

          <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-700">
            <Link :href="route('business.sales-scripts.edit', script.id)"
              class="flex-1 text-center px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
              ✏️ Tahrirlash
            </Link>
            <button @click="deleteScript(script)"
              class="px-3 py-1.5 text-sm bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/40 dark:text-red-400 rounded-lg transition-colors">
              🗑
            </button>
          </div>
        </div>
      </div>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import axios from 'axios';
import { useConfirm } from '@/composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
  scripts: { type: Array, default: () => [] },
  defaultTemplate: { type: Object, default: () => ({}) },
  stageLabels: { type: Object, default: () => ({}) },
});

const typeLabel = (type) => ({
  inbound: 'Kiruvchi',
  outbound: 'Chiquvchi',
  follow_up: 'Follow-up',
  general: 'Umumiy',
})[type] || type;

const deleteScript = async (script) => {
  if (!await confirm({ title: "O'chirishni tasdiqlang", message: `"${script.name}" skriptini o'chirishni xohlaysizmi?`, type: 'danger', confirmText: "O'chirish" })) return;
  try {
    await axios.delete(`/business/sales-scripts/${script.id}`);
    router.reload();
  } catch (e) {
    alert('Xatolik: ' + (e.response?.data?.message || 'Nomalum'));
  }
};
</script>
