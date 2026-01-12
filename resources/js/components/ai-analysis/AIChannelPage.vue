<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    channel: Object,
    metrics: Array,
    panelType: {
        type: String,
        required: true,
        validator: (value) => ['business', 'marketing', 'finance', 'operator', 'saleshead'].includes(value),
    },
    platformConfig: {
        type: Object,
        required: true,
    },
});

const connecting = ref(false);

// Connect to platform (Facebook/Instagram via Meta OAuth)
// Uses shared /integrations/meta route for all panels
const connectPlatform = async () => {
    connecting.value = true;
    try {
        // Get Meta auth URL from shared integrations route
        const response = await fetch(route('integrations.meta.auth-url'), {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.url) {
            // Redirect to Facebook OAuth
            window.location.href = data.url;
        } else {
            console.error('No auth URL received');
            alert('Integratsiya xatosi. Qayta urinib ko\'ring.');
        }
    } catch (error) {
        console.error('Connect error:', error);
        alert('Integratsiya xatosi. Qayta urinib ko\'ring.');
    } finally {
        connecting.value = false;
    }
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center text-white shadow-lg', platformConfig.headerGradient]">
                <component :is="platformConfig.icon" class="w-8 h-8" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ platformConfig.title }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ platformConfig.subtitle }}</p>
            </div>
        </div>

        <!-- No Channel Connected -->
        <div v-if="!channel" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-12 text-center">
            <div :class="['w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-6', platformConfig.emptyIconBg]">
                <component :is="platformConfig.icon" :class="['w-10 h-10', platformConfig.emptyIconColor]" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">{{ platformConfig.connectTitle }}</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-md mx-auto">
                {{ platformConfig.connectDescription }}
            </p>
            <button
                @click="connectPlatform"
                :disabled="connecting"
                :class="['px-8 py-3 text-white font-medium rounded-xl transition-all inline-flex items-center gap-2', platformConfig.connectButtonClass, connecting ? 'opacity-70 cursor-wait' : '']"
            >
                <svg v-if="connecting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ connecting ? 'Ulanmoqda...' : platformConfig.connectButtonText }}
            </button>
        </div>

        <!-- Has Channel -->
        <template v-else>
            <!-- Metrics Grid -->
            <div :class="['grid gap-6', `grid-cols-1 md:grid-cols-${platformConfig.metricsConfig.length > 3 ? 4 : platformConfig.metricsConfig.length}`]">
                <div
                    v-for="metric in platformConfig.metricsConfig"
                    :key="metric.key"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6"
                >
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ metric.label }}</span>
                        <span :class="metric.iconColor">{{ metric.icon }}</span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ metric.prefix || '' }}{{ channel.metrics?.[metric.key] || 0 }}{{ metric.suffix || '' }}
                    </p>
                </div>
            </div>

            <!-- Metrics Table (if available) -->
            <div v-if="metrics && metrics.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ platformConfig.tableTitle || 'Statistika' }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    v-for="column in platformConfig.tableColumns"
                                    :key="column.key"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"
                                >
                                    {{ column.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="metric in metrics" :key="metric.id">
                                <td
                                    v-for="column in platformConfig.tableColumns"
                                    :key="column.key"
                                    class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100"
                                >
                                    {{ column.prefix || '' }}{{ metric[column.key] }}{{ column.suffix || '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </div>
</template>
