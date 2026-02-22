<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Mahsulot ma'lumotlari</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <!-- SKU -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    SKU (artikul)
                </label>
                <input
                    v-model="form.sku"
                    type="text"
                    placeholder="Masalan: PRD-001"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">{{ form.errors.sku }}</p>
            </div>

            <!-- Stock Quantity -->
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    Ombordagi miqdor
                </label>
                <input
                    v-model.number="form.stock_quantity"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
                <p v-if="form.errors.stock_quantity" class="mt-1 text-sm text-red-500">{{ form.errors.stock_quantity }}</p>
            </div>
        </div>

        <!-- Track Stock Toggle -->
        <div class="mt-5">
            <label class="flex items-center gap-3 cursor-pointer">
                <button
                    type="button"
                    @click="form.track_stock = !form.track_stock"
                    :class="[
                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                        form.track_stock ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'
                    ]"
                >
                    <span
                        :class="[
                            'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                            form.track_stock ? 'translate-x-6' : 'translate-x-1'
                        ]"
                    />
                </button>
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                    Omborni kuzatish
                </span>
            </label>
            <p v-if="form.errors.track_stock" class="mt-1 text-sm text-red-500">{{ form.errors.track_stock }}</p>
        </div>

        <!-- Variants Section -->
        <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Variantlar</h3>
                <button
                    type="button"
                    @click="addVariant"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Variant qo'shish
                </button>
            </div>

            <div v-if="!form.variants || form.variants.length === 0" class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">
                Hozircha variantlar yo'q
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="(variant, index) in form.variants"
                    :key="index"
                    class="relative bg-slate-50 dark:bg-slate-700/50 rounded-lg p-4 border border-slate-200 dark:border-slate-600"
                >
                    <button
                        type="button"
                        @click="removeVariant(index)"
                        class="absolute top-3 right-3 p-1 text-slate-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nomi</label>
                            <input
                                v-model="variant.name"
                                type="text"
                                placeholder="Masalan: Katta"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Narxi</label>
                            <input
                                v-model.number="variant.price"
                                type="number"
                                min="0"
                                step="100"
                                placeholder="0"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Miqdori</label>
                            <input
                                v-model.number="variant.stock"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Atributlar</label>
                            <input
                                v-model="variant.attributes"
                                type="text"
                                placeholder="Masalan: Qizil, XL"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <p v-if="form.errors.variants" class="mt-1 text-sm text-red-500">{{ form.errors.variants }}</p>
        </div>
    </div>
</template>

<script setup>
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

const addVariant = () => {
    if (!props.form.variants) {
        props.form.variants = [];
    }
    props.form.variants.push({
        name: '',
        price: null,
        stock: null,
        attributes: '',
    });
};

const removeVariant = (index) => {
    props.form.variants.splice(index, 1);
};
</script>
