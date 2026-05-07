<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
        <div class="flex items-baseline justify-between mb-2">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Variantlar va narxlar</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Mahsulot bir necha turda bo'lsa (rang, hajm, model) — har biriga alohida narx va miqdor belgilang
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

        <!-- Track Stock Toggle -->
        <div class="mt-5 mb-5 pb-5 border-b border-slate-200 dark:border-slate-700">
            <label class="flex items-center gap-3 cursor-pointer">
                <button
                    type="button"
                    @click="form.track_stock = !form.track_stock"
                    :class="[
                        'relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors duration-200',
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
        </div>

        <!-- Variants list -->
        <div class="space-y-3">
            <div
                v-for="(variant, index) in form.variants"
                :key="index"
                class="relative bg-slate-50 dark:bg-slate-700/30 rounded-lg p-4 border border-slate-200 dark:border-slate-600"
            >
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                        Variant {{ index + 1 }}
                    </span>
                    <button
                        v-if="form.variants.length > 1"
                        type="button"
                        @click="removeVariant(index)"
                        class="p-1 text-slate-400 hover:text-red-500 transition-colors"
                        title="Variantni o'chirish"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                    <!-- Nomi: 4 of 12 -->
                    <div class="lg:col-span-4">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Nomi <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="variant.name"
                            type="text"
                            :placeholder="form.variants.length === 1 ? 'Standart' : 'Masalan: 17 Pro Max'"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                        />
                    </div>

                    <!-- Narxi: 3 of 12 -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Narxi (so'm) <span class="text-red-500">*</span>
                        </label>
                        <input
                            :value="formatPrice(variant.price)"
                            @input="onVariantPriceInput($event, index)"
                            type="text"
                            inputmode="numeric"
                            placeholder="0"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                        />
                    </div>

                    <!-- Miqdor: 2 of 12 -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Miqdori
                        </label>
                        <input
                            v-model.number="variant.stock"
                            type="number"
                            min="0"
                            placeholder="0"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                        />
                    </div>

                    <!-- Atributlar: 3 of 12 -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Atributlar
                        </label>
                        <input
                            v-model="variant.attributes"
                            type="text"
                            placeholder="Qizil, XL"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer summary -->
        <div v-if="form.variants && form.variants.length > 0" class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">
                <strong class="text-slate-900 dark:text-white">{{ form.variants.length }}</strong> ta variant
            </span>
            <div class="flex items-center gap-4 text-slate-600 dark:text-slate-400">
                <span v-if="minPrice !== null">
                    Boshlang'ich narx: <strong class="text-emerald-600 dark:text-emerald-400">{{ formatPrice(minPrice) }} so'm</strong>
                </span>
                <span>
                    Jami ombor: <strong class="text-slate-900 dark:text-white">{{ totalStock }}</strong>
                </span>
            </div>
        </div>

        <p v-if="form.errors.variants" class="mt-2 text-sm text-red-500">{{ form.errors.variants }}</p>
    </div>
</template>

<script setup>
import { computed, watch, onMounted } from 'vue';

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

// Auto-create default variant on mount if none exist
onMounted(() => {
    if (!Array.isArray(props.form.variants) || props.form.variants.length === 0) {
        props.form.variants = [createEmptyVariant()];
    }
});

// Computed: variantlar narxlarining minimumi (boshlang'ich/ko'rsatish narxi)
const minPrice = computed(() => {
    if (!Array.isArray(props.form.variants) || props.form.variants.length === 0) {
        return null;
    }
    const prices = props.form.variants
        .map((v) => Number(v.price))
        .filter((n) => Number.isFinite(n) && n > 0);
    if (prices.length === 0) return null;
    return Math.min(...prices);
});

// Computed: variantlar yig'indi miqdori
const totalStock = computed(() => {
    if (!Array.isArray(props.form.variants)) return 0;
    return props.form.variants.reduce((sum, v) => {
        const n = Number(v.stock ?? 0);
        return sum + (Number.isFinite(n) ? n : 0);
    }, 0);
});

// Variantlar narx/stock o'zgarganda asosiy form maydonlarini avto-yangilash —
// backend StoreProduct.price/stock_quantity to'g'ri saqlanadi.
watch(minPrice, (val) => {
    if (val !== null) {
        props.form.price = val;
    }
});

watch(totalStock, (val) => {
    props.form.stock_quantity = val;
});

function createEmptyVariant() {
    return {
        name: '',
        price: null,
        stock: 0,
        attributes: '',
    };
}

const formatPrice = (value) => {
    if (value === null || value === undefined || value === '') return '';
    return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
};

const onVariantPriceInput = (event, index) => {
    const raw = event.target.value.replace(/\s/g, '').replace(/\D/g, '');
    props.form.variants[index].price = raw ? parseInt(raw, 10) : null;
};

const addVariant = () => {
    if (!Array.isArray(props.form.variants)) {
        props.form.variants = [];
    }
    props.form.variants.push(createEmptyVariant());
};

const removeVariant = (index) => {
    if (props.form.variants.length <= 1) return; // Kamida 1 variant qoladi
    props.form.variants.splice(index, 1);
};
</script>
