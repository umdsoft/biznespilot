<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { useI18n } from '@/i18n';
import {
    UsersIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    KeyIcon,
    DocumentTextIcon,
    CalendarIcon,
    UserMinusIcon,
    ChevronRightIcon,
    XMarkIcon,
    CheckIcon,
    ClockIcon,
    MagnifyingGlassIcon,
    PrinterIcon,
    EyeIcon,
    PhoneIcon,
    EnvelopeIcon,
    BriefcaseIcon,
    BuildingOfficeIcon,
    UserIcon,
    ArrowRightOnRectangleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    employees: { type: Array, default: () => [] },
    departments: { type: Object, default: () => ({}) },
    roles: { type: Object, default: () => ({}) },
    leaveRequests: { type: Array, default: () => [] },
    terminatedEmployees: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    jobDescriptions: { type: Array, default: () => [] },
    employmentTypes: { type: Object, default: () => ({}) },
    contractTypes: { type: Object, default: () => ({}) },
    positionLevels: { type: Object, default: () => ({}) },
    currentBusiness: { type: Object, default: null },
});

// Reactive state
const searchQuery = ref('');
const selectedDepartment = ref('');
const selectedEmployee = ref(null);
const showDetailPanel = ref(false);
const activeDetailTab = ref('info');
const loading = ref(false);

// Modals
const showAddModal = ref(false);
const showEditModal = ref(false);
const showPasswordModal = ref(false);
const showContractModal = ref(false);
const showLeaveModal = ref(false);
const showTerminationModal = ref(false);

// Forms
const addForm = ref({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    department: '',
    position_id: '',
    salary: '',
    employment_type: 'full_time',
});

// Computed - Filter positions by selected department
const filteredPositions = computed(() => {
    if (!addForm.value.department) return [];
    return props.jobDescriptions.filter(job => job.department === addForm.value.department);
});

// Watch department change - reset position
watch(() => addForm.value.department, () => {
    addForm.value.position_id = '';
    addForm.value.salary = '';
    addForm.value.employment_type = 'full_time';
});

// Watch position change - auto-fill salary and employment type
watch(() => addForm.value.position_id, (newPositionId) => {
    if (!newPositionId) {
        addForm.value.salary = '';
        addForm.value.employment_type = 'full_time';
        return;
    }
    const position = props.jobDescriptions.find(job => job.id === newPositionId);
    if (position) {
        // Set average of min and max salary, or min if max is not set
        if (position.salary_range_min && position.salary_range_max) {
            addForm.value.salary = Math.round((Number(position.salary_range_min) + Number(position.salary_range_max)) / 2);
        } else if (position.salary_range_min) {
            addForm.value.salary = Number(position.salary_range_min);
        } else if (position.salary_range_max) {
            addForm.value.salary = Number(position.salary_range_max);
        }
        addForm.value.employment_type = position.employment_type || 'full_time';
    }
});

const editForm = ref({
    department: '',
    role: '',
    position: '',
    contract_type: 'unlimited',
    contract_start_date: '',
    contract_end_date: '',
    salary: '',
});

const passwordForm = ref({
    password: '',
    password_confirmation: '',
});

const contractForm = ref({
    contract_type: 'unlimited',
    contract_start_date: '',
    contract_end_date: '',
    salary: '',
    work_schedule: 'full_time',
});

const leaveForm = ref({
    leave_type: 'annual',
    start_date: '',
    end_date: '',
    reason: '',
});

const terminationForm = ref({
    termination_type: 'voluntary',
    termination_date: new Date().toISOString().split('T')[0],
    reason: '',
});

// Options
const contractTypes = [
    { value: 'unlimited', label: 'Muddatsiz' },
    { value: 'fixed', label: 'Muddatli' },
    { value: 'probation', label: 'Sinov muddati' },
    { value: 'part_time', label: 'Yarim stavka' },
];

const leaveTypes = [
    { value: 'annual', label: 'Yillik ta\'til' },
    { value: 'sick', label: 'Kasallik ta\'tili' },
    { value: 'family', label: 'Oilaviy ta\'til' },
    { value: 'unpaid', label: 'To\'lamasdan ta\'til' },
];

