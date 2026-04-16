<template>
  <div>
    <label class="block text-xs font-medium mb-1.5">{{ label }}</label>
    <div class="space-y-1.5">
      <div v-for="(item, i) in items" :key="i" class="flex items-center gap-2">
        <input :value="item" @input="updateItem(i, $event.target.value)"
          class="flex-1 px-3 py-1.5 text-sm border rounded-lg bg-white dark:bg-gray-800"
          :class="borderClass" />
        <button @click="removeItem(i)" type="button"
          class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="flex items-center gap-2">
        <input v-model="newItem" @keydown.enter.prevent="addItem"
          :placeholder="placeholder"
          class="flex-1 px-3 py-1.5 text-sm border rounded-lg bg-white dark:bg-gray-800 border-dashed" />
        <button @click="addItem" type="button"
          class="px-3 py-1.5 text-sm font-medium rounded-lg"
          :class="buttonClass">
          +
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  items: { type: Array, default: () => [] },
  label: { type: String, required: true },
  color: { type: String, default: 'gray' },
  placeholder: { type: String, default: '' },
});

const emit = defineEmits(['update']);

const newItem = ref('');

const borderClass = computed(() => ({
  green: 'border-green-300 dark:border-green-800 focus:ring-green-500',
  red: 'border-red-300 dark:border-red-800 focus:ring-red-500',
  blue: 'border-blue-300 dark:border-blue-800 focus:ring-blue-500',
  gray: 'border-gray-300 dark:border-gray-700',
})[props.color]);

const buttonClass = computed(() => ({
  green: 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300',
  red: 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300',
  blue: 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300',
  gray: 'bg-gray-100 text-gray-700 hover:bg-gray-200',
})[props.color]);

const addItem = () => {
  const val = newItem.value.trim();
  if (!val) return;
  emit('update', [...props.items, val]);
  newItem.value = '';
};

const removeItem = (index) => {
  const next = [...props.items];
  next.splice(index, 1);
  emit('update', next);
};

const updateItem = (index, value) => {
  const next = [...props.items];
  next[index] = value;
  emit('update', next);
};
</script>
