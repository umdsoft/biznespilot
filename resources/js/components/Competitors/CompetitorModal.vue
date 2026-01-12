<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>

                <div class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-xl">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ competitor ? 'Raqobatchini Tahrirlash' : 'Yangi Raqobatchi Qo\'shish' }}
                        </h3>
                        <button
                            @click="$emit('close')"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Name with Autocomplete -->
                            <div class="md:col-span-2 relative">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nomi *</label>
                                <div class="relative">
                                    <input
                                        v-model="form.name"
                                        @input="searchGlobalCompetitors"
                                        @focus="showSuggestions = true"
                                        @blur="hideSuggestionsDelayed"
                                        type="text"
                                        required
                                        autocomplete="off"
                                        class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        placeholder="Raqobatchi nomini yozing..."
                                    />
                                    <div v-if="searching" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <svg class="w-5 h-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Autocomplete Suggestions -->
                                <div
                                    v-if="showSuggestions && suggestions.length > 0"
                                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg max-h-72 overflow-y-auto"
                                >
                                    <div class="p-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                        {{ suggestions.length }} ta raqobatchi topildi
                                    </div>
                                    <button
                                        v-for="item in suggestions"
                                        :key="item.id"
                                        type="button"
                                        @mousedown.prevent="selectGlobalCompetitor(item)"
                                        class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-0"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ item.name }}</span>
                                                    <span v-if="item.same_industry" class="px-1.5 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded">
                                                        Bir soha
                                                    </span>
                                                    <span v-if="item.same_region" class="px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded">
                                                        Bir viloyat
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    <span v-if="item.industry">{{ item.industry }}</span>
                                                    <span v-if="item.industry && item.region">•</span>
                                                    <span v-if="item.region">{{ item.region }}</span>
                                                    <span v-if="item.district">, {{ item.district }}</span>
                                                </div>
                                                <div v-if="item.instagram_handle || item.telegram_handle" class="flex items-center gap-3 mt-1">
                                                    <span v-if="item.instagram_handle" class="text-xs text-pink-600 dark:text-pink-400">
                                                        @{{ item.instagram_handle.replace('@', '') }}
                                                    </span>
                                                    <span v-if="item.telegram_handle" class="text-xs text-blue-600 dark:text-blue-400">
                                                        @{{ item.telegram_handle.replace('@', '') }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 text-right">
                                                <div v-if="item.has_swot" class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    SWOT mavjud
                                                </div>
                                                <div v-if="item.swot_contributors_count > 0" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                    {{ item.swot_contributors_count }} biznes
                                                </div>
                                            </div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Selected Global Competitor Notice -->
                                <div v-if="selectedGlobalCompetitor" class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">Ma'lumotlar avtomatik yuklandi</p>
                                                <p class="text-xs text-emerald-600 dark:text-emerald-400">Bu raqobatchi bazada mavjud edi</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="clearSelectedGlobal"
                                            class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tavsif</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
                                    placeholder="Raqobatchi haqida qisqacha ma'lumot"
                                ></textarea>
                            </div>

                            <!-- Location Fields -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Viloyat</label>
                                <select
                                    v-model="form.region"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">Tanlang</option>
                                    <option v-for="region in regions" :key="region" :value="region">{{ region }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tuman/Shahar</label>
                                <input
                                    v-model="form.district"
                                    type="text"
                                    placeholder="Tuman yoki shahar nomi"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tahdid Darajasi *</label>
                                <select
                                    v-model="form.threat_level"
                                    required
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="low">Past</option>
                                    <option value="medium">O'rta</option>
                                    <option value="high">Yuqori</option>
                                    <option value="critical">Kritik</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Status</label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="active">Faol</option>
                                    <option value="inactive">Nofaol</option>
                                    <option value="archived">Arxivlangan</option>
                                </select>
                            </div>

                            <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Platformalar</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Instagram</label>
                                        <input
                                            v-model="form.instagram_handle"
                                            type="text"
                                            placeholder="@username"
                                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Telegram</label>
                                        <input
                                            v-model="form.telegram_handle"
                                            type="text"
                                            placeholder="@username"
                                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <label class="flex items-center cursor-pointer">
                                    <input
                                        v-model="form.auto_monitor"
                                        type="checkbox"
                                        class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Avtomatik kuzatishni yoqish</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                @click="$emit('close')"
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-sm font-medium text-white transition-colors"
                            >
                                {{ competitor ? 'Saqlash' : 'Qo\'shish' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    competitor: { type: Object, default: null },
    currentBusiness: { type: Object, default: null },
    panelType: { type: String, default: 'business' },
});

const emit = defineEmits(['close', 'submit']);

// Uzbekistan regions
const regions = [
    'Toshkent shahri',
    'Toshkent viloyati',
    'Andijon viloyati',
    'Buxoro viloyati',
    'Farg\'ona viloyati',
    'Jizzax viloyati',
    'Xorazm viloyati',
    'Namangan viloyati',
    'Navoiy viloyati',
    'Qashqadaryo viloyati',
    'Qoraqalpog\'iston Respublikasi',
    'Samarqand viloyati',
    'Sirdaryo viloyati',
    'Surxondaryo viloyati',
];

const form = reactive({
    name: '',
    description: '',
    industry: '',
    location: '',
    region: '',
    district: '',
    threat_level: 'medium',
    status: 'active',
    instagram_handle: '',
    telegram_handle: '',
    facebook_page: '',
    tiktok_handle: '',
    auto_monitor: true,
    check_frequency_hours: 24,
    global_competitor_id: null,
});

// Autocomplete state
const searching = ref(false);
const showSuggestions = ref(false);
const suggestions = ref([]);
const selectedGlobalCompetitor = ref(null);
let searchTimeout = null;
let abortController = null;

// Search global competitors - optimized with abort and longer debounce
const searchGlobalCompetitors = () => {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }

    // Abort previous request if still pending
    if (abortController) {
        abortController.abort();
        abortController = null;
    }

    const query = form.name?.trim();

    // Minimum 3 characters for search
    if (!query || query.length < 3) {
        suggestions.value = [];
        searching.value = false;
        showSuggestions.value = false;
        return;
    }

    // Show loading indicator immediately
    searching.value = true;

    // Debounce - wait 500ms before making request
    searchTimeout = setTimeout(async () => {
        // Create new abort controller
        abortController = new AbortController();

        try {
            const prefix = props.panelType === 'business' ? '/business' : '/marketing';
            const response = await axios.get(`${prefix}/competitors/search-global`, {
                params: { q: query },
                signal: abortController.signal,
            });

            // Only update if this is still the current query
            if (form.name?.trim() === query) {
                // Validate response is actually an array
                const data = response.data;
                if (Array.isArray(data)) {
                    suggestions.value = data;
                    showSuggestions.value = data.length > 0;
                } else {
                    // Invalid response format - reset
                    suggestions.value = [];
                    showSuggestions.value = false;
                }
            }
        } catch (error) {
            // Ignore abort errors
            if (error.name !== 'CanceledError' && error.code !== 'ERR_CANCELED') {
                console.error('Search failed:', error);
            }
            suggestions.value = [];
            showSuggestions.value = false;
        } finally {
            searching.value = false;
            abortController = null;
        }
    }, 500);
};

// Select a global competitor
const selectGlobalCompetitor = (item) => {
    selectedGlobalCompetitor.value = item;
    showSuggestions.value = false;
    suggestions.value = [];

    // Fill form with selected competitor data
    form.name = item.name;
    form.description = item.description || '';
    form.industry = item.industry || '';
    form.region = item.region || '';
    form.district = item.district || '';
    form.instagram_handle = item.instagram_handle || '';
    form.telegram_handle = item.telegram_handle || '';
    form.global_competitor_id = item.id;
};

// Clear selected global competitor
const clearSelectedGlobal = () => {
    selectedGlobalCompetitor.value = null;
    form.global_competitor_id = null;
};

// Hide suggestions with delay (to allow click)
const hideSuggestionsDelayed = () => {
    setTimeout(() => {
        showSuggestions.value = false;
    }, 200);
};

watch(() => props.competitor, (newVal) => {
    if (newVal) {
        Object.assign(form, newVal);
        selectedGlobalCompetitor.value = null;
    } else {
        Object.assign(form, {
            name: '',
            description: '',
            industry: props.currentBusiness?.industry_name || '',
            location: props.currentBusiness?.region || '',
            region: '',
            district: '',
            threat_level: 'medium',
            status: 'active',
            instagram_handle: '',
            telegram_handle: '',
            facebook_page: '',
            tiktok_handle: '',
            auto_monitor: true,
            check_frequency_hours: 24,
            global_competitor_id: null,
        });
        selectedGlobalCompetitor.value = null;
    }
}, { immediate: true });

const submitForm = () => {
    emit('submit', { ...form });
};
</script>
