<script setup>
import { ref, computed } from 'vue';
import {
    ChatBubbleLeftRightIcon,
    XMarkIcon,
    BugAntIcon,
    LightBulbIcon,
    QuestionMarkCircleIcon,
    ChatBubbleOvalLeftEllipsisIcon,
    PaperClipIcon,
    TrashIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    position: {
        type: String,
        default: 'bottom-right', // bottom-right, bottom-left
    },
});

// State
const isOpen = ref(false);
const isSubmitting = ref(false);
const isSuccess = ref(false);
const errorMessage = ref('');
const selectedType = ref(null);
const showForm = ref(false);

const form = ref({
    title: '',
    description: '',
    attachments: [],
});

// File input ref
const fileInput = ref(null);

// Feedback types
const feedbackTypes = [
    {
        value: 'bug',
        label: 'Xatolik',
        description: 'Tizimda xatolik topdingizmi?',
        icon: BugAntIcon,
        color: 'red',
    },
    {
        value: 'suggestion',
        label: 'Taklif',
        description: 'Yangi g\'oya yoki takomillashtirishlar',
        icon: LightBulbIcon,
        color: 'blue',
    },
    {
        value: 'question',
        label: 'Savol',
        description: 'Yordam kerakmi?',
        icon: QuestionMarkCircleIcon,
        color: 'purple',
    },
    {
        value: 'other',
        label: 'Boshqa',
        description: 'Boshqa xabar',
        icon: ChatBubbleOvalLeftEllipsisIcon,
        color: 'gray',
    },
];

// Computed
const selectedTypeData = computed(() => {
    return feedbackTypes.find(t => t.value === selectedType.value);
});

const totalFileSize = computed(() => {
    return form.value.attachments.reduce((sum, file) => sum + file.size, 0);
});

const canSubmit = computed(() => {
    return selectedType.value && form.value.title.trim() && form.value.description.trim() && !isSubmitting.value;
});

// Methods
const toggleWidget = () => {
    isOpen.value = !isOpen.value;
    if (!isOpen.value) {
        resetForm();
    }
};

const selectType = (type) => {
    selectedType.value = type;
    showForm.value = true;
};

const goBack = () => {
    showForm.value = false;
    selectedType.value = null;
};

const resetForm = () => {
    selectedType.value = null;
    showForm.value = false;
    isSuccess.value = false;
    errorMessage.value = '';
    form.value = {
        title: '',
        description: '',
        attachments: [],
    };
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const maxSize = 5 * 1024 * 1024; // 5MB

    for (const file of files) {
        if (file.size > maxSize) {
            errorMessage.value = `"${file.name}" hajmi 5 MB dan oshmasligi kerak`;
            continue;
        }

        if (form.value.attachments.length >= 5) {
            errorMessage.value = 'Maksimum 5 ta fayl biriktirish mumkin';
            break;
        }

        form.value.attachments.push(file);
    }

    // Clear file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeAttachment = (index) => {
    form.value.attachments.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    }
    if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return bytes + ' bytes';
};

const submitFeedback = async () => {
    if (!canSubmit.value) return;

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const formData = new FormData();
        formData.append('type', selectedType.value);
        formData.append('title', form.value.title);
        formData.append('description', form.value.description);
        formData.append('page_url', window.location.href);
        formData.append('browser_info', navigator.userAgent);

        form.value.attachments.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });

        const response = await fetch('/business/feedback', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (response.ok && data.success) {
            isSuccess.value = true;
            setTimeout(() => {
                toggleWidget();
            }, 3000);
        } else {
            errorMessage.value = data.message || 'Xatolik yuz berdi';
        }
    } catch (error) {
        console.error('Failed to submit feedback:', error);
        errorMessage.value = 'Xatolik yuz berdi. Qayta urinib ko\'ring.';
    } finally {
        isSubmitting.value = false;
    }
};

