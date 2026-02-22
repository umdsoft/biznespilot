<template>
  <Head title="Kategoriyalar" />
  <BusinessLayout title="Kategoriyalar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Kategoriyalar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Mahsulot kategoriyalarini boshqaring</p>
        </div>
        <button
          @click="openModal(null)"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Kategoriya qo'shish
        </button>
      </div>

      <!-- Categories Tree -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="localCategories.length > 0" class="divide-y divide-slate-100 dark:divide-slate-700">
          <template v-for="(category, index) in localCategories" :key="category.id">
            <!-- Parent Category -->
            <div
              class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors group"
              draggable="true"
              @dragstart="onDragStart($event, index, null)"
              @dragover.prevent="onDragOver($event, index, null)"
              @drop.prevent="onDrop($event, index, null)"
            >
              <div class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <Bars3Icon class="w-5 h-5" />
              </div>

              <button
                v-if="category.children && category.children.length > 0"
                @click="toggleExpand(category.id)"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
              >
                <ChevronRightIcon
                  class="w-5 h-5 transition-transform"
                  :class="expanded[category.id] ? 'rotate-90' : ''"
                />
              </button>
              <div v-else class="w-5"></div>

              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                :style="{ backgroundColor: category.color || '#e2e8f0' }"
              >
                <FolderIcon class="w-4 h-4 text-white" />
              </div>

              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 dark:text-white">{{ category.name }}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  {{ category.products_count || 0 }} ta mahsulot
                  <span v-if="category.children?.length"> &middot; {{ category.children.length }} ta ichki kategoriya</span>
                </p>
              </div>

              <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  @click="openModal(null, category.id)"
                  class="p-1.5 text-slate-400 hover:text-emerald-600 transition-colors"
                  title="Ichki kategoriya qo'shish"
                >
                  <PlusIcon class="w-4 h-4" />
                </button>
                <button
                  @click="openModal(category)"
                  class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors"
                  title="Tahrirlash"
                >
                  <PencilIcon class="w-4 h-4" />
                </button>
                <button
                  @click="confirmDelete(category)"
                  class="p-1.5 text-slate-400 hover:text-red-600 transition-colors"
                  title="O'chirish"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Children -->
            <template v-if="expanded[category.id] && category.children?.length > 0">
              <div
                v-for="(child, childIndex) in category.children"
                :key="child.id"
                class="flex items-center gap-3 px-5 py-3 pl-16 bg-slate-50/50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-700/30 transition-colors group"
                draggable="true"
                @dragstart="onDragStart($event, childIndex, category.id)"
                @dragover.prevent="onDragOver($event, childIndex, category.id)"
                @drop.prevent="onDrop($event, childIndex, category.id)"
              >
                <div class="cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                  <Bars3Icon class="w-4 h-4" />
                </div>

                <div class="w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0"
                  :style="{ backgroundColor: child.color || '#cbd5e1' }"
                >
                  <FolderIcon class="w-3.5 h-3.5 text-white" />
                </div>

                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ child.name }}</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">{{ child.products_count || 0 }} ta mahsulot</p>
                </div>

                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    @click="openModal(child)"
                    class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors"
                  >
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="confirmDelete(child)"
                    class="p-1.5 text-slate-400 hover:text-red-600 transition-colors"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </template>
          </template>
        </div>

        <div v-else class="text-center py-16">
          <FolderIcon class="w-16 h-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Kategoriyalar yo'q</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Birinchi kategoriyani qo'shing</p>
          <button
            @click="openModal(null)"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
          >
            <PlusIcon class="w-4 h-4" />
            Kategoriya qo'shish
          </button>
        </div>
      </div>

      <!-- Add/Edit Modal -->
      <Modal v-model="showModal" :title="editingCategory ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya'" max-width="lg">
        <form @submit.prevent="saveCategory" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Kategoriya nomi *</label>
            <input
              v-model="categoryForm.name"
              type="text"
              placeholder="Masalan: Elektronika"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              ref="nameInput"
            />
            <p v-if="categoryForm.errors.name" class="mt-1 text-sm text-red-500">{{ categoryForm.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tavsif</label>
            <textarea
              v-model="categoryForm.description"
              rows="2"
              placeholder="Kategoriya haqida qisqacha"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Rang</label>
            <div class="flex items-center gap-3">
              <input
                v-model="categoryForm.color"
                type="color"
                class="w-10 h-10 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer"
              />
              <span class="text-sm text-slate-500">{{ categoryForm.color }}</span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showModal = false"
              class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="categoryForm.processing"
              class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <svg v-if="categoryForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ editingCategory ? 'Saqlash' : 'Qo\'shish' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Delete Confirmation Modal -->
      <Modal v-model="showDeleteModal" title="Kategoriyani o'chirish" max-width="md">
        <div class="space-y-4">
          <p class="text-sm text-slate-600 dark:text-slate-400">
            <span class="font-semibold text-slate-900 dark:text-white">{{ deletingCategory?.name }}</span>
            kategoriyasini o'chirishni xohlaysizmi?
          </p>
          <p v-if="deletingCategory?.children?.length > 0" class="text-sm text-amber-600 dark:text-amber-400">
            Diqqat: Bu kategoriya ichida {{ deletingCategory.children.length }} ta ichki kategoriya bor. Ular ham o'chiriladi.
          </p>
          <div class="flex items-center justify-end gap-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
              Bekor qilish
            </button>
            <button
              @click="deleteCategory"
              :disabled="deleting"
              class="inline-flex items-center gap-2 px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <svg v-if="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              O'chirish
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, reactive, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Modal from '@/components/Modal.vue';
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  FolderIcon,
  Bars3Icon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  categories: { type: Array, default: () => [] },
});