const terminationTypes = [
    { value: 'voluntary', label: 'O\'z xohishi bilan' },
    { value: 'involuntary', label: 'Ish beruvchi qarori' },
    { value: 'retirement', label: 'Pensiyaga chiqish' },
    { value: 'contract_end', label: 'Shartnoma tugashi' },
];

const workSchedules = [
    { value: 'full_time', label: 'To\'liq stavka' },
    { value: 'part_time', label: 'Yarim stavka' },
];

// Computed
const filteredEmployees = computed(() => {
    return props.employees.filter(emp => {
        const matchesSearch = !searchQuery.value ||
            emp.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            emp.phone?.includes(searchQuery.value) ||
            emp.position?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesDept = !selectedDepartment.value || emp.department === selectedDepartment.value;
        return matchesSearch && matchesDept;
    });
});

const employeeLeaveRequests = computed(() => {
    if (!selectedEmployee.value) return [];
    return props.leaveRequests.filter(lr => lr.user_id === selectedEmployee.value.user_id);
});

const computedStats = computed(() => ({
    total: props.employees.length,
    active: props.employees.filter(e => !e.terminated_at).length,
    onLeave: props.stats?.on_leave_today || 0,
    pendingLeave: props.leaveRequests.filter(l => l.status === 'pending').length,
    unlimited: props.employees.filter(e => e.contract_type === 'unlimited').length,
    fixed: props.employees.filter(e => e.contract_type === 'fixed').length,
    probation: props.employees.filter(e => e.contract_type === 'probation').length,
}));

// Methods
const reloadPage = () => {
    router.reload();
};

const openEmployeeDetail = (employee) => {
    selectedEmployee.value = employee;
    showDetailPanel.value = true;
    activeDetailTab.value = 'info';
};

const closeDetailPanel = () => {
    showDetailPanel.value = false;
    selectedEmployee.value = null;
};

const getContractTypeLabel = (type) => {
    return contractTypes.find(t => t.value === type)?.label || type || 'Belgilanmagan';
};

