<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Avtomobil ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Brand -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Brend
                </label>
                <input
                    v-model="form.brand"
                    type="text"
                    placeholder="Masalan: Chevrolet"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.brand" class="mt-1 text-sm text-red-500">{{ form.errors.brand }}</p>
            </div>

            <!-- Model -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Model
                </label>
                <input
                    v-model="form.model"
                    type="text"
                    placeholder="Masalan: Malibu"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.model" class="mt-1 text-sm text-red-500">{{ form.errors.model }}</p>
            </div>

            <!-- Year -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Ishlab chiqarilgan yili
                </label>
                <input
                    v-model.number="form.year"
                    type="number"
                    min="1900"
                    :max="new Date().getFullYear() + 1"
                    placeholder="Masalan: 2023"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.year" class="mt-1 text-sm text-red-500">{{ form.errors.year }}</p>
            </div>

            <!-- Mileage -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Yurgan masofasi (km)
                </label>
                <input
                    v-model.number="form.mileage_km"
                    type="number"
                    min="0"
                    placeholder="Masalan: 50000"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.mileage_km" class="mt-1 text-sm text-red-500">{{ form.errors.mileage_km }}</p>
            </div>

            <!-- Fuel Type -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Yoqilg'i turi
                </label>
                <select
                    v-model="form.fuel_type"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="petrol">Benzin</option>
                    <option value="diesel">Dizel</option>
                    <option value="gas">Gaz</option>
                    <option value="electric">Elektr</option>
                    <option value="hybrid">Gibrid</option>
                </select>
                <p v-if="form.errors.fuel_type" class="mt-1 text-sm text-red-500">{{ form.errors.fuel_type }}</p>
            </div>

            <!-- Transmission -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Uzatmalar qutisi
                </label>
                <select
                    v-model="form.transmission"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="manual">Mexanika</option>
                    <option value="automatic">Avtomat</option>
                </select>
                <p v-if="form.errors.transmission" class="mt-1 text-sm text-red-500">{{ form.errors.transmission }}</p>
            </div>

            <!-- Color -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Rangi
                </label>
                <input
                    v-model="form.color"
                    type="text"
                    placeholder="Masalan: Oq"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.color" class="mt-1 text-sm text-red-500">{{ form.errors.color }}</p>
            </div>

            <!-- Engine Volume -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Dvigatel hajmi (L)
                </label>
                <input
                    v-model="form.engine_volume"
                    type="text"
                    placeholder="Masalan: 1.5"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.engine_volume" class="mt-1 text-sm text-red-500">{{ form.errors.engine_volume }}</p>
            </div>

            <!-- Condition -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Holati
                </label>
                <select
                    v-model="form.condition"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                >
                    <option value="">Tanlang</option>
                    <option value="new">Yangi</option>
                    <option value="used">Ishlatilgan</option>
                    <option value="certified">Sertifikatlangan</option>
                </select>
                <p v-if="form.errors.condition" class="mt-1 text-sm text-red-500">{{ form.errors.condition }}</p>
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
