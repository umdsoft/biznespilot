<script setup>
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { BellIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

defineProps({
    invitations: { type: Array, default: () => [] },
});
</script>

<template>
    <HRLayout :title="t('hr.invitations')">
        <Head :title="t('hr.invitations')" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ t('hr.invitations') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('hr.pending_invitations') }}</p>
            </div>

            <!-- Invitations List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="invitations.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('hr.full_name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('hr.phone') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('hr.department') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ t('hr.joined_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="invitation in invitations"
                                :key="invitation.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50"
                            >
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ invitation.name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ invitation.phone }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ invitation.department }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ invitation.invited_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <BellIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p class="text-lg font-medium mb-2">{{ t('hr.invitations') }} {{ t('hr.none').toLowerCase() }}</p>
                    <p class="text-sm">Hozircha hech kim taklif qilinmagan yoki barcha takliflar qabul qilingan</p>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
