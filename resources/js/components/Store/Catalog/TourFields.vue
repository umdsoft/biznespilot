<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Tur ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Duration Days -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Davomiyligi (kun)
                </label>
                <input
                    v-model.number="form.duration_days"
                    type="number"
                    min="1"
                    placeholder="Masalan: 7"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.duration_days" class="mt-1 text-sm text-red-500">{{ form.errors.duration_days }}</p>
            </div>

            <!-- Destination -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Manzil
                </label>
                <input
                    v-model="form.destination"
                    type="text"
                    placeholder="Masalan: Samarqand"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.destination" class="mt-1 text-sm text-red-500">{{ form.errors.destination }}</p>
            </div>

            <!-- Departure City -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Jo'nash shahri
                </label>
                <input
                    v-model="form.departure_city"
                    type="text"
                    placeholder="Masalan: Toshkent"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.departure_city" class="mt-1 text-sm text-red-500">{{ form.errors.departure_city }}</p>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Boshlanish sanasi
                </label>
                <input
                    v-model="form.start_date"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">{{ form.errors.start_date }}</p>
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Tugash sanasi
                </label>
                <input
                    v-model="form.end_date"
                    type="date"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-500">{{ form.errors.end_date }}</p>
            </div>

            <!-- Max Travelers -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Maksimal sayohatchilar
                </label>
                <input
                    v-model.number="form.max_travelers"
                    type="number"
                    min="1"
                    placeholder="Masalan: 20"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.max_travelers" class="mt-1 text-sm text-red-500">{{ form.errors.max_travelers }}</p>
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
                    <option value="easy">Oson</option>
                    <option value="moderate">O'rtacha</option>
                    <option value="challenging">Qiyin</option>
                    <option value="extreme">Ekstremal</option>
                </select>
                <p v-if="form.errors.difficulty" class="mt-1 text-sm text-red-500">{{ form.errors.difficulty }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    form: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});
</script>
