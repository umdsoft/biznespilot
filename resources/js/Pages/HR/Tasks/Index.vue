<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';

defineProps({
    tasks: { type: Array, default: () => [] },
});
</script>

<template>
    <HRLayout title="Vazifalar">
        <Head title="Vazifalar" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Vazifalar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Jamoa vazifalari</p>
            </div>

            <!-- Coming Soon -->
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-12 text-white text-center">
                <ClipboardDocumentListIcon class="w-16 h-16 mx-auto mb-4 opacity-80" />
                <h2 class="text-3xl font-bold mb-2">Tez Orada</h2>
                <p class="text-lg opacity-90">Vazifalar boshqaruvi tizimi ishlab chiqilmoqda</p>
                <p class="text-sm opacity-75 mt-2">Vazifalar, Tayinlash, Progress Tracking</p>
            </div>
        </div>
    </HRLayout>
</template>
