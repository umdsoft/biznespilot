<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    ArrowRightIcon,
    ClockIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import axios from 'axios';

// State
const loading = ref(true);
const todos = ref([]);
const stats = ref({
    total_today: 0,
    completed_today: 0,
    overdue: 0,
    progress: 0,
});

// Fetch data
const fetchData = async () => {
    try {
        const response = await axios.get('/business/todos/dashboard');
        if (response.data) {
            todos.value = response.data.todos || [];
            stats.value = response.data.stats || stats.value;
        }
    } catch (error) {
        console.error('Failed to fetch todos for dashboard:', error);
    } finally {
        loading.value = false;
    }
};

// Toggle todo completion
const toggleTodo = async (todo) => {
    try {
        await axios.post(`/business/todos/${todo.id}/toggle`);
        // Refresh data
        fetchData();
    } catch (error) {
        console.error('Failed to toggle todo:', error);
    }
};

// Priority colors
const getPriorityColor = (priority) => {
    const colors = {
        urgent: 'text-red-500',
        high: 'text-orange-500',
        medium: 'text-yellow-500',
        low: 'text-green-500',
    };
    return colors[priority] || colors.medium;
};

onMounted(() => {
    fetchData();
});
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <CheckCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <h3 class="font-semibold text-gray-900 dark:text-white">Bugungi vazifalar</h3>
            </div>
            <Link
                href="/business/todos"
                class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
            >
                Barchasi
                <ArrowRightIcon class="w-4 h-4" />
            </Link>
        </div>

        <!-- Content -->
        <div class="p-5">
            <!-- Loading state -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="animate-spin w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full"></div>
            </div>

            <!-- Todos list -->
            <div v-else-if="todos.length > 0" class="space-y-3">
                <div
                    v-for="todo in todos"
                    :key="todo.id"
                    class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                    <button
                        @click="toggleTodo(todo)"
                        :class="[
                            'flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors',
                            todo.is_completed
                                ? 'bg-green-500 border-green-500 text-white'
                                : 'border-gray-300 dark:border-gray-600 hover:border-green-400'
                        ]"
                    >
                        <CheckCircleIcon v-if="todo.is_completed" class="w-3 h-3" />
                    </button>
                    <div class="flex-1 min-w-0">
                        <p :class="[
                            'text-sm font-medium truncate',
                            todo.is_completed
                                ? 'line-through text-gray-400'
                                : 'text-gray-900 dark:text-white'
                        ]">
                            {{ todo.title }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span :class="getPriorityColor(todo.priority)">
                            <ExclamationTriangleIcon v-if="todo.priority === 'urgent'" class="w-4 h-4" />
                            <span v-else class="w-2 h-2 rounded-full inline-block" :class="[
                                todo.priority === 'high' ? 'bg-orange-500' :
                                todo.priority === 'medium' ? 'bg-yellow-500' : 'bg-green-500'
                            ]"></span>
                        </span>
                        <span v-if="todo.due_time" class="text-gray-500 dark:text-gray-400 flex items-center gap-1">
                            <ClockIcon class="w-3 h-3" />
                            {{ todo.due_time }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-8">
                <CheckCircleIcon class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">Bugun uchun vazifalar yo'q</p>
            </div>

            <!-- Stats bar -->
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ stats.completed_today }}/{{ stats.total_today }} bajarildi
                    </span>
                    <span v-if="stats.overdue > 0" class="text-red-500 flex items-center gap-1">
                        <ExclamationTriangleIcon class="w-4 h-4" />
                        {{ stats.overdue }} muddati o'tgan
                    </span>
                </div>
                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        :style="{ width: `${stats.progress}%` }"
                        class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 transition-all duration-500"
                    ></div>
                </div>
                <p class="text-center text-xs text-gray-500 dark:text-gray-400 mt-1">{{ stats.progress }}%</p>
            </div>
        </div>
    </div>
</template>
