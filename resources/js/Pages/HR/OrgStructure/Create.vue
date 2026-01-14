<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import {
    BuildingOfficeIcon,
    CheckCircleIcon,
    AcademicCapIcon,
    HeartIcon,
    WrenchScrewdriverIcon,
    ShoppingBagIcon,
    CakeIcon,
    ComputerDesktopIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    businessTypes: Array,
    business: Object,
});

const form = useForm({
    business_type_id: null,
    name: `${props.business.name} - Tashkiliy Tuzilma`,
    description: '',
    use_templates: true,
});

const businessTypeIcons = {
    'education': AcademicCapIcon,
    'healthcare': HeartIcon,
    'construction': WrenchScrewdriverIcon,
    'retail': ShoppingBagIcon,
    'restaurant': CakeIcon,
    'software': ComputerDesktopIcon,
};

const getBusinessTypeIcon = (code) => {
    return businessTypeIcons[code] || BuildingOfficeIcon;
};

const submit = () => {
    form.post(route('hr.org-structure.store'));
};
</script>

<template>
    <HRLayout title="Tashkiliy Tuzilma Yaratish">
        <Head title="Tashkiliy Tuzilma Yaratish" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Tashkiliy Tuzilma Yaratish
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Biznesingiz uchun tashkiliy tuzilma yarating va shablonlardan foydalaning
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Business Type Selection -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        1. Biznes turini tanlang
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Biznesingiz turiga mos bo'lgan tashkiliy tuzilma shablonlarini taklif qilamiz
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            v-for="businessType in businessTypes"
                            :key="businessType.id"
                            type="button"
                            @click="form.business_type_id = businessType.id"
                            class="relative flex items-start p-4 border-2 rounded-lg transition-all duration-200"
                            :class="[
                                form.business_type_id === businessType.id
                                    ? 'border-purple-600 bg-purple-50 dark:bg-purple-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700 bg-white dark:bg-gray-700/50'
                            ]"
                        >
                            <div class="flex items-start space-x-3 flex-1">
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center text-white"
                                    :style="{ backgroundColor: businessType.color }"
                                >
                                    <component
                                        :is="getBusinessTypeIcon(businessType.code)"
                                        class="w-5 h-5"
                                    />
                                </div>
                                <div class="flex-1 text-left">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ businessType.name_uz }}
                                    </h3>
                                    <p v-if="businessType.description_uz" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ businessType.description_uz }}
                                    </p>
                                </div>
                            </div>
                            <CheckCircleIcon
                                v-if="form.business_type_id === businessType.id"
                                class="w-6 h-6 text-purple-600 dark:text-purple-400"
                            />
                        </button>
                    </div>

                    <div v-if="form.errors.business_type_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.business_type_id }}
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        2. Asosiy ma'lumotlar
                    </h2>

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nomi
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent dark:bg-gray-700 dark:text-white"
                                required
                            />
                            <div v-if="form.errors.name" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Tavsif (ixtiyoriy)
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.description }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Template Usage -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        3. Shablonlardan foydalanish
                    </h2>

                    <div class="flex items-start space-x-3">
                        <input
                            id="use_templates"
                            v-model="form.use_templates"
                            type="checkbox"
                            class="mt-1 w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-600"
                        />
                        <div class="flex-1">
                            <label for="use_templates" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Tayyor shablonlardan foydalanish
                            </label>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Tanlangan biznes turi uchun tayyor bo'limlar va lavozimlarni avtomatik yaratish.
                                Har bir lavozim uchun YQM (Yakuniy Qiymatdagi Maxsulot) ta'rifi mavjud.
                            </p>
                            <div v-if="form.use_templates" class="mt-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <p class="text-sm text-purple-900 dark:text-purple-300">
                                    <strong>Quyidagi bo'limlar yaratiladi:</strong>
                                </p>
                                <ul class="list-disc list-inside text-sm text-purple-800 dark:text-purple-400 mt-2 space-y-1">
                                    <li>Kadrlar bo'limi (HR)</li>
                                    <li>Moliya bo'limi</li>
                                    <li>Marketing bo'limi</li>
                                    <li>Sotuv bo'limi</li>
                                    <li>Texnik yordam</li>
                                    <li>+ Biznes turingizga xos bo'limlar</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-4">
                    <button
                        type="button"
                        @click="$inertia.visit(route('hr.org-structure.index'))"
                        class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        Bekor qilish
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || !form.business_type_id"
                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Yaratilmoqda...</span>
                        <span v-else>Tashkiliy Tuzilma Yaratish</span>
                    </button>
                </div>
            </form>
        </div>
    </HRLayout>
</template>
