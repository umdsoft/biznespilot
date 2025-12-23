<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
      <component :is="iconComponent" class="w-8 h-8 text-gray-400" />
    </div>
    <h3 class="text-lg font-medium text-gray-900 mb-2">{{ title }}</h3>
    <p class="text-sm text-gray-500 max-w-md mx-auto">{{ description }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  CurrencyDollarIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
  QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: {
    type: String,
    default: "Ma'lumot mavjud emas",
  },
  description: {
    type: String,
    default: "Bu bo'limda ko'rsatiladigan ma'lumot topilmadi",
  },
  icon: {
    type: String,
    default: 'QuestionMarkCircleIcon',
  },
});

const icons = {
  CurrencyDollarIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  UserGroupIcon,
  GiftIcon,
  MegaphoneIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  Squares2X2Icon,
  QuestionMarkCircleIcon,
};

const iconComponent = computed(() => icons[props.icon] || QuestionMarkCircleIcon);
</script>
