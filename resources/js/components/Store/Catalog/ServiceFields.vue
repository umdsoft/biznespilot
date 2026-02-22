<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Xizmat ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
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

            <!-- Max Capacity -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Maksimal sig'im
                </label>
                <input
                    v-model.number="form.max_capacity"
                    type="number"
                    min="1"
                    placeholder="Masalan: 10"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.max_capacity" class="mt-1 text-sm text-red-500">{{ form.errors.max_capacity }}</p>
            </div>
        </div>

        <!-- Requires Staff Toggle -->
        <div class="mt-5">
            <label class="flex items-center gap-3 cursor-pointer">
                <button
                    type="button"
                    @click="form.requires_staff = !form.requires_staff"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                        form.requires_staff ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'
                    ]"
                >
                    <span
                        :class="[
                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                            form.requires_staff ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    />
                </button>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                    Xodim talab qilinadi
                </span>
            </label>
            <p v-if="form.errors.requires_staff" class="mt-1 text-sm text-red-500">{{ form.errors.requires_staff }}</p>
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
