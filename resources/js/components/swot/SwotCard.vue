<template>
    <div :class="cardClasses" class="rounded-xl p-5 shadow-sm border">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 :class="titleClasses" class="text-lg font-semibold flex items-center gap-2">
                    <component :is="iconComponent" class="w-5 h-5" />
                    {{ title }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ subtitle }}</p>
            </div>
            <span :class="badgeClasses" class="text-xs font-medium px-2.5 py-1 rounded-full">
                {{ items.length }} ta
            </span>
        </div>

        <!-- Items List -->
        <div class="space-y-2 mb-4 max-h-64 overflow-y-auto">
            <div
                v-for="(item, index) in items"
                :key="index"
                :class="itemHoverClasses"
                class="group flex items-start gap-2 p-2 rounded-lg transition-colors"
            >
                <span :class="bulletClasses" class="w-2 h-2 rounded-full mt-2 flex-shrink-0"></span>

                <div v-if="editingIndex === index" class="flex-1 flex gap-2">
                    <input
                        v-model="editValue"
                        @keyup.enter="saveEdit(index)"
                        @keyup.escape="cancelEdit"
                        :class="editInputClasses"
                        class="flex-1 px-2 py-1 text-sm border rounded focus:outline-none focus:ring-2"
                        ref="editInput"
                    />
                    <button @click="saveEdit(index)" class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300">
                        <CheckIcon class="w-4 h-4" />
                    </button>
                    <button @click="cancelEdit" class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>

                <template v-else>
                    <span class="flex-1 text-sm text-gray-700 dark:text-gray-200">{{ getItemText(item) }}</span>
                    <!-- Edit/Delete buttons - only show for own items -->
                    <div v-if="isOwnItem(item)" class="opacity-0 group-hover:opacity-100 flex gap-1 transition-opacity">
                        <button
                            @click="startEdit(index, getItemText(item))"
                            class="p-1 text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400 rounded"
                            title="Tahrirlash"
                        >
                            <PencilIcon class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click="$emit('remove', index)"
                            class="p-1 text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 rounded"
                            title="O'chirish"
                        >
                            <TrashIcon class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <!-- Show indicator that item was added by another business -->
                    <div v-else class="opacity-0 group-hover:opacity-100 flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 transition-opacity">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Boshqa biznes</span>
                    </div>
                </template>
            </div>

            <p v-if="items.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic py-2">
                Hozircha ma'lumot yo'q
            </p>
        </div>

        <!-- Add New Item -->
        <div class="flex gap-2">
            <input
                v-model="newItem"
                @keyup.enter="addItem"
                :placeholder="placeholder"
                :class="inputClasses"
                class="flex-1 px-3 py-2 text-sm rounded-lg focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
            />
            <button
                @click="addItem"
                :disabled="!newItem.trim()"
                :class="buttonClasses"
                class="px-3 py-2 rounded-lg text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <PlusIcon class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    XMarkIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    LightBulbIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (v) => ['strengths', 'weaknesses', 'opportunities', 'threats'].includes(v),
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    items: {
        type: Array,
        default: () => [],
    },
    color: {
        type: String,
        default: 'green',
        validator: (v) => ['green', 'red', 'blue', 'orange'].includes(v),
    },
    placeholder: {
        type: String,
        default: 'Yangi element qo\'shing...',
    },
    currentBusinessId: {
        type: [Number, String],
        default: null,
    },
});

// Check if item is owned by current business
const isOwnItem = (item) => {
    // If item is a simple string (legacy format), allow editing
    if (typeof item === 'string') {
        return true;
    }
    // If item has business_id, check if it matches current business
    if (item && item.business_id && props.currentBusinessId) {
        return item.business_id === props.currentBusinessId;
    }
    // Default to allowing edit if no business_id set
    return true;
};

// Get display text from item (handles both string and object format)
const getItemText = (item) => {
    if (typeof item === 'string') {
        return item;
    }
    return item?.text || '';
};

const emit = defineEmits(['add', 'remove', 'edit']);

const newItem = ref('');
const editingIndex = ref(null);
const editValue = ref('');
const editInput = ref(null);

const iconComponent = computed(() => {
    const icons = {
        strengths: ShieldCheckIcon,
        weaknesses: ExclamationTriangleIcon,
        opportunities: LightBulbIcon,
        threats: BoltIcon,
    };
    return icons[props.type];
});

const colorClasses = computed(() => {
    const colors = {
        green: {
            card: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
            title: 'text-green-800 dark:text-green-300',
            badge: 'bg-green-100 dark:bg-green-800/50 text-green-700 dark:text-green-300',
            bullet: 'bg-green-500',
            button: 'bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-500',
            input: 'border-green-200 dark:border-green-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-green-500',
            editInput: 'border-green-300 dark:border-green-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-green-500',
            itemHover: 'hover:bg-green-100/50 dark:hover:bg-green-800/30',
        },
        red: {
            card: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
            title: 'text-red-800 dark:text-red-300',
            badge: 'bg-red-100 dark:bg-red-800/50 text-red-700 dark:text-red-300',
            bullet: 'bg-red-500',
            button: 'bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-500',
            input: 'border-red-200 dark:border-red-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-red-500',
            editInput: 'border-red-300 dark:border-red-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-red-500',
            itemHover: 'hover:bg-red-100/50 dark:hover:bg-red-800/30',
        },
        blue: {
            card: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
            title: 'text-blue-800 dark:text-blue-300',
            badge: 'bg-blue-100 dark:bg-blue-800/50 text-blue-700 dark:text-blue-300',
            bullet: 'bg-blue-500',
            button: 'bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500',
            input: 'border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-blue-500',
            editInput: 'border-blue-300 dark:border-blue-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500',
            itemHover: 'hover:bg-blue-100/50 dark:hover:bg-blue-800/30',
        },
        orange: {
            card: 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800',
            title: 'text-orange-800 dark:text-orange-300',
            badge: 'bg-orange-100 dark:bg-orange-800/50 text-orange-700 dark:text-orange-300',
            bullet: 'bg-orange-500',
            button: 'bg-orange-600 hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-500',
            input: 'border-orange-200 dark:border-orange-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-orange-500',
            editInput: 'border-orange-300 dark:border-orange-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-orange-500',
            itemHover: 'hover:bg-orange-100/50 dark:hover:bg-orange-800/30',
        },
    };
    return colors[props.color];
});

const cardClasses = computed(() => colorClasses.value.card);
const titleClasses = computed(() => colorClasses.value.title);
const badgeClasses = computed(() => colorClasses.value.badge);
const bulletClasses = computed(() => colorClasses.value.bullet);
const buttonClasses = computed(() => colorClasses.value.button);
const inputClasses = computed(() => colorClasses.value.input);
const editInputClasses = computed(() => colorClasses.value.editInput);
const itemHoverClasses = computed(() => colorClasses.value.itemHover);

const addItem = () => {
    if (newItem.value.trim()) {
        emit('add', newItem.value.trim());
        newItem.value = '';
    }
};

const startEdit = async (index, value) => {
    editingIndex.value = index;
    editValue.value = value;
    await nextTick();
    editInput.value?.focus();
};

const saveEdit = (index) => {
    if (editValue.value.trim()) {
        emit('edit', { index, value: editValue.value.trim() });
    }
    cancelEdit();
};

const cancelEdit = () => {
    editingIndex.value = null;
    editValue.value = '';
};
</script>
