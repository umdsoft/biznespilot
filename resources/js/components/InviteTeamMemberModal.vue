<script setup>
import { ref, computed, watch } from 'vue';
import {
    XMarkIcon,
    PhoneIcon,
    UserIcon,
    LockClosedIcon,
    BriefcaseIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    departments: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close', 'added']);

// Form state
const form = ref({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    department: '',
});

const isLoading = ref(false);
const error = ref('');
const showPassword = ref(false);

// Department options with colors
const departmentColors = {
    sales_head: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    marketing: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    sales_operator: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    hr: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    finance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
};

// Reset form when modal opens
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.value = {
            name: '',
            phone: '',
            password: '',
            password_confirmation: '',
            department: Object.keys(props.departments)[0] || '',
        };
        error.value = '';
        showPassword.value = false;
    }
});

// Format phone number
const formatPhone = (value) => {
    // Remove non-digits
    let digits = value.replace(/\D/g, '');

    // If starts with 998, keep it, otherwise add it
    if (!digits.startsWith('998') && digits.length > 0) {
        if (digits.startsWith('9') && digits.length <= 9) {
            digits = '998' + digits;
        }
    }

    return digits.slice(0, 12); // 998 + 9 digits
};

const onPhoneInput = (e) => {
    form.value.phone = formatPhone(e.target.value);
};

// Check if form is valid
const isValid = computed(() => {
    return form.value.name.trim() &&
           form.value.phone.length >= 12 &&
           form.value.password.length >= 6 &&
           form.value.password === form.value.password_confirmation &&
           form.value.department;
});

// Password validation
const passwordError = computed(() => {
    if (form.value.password && form.value.password.length < 6) {
        return 'Parol kamida 6 ta belgidan iborat bo\'lishi kerak';
    }
    if (form.value.password_confirmation && form.value.password !== form.value.password_confirmation) {
        return 'Parollar mos kelmayapti';
    }
    return '';
});

// Submit form using axios (auto-handles CSRF)
const submit = async () => {
    if (!isValid.value || isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        const response = await window.axios.post(route('business.settings.team.invite'), {
            name: form.value.name,
            phone: form.value.phone,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation,
            department: form.value.department,
        });

        if (response.data.success) {
            emit('added', response.data.member);
            emit('close');
        } else {
            error.value = response.data.error || response.data.message || 'Xatolik yuz berdi';
        }
    } catch (err) {
        console.error('Failed to add member:', err);
        if (err.response?.data?.error) {
            error.value = err.response.data.error;
        } else if (err.response?.data?.message) {
            error.value = err.response.data.message;
        } else if (err.response?.data?.errors) {
            // Laravel validation errors
            const errors = Object.values(err.response.data.errors).flat();
            error.value = errors[0] || 'Validatsiya xatosi';
        } else {
            error.value = 'Tarmoq xatosi';
        }
    } finally {
        isLoading.value = false;
    }
};

// Close modal
const close = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/50" @click="close"></div>

                <!-- Modal -->
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div v-if="show" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg z-10 overflow-hidden max-h-[90vh] overflow-y-auto">
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                                    <UserIcon class="w-5 h-5 text-white" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Yangi xodim qo'shish
                                </h3>
                            </div>
                            <button @click="close" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6 space-y-5">
                            <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="flex items-center gap-2">
                                        <UserIcon class="w-4 h-4 text-blue-500" />
                                        F.I.O
                                    </span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Hodimning to'liq ismi"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="flex items-center gap-2">
                                        <PhoneIcon class="w-4 h-4 text-blue-500" />
                                        Telefon raqam (Login)
                                    </span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">+</span>
                                    <input
                                        :value="form.phone"
                                        @input="onPhoneInput"
                                        type="tel"
                                        placeholder="998901234567"
                                        class="w-full pl-8 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hodim shu raqam bilan tizimga kiradi</p>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="flex items-center gap-2">
                                        <LockClosedIcon class="w-4 h-4 text-blue-500" />
                                        Parol
                                    </span>
                                </label>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Kamida 6 ta belgi"
                                        class="w-full px-4 py-2.5 pr-12 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    />
                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        <svg v-if="showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        </svg>
                                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Parolni tasdiqlang
                                </label>
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Parolni qayta kiriting"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <p v-if="passwordError" class="mt-1 text-xs text-red-500">{{ passwordError }}</p>
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    <span class="flex items-center gap-2">
                                        <BriefcaseIcon class="w-4 h-4 text-blue-500" />
                                        Bo'lim
                                    </span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="(label, value) in departments"
                                        :key="value"
                                        @click="form.department = value"
                                        :class="[
                                            'flex items-center justify-center gap-2 p-3 rounded-xl border-2 transition-all text-sm font-medium',
                                            form.department === value
                                                ? 'border-blue-500 ' + (departmentColors[value] || 'bg-blue-50 text-blue-700')
                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 text-gray-700 dark:text-gray-300'
                                        ]"
                                    >
                                        {{ label }}
                                    </button>
                                </div>
                            </div>

                            <!-- Error -->
                            <p v-if="error" class="text-sm text-red-500">{{ error }}</p>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 sticky bottom-0">
                            <button
                                @click="close"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 font-medium rounded-xl transition-colors"
                            >
                                Bekor qilish
                            </button>
                            <button
                                @click="submit"
                                :disabled="!isValid || isLoading"
                                :class="[
                                    'px-6 py-2 font-medium rounded-xl transition-colors',
                                    isValid && !isLoading
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                        : 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                ]"
                            >
                                {{ isLoading ? 'Saqlanmoqda...' : 'Xodim qo\'shish' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
