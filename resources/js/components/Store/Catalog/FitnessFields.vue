<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Fitness ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Duration Minutes -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Davomiyligi (daqiqa)
                </label>
                <input
                    v-model.number="form.duration_minutes"
                    type="number"
                    min="1"
                    placeholder="Masalan: 60"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.duration_minutes" class="mt-1 text-sm text-red-500">{{ form.errors.duration_minutes }}</p>
            </div>

            <!-- Max Participants -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Maksimal qatnashchilar
                </label>
                <input
                    v-model.number="form.max_participants"
                    type="number"
                    min="1"
                    placeholder="Masalan: 20"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.max_participants" class="mt-1 text-sm text-red-500">{{ form.errors.max_participants }}</p>
            </div>

            <!-- Instructor -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Trener
                </label>
                <input
                    v-model="form.instructor"
                    type="text"
                    placeholder="To'liq ismi"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.instructor" class="mt-1 text-sm text-red-500">{{ form.errors.instructor }}</p>
            </div>

            <!-- Difficulty -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Qiyinlik darajasi
                </label>
                <select
                    v-model="form.difficulty"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="beginner">Boshlang'ich</option>
                    <option value="intermediate">O'rta</option>
                    <option value="advanced">Yuqori</option>
                    <option value="professional">Professional</option>
                </select>
                <p v-if="form.errors.difficulty" class="mt-1 text-sm text-red-500">{{ form.errors.difficulty }}</p>
            </div>

            <!-- Duration Days (for membership) -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    A'zolik muddati (kun)
                </label>
                <input
                    v-model.number="form.duration_days"
                    type="number"
                    min="1"
                    placeholder="Masalan: 30"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.duration_days" class="mt-1 text-sm text-red-500">{{ form.errors.duration_days }}</p>
            </div>
        </div>

        <!-- Features -->
        <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Xususiyatlar
            </label>
            <input
                :value="featuresString"
                @input="updateFeatures($event.target.value)"
                type="text"
                placeholder="Vergul bilan ajrating: basseyn, sauna, dush"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Vergul bilan ajrating</p>
            <p v-if="form.errors.features" class="mt-1 text-sm text-red-500">{{ form.errors.features }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const featuresString = computed(() => {
    if (Array.isArray(props.form.features)) {
        return props.form.features.join(', ');
    }
    return props.form.features || '';
});

const updateFeatures = (value) => {
    props.form.features = value
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0);
};
</script>
