<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    UsersIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    members: { type: Array, default: () => [] },
    departments: { type: Object, default: () => ({}) },
    roles: { type: Object, default: () => ({}) },
});

const members = ref(props.members);
const departments = ref(props.departments);
const roles = ref(props.roles);
const loading = ref(false);

const showAddModal = ref(false);
const showEditModal = ref(false);
const showPasswordModal = ref(false);
const selectedMember = ref(null);

const addForm = ref({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    department: '',
});

const editForm = ref({
    department: '',
    role: '',
});

const passwordForm = ref({
    password: '',
    password_confirmation: '',
});

// Reload page using Inertia
const reloadPage = () => {
    router.reload({ only: ['members'] });
};

// Add new member
const addMember = async () => {
    try {
        const response = await fetch(route('hr.team.invite'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(addForm.value)
        });

        const data = await response.json();

        if (data.success) {
            showAddModal.value = false;
            addForm.value = {
                name: '',
                phone: '',
                password: '',
                password_confirmation: '',
                department: '',
            };
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error adding member:', error);
        alert('Xatolik yuz berdi');
    }
};

// Open edit modal
const openEditModal = (member) => {
    selectedMember.value = member;
    editForm.value = {
        department: member.department || '',
        role: member.role || '',
    };
    showEditModal.value = true;
};

// Update member
const updateMember = async () => {
    if (!selectedMember.value) return;

    try {
        const response = await fetch(route('hr.team.update', selectedMember.value.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(editForm.value)
        });

        const data = await response.json();

        if (data.success) {
            showEditModal.value = false;
            selectedMember.value = null;
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error updating member:', error);
        alert('Xatolik yuz berdi');
    }
};

// Open password modal
const openPasswordModal = (member) => {
    selectedMember.value = member;
    passwordForm.value = {
        password: '',
        password_confirmation: '',
    };
    showPasswordModal.value = true;
};

// Reset password
const resetPassword = async () => {
    if (!selectedMember.value) return;

    try {
        const response = await fetch(route('hr.team.reset-password', selectedMember.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(passwordForm.value)
        });

        const data = await response.json();

        if (data.success) {
            showPasswordModal.value = false;
            selectedMember.value = null;
            passwordForm.value = {
                password: '',
                password_confirmation: '',
            };
            alert('Parol muvaffaqiyatli o\'zgartirildi');
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error resetting password:', error);
        alert('Xatolik yuz berdi');
    }
};

// Remove member
const removeMember = async (member) => {
    if (!confirm(`${member.name}ni jamoadan o'chirmoqchimisiz?`)) return;

    try {
        const response = await fetch(route('hr.team.remove', member.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();

        if (data.success) {
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error removing member:', error);
        alert('Xatolik yuz berdi');
    }
};
</script>

<template>
    <HRLayout title="Xodimlar">
        <Head title="Xodimlar Boshqaruvi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Xodimlar</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Jamoa a'zolarini boshqarish</p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2"
                >
                    <PlusIcon class="w-5 h-5" />
                    Xodim Qo'shish
                </button>
            </div>

            <!-- Members List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="loading" class="p-12 text-center text-gray-500 dark:text-gray-400">
                    Yuklanmoqda...
                </div>

                <div v-else-if="members.length > 0" class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">F.I.O</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Telefon</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bo'lim</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Qo'shilgan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="member in members"
                                :key="member.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                            <UsersIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ member.name }}</p>
                                            <p v-if="member.is_owner" class="text-xs text-purple-600 dark:text-purple-400">Biznes egasi</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ member.phone || 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                        {{ member.department_label || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ member.joined_at || member.created_at }}
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="!member.is_owner" class="flex items-center gap-2">
                                        <button
                                            @click="openEditModal(member)"
                                            class="p-1 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded"
                                            title="Tahrirlash"
                                        >
                                            <PencilIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="openPasswordModal(member)"
                                            class="p-1 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded"
                                            title="Parolni o'zgartirish"
                                        >
                                            <KeyIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="removeMember(member)"
                                            class="p-1 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                                            title="O'chirish"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-12 text-center text-gray-500 dark:text-gray-400">
                    <UsersIcon class="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Hali xodimlar yo'q</p>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showAddModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Yangi Xodim Qo'shish</h3>
                </div>

                <form @submit.prevent="addMember" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">F.I.O *</label>
                        <input
                            v-model="addForm.name"
                            type="text"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Ism Familiya"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Telefon *</label>
                        <input
                            v-model="addForm.phone"
                            type="text"
                            required
                            maxlength="12"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="998901234567"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bo'lim *</label>
                        <select
                            v-model="addForm.department"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                        >
                            <option value="">Tanlang</option>
                            <option v-for="(label, key) in departments" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parol *</label>
                        <input
                            v-model="addForm.password"
                            type="password"
                            required
                            minlength="6"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Kamida 6 ta belgi"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parolni tasdiqlang *</label>
                        <input
                            v-model="addForm.password_confirmation"
                            type="password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Parolni qayta kiriting"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showAddModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700"
                        >
                            Qo'shish
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Member Modal -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showEditModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Xodimni Tahrirlash</h3>
                </div>

                <form @submit.prevent="updateMember" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bo'lim</label>
                        <select
                            v-model="editForm.department"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                        >
                            <option value="">Tanlang</option>
                            <option v-for="(label, key) in departments" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700"
                        >
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reset Password Modal -->
        <div
            v-if="showPasswordModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showPasswordModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">Parolni O'zgartirish</h3>
                </div>

                <form @submit.prevent="resetPassword" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yangi parol *</label>
                        <input
                            v-model="passwordForm.password"
                            type="password"
                            required
                            minlength="6"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Kamida 6 ta belgi"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parolni tasdiqlang *</label>
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-purple-500"
                            placeholder="Parolni qayta kiriting"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showPasswordModal = false"
                            class="px-6 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Bekor qilish
                        </button>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700"
                        >
                            O'zgartirish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </HRLayout>
</template>
