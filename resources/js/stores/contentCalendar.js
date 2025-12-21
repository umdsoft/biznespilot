import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export const useContentCalendarStore = defineStore('contentCalendar', () => {
    // State
    const loading = ref(false);
    const error = ref(null);
    const items = ref([]);
    const currentView = ref('month'); // month, week, day
    const currentDate = ref(new Date().toISOString().split('T')[0]);
    const selectedChannel = ref(null);
    const selectedItem = ref(null);
    const showModal = ref(false);
    const modalMode = ref('create'); // create, edit, view

    // Drag and drop state
    const draggedItem = ref(null);

    // Computed
    const groupedByDate = computed(() => {
        const grouped = {};
        items.value.forEach(item => {
            const date = item.scheduled_date;
            if (!grouped[date]) {
                grouped[date] = [];
            }
            grouped[date].push(item);
        });
        return grouped;
    });

    const todaysItems = computed(() => {
        const today = new Date().toISOString().split('T')[0];
        return items.value.filter(item => item.scheduled_date === today);
    });

    const upcomingItems = computed(() => {
        const today = new Date().toISOString().split('T')[0];
        return items.value
            .filter(item => item.scheduled_date >= today && ['approved', 'scheduled'].includes(item.status))
            .sort((a, b) => new Date(a.scheduled_date) - new Date(b.scheduled_date))
            .slice(0, 7);
    });

    const itemsByStatus = computed(() => {
        const byStatus = {};
        items.value.forEach(item => {
            if (!byStatus[item.status]) {
                byStatus[item.status] = [];
            }
            byStatus[item.status].push(item);
        });
        return byStatus;
    });

    // Actions
    function fetchCalendar(params = {}) {
        loading.value = true;

        const query = {
            view: params.view || currentView.value,
            date: params.date || currentDate.value,
            channel: params.channel || selectedChannel.value,
        };

        router.get('/business/content-calendar', query, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                loading.value = false;
            },
            onError: (errors) => {
                error.value = errors;
                loading.value = false;
            },
        });
    }

    function setView(view) {
        currentView.value = view;
        fetchCalendar({ view });
    }

    function setDate(date) {
        currentDate.value = date;
        fetchCalendar({ date });
    }

    function setChannel(channel) {
        selectedChannel.value = channel;
        fetchCalendar({ channel });
    }

    function navigateDate(direction) {
        const current = new Date(currentDate.value);

        if (currentView.value === 'month') {
            current.setMonth(current.getMonth() + direction);
        } else if (currentView.value === 'week') {
            current.setDate(current.getDate() + (direction * 7));
        } else {
            current.setDate(current.getDate() + direction);
        }

        setDate(current.toISOString().split('T')[0]);
    }

    async function createContent(data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post('/business/content-calendar', data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    closeModal();
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function updateContent(id, data) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.put(`/business/content-calendar/${id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    closeModal();
                    resolve(true);
                },
                onError: (errors) => {
                    error.value = errors;
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function deleteContent(id) {
        if (!confirm('Haqiqatan ham o\'chirmoqchimisiz?')) return;

        return new Promise((resolve, reject) => {
            router.delete(`/business/content-calendar/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                    resolve(true);
                },
                onError: (errors) => reject(errors),
            });
        });
    }

    async function moveContent(id, newDate, newTime = null) {
        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/move`, {
                date: newDate,
                time: newTime
            }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function duplicateContent(id, newDate = null) {
        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/duplicate`, { date: newDate }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function approveContent(id) {
        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/approve`, {}, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function scheduleContent(id) {
        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/schedule`, {}, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function publishContent(id, externalId = null, postUrl = null) {
        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/publish`, {
                external_id: externalId,
                post_url: postUrl
            }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function updateMetrics(id, metrics) {
        return new Promise((resolve, reject) => {
            router.put(`/business/content-calendar/${id}/metrics`, metrics, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function generateAIContent(id) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/${id}/generate-ai`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function bulkUpdateStatus(ids, status) {
        return new Promise((resolve, reject) => {
            router.post('/business/content-calendar/bulk-status', { ids, status }, {
                preserveScroll: true,
                onSuccess: () => resolve(true),
                onError: (errors) => reject(errors),
            });
        });
    }

    async function generateFromMonthly(monthlyPlanId) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/generate-monthly/${monthlyPlanId}`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    async function generateFromWeekly(weeklyPlanId) {
        loading.value = true;

        return new Promise((resolve, reject) => {
            router.post(`/business/content-calendar/generate-weekly/${weeklyPlanId}`, {}, {
                onSuccess: () => {
                    loading.value = false;
                    resolve(true);
                },
                onError: (errors) => {
                    loading.value = false;
                    reject(errors);
                },
            });
        });
    }

    // Modal actions
    function openCreateModal(date = null) {
        selectedItem.value = date ? { scheduled_date: date } : null;
        modalMode.value = 'create';
        showModal.value = true;
    }

    function openEditModal(item) {
        selectedItem.value = item;
        modalMode.value = 'edit';
        showModal.value = true;
    }

    function openViewModal(item) {
        selectedItem.value = item;
        modalMode.value = 'view';
        showModal.value = true;
    }

    function closeModal() {
        showModal.value = false;
        selectedItem.value = null;
        modalMode.value = 'create';
    }

    // Drag and drop
    function startDrag(item) {
        draggedItem.value = item;
    }

    function endDrag() {
        draggedItem.value = null;
    }

    async function dropOnDate(date) {
        if (draggedItem.value && draggedItem.value.scheduled_date !== date) {
            await moveContent(draggedItem.value.id, date);
        }
        endDrag();
    }

    // Set items from page props
    function setItems(data) {
        items.value = data || [];
    }

    return {
        // State
        loading,
        error,
        items,
        currentView,
        currentDate,
        selectedChannel,
        selectedItem,
        showModal,
        modalMode,
        draggedItem,

        // Computed
        groupedByDate,
        todaysItems,
        upcomingItems,
        itemsByStatus,

        // Actions
        fetchCalendar,
        setView,
        setDate,
        setChannel,
        navigateDate,
        createContent,
        updateContent,
        deleteContent,
        moveContent,
        duplicateContent,
        approveContent,
        scheduleContent,
        publishContent,
        updateMetrics,
        generateAIContent,
        bulkUpdateStatus,
        generateFromMonthly,
        generateFromWeekly,
        openCreateModal,
        openEditModal,
        openViewModal,
        closeModal,
        startDrag,
        endDrag,
        dropOnDate,
        setItems,
    };
});
