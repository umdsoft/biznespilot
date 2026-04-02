<template>
  <HRLayout title="Kadrlar Zaxirasi">
    <Head title="Kadrlar Zaxirasi" />
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Kadrlar Zaxirasi</h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Potentsial nomzodlar bazasi</p>
        </div>
        <button @click="showAddModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Nomzod qo'shish
        </button>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
          <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ stats.total }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Jami</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
          <p class="text-2xl font-semibold text-emerald-600">{{ stats.available }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Mavjud</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
          <p class="text-2xl font-semibold text-blue-600">{{ stats.contacted }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Bog'lanildi</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3">
          <p class="text-2xl font-semibold text-purple-600">{{ stats.hired }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">Ishga olindi</p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
        <div class="flex flex-wrap gap-3">
          <input v-model="search" type="text" placeholder="Nomzod qidirish..." class="flex-1 min-w-[200px] px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" @input="debouncedFilter" />
          <select v-model="statusFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg" @change="applyFilters">
            <option value="">Barcha statuslar</option>
            <option value="available">Mavjud</option>
            <option value="contacted">Bog'lanildi</option>
            <option value="not_interested">Qiziqmaydi</option>
            <option value="hired">Ishga olindi</option>
          </select>
          <select v-model="ratingFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg" @change="applyFilters">
            <option value="">Barcha reytinglar</option>
            <option value="5">5 yulduz</option>
            <option value="4">4+ yulduz</option>
            <option value="3">3+ yulduz</option>
          </select>
          <select v-model="typeFilter" class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg" @change="applyFilters">
            <option value="">Barcha turlar</option>
            <option value="thinker">O'ylovchi</option>
            <option value="doer">Ijrochi</option>
            <option value="mixed">Aralash</option>
          </select>
        </div>
      </div>

      <!-- Candidates List -->
      <div v-if="candidates.data && candidates.data.length > 0" class="space-y-3">
        <Link
          v-for="c in candidates.data"
          :key="c.id"
          :href="route('hr.talent-pool.show', c.id)"
          class="block bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
        >
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/20 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">{{ c.candidate_name?.charAt(0)?.toUpperCase() }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ c.candidate_name }}</p>
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
              </div>
              <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span v-if="c.preferred_position">{{ c.preferred_position }}</span>
                <span v-if="c.current_company">{{ c.current_company }}</span>
                <span v-if="c.years_of_experience">{{ c.years_of_experience }} yil tajriba</span>
              </div>
            </div>
            <!-- Skills -->
            <div class="hidden md:flex flex-wrap gap-1 max-w-[200px]">
              <span v-for="skill in (c.skills || []).slice(0, 3)" :key="skill" class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-400 rounded">{{ skill }}</span>
              <span v-if="(c.skills || []).length > 3" class="text-xs text-gray-400">+{{ c.skills.length - 3 }}</span>
            </div>
            <!-- Rating -->
            <div v-if="c.rating" class="flex items-center gap-0.5 flex-shrink-0">
              <svg v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= c.rating ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
            </div>
          </div>
        </Link>
      </div>

      <!-- Empty -->
      <div v-else class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl py-16 text-center">
        <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center mx-auto mb-4">
          <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
        </div>
        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-1">Kadrlar zaxirasi bo'sh</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Nomzodlarni qo'shib boshlang</p>
        <button @click="showAddModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Nomzod qo'shish
        </button>
      </div>
    </div>

    <!-- Add Candidate Modal -->
    <Teleport to="body">
      <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/40" @click="showAddModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full border border-gray-200 dark:border-gray-700 max-h-[90vh] overflow-y-auto">
          <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Nomzod qo'shish</h3>
          </div>
          <form @submit.prevent="submitAdd" class="p-5 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ism *</label>
              <input v-model="addForm.candidate_name" type="text" required class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input v-model="addForm.candidate_email" type="email" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telefon</label>
                <input v-model="addForm.candidate_phone" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lavozim</label>
                <input v-model="addForm.preferred_position" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reyting</label>
                <select v-model="addForm.rating" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                  <option :value="null">Tanlanmagan</option>
                  <option :value="5">5 — Ajoyib</option>
                  <option :value="4">4 — Yaxshi</option>
                  <option :value="3">3 — O'rtacha</option>
                  <option :value="2">2 — Past</option>
                  <option :value="1">1 — Mos emas</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Xodim turi</label>
              <select v-model="addForm.employee_type" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">Tanlanmagan</option>
                <option value="thinker">O'ylovchi (Thinker)</option>
                <option value="doer">Ijrochi (Doer)</option>
                <option value="mixed">Aralash</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Izoh</label>
              <textarea v-model="addForm.notes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"></textarea>
            </div>
            <div class="flex gap-2.5 pt-2">
              <button type="button" @click="showAddModal = false" class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
              <button type="submit" :disabled="addForm.processing" class="flex-1 px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50">Qo'shish</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </HRLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';

const props = defineProps({
  candidates: Object,
  stats: Object,
  filters: Object,
});

const showAddModal = ref(false);
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const ratingFilter = ref(props.filters?.rating || '');
const typeFilter = ref(props.filters?.employee_type || '');

const addForm = useForm({
  candidate_name: '',
  candidate_email: '',
  candidate_phone: '',
  preferred_position: '',
  rating: null,
  employee_type: '',
  notes: '',
});

let debounceTimer;
const debouncedFilter = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(applyFilters, 400);
};

const applyFilters = () => {
  router.get(route('hr.talent-pool.index'), {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    rating: ratingFilter.value || undefined,
    employee_type: typeFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

const submitAdd = () => {
  addForm.post(route('hr.talent-pool.store'), {
    onSuccess: () => {
      showAddModal.value = false;
      addForm.reset();
    },
  });
};

const statusLabel = (s) => ({ available: 'Mavjud', contacted: "Bog'lanildi", not_interested: 'Qiziqmaydi', hired: 'Ishga olindi', archived: 'Arxiv' }[s] || s);
const statusClass = (s) => ({
  available: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400',
  contacted: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
  not_interested: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
  hired: 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400',
  archived: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
}[s] || 'bg-gray-100 text-gray-600');
</script>
