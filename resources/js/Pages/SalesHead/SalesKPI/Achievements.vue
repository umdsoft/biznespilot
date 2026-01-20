<template>
  <SalesHeadLayout title="Yutuqlar">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Yutuqlar</h2>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Jamoa yutuqlari va mukofotlar tizimi</p>
        </div>
        <button @click="openCreateModal"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Yangi yutuq qo'shish
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-gradient-to-r from-yellow-500 to-amber-500 rounded-xl p-6 text-white">
        <p class="text-sm text-yellow-100 mb-1">Jami yutuqlar</p>
        <p class="text-3xl font-bold">{{ stats.total_definitions }}</p>
      </div>
      <div class="bg-gradient-to-r from-emerald-500 to-green-500 rounded-xl p-6 text-white">
        <p class="text-sm text-emerald-100 mb-1">Berilgan yutuqlar</p>
        <p class="text-3xl font-bold">{{ stats.total_awarded }}</p>
      </div>
      <div class="bg-gradient-to-r from-blue-500 to-indigo-500 rounded-xl p-6 text-white">
        <p class="text-sm text-blue-100 mb-1">Bu oy olingan</p>
        <p class="text-3xl font-bold">{{ stats.awarded_this_month }}</p>
      </div>
      <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
        <p class="text-sm text-purple-100 mb-1">Jami ballar</p>
        <p class="text-3xl font-bold">{{ stats.total_points }}</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="flex gap-8">
        <button @click="activeTab = 'definitions'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'definitions' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Yutuq turlari
        </button>
        <button @click="activeTab = 'recent'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'recent' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          So'nggi olinganlar
        </button>
        <button @click="activeTab = 'team'"
                :class="['pb-4 px-1 font-medium text-sm border-b-2 transition-colors',
                         activeTab === 'team' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
          Jamoa statistikasi
        </button>
      </nav>
    </div>

    <!-- Achievement Definitions Tab -->
    <div v-if="activeTab === 'definitions'" class="space-y-6">
      <!-- Categories -->
      <div v-for="(achievements, category) in groupedAchievements" :key="category" class="space-y-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <span :class="getCategoryIcon(category)"></span>
          {{ getCategoryLabel(category) }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="achievement in achievements" :key="achievement.id"
               class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow">
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                <div :class="['w-14 h-14 rounded-xl flex items-center justify-center text-2xl', getTierBackground(achievement.tier)]">
                  {{ achievement.icon || '🏆' }}
                </div>
                <div>
                  <h4 class="font-bold text-gray-900 dark:text-white">{{ achievement.name }}</h4>
                  <span :class="getTierBadge(achievement.tier)">{{ getTierLabel(achievement.tier) }}</span>
                </div>
              </div>
              <div class="flex gap-1">
                <button @click="editAchievement(achievement)" class="p-1 text-blue-600 hover:bg-blue-50 rounded">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
              </div>
            </div>
            <p class="text-sm text-gray-500 mb-4">{{ achievement.description }}</p>
            <div class="flex items-center justify-between text-sm">
              <span class="text-emerald-600 font-medium">+{{ achievement.points }} ball</span>
              <span class="text-gray-500">{{ achievement.awarded_count || 0 }} marta berilgan</span>
            </div>
            <div v-if="achievement.threshold" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
              <p class="text-xs text-gray-500">Shart: {{ achievement.threshold_type }} ≥ {{ achievement.threshold }}</p>
            </div>
          </div>
        </div>
      </div>
      <div v-if="Object.keys(groupedAchievements).length === 0"
           class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center text-gray-500 border border-gray-200 dark:border-gray-700">
        Yutuqlar hali qo'shilmagan
      </div>
    </div>

    <!-- Recent Achievements Tab -->
    <div v-if="activeTab === 'recent'" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="divide-y divide-gray-200 dark:divide-gray-700">
        <div v-for="earned in recentAchievements" :key="earned.id"
             class="p-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
          <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-xl', getTierBackground(earned.achievement?.tier)]">
            {{ earned.achievement?.icon || '🏆' }}
          </div>
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900 dark:text-white">{{ earned.user?.name }}</span>
              <span class="text-gray-400">→</span>
              <span class="font-medium text-emerald-600">{{ earned.achievement?.name }}</span>
            </div>
            <p class="text-sm text-gray-500">{{ formatDate(earned.earned_at) }}</p>
          </div>
          <div class="text-right">
            <span class="text-emerald-600 font-bold">+{{ earned.points_awarded }} ball</span>
            <p v-if="earned.times_earned > 1" class="text-xs text-gray-500">{{ earned.times_earned }} marta</p>
          </div>
        </div>
        <div v-if="recentAchievements.length === 0" class="p-12 text-center text-gray-500">
          So'nggi olingan yutuqlar yo'q
        </div>
      </div>
    </div>

    <!-- Team Stats Tab -->
    <div v-if="activeTab === 'team'" class="space-y-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Achievers -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">Top 5 - Yutuqlar bo'yicha</h3>
          <div class="space-y-3">
            <div v-for="(member, index) in topAchievers" :key="member.user_id"
                 class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
              <span :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm',
                            index === 0 ? 'bg-yellow-100 text-yellow-700' :
                            index === 1 ? 'bg-gray-100 text-gray-700' :
                            index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-50 text-gray-500']">
                {{ index + 1 }}
              </span>
              <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-700 dark:text-emerald-400 font-medium">
                {{ getInitials(member.user?.name) }}
              </div>
              <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-white">{{ member.user?.name }}</p>
                <p class="text-sm text-gray-500">{{ member.achievements_count }} ta yutuq</p>
              </div>
              <span class="text-emerald-600 font-bold">{{ member.total_points }} ball</span>
            </div>
            <div v-if="topAchievers.length === 0" class="p-6 text-center text-gray-500">
              Ma'lumotlar yo'q
            </div>
          </div>
        </div>

        <!-- Achievements Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">Kategoriya bo'yicha</h3>
          <div class="space-y-4">
            <div v-for="(count, category) in categoryStats" :key="category">
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-400">{{ getCategoryLabel(category) }}</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ count }}</span>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div :class="getCategoryBarColor(category)"
                     :style="{ width: getCategoryPercentage(count) + '%' }"
                     class="h-2 rounded-full transition-all duration-500"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ editingAchievement ? 'Yutuqni tahrirlash' : 'Yangi yutuq qo\'shish' }}
        </h3>
        <form @submit.prevent="saveAchievement" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nomi</label>
              <input v-model="form.name" type="text" required
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Icon (emoji)</label>
              <input v-model="form.icon" type="text" placeholder="🏆"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
            <textarea v-model="form.description" rows="2"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategoriya</label>
              <select v-model="form.category" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="sales">Sotuv</option>
                <option value="activity">Faoliyat</option>
                <option value="quality">Sifat</option>
                <option value="milestone">Bosqich</option>
                <option value="special">Maxsus</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Daraja</label>
              <select v-model="form.tier" required
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                <option value="bronze">Bronza</option>
                <option value="silver">Kumush</option>
                <option value="gold">Oltin</option>
                <option value="platinum">Platina</option>
                <option value="diamond">Olmoz</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ball</label>
              <input v-model.number="form.points" type="number" min="1" required
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chegarasi</label>
              <input v-model.number="form.threshold" type="number"
                     class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
            </div>
          </div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
              <input v-model="form.is_repeatable" type="checkbox" class="rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Takrorlanadigan</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="form.is_active" type="checkbox" class="rounded">
              <span class="text-sm text-gray-700 dark:text-gray-300">Faol</span>
            </label>
          </div>
          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="modalOpen = false"
                    class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
              Bekor qilish
            </button>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
              Saqlash
            </button>
          </div>
        </form>
      </div>
    </div>
  </SalesHeadLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';

