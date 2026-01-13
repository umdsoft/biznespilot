<script setup>
import { ref } from 'vue';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    UserCircleIcon,
    PhoneIcon,
    EnvelopeIcon,
    BriefcaseIcon,
    ArrowLeftIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    applications: { type: Array, default: () => [] },
    jobPostings: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
});

const getStatusColor = (color) => {
    const colors = {
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
        green: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        red: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[color] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <HRLayout title="Arizalar">
        <Head title="Arizalar - Recruiting" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        :href="route('hr.recruiting.index')"
                        class="inline-flex items-center gap-2 text-purple-600 hover:text-purple-700 mb-2 transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                        <span>Vakansiyalarga qaytish</span>
                    </Link>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Arizalar</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Ishga ariza qilgan nomzodlar
                    </p>
                </div>
            </div>

            <!-- Applications List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="applications.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="app in applications"
                        :key="app.id"
                        class="p-6 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4 flex-1">
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                    <UserCircleIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ app.candidate_name }}
                                    </h3>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="flex items-center gap-1">
                                            <BriefcaseIcon class="w-4 h-4" />
                                            {{ app.job_posting_title }}
                                        </span>
                                        <span v-if="app.years_of_experience" class="flex items-center gap-1">
                                            {{ app.years_of_experience }} yil tajriba
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <a
                                            :href="`mailto:${app.candidate_email}`"
                                            class="flex items-center gap-1 hover:text-purple-600"
                                        >
                                            <EnvelopeIcon class="w-4 h-4" />
                                            {{ app.candidate_email }}
                                        </a>
                                        <a
                                            :href="`tel:${app.candidate_phone}`"
                                            class="flex items-center gap-1 hover:text-purple-600"
                                        >
                                            <PhoneIcon class="w-4 h-4" />
                                            {{ app.candidate_phone }}
                                        </a>
                                    </div>
                                    <div class="flex items-center gap-2 mt-3">
                                        <span
                                            :class="[
                                                'inline-flex px-3 py-1 rounded-full text-xs font-medium',
                                                getStatusColor(app.status_color)
                                            ]"
                                        >
                                            {{ app.status_label }}
                                        </span>
                                        <span v-if="app.rating" class="text-sm text-gray-600 dark:text-gray-400">
                                            ⭐ {{ app.rating }}/5
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-500">
                                            {{ app.applied_at }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <UserCircleIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        Arizalar mavjud emas
                    </p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
