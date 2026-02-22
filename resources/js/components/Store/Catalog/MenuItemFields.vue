<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Menyu elementi ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Preparation Time -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Tayyorlanish vaqti (daqiqa)
                </label>
                <input
                    v-model.number="form.preparation_time_minutes"
                    type="number"
                    min="1"
                    placeholder="Masalan: 30"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.preparation_time_minutes" class="mt-1 text-sm text-red-500">{{ form.errors.preparation_time_minutes }}</p>
            </div>

            <!-- Calories -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Kaloriya (kkal)
                </label>
                <input
                    v-model.number="form.calories"
                    type="number"
                    min="0"
                    placeholder="Masalan: 350"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.calories" class="mt-1 text-sm text-red-500">{{ form.errors.calories }}</p>
            </div>

            <!-- Portion Size -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Porsiya hajmi
                </label>
                <input
                    v-model="form.portion_size"
                    type="text"
                    placeholder="Masalan: 300g"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.portion_size" class="mt-1 text-sm text-red-500">{{ form.errors.portion_size }}</p>
            </div>
        </div>

        <!-- Allergens -->
        <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                Allergenlar
            </label>
            <input
                :value="allergensString"
                @input="updateAllergens($event.target.value)"
                type="text"
                placeholder="Vergul bilan ajrating: gluten, sut, yong'oq"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Vergul bilan ajrating</p>
            <p v-if="form.errors.allergens" class="mt-1 text-sm text-red-500">{{ form.errors.allergens }}</p>
        </div>

        <!-- Dietary Tags -->
        <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                Dieta teglari
            </label>
            <div class="flex flex-wrap gap-4">
                <label
                    v-for="tag in dietaryOptions"
                    :key="tag.value"
                    class="flex items-center gap-2 cursor-pointer"
                >
                    <input
                        type="checkbox"
                        :value="tag.value"
                        :checked="isDietaryTagSelected(tag.value)"
                        @change="toggleDietaryTag(tag.value)"
                        class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-emerald-500 focus:ring-emerald-500/20"
                    />
                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ tag.label }}</span>
                </label>
            </div>
            <p v-if="form.errors.dietary_tags" class="mt-1 text-sm text-red-500">{{ form.errors.dietary_tags }}</p>
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

const dietaryOptions = [
    { value: 'vegetarian', label: 'Vegetarian' },
    { value: 'vegan', label: 'Vegan' },
    { value: 'halal', label: 'Halol' },
    { value: 'gluten-free', label: 'Glutensiz' },
];

const allergensString = computed(() => {
    if (Array.isArray(props.form.allergens)) {
        return props.form.allergens.join(', ');
    }
    return props.form.allergens || '';
});

const updateAllergens = (value) => {
    props.form.allergens = value
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0);
};

const isDietaryTagSelected = (tag) => {
    if (!Array.isArray(props.form.dietary_tags)) return false;
    return props.form.dietary_tags.includes(tag);
};

const toggleDietaryTag = (tag) => {
    if (!Array.isArray(props.form.dietary_tags)) {
        props.form.dietary_tags = [];
    }
    const index = props.form.dietary_tags.indexOf(tag);
    if (index === -1) {
        props.form.dietary_tags.push(tag);
    } else {
        props.form.dietary_tags.splice(index, 1);
    }
};
</script>
