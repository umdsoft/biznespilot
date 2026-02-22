<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Kurs ma'lumotlari</h2>

        <!-- What You Learn -->
        <div class="mb-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Nimalarni o'rganasiz
            </label>
            <textarea
                v-model="form.what_you_learn"
                rows="3"
                placeholder="Kurs davomida nimalarni o'rganishingiz mumkin..."
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p v-if="form.errors.what_you_learn" class="mt-1 text-sm text-red-500">{{ form.errors.what_you_learn }}</p>
        </div>

        <!-- Requirements -->
        <div class="mb-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Talablar
            </label>
            <textarea
                v-model="form.requirements"
                rows="3"
                placeholder="Kursga qatnashish uchun zarur bilim va ko'nikmalar..."
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p v-if="form.errors.requirements" class="mt-1 text-sm text-red-500">{{ form.errors.requirements }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Duration Hours -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Davomiyligi (soat)
                </label>
                <input
                    v-model.number="form.duration_hours"
                    type="number"
                    min="1"
                    placeholder="Masalan: 40"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.duration_hours" class="mt-1 text-sm text-red-500">{{ form.errors.duration_hours }}</p>
            </div>

            <!-- Level -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Daraja
                </label>
                <select
                    v-model="form.level"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="beginner">Boshlang'ich</option>
                    <option value="intermediate">O'rta</option>
                    <option value="advanced">Yuqori</option>
                    <option value="all">Barcha darajalar</option>
                </select>
                <p v-if="form.errors.level" class="mt-1 text-sm text-red-500">{{ form.errors.level }}</p>
            </div>

            <!-- Instructor -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    O'qituvchi
                </label>
                <input
                    v-model="form.instructor"
                    type="text"
                    placeholder="To'liq ismi"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.instructor" class="mt-1 text-sm text-red-500">{{ form.errors.instructor }}</p>
            </div>

            <!-- Max Students -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Maksimal talabalar soni
                </label>
                <input
                    v-model.number="form.max_students"
                    type="number"
                    min="1"
                    placeholder="Masalan: 30"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.max_students" class="mt-1 text-sm text-red-500">{{ form.errors.max_students }}</p>
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
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mt-5">
            <!-- Format -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Format
                </label>
                <select
                    v-model="form.format"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="online">Onlayn</option>
                    <option value="offline">Oflayn</option>
                    <option value="hybrid">Gibrid</option>
                </select>
                <p v-if="form.errors.format" class="mt-1 text-sm text-red-500">{{ form.errors.format }}</p>
            </div>

            <!-- Certificate Included Toggle -->
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-3 cursor-pointer">
                    <button
                        type="button"
                        @click="form.certificate_included = !form.certificate_included"
                        :class="[
                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                            form.certificate_included ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'
                        ]"
                    >
                        <span
                            :class="[
                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                                form.certificate_included ? 'translate-x-6' : 'translate-x-1'
                            ]"
                        />
                    </button>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        Sertifikat beriladi
                    </span>
                </label>
                <p v-if="form.errors.certificate_included" class="mt-1 text-sm text-red-500">{{ form.errors.certificate_included }}</p>
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