const getContractTypeColor = (type) => {
    const colors = {
        unlimited: 'bg-gradient-to-r from-green-50 to-emerald-50 text-green-700 dark:from-green-900/30 dark:to-emerald-900/30 dark:text-green-400 border-green-200 dark:border-green-800',
        fixed: 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 dark:from-blue-900/30 dark:to-indigo-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
        fixed_term: 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 dark:from-blue-900/30 dark:to-indigo-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
        probation: 'bg-gradient-to-r from-amber-50 to-orange-50 text-amber-700 dark:from-amber-900/30 dark:to-orange-900/30 dark:text-amber-400 border-amber-200 dark:border-amber-800',
        part_time: 'bg-gradient-to-r from-purple-50 to-pink-50 text-purple-700 dark:from-purple-900/30 dark:to-pink-900/30 dark:text-purple-400 border-purple-200 dark:border-purple-800',
    };
    return colors[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700/50 dark:text-gray-300 border-gray-200 dark:border-gray-600';
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = { pending: 'Kutilmoqda', approved: 'Tasdiqlangan', rejected: 'Rad etilgan' };
    return labels[status] || status;
};

// API Methods
const addMember = async () => {
    try {
        loading.value = true;

        // Get position title if selected
        const selectedPosition = addForm.value.position_id
            ? props.jobDescriptions.find(j => j.id === addForm.value.position_id)
            : null;

        const formData = {
            name: addForm.value.name,
            phone: addForm.value.phone,
            password: addForm.value.password,
            password_confirmation: addForm.value.password_confirmation,
            department: addForm.value.department,
            job_description_id: addForm.value.position_id || null,
            salary: addForm.value.salary || null,
            employment_type: addForm.value.employment_type,
            position: selectedPosition?.title || null,
        };

        const response = await fetch(route('hr.team.invite'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(formData)
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
                position_id: '',
                salary: '',
                employment_type: 'full_time'
            };
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const updateMember = async () => {
    if (!selectedEmployee.value) return;
    try {
        loading.value = true;
        const response = await fetch(route('hr.team.update', selectedEmployee.value.id), {
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
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const resetPassword = async () => {
    if (!selectedEmployee.value) return;
    try {
        loading.value = true;
        const response = await fetch(route('hr.team.reset-password', selectedEmployee.value.id), {
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
            passwordForm.value = { password: '', password_confirmation: '' };
            alert('Parol muvaffaqiyatli o\'zgartirildi');
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const removeMember = async (employee) => {
    if (!confirm(`${employee.name} ni o'chirishni tasdiqlaysizmi?`)) return;
    try {
        loading.value = true;
        const response = await fetch(route('hr.team.remove', employee.id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        const data = await response.json();
        if (data.success) {
            closeDetailPanel();
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const saveContract = async () => {
    if (!selectedEmployee.value) return;
    try {
        loading.value = true;
        const response = await fetch(route('hr.employees.contract.update', selectedEmployee.value.id), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(contractForm.value)
        });
        const data = await response.json();
        if (data.success) {
            showContractModal.value = false;
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const createLeaveRequest = async () => {
    if (!selectedEmployee.value) return;
    try {
        loading.value = true;
        const response = await fetch(route('hr.employees.leave-request.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                user_id: selectedEmployee.value.user_id,
                ...leaveForm.value
            })
        });
        const data = await response.json();
        if (data.success) {
            showLeaveModal.value = false;
            leaveForm.value = { leave_type: 'annual', start_date: '', end_date: '', reason: '' };
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

const approveLeave = async (leaveId) => {
    if (!confirm('Ushbu ta\'til so\'rovini tasdiqlaysizmi?')) return;
    router.post(`/hr/leave/${leaveId}/approve`);
};

const rejectLeave = async (leaveId) => {
    if (!confirm('Ushbu ta\'til so\'rovini rad etasizmi?')) return;
    router.post(`/hr/leave/${leaveId}/reject`);
};

const terminateEmployee = async () => {
    if (!selectedEmployee.value) return;
    if (!confirm(`${selectedEmployee.value.name} ni ishdan bo'shatishni tasdiqlaysizmi?`)) return;

    try {
        loading.value = true;
        const response = await fetch(route('hr.employees.terminate', selectedEmployee.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify(terminationForm.value)
        });
        const data = await response.json();
        if (data.success) {
            showTerminationModal.value = false;
            closeDetailPanel();
            reloadPage();
        } else {
            alert(data.error || 'Xatolik yuz berdi');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Xatolik yuz berdi');
    } finally {
        loading.value = false;
    }
};

// Open modals with prefilled data
const openEditEmployee = (employee) => {
    selectedEmployee.value = employee;
    editForm.value = {
        department: employee.department || '',
        role: employee.role || '',
        position: employee.position || '',
        contract_type: employee.contract_type || 'unlimited',
        contract_start_date: employee.contract_start_date || '',
        contract_end_date: employee.contract_end_date || '',
        salary: employee.salary || '',
    };
    showEditModal.value = true;
};

const openContractModal = (employee) => {
    selectedEmployee.value = employee;
    contractForm.value = {
        contract_type: employee.contract_type || 'unlimited',
        contract_start_date: employee.contract_start_date || '',
        contract_end_date: employee.contract_end_date || '',
        salary: employee.salary || '',
        work_schedule: employee.work_schedule || 'full_time',
    };
    showContractModal.value = true;
};

const openLeaveModal = (employee) => {
    selectedEmployee.value = employee;
    leaveForm.value = { leave_type: 'annual', start_date: '', end_date: '', reason: '' };
    showLeaveModal.value = true;
};

const openTerminationModal = (employee) => {
    selectedEmployee.value = employee;
    terminationForm.value = {
        termination_type: 'voluntary',
        termination_date: new Date().toISOString().split('T')[0],
        reason: '',
    };
    showTerminationModal.value = true;
};

const openPasswordModal = (employee) => {
    selectedEmployee.value = employee;
    passwordForm.value = { password: '', password_confirmation: '' };
    showPasswordModal.value = true;
};

// Detail tabs
const detailTabs = [
    { id: 'info', label: 'Ma\'lumotlar', icon: UserIcon },
    { id: 'contract', label: 'Shartnoma', icon: DocumentTextIcon },
    { id: 'leave', label: 'Ta\'tillar', icon: CalendarIcon },
    { id: 'history', label: 'Tarix', icon: ClockIcon },
];
</script>

<template>
    <HRLayout title="Xodimlar boshqaruvi">
        <Head title="Xodimlar boshqaruvi" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Xodimlar</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Jamoa a'zolarini boshqarish</p>
                </div>
                <button
                    @click="showAddModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-all shadow-lg shadow-purple-600/20"
                >
                    <PlusIcon class="w-5 h-5" />
                    Xodim qo'shish
                </button>
            </div>

            <!-- Stats Cards - Enhanced Design -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                <!-- Total -->
                <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-4 shadow-lg shadow-purple-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <UsersIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-purple-100 text-xs font-medium">Jami</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.total }}</p>
                    </div>
                </div>

                <!-- Active -->
                <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 shadow-lg shadow-green-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <CheckIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-green-100 text-xs font-medium">Faol</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.active }}</p>
                    </div>
                </div>

                <!-- On Leave -->
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 shadow-lg shadow-blue-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <CalendarIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-blue-100 text-xs font-medium">Ta'tilda</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.onLeave }}</p>
                    </div>
                </div>

                <!-- Pending -->
                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-4 shadow-lg shadow-amber-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <ClockIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-amber-100 text-xs font-medium">Kutilmoqda</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.pendingLeave }}</p>
                    </div>
                </div>

                <!-- Unlimited -->
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-4 shadow-lg shadow-emerald-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <DocumentTextIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-emerald-100 text-xs font-medium">Muddatsiz</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.unlimited }}</p>
                    </div>
                </div>

                <!-- Fixed Term -->
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl p-4 shadow-lg shadow-indigo-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <DocumentTextIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-indigo-100 text-xs font-medium">Muddatli</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.fixed }}</p>
                    </div>
                </div>

                <!-- Probation -->
                <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-500 rounded-2xl p-4 shadow-lg shadow-orange-500/20">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center mb-3">
                            <DocumentTextIcon class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-orange-100 text-xs font-medium">Sinov</p>
                        <p class="text-2xl font-bold text-white mt-1">{{ computedStats.probation }}</p>
                    </div>
                </div>
            </div>

            <!-- Filters - Enhanced -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[300px]">
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Xodim qidirish (ism, telefon, lavozim)..."
                                class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 focus:bg-white dark:focus:bg-gray-700 transition-all text-sm"
                            />
                        </div>
                    </div>
                    <select
                        v-model="selectedDepartment"
                        class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm min-w-[180px]"
                    >
                        <option value="">Barcha bo'limlar</option>
                        <option v-for="(label, key) in departments" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ filteredEmployees.length }}</span> xodim topildi
                    </div>
                </div>
            </div>

            <!-- Employee Table - Enhanced Design -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-800 dark:to-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Xodim</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Telefon</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Bo'lim / Lavozim</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Shartnoma</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Qo'shilgan</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                            <tr
                                v-for="employee in filteredEmployees"
                                :key="employee.id"
                                class="group hover:bg-purple-50/50 dark:hover:bg-purple-900/10 transition-all duration-200 cursor-pointer"
                                @click="openEmployeeDetail(employee)"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative">
                                            <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-white font-semibold shadow-lg shadow-purple-500/20 group-hover:shadow-xl group-hover:shadow-purple-500/30 transition-all">
                                                {{ employee.name?.charAt(0) || '?' }}
                                            </div>
                                            <div v-if="employee.is_owner" class="absolute -top-1 -right-1 w-4 h-4 bg-yellow-400 rounded-full border-2 border-white dark:border-gray-800 flex items-center justify-center">
                                                <svg class="w-2.5 h-2.5 text-yellow-800" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-700 dark:group-hover:text-purple-400 transition-colors">{{ employee.name }}</p>
                                            <p v-if="employee.is_owner" class="text-xs font-medium text-purple-600 dark:text-purple-400">Biznes egasi</p>
                                            <p v-else-if="employee.email" class="text-xs text-gray-400">{{ employee.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                            <PhoneIcon class="w-4 h-4 text-gray-500" />
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{ employee.phone ? '+' + employee.phone : 'N/A' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 dark:from-blue-900/30 dark:to-indigo-900/30 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                                            <BuildingOfficeIcon class="w-3 h-3" />
                                            {{ employee.department_label || 'N/A' }}
                                        </span>
                                        <p v-if="employee.position" class="text-xs text-gray-500 dark:text-gray-400 pl-1">{{ employee.position }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg border', getContractTypeColor(employee.contract_type)]">
                                        {{ getContractTypeLabel(employee.contract_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <CalendarIcon class="w-4 h-4 text-gray-400" />
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ employee.joined_at || employee.created_at }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4" @click.stop>
                                    <div v-if="!employee.is_owner" class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEmployeeDetail(employee)"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors"
                                            title="Ko'rish"
                                        >
                                            <EyeIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="openEditEmployee(employee)"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                            title="Tahrirlash"
                                        >
                                            <PencilIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="openContractModal(employee)"
                                            class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                                            title="Shartnoma"
                                        >
                                            <DocumentTextIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="openLeaveModal(employee)"
                                            class="p-2 text-gray-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                                            title="Ta'tilga chiqarish"
                                        >
                                            <CalendarIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="openTerminationModal(employee)"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                            title="Ishdan bo'shatish"
                                        >
                                            <UserMinusIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="filteredEmployees.length === 0" class="p-16 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl mb-6">
                        <UsersIcon class="w-10 h-10 text-purple-500 dark:text-purple-400" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Xodimlar topilmadi</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">Qidiruv so'rovingizni o'zgartiring yoki yangi xodim qo'shing</p>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-semibold rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg shadow-purple-500/20 hover:shadow-xl"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Yangi xodim qo'shish
                    </button>
                </div>
            </div>
        </div>

        <!-- Employee Detail Slide-over Panel -->
        <Teleport to="body">
            <div v-if="showDetailPanel" class="fixed inset-0 z-50 overflow-hidden">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetailPanel"></div>
                <div class="absolute inset-y-0 right-0 max-w-2xl w-full">
                    <div class="h-full bg-white dark:bg-gray-800 shadow-2xl flex flex-col">
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-purple-600 to-pink-600">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center text-white text-xl font-bold">
                                    {{ selectedEmployee?.name?.charAt(0) || '?' }}
                                </div>
                                <div class="text-white">
                                    <h2 class="text-xl font-bold">{{ selectedEmployee?.name }}</h2>
                                    <p class="text-white/80 text-sm">{{ selectedEmployee?.department_label }} / {{ selectedEmployee?.position || 'Lavozim belgilanmagan' }}</p>
                                </div>
                            </div>
                            <button @click="closeDetailPanel" class="p-2 text-white/80 hover:text-white hover:bg-white/20 rounded-lg transition-colors">
                                <XMarkIcon class="w-6 h-6" />
                            </button>
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <nav class="flex px-6 gap-1">
                                <button
                                    v-for="tab in detailTabs"
                                    :key="tab.id"
                                    @click="activeDetailTab = tab.id"
                                    :class="[
                                        'flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px',
                                        activeDetailTab === tab.id
                                            ? 'border-purple-600 text-purple-600 dark:text-purple-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                                    ]"
                                >
                                    <component :is="tab.icon" class="w-4 h-4" />
                                    {{ tab.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <!-- Info Tab -->
                            <div v-if="activeDetailTab === 'info'" class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 mb-1">
                                            <PhoneIcon class="w-4 h-4" />
                                            <span class="text-xs uppercase tracking-wide">Telefon</span>
                                        </div>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ selectedEmployee?.phone || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 mb-1">
                                            <BuildingOfficeIcon class="w-4 h-4" />
                                            <span class="text-xs uppercase tracking-wide">Bo'lim</span>
                                        </div>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ selectedEmployee?.department_label || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 mb-1">
                                            <BriefcaseIcon class="w-4 h-4" />
                                            <span class="text-xs uppercase tracking-wide">Lavozim</span>
                                        </div>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ selectedEmployee?.position || 'Belgilanmagan' }}</p>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                        <div class="flex items-center gap-3 text-gray-500 dark:text-gray-400 mb-1">
                                            <CalendarIcon class="w-4 h-4" />
                                            <span class="text-xs uppercase tracking-wide">Qo'shilgan</span>
                                        </div>
                                        <p class="text-gray-900 dark:text-white font-medium">{{ selectedEmployee?.joined_at || selectedEmployee?.created_at }}</p>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div v-if="!selectedEmployee?.is_owner" class="space-y-3">
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Tezkor amallar</h3>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button
                                            @click="openEditEmployee(selectedEmployee)"
                                            class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                        >
                                            <PencilIcon class="w-5 h-5" />
                                            <span class="font-medium">Tahrirlash</span>
                                        </button>
                                        <button
                                            @click="openPasswordModal(selectedEmployee)"
                                            class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                                        >
                                            <KeyIcon class="w-5 h-5" />
                                            <span class="font-medium">Parol o'zgartirish</span>
                                        </button>
                                        <button
                                            @click="openContractModal(selectedEmployee)"
                                            class="flex items-center gap-3 p-4 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 rounded-xl hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors"
                                        >
                                            <DocumentTextIcon class="w-5 h-5" />
                                            <span class="font-medium">Shartnoma</span>
                                        </button>
                                        <button
                                            @click="openLeaveModal(selectedEmployee)"
                                            class="flex items-center gap-3 p-4 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 rounded-xl hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors"
                                        >
                                            <CalendarIcon class="w-5 h-5" />
                                            <span class="font-medium">Ta'tilga chiqarish</span>
                                        </button>
                                    </div>
                                    <button
                                        @click="openTerminationModal(selectedEmployee)"
                                        class="w-full flex items-center justify-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                                    >
                                        <UserMinusIcon class="w-5 h-5" />
                                        <span class="font-medium">Ishdan bo'shatish</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Contract Tab -->
                            <div v-if="activeDetailTab === 'contract'" class="space-y-6">
                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 border border-purple-100 dark:border-purple-800">
                                    <div class="flex items-center justify-between mb-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mehnat shartnomasi</h3>
                                        <button
                                            @click="openContractModal(selectedEmployee)"
                                            class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors"
                                        >
                                            Tahrirlash
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Shartnoma turi</p>
                                            <span :class="['inline-flex mt-1 px-3 py-1 text-sm font-medium rounded-lg', getContractTypeColor(selectedEmployee?.contract_type)]">
                                                {{ getContractTypeLabel(selectedEmployee?.contract_type) }}
                                            </span>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Ish tartibi</p>
                                            <p class="text-gray-900 dark:text-white font-medium mt-1">{{ selectedEmployee?.work_schedule === 'full_time' ? 'To\'liq stavka' : 'Yarim stavka' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Boshlanish sanasi</p>
                                            <p class="text-gray-900 dark:text-white font-medium mt-1">{{ selectedEmployee?.contract_start_date || selectedEmployee?.joined_at || 'Belgilanmagan' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Tugash sanasi</p>
                                            <p class="text-gray-900 dark:text-white font-medium mt-1">{{ selectedEmployee?.contract_end_date || 'Muddatsiz' }}</p>
                                        </div>
                                        <div class="col-span-2">
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Oylik maosh</p>
                                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                                {{ selectedEmployee?.salary ? Number(selectedEmployee.salary).toLocaleString() + ' UZS' : 'Belgilanmagan' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <button class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        <PrinterIcon class="w-5 h-5" />
                                        Shartnomani chop etish
                                    </button>
                                </div>
                            </div>

                            <!-- Leave Tab -->
                            <div v-if="activeDetailTab === 'leave'" class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ta'til so'rovlari</h3>
                                    <button
                                        @click="openLeaveModal(selectedEmployee)"
                                        class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors"
                                    >
                                        + Ta'tilga chiqarish
                                    </button>
                                </div>

                                <div v-if="employeeLeaveRequests.length > 0" class="space-y-3">
                                    <div
                                        v-for="leave in employeeLeaveRequests"
                                        :key="leave.id"
                                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ leave.leave_type }}</span>
                                            <span :class="['px-2 py-1 text-xs font-medium rounded-lg', getStatusColor(leave.status)]">
                                                {{ getStatusLabel(leave.status) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ leave.start_date }} - {{ leave.end_date }} ({{ leave.total_days }} kun)
                                        </p>
                                        <p v-if="leave.reason" class="text-sm text-gray-600 dark:text-gray-300 mt-2">{{ leave.reason }}</p>
                                        <div v-if="leave.status === 'pending'" class="flex gap-2 mt-3">
                                            <button
                                                @click="approveLeave(leave.id)"
                                                class="flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700"
                                            >
                                                <CheckIcon class="w-4 h-4" />
                                                Tasdiqlash
                                            </button>
                                            <button
                                                @click="rejectLeave(leave.id)"
                                                class="flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700"
                                            >
                                                <XMarkIcon class="w-4 h-4" />
                                                Rad etish
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    <CalendarIcon class="w-12 h-12 mx-auto opacity-30 mb-3" />
                                    <p>Ta'til so'rovlari mavjud emas</p>
                                </div>
                            </div>

                            <!-- History Tab -->
                            <div v-if="activeDetailTab === 'history'" class="space-y-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ish tarixi</h3>
                                <div class="space-y-4">
                                    <div class="flex gap-4">
                                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                            <UsersIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">Jamoaga qo'shildi</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ selectedEmployee?.joined_at || selectedEmployee?.created_at }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Add Employee Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 sticky top-0 bg-white dark:bg-gray-800 z-10">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Yangi xodim qo'shish</h3>
                    </div>
                    <form @submit.prevent="addMember" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">F.I.O *</label>
                                <input v-model="addForm.name" type="text" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Ism Familiya" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Telefon *</label>
                                <input v-model="addForm.phone" type="text" required maxlength="12" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="998901234567" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bo'lim *</label>
                                <select v-model="addForm.department" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    <option value="">Tanlang</option>
                                    <option v-for="(label, key) in departments" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lavozim</label>
                                <select v-model="addForm.position_id" :disabled="!addForm.department || filteredPositions.length === 0" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 disabled:opacity-50">
                                    <option value="">{{ !addForm.department ? 'Avval bo\'lim tanlang' : (filteredPositions.length === 0 ? 'Lavozim mavjud emas' : 'Tanlang') }}</option>
                                    <option v-for="pos in filteredPositions" :key="pos.id" :value="pos.id">
                                        {{ pos.title }} {{ pos.position_level_label ? `(${pos.position_level_label})` : '' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Salary & Employment Type (auto-filled from position) -->
                        <div v-if="addForm.position_id" class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 space-y-3">
                            <div class="flex items-center gap-2 text-purple-700 dark:text-purple-300 text-sm font-medium">
                                <BriefcaseIcon class="w-4 h-4" />
                                <span>Lavozim ma'lumotlari (avtomatik to'ldirildi)</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Oylik maosh (UZS)</label>
                                    <input v-model="addForm.salary" type="number" class="w-full px-3 py-2 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm" placeholder="0" />
                                    <p v-if="filteredPositions.find(p => p.id === addForm.position_id)?.salary_range_formatted" class="text-xs text-gray-500 mt-1">
                                        Diapazon: {{ filteredPositions.find(p => p.id === addForm.position_id)?.salary_range_formatted }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Ish tartibi</label>
                                    <select v-model="addForm.employment_type" class="w-full px-3 py-2 border border-purple-200 dark:border-purple-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 text-sm">
                                        <option v-for="(label, key) in employmentTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parol *</label>
                                <input v-model="addForm.password" type="password" required minlength="6" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Kamida 6 belgi" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tasdiqlash *</label>
                                <input v-model="addForm.password_confirmation" type="password" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Qayta kiriting" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showAddModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50">
                                {{ loading ? 'Saqlanmoqda...' : 'Qo\'shish' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Edit Employee Modal -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Xodimni tahrirlash</h3>
                    </div>
                    <form @submit.prevent="updateMember" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bo'lim</label>
                            <select v-model="editForm.department" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                <option value="">Tanlang</option>
                                <option v-for="(label, key) in departments" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lavozim</label>
                            <input v-model="editForm.position" type="text" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Lavozim nomi" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showEditModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50">Saqlash</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Password Modal -->
        <Teleport to="body">
            <div v-if="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Parolni o'zgartirish</h3>
                    </div>
                    <form @submit.prevent="resetPassword" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Yangi parol *</label>
                            <input v-model="passwordForm.password" type="password" required minlength="6" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parolni tasdiqlash *</label>
                            <input v-model="passwordForm.password_confirmation" type="password" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showPasswordModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50">O'zgartirish</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Contract Modal -->
        <Teleport to="body">
            <div v-if="showContractModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full shadow-2xl">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Mehnat shartnomasi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ selectedEmployee?.name }}</p>
                    </div>
                    <form @submit.prevent="saveContract" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shartnoma turi</label>
                                <select v-model="contractForm.contract_type" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    <option v-for="type in contractTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ish tartibi</label>
                                <select v-model="contractForm.work_schedule" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                    <option v-for="schedule in workSchedules" :key="schedule.value" :value="schedule.value">{{ schedule.label }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boshlanish sanasi</label>
                                <input v-model="contractForm.contract_start_date" type="date" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tugash sanasi</label>
                                <input v-model="contractForm.contract_end_date" type="date" :disabled="contractForm.contract_type === 'unlimited'" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 disabled:opacity-50" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Oylik maosh (UZS)</label>
                            <input v-model="contractForm.salary" type="number" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" placeholder="0" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showContractModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50">Saqlash</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Leave Modal -->
        <Teleport to="body">
            <div v-if="showLeaveModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ta'tilga chiqarish</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ selectedEmployee?.name }}</p>
                    </div>
                    <form @submit.prevent="createLeaveRequest" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ta'til turi</label>
                            <select v-model="leaveForm.leave_type" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                <option v-for="type in leaveTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boshlanish</label>
                                <input v-model="leaveForm.start_date" type="date" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tugash</label>
                                <input v-model="leaveForm.end_date" type="date" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sabab</label>
                            <textarea v-model="leaveForm.reason" rows="3" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" placeholder="Ta'til sababi..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showLeaveModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-xl hover:bg-purple-700 transition-colors disabled:opacity-50">Yuborish</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Termination Modal -->
        <Teleport to="body">
            <div v-if="showTerminationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full shadow-2xl">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-900/20 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ishdan bo'shatish</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ selectedEmployee?.name }}</p>
                            </div>
                        </div>
                    </div>
                    <form @submit.prevent="terminateEmployee" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ketish turi</label>
                            <select v-model="terminationForm.termination_type" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
                                <option v-for="type in terminationTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ketish sanasi</label>
                            <input v-model="terminationForm.termination_date" type="date" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sabab</label>
                            <textarea v-model="terminationForm.reason" rows="3" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500" placeholder="Ishdan ketish sababi..."></textarea>
                        </div>
                        <div class="flex justify-end gap-3 pt-4">
                            <button type="button" @click="showTerminationModal = false" class="px-6 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">Bekor qilish</button>
                            <button type="submit" :disabled="loading" class="px-6 py-2.5 bg-red-600 text-white text-sm font-medium rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50">Ishdan bo'shatish</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </HRLayout>
</template>
