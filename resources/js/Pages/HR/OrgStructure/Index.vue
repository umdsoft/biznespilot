<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HRLayout from '@/layouts/HRLayout.vue';
import { useI18n } from '@/i18n';
import {
    PlusIcon,
    PrinterIcon,
    PencilIcon,
    UserPlusIcon,
    DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps({
    orgStructure: Object,
    business: Object,
    systemDepartments: Array,
    departments: Object,
    ownerInfo: Object,
});

// Professional department colors (light text for dark mode compatibility)
const departmentColors = {
    sales_head: { bg: '#059669', light: '#D1FAE5', text: '#10B981', darkBg: '#064E3B' },
    sales_operator: { bg: '#059669', light: '#D1FAE5', text: '#10B981', darkBg: '#064E3B' },
    marketing: { bg: '#7C3AED', light: '#EDE9FE', text: '#A78BFA', darkBg: '#4C1D95' },
    hr: { bg: '#DB2777', light: '#FCE7F3', text: '#F472B6', darkBg: '#831843' },
    finance: { bg: '#EA580C', light: '#FFEDD5', text: '#FB923C', darkBg: '#7C2D12' },
    default: { bg: '#475569', light: '#F1F5F9', text: '#94A3B8', darkBg: '#1E293B' },
};

// Merged department structure
const mergedDepartments = computed(() => {
    if (!props.systemDepartments) return [];

    const departments = [];
    const salesDept = {
        code: 'sales',
        name: "Sotuv bo'limi",
        employees: [],
        positions: [],
        employee_count: 0,
    };

    let hasSalesData = false;

    props.systemDepartments.forEach(dept => {
        if (dept.code === 'sales_head' || dept.code === 'sales_operator') {
            salesDept.employees = [...salesDept.employees, ...dept.employees];
            salesDept.positions = [...salesDept.positions, ...dept.positions];
            salesDept.employee_count += dept.employee_count;
            hasSalesData = true;
        } else {
            departments.push(dept);
        }
    });

    if (hasSalesData || props.systemDepartments.some(d => d.code === 'sales_head' || d.code === 'sales_operator')) {
        departments.unshift(salesDept);
    }

    return departments;
});

const getColor = (code) => {
    if (code === 'sales' || code === 'sales_head' || code === 'sales_operator') {
        return departmentColors.sales_head;
    }
    return departmentColors[code] || departmentColors.default;
};

// Department goals
const deptGoals = {
    sales: "Sotuvlarni amalga oshirish va daromadni ko'paytirish",
    marketing: "Brend qiymatini oshirish va sifatli lidlar generatsiya qilish",
    hr: "Malakali kadrlarni jalb qilish va samaradorlikni oshirish",
    finance: "Moliyaviy barqarorlikni ta'minlash",
};

const getGoal = (code) => {
    if (code === 'sales' || code === 'sales_head' || code === 'sales_operator') return deptGoals.sales;
    return deptGoals[code] || '';
};

// Stats
const stats = computed(() => {
    let employees = 0, positions = 0;
    mergedDepartments.value.forEach(d => {
        employees += d.employee_count || 0;
        positions += d.positions?.length || 0;
    });
    return { departments: mergedDepartments.value.length, employees, positions };
});

// Mission
const mission = computed(() => {
    return props.orgStructure?.description || "Mijozlarga yuqori sifatli xizmat ko'rsatish va biznesni barqaror rivojlantirish";
});
</script>

<template>
    <HRLayout :title="t('hr.org_structure')">
        <Head :title="t('hr.org_structure')" />

        <div class="space-y-5">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Tashkiliy tuzilma
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ stats.departments }} bo'lim · {{ stats.employees }} xodim · {{ stats.positions }} lavozim
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('hr.job-descriptions.index')"
                        class="inline-flex items-center px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <DocumentTextIcon class="w-4 h-4 mr-1.5" />
                        Lavozimlar
                    </Link>
                    <button class="inline-flex items-center px-3 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <PrinterIcon class="w-4 h-4 mr-1.5" />
                        Chop etish
                    </button>
                    <Link
                        v-if="orgStructure"
                        :href="route('hr.org-structure.edit', orgStructure.id)"
                        class="inline-flex items-center px-3 py-2 text-sm bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100"
                    >
                        <PencilIcon class="w-4 h-4 mr-1.5" />
                        Tahrirlash
                    </Link>
                    <Link
                        v-else
                        :href="route('hr.org-structure.create')"
                        class="inline-flex items-center px-3 py-2 text-sm bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100"
                    >
                        <PlusIcon class="w-4 h-4 mr-1.5" />
                        Yaratish
                    </Link>
                </div>
            </div>

            <!-- Org Chart Container -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 overflow-x-auto border border-gray-200 dark:border-gray-800">
                <div class="min-w-max flex flex-col items-center">

                    <!-- Director -->
                    <div class="bg-gray-900 dark:bg-gray-800 rounded-lg shadow-xl w-72 overflow-hidden">
                        <div class="bg-gray-800 dark:bg-gray-700 px-4 py-2.5">
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Direktor</p>
                        </div>
                        <div class="p-4">
                            <p class="text-white font-semibold text-base text-center">
                                {{ ownerInfo?.name || business?.name }}
                            </p>
                            <p class="text-gray-400 text-sm text-center mt-0.5">
                                {{ ownerInfo?.position || 'Bosh direktor' }}
                            </p>
                        </div>
                        <div class="bg-gray-800/50 dark:bg-gray-700/50 px-4 py-2.5 border-t border-gray-700">
                            <p class="text-xs text-gray-400 text-center">
                                <span class="text-yellow-500 font-medium">YQM:</span>
                                {{ ownerInfo?.yqm || 'Korxonani boshqarish va rivojlantirish' }}
                            </p>
                        </div>
                    </div>

                    <!-- Connector from Director -->
                    <div class="w-px h-8 bg-gray-300 dark:bg-gray-600"></div>

                    <!-- Horizontal Line -->
                    <div v-if="mergedDepartments.length > 1" class="relative flex items-center">
                        <div
                            class="h-px bg-gray-300 dark:bg-gray-600"
                            :style="{ width: `${(mergedDepartments.length - 1) * 232 + (mergedDepartments.length - 1) * 12}px` }"
                        ></div>
                    </div>

                    <!-- Departments -->
                    <div class="flex gap-3">
                        <div
                            v-for="dept in mergedDepartments"
                            :key="dept.code"
                            class="flex flex-col items-center"
                        >
                            <!-- Vertical connector -->
                            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>

                            <!-- Department Card -->
                            <div class="w-56 rounded-lg overflow-hidden shadow-lg">
                                <!-- Header -->
                                <div
                                    class="px-4 py-3 text-center"
                                    :style="{ backgroundColor: getColor(dept.code).bg }"
                                >
                                    <p class="text-white font-semibold text-sm">{{ dept.name }}</p>
                                    <p class="text-white/70 text-xs mt-0.5">{{ dept.employee_count }} xodim</p>
                                </div>

                                <!-- Employees -->
                                <div class="bg-white dark:bg-gray-800 max-h-64 overflow-y-auto">
                                    <template v-if="dept.employees?.length > 0">
                                        <div
                                            v-for="emp in dept.employees"
                                            :key="emp.id"
                                            class="px-3 py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                                        >
                                            <p class="font-medium text-gray-900 dark:text-white text-sm">{{ emp.name }}</p>
                                            <p class="text-xs mt-0.5 font-medium" :style="{ color: getColor(dept.code).text }">
                                                {{ emp.position }}
                                            </p>
                                            <p v-if="emp.yqm" class="text-xs text-gray-600 dark:text-gray-300 mt-1.5 leading-relaxed">
                                                <span class="font-medium" :style="{ color: getColor(dept.code).text }">YQM:</span>
                                                {{ emp.yqm }}
                                            </p>
                                        </div>
                                    </template>
                                    <div v-else class="px-4 py-8 text-center">
                                        <UserPlusIcon class="w-8 h-8 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Xodim yo'q</p>
                                    </div>
                                </div>

                                <!-- Goal -->
                                <div
                                    class="px-3 py-2.5"
                                    :style="{ backgroundColor: getColor(dept.code).bg }"
                                >
                                    <p class="text-xs text-center leading-snug text-white font-medium">
                                        {{ getGoal(dept.code) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mission -->
                    <div class="mt-8 w-full max-w-3xl">
                        <div class="bg-gray-900 dark:bg-gray-800 rounded-lg px-6 py-4 text-center">
                            <p class="text-white text-sm font-medium">{{ mission }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </HRLayout>
</template>
