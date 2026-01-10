<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeftIcon,
    BugAntIcon,
    LightBulbIcon,
    QuestionMarkCircleIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    UserIcon,
    BuildingOfficeIcon,
    GlobeAltIcon,
    ComputerDesktopIcon,
    PaperClipIcon,
    CalendarIcon,
    CheckCircleIcon,
    TrashIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    feedback: Object,
    statuses: Object,
    priorities: Object,
});

// State
const selectedStatus = ref(props.feedback.status);
const selectedPriority = ref(props.feedback.priority);
const newNote = ref('');
const isAddingNote = ref(false);
const isSaving = ref(false);

// Type icons
const typeIcons = {
    bug: BugAntIcon,
    suggestion: LightBulbIcon,
    question: QuestionMarkCircleIcon,
    other: ChatBubbleOvalLeftEllipsisIcon,
};

// Color classes
const getTypeColorClass = (type) => {
    const colors = {
        bug: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800/30',
        suggestion: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800/30',
        question: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 border-purple-200 dark:border-purple-800/30',
        other: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400 border-gray-200 dark:border-gray-600',
    };
    return colors[type] || colors.other;
};

const getStatusColorClass = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        resolved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
    };
    return colors[status] || colors.pending;
};

const getPriorityColorClass = (priority) => {
    const colors = {
        low: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        medium: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[priority] || colors.medium;
};

// Update status
const updateStatus = async () => {
    isSaving.value = true;
    try {
        const response = await fetch(route('admin.feedback.update-status', props.feedback.id), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ status: selectedStatus.value }),
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to update status:', error);
    } finally {
        isSaving.value = false;
    }
};

// Update priority
const updatePriority = async () => {
    isSaving.value = true;
    try {
        const response = await fetch(route('admin.feedback.update-priority', props.feedback.id), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ priority: selectedPriority.value }),
        });
        if (response.ok) {
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to update priority:', error);
    } finally {
        isSaving.value = false;
    }
};

// Add note
const addNote = async () => {
    if (!newNote.value.trim()) return;

    isSaving.value = true;
    try {
        const response = await fetch(route('admin.feedback.add-note', props.feedback.id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ note: newNote.value }),
        });
        if (response.ok) {
            newNote.value = '';
            isAddingNote.value = false;
            router.reload({ preserveScroll: true });
        }
    } catch (error) {
        console.error('Failed to add note:', error);
    } finally {
        isSaving.value = false;
    }
};

// Delete feedback
const deleteFeedback = async () => {
    if (!confirm('Ushbu feedbackni o\'chirmoqchimisiz?')) return;

    try {
        const response = await fetch(route('admin.feedback.destroy', props.feedback.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
        });
        if (response.ok) {
            router.visit(route('admin.feedback.index'));
        }
    } catch (error) {
        console.error('Failed to delete feedback:', error);
    }
};
</script>

