<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                {{ t('pagination.previous') }}
            </Link>
            <span
                v-else
                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-300"
            >
                {{ t('pagination.previous') }}
            </span>
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                {{ t('pagination.next') }}
            </Link>
            <span
                v-else
                class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-300"
            >
                {{ t('pagination.next') }}
            </span>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p v-if="from && to && total" class="text-sm text-gray-700">
                    <span class="font-medium">{{ from }}</span>
                    -
                    <span class="font-medium">{{ to }}</span>
                    {{ t('pagination.from') }}
                    <span class="font-medium">{{ total }}</span>
                    {{ t('pagination.items') }}
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                    <template v-for="(link, index) in links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 focus:z-20 focus:outline-offset-0"
                            :class="{
                                'z-10 bg-blue-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600': link.active,
                                'text-gray-900 hover:bg-gray-50 focus:outline-offset-0': !link.active,
                                'rounded-l-md': index === 0,
                                'rounded-r-md': index === links.length - 1,
                            }"
                        >{{ decodeLabel(link.label) }}</Link>
                        <span
                            v-else
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-400 ring-1 ring-inset ring-gray-300"
                            :class="{
                                'rounded-l-md': index === 0,
                                'rounded-r-md': index === links.length - 1,
                            }"
                        >{{ decodeLabel(link.label) }}</span>
                    </template>
                </nav>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';

const { t } = useI18n();

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    from: {
        type: Number,
        default: null,
    },
    to: {
        type: Number,
        default: null,
    },
    total: {
        type: Number,
        default: null,
    },
});

// Xavfsiz HTML entity decode (faqat pagination labels uchun)
const decodeLabel = (label) => {
    if (!label) return '';
    // Faqat ruxsat etilgan HTML entities
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&amp;/g, '&')
        .replace(/<[^>]*>/g, ''); // Barcha HTML taglarni olib tashlash
};
</script>