const getTypeColorClasses = (color, isSelected = false) => {
    const colors = {
        red: isSelected
            ? 'bg-red-500 text-white border-red-500'
            : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800/30 dark:hover:bg-red-900/30',
        blue: isSelected
            ? 'bg-blue-500 text-white border-blue-500'
            : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800/30 dark:hover:bg-blue-900/30',
        purple: isSelected
            ? 'bg-purple-500 text-white border-purple-500'
            : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800/30 dark:hover:bg-purple-900/30',
        gray: isSelected
            ? 'bg-gray-500 text-white border-gray-500'
            : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 dark:hover:bg-gray-700',
    };
    return colors[color] || colors.gray;
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Feedback Button -->
        <button
            @click="toggleWidget"
            :class="[
                'group relative flex items-center justify-center w-14 h-14 rounded-full shadow-lg transition-all duration-300',
                isOpen
                    ? 'bg-gray-800 dark:bg-gray-700 rotate-0'
                    : 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 hover:scale-110'
            ]"
        >
            <XMarkIcon v-if="isOpen" class="w-6 h-6 text-white" />
            <ChatBubbleLeftRightIcon v-else class="w-6 h-6 text-white" />

            <!-- Tooltip -->
            <span
                v-if="!isOpen"
                class="absolute right-full mr-3 px-3 py-1.5 bg-gray-900 text-white text-sm font-medium rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
            >
                Xabar yuborish
            </span>
        </button>

        <!-- Feedback Panel -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div
                v-if="isOpen"
                class="absolute bottom-20 right-0 w-96 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">Xabar yuborish</h3>
                    <p class="text-blue-100 text-sm">Taklif yoki xatolik haqida xabar bering</p>
                </div>

                <!-- Success State -->
                <div v-if="isSuccess" class="p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <CheckCircleIcon class="w-8 h-8 text-green-500" />
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Rahmat!</h4>
                    <p class="text-gray-500 dark:text-gray-400">Xabaringiz muvaffaqiyatli yuborildi. Tez orada ko'rib chiqamiz.</p>
                </div>

                <!-- Type Selection -->
                <div v-else-if="!showForm" class="p-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Xabar turini tanlang:</p>
                    <div class="space-y-2">
                        <button
                            v-for="type in feedbackTypes"
                            :key="type.value"
                            @click="selectType(type.value)"
                            :class="[
                                'w-full flex items-center gap-4 p-4 rounded-xl border transition-all',
                                getTypeColorClasses(type.color)
                            ]"
                        >
                            <div :class="[
                                'w-10 h-10 rounded-lg flex items-center justify-center',
                                `bg-${type.color}-100 dark:bg-${type.color}-900/30`
                            ]">
                                <component :is="type.icon" :class="['w-5 h-5', `text-${type.color}-600 dark:text-${type.color}-400`]" />
                            </div>
                            <div class="text-left">
                                <span class="font-medium">{{ type.label }}</span>
                                <p class="text-xs opacity-70">{{ type.description }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Feedback Form -->
                <div v-else class="p-4">
                    <!-- Back button & type indicator -->
                    <div class="flex items-center gap-3 mb-4">
                        <button
                            @click="goBack"
                            class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div :class="[
                            'flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium',
                            getTypeColorClasses(selectedTypeData?.color, true)
                        ]">
                            <component :is="selectedTypeData?.icon" class="w-4 h-4" />
                            {{ selectedTypeData?.label }}
                        </div>
                    </div>

                    <!-- Error message -->
                    <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-sm rounded-lg flex items-start gap-2">
                        <ExclamationTriangleIcon class="w-5 h-5 flex-shrink-0" />
                        {{ errorMessage }}
                    </div>

                    <!-- Form fields -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sarlavha *</label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Qisqa sarlavha"
                                maxlength="255"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif *</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                placeholder="Batafsil yozing..."
                                maxlength="5000"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">{{ form.description.length }}/5000</p>
                        </div>

                        <!-- Attachments -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fayllar biriktirish
                                <span class="font-normal text-gray-400">(max 5 ta, har biri 5 MB gacha)</span>
                            </label>

                            <!-- Attached files -->
                            <div v-if="form.attachments.length > 0" class="space-y-2 mb-3">
                                <div
                                    v-for="(file, index) in form.attachments"
                                    :key="index"
                                    class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <PaperClipIcon class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ file.name }}</span>
                                        <span class="text-xs text-gray-400 flex-shrink-0">({{ formatFileSize(file.size) }})</span>
                                    </div>
                                    <button
                                        @click="removeAttachment(index)"
                                        class="p-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Add file button -->
                            <button
                                v-if="form.attachments.length < 5"
                                @click="fileInput?.click()"
                                class="w-full flex items-center justify-center gap-2 p-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 transition-colors"
                            >
                                <PaperClipIcon class="w-5 h-5" />
                                Fayl tanlash
                            </button>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.doc,.docx,.xls,.xlsx"
                                class="hidden"
                                @change="handleFileSelect"
                            />
                        </div>
                    </div>

                    <!-- Submit button -->
                    <button
                        @click="submitFeedback"
                        :disabled="!canSubmit"
                        :class="[
                            'w-full mt-6 py-3 rounded-xl font-medium transition-all',
                            canSubmit
                                ? 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white shadow-lg'
                                : 'bg-gray-200 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                        ]"
                    >
                        <span v-if="isSubmitting" class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Yuborilmoqda...
                        </span>
                        <span v-else>Yuborish</span>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