const props = defineProps({
  achievements: Array,
  recentAchievements: Array,
  topAchievers: Array,
  stats: Object,
  panelType: String,
});

const activeTab = ref('definitions');
const modalOpen = ref(false);
const editingAchievement = ref(null);
const form = ref({
  name: '',
  description: '',
  icon: '🏆',
  category: 'sales',
  tier: 'bronze',
  points: 10,
  threshold: null,
  is_repeatable: false,
  is_active: true,
});

const groupedAchievements = computed(() => {
  const grouped = {};
  props.achievements.forEach(a => {
    if (!grouped[a.category]) grouped[a.category] = [];
    grouped[a.category].push(a);
  });
  return grouped;
});

const categoryStats = computed(() => {
  const stats = {};
  props.achievements.forEach(a => {
    stats[a.category] = (stats[a.category] || 0) + (a.awarded_count || 0);
  });
  return stats;
});

const maxCategoryStat = computed(() => {
  const values = Object.values(categoryStats.value);
  return Math.max(...values, 1);
});

const getCategoryPercentage = (count) => {
  return (count / maxCategoryStat.value) * 100;
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('uz-UZ', { day: 'numeric', month: 'short', year: 'numeric' });
};

const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';

const getCategoryLabel = (category) => {
  const labels = {
    sales: 'Sotuv yutuqlari',
    activity: 'Faoliyat yutuqlari',
    quality: 'Sifat yutuqlari',
    milestone: 'Bosqich yutuqlari',
    special: 'Maxsus yutuqlar',
  };
  return labels[category] || category;
};

