<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { ref, computed } from 'vue';
import { ChartBarIcon, CheckCircleIcon, ClockIcon, PlusIcon, PencilIcon, StarIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    goals: { type: Array, default: () => [] },
    reviews: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
    currentUserId: { type: [String, Number], default: null },
});

// Modals
const showCreateGoalModal = ref(false);
const showUpdateProgressModal = ref(false);
const selectedGoal = ref(null);

// Forms
const goalForm = useForm({
    user_id: null,
    title: '',
    description: '',
    kpi_template_id: null,
    start_date: '',
    due_date: '',
    target_value: '',
    measurement_unit: 'number',
});

const progressForm = useForm({
    progress: 0,
    current_value: '',
    notes: '',
});

// Computed
const activeGoals = computed(() => props.goals.filter(g => g.status === 'active'));
const completedGoals = computed(() => props.goals.filter(g => g.status === 'completed'));
const averageProgress = computed(() => {
    if (!activeGoals.value.length) return 0;
    return Math.round(activeGoals.value.reduce((sum, g) => sum + g.progress, 0) / activeGoals.value.length);
});

// Methods
const createGoal = () => {
    goalForm.post(route('hr.performance.goals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateGoalModal.value = false;
            goalForm.reset();
        },
    });
};

const openUpdateProgress = (goal) => {
    selectedGoal.value = goal;
    progressForm.progress = goal.progress;
    progressForm.current_value = goal.current_value || '';
    progressForm.notes = goal.notes || '';
    showUpdateProgressModal.value = true;
};

const updateProgress = () => {
    progressForm.put(route('hr.performance.goals.update', selectedGoal.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showUpdateProgressModal.value = false;
            progressForm.reset();
            selectedGoal.value = null;
        },
    });
};

