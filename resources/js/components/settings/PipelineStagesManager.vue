<template>
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Voronka Bosqichlari</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        Lead voronkangiz bosqichlarini sozlang va moslang
                    </p>
                </div>
                <button
                    @click="openAddModal"
                    :class="[
                        'flex items-center px-4 py-2.5 text-white rounded-xl transition-all shadow-lg hover:shadow-xl',
                        themeColors.buttonGradient
                    ]"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Yangi bosqich
                </button>
            </div>
        </div>

        <!-- Info Alert -->
        <div :class="[
            'mb-6 border rounded-xl p-4',
            themeColors.alertBg,
            themeColors.alertBorder
        ]">
            <div class="flex items-start">
                <InformationCircleIcon :class="['w-5 h-5 mt-0.5 mr-3 flex-shrink-0', themeColors.alertIcon]" />
                <div>
                    <p :class="['text-sm', themeColors.alertText]">
                        <strong>Yangi</strong> va <strong>Yakunlangan (Sotuv/Sifatsiz)</strong> bosqichlari tizim bosqichlari bo'lib, ularni o'chirish mumkin emas.
                        O'rtadagi bosqichlarni xohlaganingizcha qo'shishingiz, o'zgartirishingiz yoki o'chirishingiz mumkin.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stages List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Mavjud bosqichlar</h2>
            </div>

            <!-- Draggable List -->
            <draggable
                v-model="sortedStages"
                item-key="id"
                handle=".drag-handle"
                @end="onDragEnd"
                class="divide-y divide-gray-200 dark:divide-gray-700"
            >
                <template #item="{ element: stage, index }">
                    <div :class="[
                        'flex items-center gap-4 p-4 transition-colors',
                        stage.is_system ? 'bg-gray-50 dark:bg-gray-700/50' : 'hover:bg-gray-50 dark:hover:bg-gray-700/30'
                    ]">
                        <!-- Drag Handle -->
                        <div :class="[
                            'drag-handle cursor-grab',
                            stage.is_system ? 'opacity-30 cursor-not-allowed' : ''
                        ]">
                            <Bars3Icon class="w-5 h-5 text-gray-400" />
                        </div>

                        <!-- Order Number -->
                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ index + 1 }}</span>
                        </div>

                        <!-- Color Indicator -->
                        <div :class="[
                            'w-4 h-4 rounded-full',
                            getColorClass(stage.color)
                        ]"></div>

                        <!-- Stage Name -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900 dark:text-white">{{ stage.name }}</span>
                                <span v-if="stage.is_system" class="px-2 py-0.5 text-xs bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full">
                                    Tizim
                                </span>
                                <span v-if="stage.is_won" class="px-2 py-0.5 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                                    Yutildi
                                </span>
                                <span v-if="stage.is_lost" class="px-2 py-0.5 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full">
                                    Yo'qotildi
                                </span>
                                <span v-if="!stage.is_active" class="px-2 py-0.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full">
                                    O'chirilgan
                                </span>
                            </div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">slug: {{ stage.slug }}</span>
                        </div>

                        <!-- Leads Count -->
                        <div class="text-right">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ stage.leads_count || 0 }}</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">lead</span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="editStage(stage)"
                                :class="[
                                    'p-2 text-gray-500 rounded-lg transition-colors',
                                    themeColors.editHover
                                ]"
                                title="Tahrirlash"
                            >
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <button
                                v-if="!stage.is_system"
                                @click="confirmDelete(stage)"
                                class="p-2 text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                title="O'chirish"
                            >
                                <TrashIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <TransitionRoot appear :show="showModal" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-50">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
                            <DialogTitle as="h3" class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                {{ editingStage ? 'Bosqichni tahrirlash' : 'Yangi bosqich qo\'shish' }}
                            </DialogTitle>

                            <form @submit.prevent="saveStage" class="space-y-4">
                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Bosqich nomi
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        :class="[
                                            'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:border-transparent transition-colors',
                                            themeColors.inputFocus
                                        ]"
                                        placeholder="Masalan: Kelishuv bosqichi"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                                </div>

                                <!-- Color -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Rang
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="color in colors"
                                            :key="color"
                                            type="button"
                                            @click="form.color = color"
                                            :class="[
                                                'w-8 h-8 rounded-lg transition-all',
                                                getColorClass(color),
                                                form.color === color ? `ring-2 ring-offset-2 dark:ring-offset-gray-800 scale-110 ${themeColors.colorRing}` : ''
                                            ]"
                                        ></button>
                                    </div>
                                    <p v-if="form.errors.color" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.color }}</p>
                                </div>

                                <!-- Buttons -->
                                <div class="flex justify-end gap-3 pt-4">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                                    >
                                        Bekor qilish
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="form.processing"
                                        :class="[
                                            'px-4 py-2 text-white rounded-xl transition-colors disabled:opacity-50',
                                            themeColors.submitButton
                                        ]"
                                    >
                                        {{ form.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Delete Confirmation Modal -->
    <TransitionRoot appear :show="showDeleteModal" as="template">
        <Dialog as="div" @close="showDeleteModal = false" class="relative z-50">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl transition-all">
                            <DialogTitle as="h3" class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                Bosqichni o'chirish
                            </DialogTitle>

                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                <strong>{{ deletingStage?.name }}</strong> bosqichini o'chirmoqchimisiz?
                                <span v-if="deletingStage?.leads_count > 0">
                                    Ushbu bosqichdagi {{ deletingStage.leads_count }} ta lead boshqa bosqichga ko'chiriladi.
                                </span>
                            </p>

                            <!-- Move to stage selector -->
                            <div v-if="deletingStage?.leads_count > 0" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Leadlarni qaysi bosqichga ko'chirish kerak?
                                </label>
                                <select
                                    v-model="deleteForm.move_to_stage"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                >
                                    <option v-for="stage in availableStagesForMove" :key="stage.id" :value="stage.id">
                                        {{ stage.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="showDeleteModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
                                >
                                    Bekor qilish
                                </button>
                                <button
                                    @click="deleteStage"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors disabled:opacity-50"
                                >
                                    {{ deleteForm.processing ? 'O\'chirilmoqda...' : 'O\'chirish' }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionRoot,
    TransitionChild,
} from '@headlessui/vue';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    Bars3Icon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stages: { type: Array, default: () => [] },
    colors: { type: Array, default: () => ['blue', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'green', 'teal', 'cyan', 'gray'] },
    panelType: {
        type: String,
        default: 'business',
        validator: (value) => ['business', 'saleshead'].includes(value)
    },
});

// Theme colors based on panel type
const themeColors = computed(() => {
    if (props.panelType === 'saleshead') {
        return {
            buttonGradient: 'bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700',
            alertBg: 'bg-emerald-50 dark:bg-emerald-900/30',
            alertBorder: 'border-emerald-200 dark:border-emerald-800',
            alertIcon: 'text-emerald-500 dark:text-emerald-400',
            alertText: 'text-emerald-800 dark:text-emerald-200',
            editHover: 'hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/30',
            inputFocus: 'focus:ring-emerald-500',
            colorRing: 'ring-emerald-500',
            submitButton: 'bg-emerald-600 hover:bg-emerald-700',
        };
    }
    return {
        buttonGradient: 'bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700',
        alertBg: 'bg-blue-50 dark:bg-blue-900/30',
        alertBorder: 'border-blue-200 dark:border-blue-800',
        alertIcon: 'text-blue-500 dark:text-blue-400',
        alertText: 'text-blue-800 dark:text-blue-200',
        editHover: 'hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30',
        inputFocus: 'focus:ring-blue-500',
        colorRing: 'ring-blue-500',
        submitButton: 'bg-blue-600 hover:bg-blue-700',
    };
});

// Route prefix based on panel type
const routePrefix = computed(() => {
    return props.panelType === 'saleshead'
        ? 'sales-head.settings.pipeline-stages'
        : 'business.settings.pipeline-stages';
});

// Sorted stages for drag and drop
const sortedStages = ref([...props.stages]);

// Watch for props changes to update sortedStages
watch(() => props.stages, (newStages) => {
    sortedStages.value = [...newStages];
}, { deep: true });

// Modal states
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingStage = ref(null);
const deletingStage = ref(null);

// Forms
const form = useForm({
    name: '',
    color: 'blue',
});

const deleteForm = useForm({
    move_to_stage: null,
});

// Computed
const availableStagesForMove = computed(() => {
    if (!deletingStage.value) return [];
    return sortedStages.value.filter(s => s.id !== deletingStage.value.id && !s.is_lost);
});

// Methods
const getColorClass = (color) => {
    const colorMap = {
        blue: 'bg-blue-500',
        indigo: 'bg-indigo-500',
        purple: 'bg-purple-500',
        pink: 'bg-pink-500',
        red: 'bg-red-500',
        orange: 'bg-orange-500',
        yellow: 'bg-yellow-500',
        green: 'bg-green-500',
        teal: 'bg-teal-500',
        cyan: 'bg-cyan-500',
        gray: 'bg-gray-500',
    };
    return colorMap[color] || 'bg-gray-500';
};

const openAddModal = () => {
    editingStage.value = null;
    form.reset();
    form.color = 'blue';
    showModal.value = true;
};

const editStage = (stage) => {
    editingStage.value = stage;
    form.name = stage.name;
    form.color = stage.color;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingStage.value = null;
    form.reset();
};

const saveStage = () => {
    if (editingStage.value) {
        form.put(route(`${routePrefix.value}.update`, editingStage.value.id), {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post(route(`${routePrefix.value}.store`), {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const confirmDelete = (stage) => {
    deletingStage.value = stage;
    deleteForm.move_to_stage = availableStagesForMove.value[0]?.id || null;
    showDeleteModal.value = true;
};

const deleteStage = () => {
    deleteForm.delete(route(`${routePrefix.value}.destroy`, deletingStage.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingStage.value = null;
        },
    });
};

const onDragEnd = () => {
    const stages = sortedStages.value.map((stage, index) => ({
        id: stage.id,
        order: index + 1,
    }));

    router.post(route(`${routePrefix.value}.reorder`), { stages }, {
        preserveScroll: true,
    });
};
</script>