<template>
    <AdminLayout title="Feedback Tafsilotlari">
        <Head :title="`Feedback: ${feedback.title}`" />

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Back button -->
            <Link
                :href="route('admin.feedback.index')"
                class="inline-flex items-center gap-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
            >
                <ArrowLeftIcon class="w-5 h-5" />
                Orqaga
            </Link>

            <!-- Main content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Header Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Type banner -->
                        <div :class="['px-6 py-4 border-b', getTypeColorClass(feedback.type)]">
                            <div class="flex items-center gap-3">
                                <component :is="typeIcons[feedback.type]" class="w-6 h-6" />
                                <span class="font-semibold text-lg">{{ feedback.type_label }}</span>
                            </div>
                        </div>

                        <div class="p-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ feedback.title }}</h1>

                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ feedback.description }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments -->
                    <div v-if="feedback.attachments.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <PaperClipIcon class="w-5 h-5" />
                            Biriktirilgan fayllar ({{ feedback.attachments.length }})
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="attachment in feedback.attachments"
                                :key="attachment.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                            >
                                <!-- Image preview -->
                                <div v-if="attachment.is_image" class="aspect-video bg-gray-100 dark:bg-gray-700">
                                    <img :src="attachment.url" :alt="attachment.file_name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="aspect-video bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    <PaperClipIcon class="w-12 h-12 text-gray-400" />
                                </div>
                                <div class="p-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ attachment.file_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ attachment.file_size }}</p>
                                    <a
                                        :href="attachment.url"
                                        target="_blank"
                                        class="text-sm text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block"
                                    >
                                        Ko'rish
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Notes -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Admin izohlari</h3>
                            <button
                                v-if="!isAddingNote"
                                @click="isAddingNote = true"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                            >
                                <PlusIcon class="w-4 h-4" />
                                Izoh qo'shish
                            </button>
                        </div>

                        <!-- Add note form -->
                        <div v-if="isAddingNote" class="mb-4">
                            <textarea
                                v-model="newNote"
                                rows="3"
                                placeholder="Izoh yozing..."
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm resize-none"
                            ></textarea>
                            <div class="flex justify-end gap-2 mt-2">
                                <button
                                    @click="isAddingNote = false; newNote = ''"
                                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg text-sm"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    @click="addNote"
                                    :disabled="!newNote.trim() || isSaving"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm disabled:opacity-50"
                                >
                                    Saqlash
                                </button>
                            </div>
                        </div>

                        <!-- Existing notes -->
                        <div v-if="feedback.admin_notes" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans">{{ feedback.admin_notes }}</pre>
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-sm">Hozircha izoh yo'q</p>
                    </div>
                </div>

                <!-- Right: Sidebar -->
                <div class="space-y-6">
                    <!-- Status & Priority -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                            <select
                                v-model="selectedStatus"
                                @change="updateStatus"
                                :class="['w-full px-4 py-2.5 rounded-xl text-sm font-medium border-0', getStatusColorClass(selectedStatus)]"
                            >
                                <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Muhimlik</label>
                            <select
                                v-model="selectedPriority"
                                @change="updatePriority"
                                :class="['w-full px-4 py-2.5 rounded-xl text-sm font-medium border-0', getPriorityColorClass(selectedPriority)]"
                            >
                                <option v-for="(label, value) in priorities" :key="value" :value="value">{{ label }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Foydalanuvchi</h3>
                        <div class="space-y-3">
                            <div v-if="feedback.user" class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <UserIcon class="w-5 h-5 text-gray-500" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ feedback.user.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ feedback.user.email }}</p>
                                </div>
                            </div>

                            <div v-if="feedback.business" class="flex items-start gap-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Biznes</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ feedback.business.name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technical Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Texnik ma'lumot</h3>
                        <div class="space-y-3 text-sm">
                            <div v-if="feedback.page_url" class="flex items-start gap-3">
                                <GlobeAltIcon class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-gray-500 dark:text-gray-400">Sahifa URL</p>
                                    <a :href="feedback.page_url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline break-all">
                                        {{ feedback.page_url }}
                                    </a>
                                </div>
                            </div>

                            <div v-if="feedback.browser_info" class="flex items-start gap-3">
                                <ComputerDesktopIcon class="w-5 h-5 text-gray-400 mt-0.5 flex-shrink-0" />
                                <div class="min-w-0">
                                    <p class="text-gray-500 dark:text-gray-400">Brauzer</p>
                                    <p class="text-gray-700 dark:text-gray-300 break-all text-xs">{{ feedback.browser_info }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <CalendarIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Yaratilgan</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ feedback.created_at }}</p>
                                </div>
                            </div>

                            <div v-if="feedback.resolved_at" class="flex items-start gap-3">
                                <CheckCircleIcon class="w-5 h-5 text-green-500 mt-0.5" />
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">Hal qilingan</p>
                                    <p class="text-gray-700 dark:text-gray-300">{{ feedback.resolved_at }}</p>
                                    <p v-if="feedback.resolved_by" class="text-xs text-gray-500">{{ feedback.resolved_by.name }} tomonidan</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Button -->
                    <button
                        @click="deleteFeedback"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition-colors"
                    >
                        <TrashIcon class="w-5 h-5" />
                        O'chirish
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
