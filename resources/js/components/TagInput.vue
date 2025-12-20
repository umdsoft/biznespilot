<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    <div class="relative">
      <div class="flex flex-wrap gap-2 p-2 border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
        <span
          v-for="(tag, index) in modelValue"
          :key="index"
          class="inline-flex items-center px-2 py-1 bg-primary-100 text-primary-700 text-sm rounded"
        >
          {{ tag }}
          <button
            type="button"
            @click="removeTag(index)"
            class="ml-1 hover:text-primary-900"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </span>
        <input
          v-model="inputValue"
          type="text"
          :placeholder="placeholder"
          @keydown.enter.prevent="addTag"
          @keydown.comma.prevent="addTag"
          class="flex-1 min-w-[120px] border-0 focus:ring-0 p-0 text-sm"
        />
      </div>
    </div>
    <p v-if="hint" class="mt-1 text-sm text-gray-500">
      {{ hint }}
    </p>
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Yozing va Enter bosing...',
  },
  hint: {
    type: String,
    default: 'Enter yoki vergul bilan qo\'shing',
  },
  error: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

const inputValue = ref('');

const addTag = () => {
  const trimmed = inputValue.value.trim();
  if (trimmed && !props.modelValue.includes(trimmed)) {
    emit('update:modelValue', [...props.modelValue, trimmed]);
    inputValue.value = '';
  }
};

const removeTag = (index) => {
  const newTags = [...props.modelValue];
  newTags.splice(index, 1);
  emit('update:modelValue', newTags);
};
</script>
