<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { locales, getCurrentLocale, setLocale, useI18n } from '@/i18n';
import { GlobeAltIcon, ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const isOpen = ref(false);
const isChanging = ref(false);
const currentLocale = ref(getCurrentLocale());

const currentLocaleData = computed(() => {
    return locales[currentLocale.value] || locales['uz-latn'];
});

const availableLocales = computed(() => {
    return Object.values(locales).filter(l => l.code !== currentLocale.value);
});

// Labels using i18n
const labels = computed(() => {
    return {
        currentLanguage: t('language.current'),
        otherLanguages: t('language.other')
    };
});

const selectLocale = (locale) => {
    if (isChanging.value) return;
    isChanging.value = true;
    isOpen.value = false;

    // Small delay for visual feedback
    setTimeout(() => {
        setLocale(locale);
    }, 100);
};

// Close dropdown when clicking outside
const closeDropdown = (e) => {
    if (!e.target.closest('.language-switcher')) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
});
</script>

<template>
    <div class="language-switcher relative">
        <button
            @click="isOpen = !isOpen"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
            <GlobeAltIcon class="w-5 h-5" />
            <span class="hidden sm:inline">{{ currentLocaleData.nativeName }}</span>
            <span class="sm:hidden">{{ currentLocaleData.flag }}</span>
            <ChevronDownIcon
                class="w-4 h-4 transition-transform"
                :class="{ 'rotate-180': isOpen }"
            />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none z-50"
            >
                <div class="py-1">
                    <!-- Current locale (disabled) -->
                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ labels.currentLanguage }}
                    </div>
                    <div class="flex items-center gap-3 px-4 py-2 bg-blue-50 dark:bg-blue-900/20">
                        <span class="text-lg">{{ currentLocaleData.flag }}</span>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-400">
                            {{ currentLocaleData.nativeName }}
                        </span>
                        <CheckIcon class="w-4 h-4 ml-auto text-blue-500" />
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                    <!-- Other locales -->
                    <div class="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ labels.otherLanguages }}
                    </div>
                    <button
                        v-for="locale in availableLocales"
                        :key="locale.code"
                        @click="selectLocale(locale.code)"
                        :disabled="isChanging"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                    >
                        <span class="text-lg">{{ locale.flag }}</span>
                        <span>{{ locale.nativeName }}</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
