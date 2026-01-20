<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { useI18n } from '@/i18n';
import {
    BuildingOfficeIcon,
    PlusIcon,
    UsersIcon,
    BriefcaseIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    orgStructure: Object,
    business: Object,
});

const getDepartmentStats = (department) => {
    if (!department.positions) return { total: 0, filled: 0, vacant: 0 };

    const total = department.positions.reduce((sum, p) => sum + (p.required_count || 0), 0);
    const filled = department.positions.reduce((sum, p) => sum + (p.current_count || 0), 0);
    const vacant = total - filled;

    return { total, filled, vacant };
};

const getDepartmentFillRate = (department) => {
    const stats = getDepartmentStats(department);
    return stats.total > 0 ? Math.round((stats.filled / stats.total) * 100) : 0;
};

const getTotalStats = () => {
    if (!props.orgStructure?.departments) return { total: 0, filled: 0, vacant: 0 };

    let total = 0;
    let filled = 0;

    props.orgStructure.departments.forEach(dept => {
        const stats = getDepartmentStats(dept);
        total += stats.total;
        filled += stats.filled;
    });

    return { total, filled, vacant: total - filled };
};

const totalStats = getTotalStats();
const overallFillRate = totalStats.total > 0 ? Math.round((totalStats.filled / totalStats.total) * 100) : 0;
</script>

<template>
    <HRLayout :title="t('hr.org_structure')">
        <Head :title="t('hr.org_structure')" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ t('hr.org_structure') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ t('hr.org_structure_subtitle') }}
                    </p>
                </div>

                <Link
                    v-if="!orgStructure"
                    :href="route('hr.org-structure.create')"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    {{ t('hr.create_org_structure') }}
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="!orgStructure" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full mb-4">
                    <BuildingOfficeIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    {{ t('hr.no_org_structure') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
                    {{ t('hr.org_structure_desc') }}
                </p>
                <Link
                    :href="route('hr.org-structure.create')"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    {{ t('hr.start') }}
                </Link>
            </div>

            <!-- Org Structure Display -->
            <div v-else class="space-y-6">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-600 dark:text-purple-400">{{ t('hr.total_positions') }}</p>
                                <p class="text-3xl font-bold text-purple-900 dark:text-purple-100 mt-2">
                                    {{ totalStats.total }}
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 dark:bg-purple-800/50 rounded-lg">
                                <BriefcaseIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-600 dark:text-green-400">{{ t('hr.filled_positions') }}</p>
                                <p class="text-3xl font-bold text-green-900 dark:text-green-100 mt-2">
                                    {{ totalStats.filled }}
                                </p>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-800/50 rounded-lg">
                                <UsersIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-600 dark:text-orange-400">{{ t('hr.vacant_positions') }}</p>
                                <p class="text-3xl font-bold text-orange-900 dark:text-orange-100 mt-2">
                                    {{ totalStats.vacant }}
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 dark:bg-orange-800/50 rounded-lg">
                                <BriefcaseIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ t('hr.fill_rate') }}</p>
                                <p class="text-3xl font-bold text-blue-900 dark:text-blue-100 mt-2">
                                    {{ overallFillRate }}%
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 dark:bg-blue-800/50 rounded-lg">
                                <ChartBarIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Org Structure Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ orgStructure.name }}
                            </h2>
                            <p v-if="orgStructure.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ orgStructure.description }}
                            </p>
                            <div v-if="orgStructure.business_type" class="mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                    {{ orgStructure.business_type.name_uz }}
                                </span>
                            </div>
                        </div>
                        <Link
                            :href="route('hr.org-structure.edit', orgStructure.id)"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            {{ t('hr.edit') }}
                        </Link>
                    </div>

                    <!-- Departments List -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Bo'limlar
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="department in orgStructure.departments"
                                :key="department.id"
                                class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-600 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-3 flex-1">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                            :style="{ backgroundColor: department.color || '#9333ea' }"
                                        >
                                            {{ department.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                                {{ department.name }}
                                            </h4>
                                            <p v-if="department.yqm_description" class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                                YQM: {{ department.yqm_description }}
                                            </p>

                                            <!-- Department Stats -->
                                            <div class="mt-3 flex items-center space-x-4 text-xs">
                                                <div class="flex items-center space-x-1">
                                                    <BriefcaseIcon class="w-4 h-4 text-gray-400" />
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        {{ department.positions?.length || 0 }} lavozim
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-1">
                                                    <UsersIcon class="w-4 h-4 text-gray-400" />
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        {{ getDepartmentStats(department).filled }}/{{ getDepartmentStats(department).total }} xodim
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Fill Rate Progress -->
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between text-xs mb-1">
                                                    <span class="text-gray-500 dark:text-gray-400">Bandlik</span>
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                                        {{ getDepartmentFillRate(department) }}%
                                                    </span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                                                    <div
                                                        class="bg-gradient-to-r from-purple-600 to-pink-600 h-1.5 rounded-full transition-all duration-300"
                                                        :style="{ width: getDepartmentFillRate(department) + '%' }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
