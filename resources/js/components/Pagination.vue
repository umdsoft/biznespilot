<template>
    <nav v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Oldingi
            </Link>
            <span v-else class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-400 bg-gray-100 dark:bg-gray-900 cursor-not-allowed">
                Oldingi
            </span>

            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
                Keyingi
            </Link>
            <span v-else class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-400 bg-gray-100 dark:bg-gray-900 cursor-not-allowed">
                Keyingi
            </span>
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <template v-for="(link, index) in links" :key="index">
                        <!-- Previous button -->
                        <Link
                            v-if="index === 0 && link.url"
                            :href="link.url"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <span class="sr-only">Oldingi</span>
                            <ChevronLeftIcon class="h-5 w-5" />
                        </Link>
                        <span
                            v-else-if="index === 0"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-sm font-medium text-gray-400 cursor-not-allowed"
                        >
                            <span class="sr-only">Oldingi</span>
                            <ChevronLeftIcon class="h-5 w-5" />
                        </span>

                        <!-- Next button -->
                        <Link
                            v-else-if="index === links.length - 1 && link.url"
                            :href="link.url"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            <span class="sr-only">Keyingi</span>
                            <ChevronRightIcon class="h-5 w-5" />
                        </Link>
                        <span
                            v-else-if="index === links.length - 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-sm font-medium text-gray-400 cursor-not-allowed"
                        >
                            <span class="sr-only">Keyingi</span>
                            <ChevronRightIcon class="h-5 w-5" />
                        </span>

                        <!-- Number pages -->
                        <Link
                            v-else-if="link.url && !link.active"
                            :href="link.url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                            v-html="link.label"
                        />
                        <span
                            v-else-if="link.active"
                            class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 dark:bg-blue-900/50 text-sm font-medium text-blue-600 dark:text-blue-400 z-10"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-gray-700 dark:text-gray-300"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
}>();
</script>
