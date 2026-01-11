<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SalesHeadLayout from '@/layouts/SalesHeadLayout.vue';
import {
    UsersIcon,
    UserIcon,
    PhoneIcon,
    EnvelopeIcon,
    ChartBarIcon,
    EyeIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    members: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            top_performer: null,
        }),
    },
});

const searchQuery = ref('');

const filteredMembers = computed(() => {
    if (!searchQuery.value) return props.members;
    const q = searchQuery.value.toLowerCase();
    return props.members.filter(m => m.name?.toLowerCase().includes(q) || m.email?.toLowerCase().includes(q));
});

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getPerformanceColor = (performance) => {
    if (performance >= 80) return 'text-green-600 dark:text-green-400';
    if (performance >= 50) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <SalesHeadLayout title="Operatorlar">
        <Head title="Operatorlar" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Operatorlar</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sotuv jamoasi a'zolari</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <UsersIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Jami operatorlar</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <UserIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Faol</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Top Performer</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.top_performer || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Operator qidirish..."
                    class="w-full max-w-md pl-10 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
            </div>

            <!-- Team Grid -->
            <div v-if="filteredMembers.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <UsersIcon class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Operator topilmadi</h3>
                <p class="text-gray-500 dark:text-gray-400">Jamoangizda hali operatorlar yo'q</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="member in filteredMembers"
                    :key="member.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold">
                                {{ getInitials(member.name) }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ member.name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.role || 'Operator' }}</p>
                            </div>
                        </div>
                        <Link :href="`/sales-head/team/${member.id}`" class="p-2 text-gray-400 hover:text-emerald-600 transition-colors">
                            <EyeIcon class="w-5 h-5" />
                        </Link>
                    </div>

                    <div class="space-y-2 mb-4">
                        <p v-if="member.phone" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <PhoneIcon class="w-4 h-4" />
                            {{ member.phone }}
                        </p>
                        <p v-if="member.email" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <EnvelopeIcon class="w-4 h-4" />
                            {{ member.email }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ member.leads_count || 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Leadlar</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ member.won_count || 0 }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Yutilgan</p>
                        </div>
                        <div>
                            <p :class="['text-lg font-bold', getPerformanceColor(member.performance || 0)]">{{ member.performance || 0 }}%</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Samaradorlik</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SalesHeadLayout>
</template>