const getProgressColor = (progress) => {
    if (progress >= 80) return 'bg-green-500';
    if (progress >= 50) return 'bg-blue-500';
    if (progress >= 25) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getProgressTextColor = (progress) => {
    if (progress >= 80) return 'text-green-600 dark:text-green-400';
    if (progress >= 50) return 'text-blue-600 dark:text-blue-400';
    if (progress >= 25) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <HRLayout :title="t('hr.performance')">
        <Head :title="t('hr.performance')" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.performance_management') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.performance_subtitle') }}</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.active_goals || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol Maqsadlar</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.completed_goals || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bajarilgan</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ averageProgress }}%</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">O'rtacha Progres</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                            <StarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending_reviews || 0 }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Baholashlar</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Goals -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Faol Maqsadlar</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Hozirgi davr maqsadlaringiz</p>
                    </div>
                    <button
                        @click="showCreateGoalModal = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Maqsad Yaratish
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="activeGoals.length === 0" class="text-center py-12">
                        <ChartBarIcon class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">Faol maqsadlar yo'q</p>
                        <button
                            @click="showCreateGoalModal = true"
                            class="mt-4 text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            Birinchi maqsadingizni yarating
                        </button>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="goal in activeGoals"
                            :key="goal.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-500 dark:hover:border-blue-500 transition-colors"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ goal.title }}</h3>
                                    <p v-if="goal.description" class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ goal.description }}</p>
                                    <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Boshlanish: {{ goal.start_date }}</span>
                                        <span>Tugash: {{ goal.due_date }}</span>
                                        <span v-if="goal.kpi_name" class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded">
                                            {{ goal.kpi_name }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    @click="openUpdateProgress(goal)"
                                    class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors flex items-center gap-2"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                    Yangilash
                                </button>
                            </div>

                            <!-- Progress Bar -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Progres</span>
                                    <span :class="getProgressTextColor(goal.progress)" class="font-semibold">{{ goal.progress }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div
                                        :class="getProgressColor(goal.progress)"
                                        class="h-2.5 rounded-full transition-all duration-300"
                                        :style="{ width: goal.progress + '%' }"
                                    ></div>
                                </div>
                                <div v-if="goal.target_value" class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>Hozirgi: {{ goal.current_value || 0 }} {{ goal.measurement_unit }}</span>
                                    <span>Maqsad: {{ goal.target_value }} {{ goal.measurement_unit }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Goals -->
            <div v-if="completedGoals.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bajarilgan Maqsadlar</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Muvaffaqiyatli yakunlangan maqsadlar</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="goal in completedGoals"
                            :key="goal.id"
                            class="border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 rounded-lg p-4"
                        >
                            <div class="flex items-start gap-3">
                                <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ goal.title }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ goal.due_date }} da yakunlandi</p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">
                                            100% Bajarildi
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Reviews -->
            <div v-if="reviews.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Samaradorlik Baholashlari</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Baholash tarixi</p>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <div
                            v-for="review in reviews"
                            :key="review.id"
                            class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ review.review_period }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ review.review_date }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Baholovchi: {{ review.reviewer_name }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-2 mb-1">
                                        <StarIcon class="w-5 h-5 text-yellow-500" />
                                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ review.overall_rating }}/5</span>
                                    </div>
                                    <span :class="{
                                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300': review.status === 'completed',
                                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300': review.status === 'draft',
                                    }" class="text-xs px-2 py-1 rounded">
                                        {{ review.status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Create Goal Modal -->
        <div v-if="showCreateGoalModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Yangi Maqsad Yaratish</h2>
                </div>

                <form @submit.prevent="createGoal" class="p-6 space-y-4">
                    <div v-if="employees.length > 0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Xodim <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="goalForm.user_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option :value="null" disabled>Xodimni tanlang</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Maqsad Nomi <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="goalForm.title"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Masalan: Oylik sotuv maqsadiga erishish"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ta'rif
                        </label>
                        <textarea
                            v-model="goalForm.description"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Maqsad haqida qo'shimcha ma'lumot"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Boshlanish Sanasi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="goalForm.start_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tugash Sanasi <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="goalForm.due_date"
                                type="date"
                                required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Maqsad Qiymati
                            </label>
                            <input
                                v-model="goalForm.target_value"
                                type="number"
                                step="0.01"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="100"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                O'lchov Birligi
                            </label>
                            <select
                                v-model="goalForm.measurement_unit"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="number">Raqam</option>
                                <option value="percentage">Foiz (%)</option>
                                <option value="currency">So'm (UZS)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showCreateGoalModal = false; goalForm.reset()"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="goalForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ goalForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Update Progress Modal -->
        <div v-if="showUpdateProgressModal && selectedGoal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-lg w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Progresni Yangilash</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ selectedGoal.title }}</p>
                </div>

                <form @submit.prevent="updateProgress" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Progres: <span :class="getProgressTextColor(progressForm.progress)" class="font-bold">{{ progressForm.progress }}%</span>
                        </label>
                        <input
                            v-model.number="progressForm.progress"
                            type="range"
                            min="0"
                            max="100"
                            class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer"
                        />
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>0%</span>
                            <span>25%</span>
                            <span>50%</span>
                            <span>75%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <div v-if="selectedGoal.target_value">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Hozirgi Qiymat
                        </label>
                        <input
                            v-model="progressForm.current_value"
                            type="number"
                            step="0.01"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :placeholder="`Maqsad: ${selectedGoal.target_value} ${selectedGoal.measurement_unit}`"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Izohlar
                        </label>
                        <textarea
                            v-model="progressForm.notes"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Progres haqida qo'shimcha ma'lumot"
                        ></textarea>
                    </div>

                    <div v-if="progressForm.progress >= 100" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <p class="text-sm text-green-800 dark:text-green-300">
                            <CheckCircleIcon class="w-5 h-5 inline mr-2" />
                            Maqsad avtomatik ravishda "Bajarildi" deb belgilanadi
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showUpdateProgressModal = false; progressForm.reset(); selectedGoal = null"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            :disabled="progressForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ progressForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </HRLayout>
</template>