const getCategoryIcon = (category) => {
  const icons = {
    sales: 'text-2xl',
    activity: 'text-2xl',
    quality: 'text-2xl',
    milestone: 'text-2xl',
    special: 'text-2xl',
  };
  return icons[category] || '';
};

const getCategoryBarColor = (category) => {
  const colors = {
    sales: 'bg-emerald-500',
    activity: 'bg-blue-500',
    quality: 'bg-purple-500',
    milestone: 'bg-yellow-500',
    special: 'bg-pink-500',
  };
  return colors[category] || 'bg-gray-500';
};

const getTierLabel = (tier) => {
  const labels = { bronze: 'Bronza', silver: 'Kumush', gold: 'Oltin', platinum: 'Platina', diamond: 'Olmoz' };
  return labels[tier] || tier;
};

const getTierBadge = (tier) => {
  const badges = {
    bronze: 'px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700',
    silver: 'px-2 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-700',
    gold: 'px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700',
    platinum: 'px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700',
    diamond: 'px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700',
  };
  return badges[tier] || badges.bronze;
};

const getTierBackground = (tier) => {
  const backgrounds = {
    bronze: 'bg-orange-100 dark:bg-orange-900/30',
    silver: 'bg-gray-200 dark:bg-gray-700',
    gold: 'bg-yellow-100 dark:bg-yellow-900/30',
    platinum: 'bg-blue-100 dark:bg-blue-900/30',
    diamond: 'bg-purple-100 dark:bg-purple-900/30',
  };
  return backgrounds[tier] || backgrounds.bronze;
};

const openCreateModal = () => {
  editingAchievement.value = null;
  form.value = {
    name: '',
    description: '',
    icon: '🏆',
    category: 'sales',
    tier: 'bronze',
    points: 10,
    threshold: null,
    is_repeatable: false,
    is_active: true,
  };
  modalOpen.value = true;
};

const editAchievement = (achievement) => {
  editingAchievement.value = achievement;
  form.value = { ...achievement };
  modalOpen.value = true;
};

const saveAchievement = () => {
  if (editingAchievement.value) {
    router.put(`/sales-head/sales-kpi/achievements/${editingAchievement.value.id}`, form.value, {
      onSuccess: () => { modalOpen.value = false; }
    });
  } else {
    router.post('/sales-head/sales-kpi/achievements', form.value, {
      onSuccess: () => { modalOpen.value = false; }
    });
  }
};
</script>
