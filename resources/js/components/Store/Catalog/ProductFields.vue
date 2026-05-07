<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Mahsulot ma'lumotlari</h2>

        <!-- Info banner: variantlar mavjudligida narx/miqdor qaysi joyda hisoblanishini tushuntiradi -->
        <div v-if="hasVariants" class="mb-5 flex gap-3 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
            <svg class="flex-shrink-0 w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-900 dark:text-blue-200">
                <p class="font-medium mb-1">Variantlar mavjud — narx va miqdor variantlardan olinadi</p>
                <p class="text-blue-700 dark:text-blue-300">
                    Asosiy <strong>"Narx"</strong> — bu boshlang'ich/eng arzon narx (mahsulot ro'yxatida ko'rsatiladi).
                    Mijoz variant tanlasa, o'sha variant narxi qo'llaniladi.
                    <strong>"Ombordagi miqdor"</strong> avtomatik variantlar yig'indisi sifatida hisoblanadi.
                </p>
            </div>
        </div>

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
                <label class="flex items-center justify-between text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                    <span>Ombordagi miqdor</span>
                    <span v-if="hasVariants" class="text-xs text-blue-600 dark:text-blue-400 font-normal">(variantlardan)</span>
                </label>
                <input
                    :value="hasVariants ? variantsStockTotal : form.stock_quantity"
                    @input="onStockInput"
                    :disabled="hasVariants"
                    type="number"
                    min="0"
                    placeholder="0"
                    :class="[
                        'w-full rounded-lg border px-4 py-2.5 transition-colors',
                        hasVariants
                            ? 'border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 cursor-not-allowed'
                            : 'border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20'
                    ]"
                />
                <p v-if="hasVariants" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Variantlar yig'indisi avtomatik hisoblanadi
                </p>
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
                <div>
                    <span class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Omborni kuzatish
                    </span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                        Yoqilgan bo'lsa: ombor tugaganda mahsulot avtomatik "tugagan" deb belgilanadi
                    </span>
                </div>
            </label>
            <p v-if="form.errors.track_stock" class="mt-1 text-sm text-red-500">{{ form.errors.track_stock }}</p>
        </div>

        <!-- Variants Section -->
        <div class="mt-6 border-t border-slate-200 dark:border-slate-700 pt-5">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Variantlar</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Mahsulot bir necha turda bo'lsa (rang, hajm, model) — qo'shing
                    </p>
                </div>
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

            <div v-if="!hasVariants" class="text-sm text-slate-500 dark:text-slate-400 text-center py-4 mt-3 bg-slate-50 dark:bg-slate-700/30 rounded-lg border border-dashed border-slate-200 dark:border-slate-600">
                Variantlar yo'q — yuqoridagi <strong>asosiy narx va miqdor</strong> ishlatiladi
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
import { computed, watch } from 'vue';

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

// Variantlar mavjudligini hisoblaydi (faqat to'liq to'ldirilganlar)
const hasVariants = computed(() => {
    if (!Array.isArray(props.form.variants) || props.form.variants.length === 0) {
        return false;
    }
    // Kamida bitta variant nomi to'ldirilgan bo'lishi kerak (bo'sh row sanalmaydi)
    return props.form.variants.some((v) => v && v.name && String(v.name).trim() !== '');
});

// Variantlar yig'indi miqdori — asosiy stock_quantity uchun avto-hisob
const variantsStockTotal = computed(() => {
    if (!hasVariants.value) return 0;
    return props.form.variants.reduce((sum, v) => {
        const n = Number(v.stock ?? v.stock_quantity ?? 0);
        return sum + (Number.isFinite(n) ? n : 0);
    }, 0);
});

// Variantlar tahrirlanganda asosiy stock_quantity'ni avto-yangilaymiz —
// shunda backend ham to'g'ri qiymatni saqlaydi (variantlar yig'indisi).
watch(variantsStockTotal, (val) => {
    if (hasVariants.value) {
        props.form.stock_quantity = val;
    }
});

const onStockInput = (event) => {
    if (hasVariants.value) return; // disabled holatda input ishlamaydi
    const value = Number(event.target.value);
    props.form.stock_quantity = Number.isFinite(value) ? value : 0;
};

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
    // Oxirgi variant olib tashlansa, asosiy stock_quantity manual rejimga qaytadi
    if (!hasVariants.value) {
        // Foydalanuvchi qo'lda kiritsin
    }
};
</script>
