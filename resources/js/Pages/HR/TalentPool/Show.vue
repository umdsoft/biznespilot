<template>
  <HRLayout :title="candidate.candidate_name">
    <Head :title="candidate.candidate_name" />
    <div class="space-y-6">
      <!-- Back + Header -->
      <div>
        <Link :href="route('hr.talent-pool.index')" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-3">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          Orqaga
        </Link>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/20 flex items-center justify-center">
              <span class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">{{ candidate.candidate_name?.charAt(0)?.toUpperCase() }}</span>
            </div>
            <div>
              <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ candidate.candidate_name }}</h1>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ candidate.preferred_position || 'Lavozim ko\'rsatilmagan' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <select @change="changeStatus($event.target.value)" :value="candidate.status" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg">
              <option value="available">Mavjud</option>
              <option value="contacted">Bog'lanildi</option>
              <option value="not_interested">Qiziqmaydi</option>
              <option value="hired">Ishga olindi</option>
              <option value="archived">Arxiv</option>
            </select>
          </div>
        </div>
      </div>

      <div class="grid lg:grid-cols-3 gap-6">
        <!-- Ma'lumotlar -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Kontakt ma'lumotlari</h2>
            <dl class="grid sm:grid-cols-2 gap-4 text-sm">
              <div v-if="candidate.candidate_email"><dt class="text-gray-500 dark:text-gray-400">Email</dt><dd class="text-gray-900 dark:text-gray-100">{{ candidate.candidate_email }}</dd></div>
              <div v-if="candidate.candidate_phone"><dt class="text-gray-500 dark:text-gray-400">Telefon</dt><dd class="text-gray-900 dark:text-gray-100">{{ candidate.candidate_phone }}</dd></div>
              <div v-if="candidate.current_company"><dt class="text-gray-500 dark:text-gray-400">Joriy kompaniya</dt><dd class="text-gray-900 dark:text-gray-100">{{ candidate.current_company }}</dd></div>
              <div v-if="candidate.years_of_experience"><dt class="text-gray-500 dark:text-gray-400">Tajriba</dt><dd class="text-gray-900 dark:text-gray-100">{{ candidate.years_of_experience }} yil</dd></div>
              <div v-if="candidate.expected_salary"><dt class="text-gray-500 dark:text-gray-400">Kutilayotgan ish haqi</dt><dd class="text-gray-900 dark:text-gray-100">{{ Number(candidate.expected_salary).toLocaleString() }} so'm</dd></div>
              <div v-if="candidate.employee_type"><dt class="text-gray-500 dark:text-gray-400">Xodim turi</dt><dd class="text-gray-900 dark:text-gray-100">{{ {thinker:"O'ylovchi",doer:'Ijrochi',mixed:'Aralash'}[candidate.employee_type] }}</dd></div>
            </dl>
            <div v-if="candidate.skills && candidate.skills.length" class="mt-4">
              <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Ko'nikmalar</p>
              <div class="flex flex-wrap gap-1.5">
                <span v-for="skill in candidate.skills" :key="skill" class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-lg">{{ skill }}</span>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
              <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Yozuvlar</h2>
            </div>
            <div class="p-5">
              <form @submit.prevent="addNote" class="flex gap-2 mb-4">
                <input v-model="noteText" type="text" placeholder="Yozuv qo'shish..." class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                <button type="submit" :disabled="!noteText.trim()" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors">Qo'shish</button>
              </form>
              <div v-if="candidate.notes_list && candidate.notes_list.length" class="space-y-3">
                <div v-for="note in candidate.notes_list" :key="note.id" class="flex gap-3">
                  <div class="w-1 rounded-full flex-shrink-0" :class="note.type === 'status_change' ? 'bg-blue-400' : 'bg-gray-200 dark:bg-gray-700'"></div>
                  <div>
                    <p class="text-sm text-gray-900 dark:text-gray-100">{{ note.content }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ note.user?.name }} — {{ new Date(note.created_at).toLocaleDateString('uz-UZ') }}</p>
                  </div>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400 text-center py-4">Yozuvlar yo'q</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
          <div v-if="candidate.rating" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
            <div class="flex justify-center gap-0.5 mb-1">
              <svg v-for="i in 5" :key="i" class="w-5 h-5" :class="i <= candidate.rating ? 'text-amber-400' : 'text-gray-200'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">Umumiy reyting</p>
          </div>
          <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Qo'shilgan</p>
            <p class="text-sm text-gray-900 dark:text-gray-100">{{ new Date(candidate.created_at).toLocaleDateString('uz-UZ') }}</p>
            <p v-if="candidate.added_by" class="text-xs text-gray-400 mt-1">{{ candidate.added_by?.name }}</p>
          </div>
        </div>
      </div>
    </div>
  </HRLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';

const props = defineProps({ candidate: Object });

const noteText = ref('');

const addNote = () => {
  if (!noteText.value.trim()) return;
  router.post(route('hr.talent-pool.add-note', props.candidate.id), { content: noteText.value }, {
    preserveScroll: true,
    onSuccess: () => noteText.value = '',
  });
};

const changeStatus = (status) => {
  router.post(route('hr.talent-pool.update-status', props.candidate.id), { status }, { preserveScroll: true });
};
</script>
