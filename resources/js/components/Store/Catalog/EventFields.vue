<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Tadbir ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- Venue -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Joy nomi
                </label>
                <input
                    v-model="form.venue"
                    type="text"
                    placeholder="Masalan: Milliy kutubxona"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">{{ form.errors.venue }}</p>
            </div>

            <!-- Total Seats -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Umumiy o'rinlar soni
                </label>
                <input
                    v-model.number="form.total_seats"
                    type="number"
                    min="1"
                    placeholder="Masalan: 200"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.total_seats" class="mt-1 text-sm text-red-500">{{ form.errors.total_seats }}</p>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Boshlanish vaqti
                </label>
                <input
                    v-model="form.start_date"
                    type="datetime-local"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">{{ form.errors.start_date }}</p>
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Tugash vaqti
                </label>
                <input
                    v-model="form.end_date"
                    type="datetime-local"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-500">{{ form.errors.end_date }}</p>
            </div>
        </div>

        <!-- Address (full width) -->
        <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Manzil
            </label>
            <input
                v-model="form.address"
                type="text"
                placeholder="To'liq manzil"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
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