const localCategories = ref(JSON.parse(JSON.stringify(props.categories)));
const expanded = reactive({});
const showModal = ref(false);
const showDeleteModal = ref(false);
const editingCategory = ref(null);
const deletingCategory = ref(null);
const deleting = ref(false);
const parentId = ref(null);
const nameInput = ref(null);

// Drag state
const dragItem = ref(null);
const dragParentId = ref(null);

const categoryForm = useForm({
  name: '',
  description: '',
  color: '#10b981',
  parent_id: null,
});

const toggleExpand = (id) => {
  expanded[id] = !expanded[id];
};

const openModal = (category, forParentId = null) => {
  editingCategory.value = category;
  parentId.value = forParentId;

  if (category) {
    categoryForm.name = category.name;
    categoryForm.description = category.description || '';
    categoryForm.color = category.color || '#10b981';
    categoryForm.parent_id = category.parent_id || null;
  } else {
    categoryForm.reset();
    categoryForm.color = '#10b981';
    categoryForm.parent_id = forParentId;
  }

  showModal.value = true;
  nextTick(() => {
    nameInput.value?.focus();
  });
};

const saveCategory = () => {
  if (editingCategory.value) {
    categoryForm.put(route('business.store.categories.update', editingCategory.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
      },
    });
  } else {
    categoryForm.parent_id = parentId.value;
    categoryForm.post(route('business.store.categories.store'), {
      preserveScroll: true,
      onSuccess: () => {
        showModal.value = false;
        if (parentId.value) {
          expanded[parentId.value] = true;
        }
      },
    });
  }
};

const confirmDelete = (category) => {
  deletingCategory.value = category;
  showDeleteModal.value = true;
};

const deleteCategory = () => {
  if (!deletingCategory.value) return;
  deleting.value = true;
  router.delete(route('business.store.categories.destroy', deletingCategory.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      deletingCategory.value = null;
    },
    onFinish: () => {
      deleting.value = false;
    },
  });
};

// Drag and drop reorder
const onDragStart = (event, index, parentCategoryId) => {
  dragItem.value = index;
  dragParentId.value = parentCategoryId;
  event.dataTransfer.effectAllowed = 'move';
};

const onDragOver = (event, index, parentCategoryId) => {
  if (dragParentId.value !== parentCategoryId) return;
  event.dataTransfer.dropEffect = 'move';
};

const onDrop = (event, dropIndex, parentCategoryId) => {
  if (dragParentId.value !== parentCategoryId) return;

  const fromIndex = dragItem.value;
  if (fromIndex === dropIndex) return;

  let list;
  if (parentCategoryId === null) {
    list = localCategories.value;
  } else {
    const parent = localCategories.value.find(c => c.id === parentCategoryId);
    if (!parent) return;
    list = parent.children;
  }

  const [moved] = list.splice(fromIndex, 1);
  list.splice(dropIndex, 0, moved);

  // Save new order
  const order = list.map((item, idx) => ({ id: item.id, sort_order: idx }));
  router.post(route('business.store.categories.reorder'), { order }, {
    preserveScroll: true,
    preserveState: true,
  });

  dragItem.value = null;
  dragParentId.value = null;
};
</script>
