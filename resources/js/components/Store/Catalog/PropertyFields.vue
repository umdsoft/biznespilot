<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Ko'chmas mulk ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Price Type -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Narx turi
                </label>
                <select
                    v-model="form.price_type"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="sale">Sotish</option>
                    <option value="rent_monthly">Oylik ijara</option>
                    <option value="rent_daily">Kunlik ijara</option>
                </select>
                <p v-if="form.errors.price_type" class="mt-1 text-sm text-red-500">{{ form.errors.price_type }}</p>
            </div>

            <!-- Area -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Maydon (m&sup2;)
                </label>
                <input
                    v-model.number="form.area_sqm"
                    type="number"
                    min="1"
                    step="0.1"
                    placeholder="Masalan: 75"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.area_sqm" class="mt-1 text-sm text-red-500">{{ form.errors.area_sqm }}</p>
            </div>

            <!-- Rooms -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Xonalar soni
                </label>
                <input
                    v-model.number="form.rooms"
                    type="number"
                    min="1"
                    placeholder="Masalan: 3"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.rooms" class="mt-1 text-sm text-red-500">{{ form.errors.rooms }}</p>
            </div>

            <!-- Bedrooms -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Yotoq xonalar
                </label>
                <input
                    v-model.number="form.bedrooms"
                    type="number"
                    min="0"
                    placeholder="Masalan: 2"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.bedrooms" class="mt-1 text-sm text-red-500">{{ form.errors.bedrooms }}</p>
            </div>

            <!-- Bathrooms -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Hammomlar
                </label>
                <input
                    v-model.number="form.bathrooms"
                    type="number"
                    min="0"
                    placeholder="Masalan: 1"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.bathrooms" class="mt-1 text-sm text-red-500">{{ form.errors.bathrooms }}</p>
            </div>

            <!-- Floor -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Qavat
                </label>
                <input
                    v-model.number="form.floor"
                    type="number"
                    min="0"
                    placeholder="Masalan: 5"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.floor" class="mt-1 text-sm text-red-500">{{ form.errors.floor }}</p>
            </div>

            <!-- Total Floors -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Umumiy qavatlar
                </label>
                <input
                    v-model.number="form.total_floors"
                    type="number"
                    min="1"
                    placeholder="Masalan: 9"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.total_floors" class="mt-1 text-sm text-red-500">{{ form.errors.total_floors }}</p>
            </div>

            <!-- District -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Tuman
                </label>
                <input
                    v-model="form.district"
                    type="text"
                    placeholder="Masalan: Mirzo Ulug'bek"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.district" class="mt-1 text-sm text-red-500">{{ form.errors.district }}</p>
            </div>

            <!-- City -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Shahar
                </label>
                <input
                    v-model="form.city"
                    type="text"
                    placeholder="Masalan: Toshkent"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.city" class="mt-1 text-sm text-red-500">{{ form.errors.city }}</p>
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
