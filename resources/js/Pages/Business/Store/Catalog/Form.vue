<template>
  <Head :title="isEditing ? `${botConfig?.catalog_label_singular || 'Element'}ni tahrirlash` : `Yangi ${botConfig?.catalog_label_singular || 'element'}`" />
  <BusinessLayout :title="isEditing ? `${botConfig?.catalog_label_singular || 'Element'}ni tahrirlash` : `Yangi ${botConfig?.catalog_label_singular || 'element'}`">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="route('business.store.catalog.index')"
          class="inline-flex items-center text-sm text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors mb-3"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          {{ botConfig?.catalog_label || 'Katalog' }}ga qaytish
        </Link>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
          {{ isEditing ? `${botConfig?.catalog_label_singular || 'Element'}ni tahrirlash` : `Yangi ${botConfig?.catalog_label_singular || 'element'} qo'shish` }}
        </h1>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">

        <!-- Basic Info (Universal) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Asosiy ma'lumotlar</h2>

          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Nomi *</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Nomi"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tavsif</label>
              <textarea
                v-model="form.description"
                rows="4"
                placeholder="Batafsil tavsif"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              ></textarea>
            </div>

            <div v-if="categories?.length">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Kategoriya</label>
              <select
                v-model="form.category_id"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              >
                <option value="">Kategoriya tanlang</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Pricing (Universal) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Narx</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Narx (so'm) *</label>
              <input
                v-model.number="form.price"
                type="number"
                min="0"
                step="100"
                placeholder="0"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.errors.price" class="mt-1 text-sm text-red-500">{{ form.errors.price }}</p>
            </div>

            <div v-if="botConfig?.has_compare_price !== false">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Taqqoslash narxi</label>
              <input
                v-model.number="form.compare_price"
                type="number"
                min="0"
                step="100"
                placeholder="Eski narx"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
          </div>

          <div class="flex items-center gap-3 mt-5">
            <button
              type="button"
              @click="form.is_featured = !form.is_featured"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              :class="form.is_featured ? 'bg-amber-500' : 'bg-slate-300 dark:bg-slate-600'"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"
                :class="form.is_featured ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
            <span class="text-sm text-slate-700 dark:text-slate-300">Tanlangan (bosh sahifada ko'rsatiladi)</span>
          </div>
        </div>

        <!-- Type-Specific Fields -->
        <component
          v-if="typeFieldsComponent"
          :is="typeFieldsComponent"
          :form="form"
          :categories="categories"
        />

        <!-- Image Upload (Universal) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Rasm</h2>

          <div v-if="existingImages.length > 0" class="mb-5">
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
              <div
                v-for="(img, index) in existingImages"
                :key="img.id || index"
                class="relative aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600 group"
              >
                <img :src="img.url || img" :alt="'Rasm ' + (index + 1)" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="removeExistingImage(index)"
                  class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
                <div v-if="index === 0" class="absolute bottom-1 left-1">
                  <span class="text-xs bg-emerald-500 text-white px-1.5 py-0.5 rounded">Asosiy</span>
                </div>
              </div>
            </div>
          </div>

          <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
            :class="isDragging ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/10' : 'border-slate-300 dark:border-slate-600 hover:border-emerald-400'"
            @click="$refs.fileInput.click()"
          >
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*"
              class="hidden"
              @change="handleFileSelect"
            />
            <CloudArrowUpIcon class="w-12 h-12 mx-auto mb-3 text-slate-400" />
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Rasmlarni shu yerga tashlang yoki bosing</p>
            <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP - max 5MB</p>
          </div>

          <div v-if="newImagePreviews.length > 0" class="mt-4">
            <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
              <div
                v-for="(preview, index) in newImagePreviews"
                :key="'new-' + index"
                class="relative aspect-square rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600 group"
              >
                <img :src="preview" :alt="'Yangi rasm ' + (index + 1)" class="w-full h-full object-cover" />
                <button
                  type="button"
                  @click="removeNewImage(index)"
                  class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
          <Link
            :href="route('business.store.catalog.index')"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            Bekor qilish
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isEditing ? 'Saqlash' : 'Yaratish' }}
          </button>
        </div>
      </form>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  ArrowLeftIcon,
  XMarkIcon,
  CloudArrowUpIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  item: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
  images: { type: Array, default: () => [] },
  botType: { type: String, default: 'ecommerce' },
  botConfig: { type: Object, default: () => ({}) },
});

const isEditing = computed(() => !!props.item);
const isDragging = ref(false);
const existingImages = ref(props.images ? [...props.images] : []);
const newImagePreviews = ref([]);
const newImageFiles = ref([]);

// Dynamic type-specific fields component
const typeFieldsMap = {
  ecommerce: defineAsyncComponent(() => import('@/components/Store/Catalog/ProductFields.vue')),
  service: defineAsyncComponent(() => import('@/components/Store/Catalog/ServiceFields.vue')),
  delivery: defineAsyncComponent(() => import('@/components/Store/Catalog/MenuItemFields.vue')),
  course: defineAsyncComponent(() => import('@/components/Store/Catalog/CourseFields.vue')),
  fitness: defineAsyncComponent(() => import('@/components/Store/Catalog/FitnessFields.vue')),
  realestate: defineAsyncComponent(() => import('@/components/Store/Catalog/PropertyFields.vue')),
  auto: defineAsyncComponent(() => import('@/components/Store/Catalog/VehicleFields.vue')),
  event: defineAsyncComponent(() => import('@/components/Store/Catalog/EventFields.vue')),
  travel: defineAsyncComponent(() => import('@/components/Store/Catalog/TourFields.vue')),
};

const typeFieldsComponent = computed(() => typeFieldsMap[props.botType] || null);

const form = useForm({
  name: props.item?.name || '',
  description: props.item?.description || '',
  price: props.item?.price || null,
  compare_price: props.item?.compare_price || null,
  category_id: props.item?.category_id || '',
  is_featured: props.item?.is_featured || false,
  images: [],
  removed_images: [],
  // Type-specific fields will be added via extra_fields
  ...getTypeDefaults(),
});

function getTypeDefaults() {
  const item = props.item || {};
  switch (props.botType) {
    case 'ecommerce':
      return {
        sku: item.sku || '',
        stock_quantity: item.stock_quantity || 0,
        track_stock: item.track_stock ?? true,
        variants: item.variants || [],
      };
    case 'service':
      return {
        duration_minutes: item.duration_minutes || null,
        max_capacity: item.max_capacity || null,
        requires_staff: item.requires_staff || false,
      };
    case 'delivery':
      return {
        preparation_time_minutes: item.preparation_time_minutes || null,
        calories: item.calories || null,
        portion_size: item.portion_size || '',
        allergens: item.allergens || [],
        dietary_tags: item.dietary_tags || [],
      };
    case 'course':
      return {
        duration_hours: item.duration_hours || null,
        level: item.level || 'all',
        instructor: item.instructor || '',
        max_students: item.max_students || null,
        start_date: item.start_date || '',
        end_date: item.end_date || '',
        format: item.format || 'online',
        certificate_included: item.certificate_included || false,
        what_you_learn: item.what_you_learn || '',
        requirements: item.requirements || '',
      };
    case 'fitness':
      return {
        duration_minutes: item.duration_minutes || 60,
        max_participants: item.max_participants || null,
        instructor: item.instructor || '',
        difficulty: item.difficulty || 'all',
        duration_days: item.duration_days || 30,
        features: item.features || [],
      };
    case 'realestate':
      return {
        price_type: item.price_type || 'sale',
        area_sqm: item.area_sqm || null,
        rooms: item.rooms || null,
        bedrooms: item.bedrooms || null,
        bathrooms: item.bathrooms || null,
        floor: item.floor || null,
        total_floors: item.total_floors || null,
        address: item.address || '',
        district: item.district || '',
        city: item.city || '',
      };
    case 'auto':
      return {
        brand: item.brand || '',
        model: item.model || '',
        year: item.year || null,
        mileage_km: item.mileage_km || null,
        fuel_type: item.fuel_type || '',
        transmission: item.transmission || '',
        color: item.color || '',
        engine_volume: item.engine_volume || null,
        condition: item.condition || 'used',
      };
    case 'event':
      return {
        venue: item.venue || '',
        address: item.address || '',
        start_date: item.start_date || '',
        end_date: item.end_date || '',
        total_seats: item.total_seats || null,
      };
    case 'travel':
      return {
        duration_days: item.duration_days || 1,
        destination: item.destination || '',
        departure_city: item.departure_city || '',
        start_date: item.start_date || '',
        end_date: item.end_date || '',
        max_travelers: item.max_travelers || null,
        difficulty: item.difficulty || 'easy',
      };
    default:
      return {};
  }
}

const handleFileSelect = (event) => {
  processFiles(Array.from(event.target.files));
};

const handleDrop = (event) => {
  isDragging.value = false;
  processFiles(Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/')));
};

const processFiles = (files) => {
  files.forEach(file => {
    if (file.size > 5 * 1024 * 1024) return;
    newImageFiles.value.push(file);
    const reader = new FileReader();
    reader.onload = (e) => newImagePreviews.value.push(e.target.result);
    reader.readAsDataURL(file);
  });
};

const removeExistingImage = (index) => {
  const removed = existingImages.value.splice(index, 1);
  if (removed[0]?.id) form.removed_images.push(removed[0].id);
};

const removeNewImage = (index) => {
  newImagePreviews.value.splice(index, 1);
  newImageFiles.value.splice(index, 1);
};

const submitForm = () => {
  const formData = new FormData();

  // Universal fields
  formData.append('name', form.name);
  formData.append('description', form.description || '');
  formData.append('price', form.price || 0);
  formData.append('compare_price', form.compare_price || '');
  formData.append('category_id', form.category_id || '');
  formData.append('is_featured', form.is_featured ? '1' : '0');

  // Type-specific fields
  const typeDefaults = getTypeDefaults();
  Object.keys(typeDefaults).forEach(key => {
    const val = form[key];
    if (val !== undefined && val !== null) {
      if (Array.isArray(val) || typeof val === 'object') {
        formData.append(key, JSON.stringify(val));
      } else if (typeof val === 'boolean') {
        formData.append(key, val ? '1' : '0');
      } else {
        formData.append(key, val);
      }
    }
  });

  // Images
  form.removed_images.forEach((id, i) => formData.append(`removed_images[${i}]`, id));
  newImageFiles.value.forEach((file, i) => formData.append(`images[${i}]`, file));

  if (isEditing.value) {
    formData.append('_method', 'PUT');
    form.post(route('business.store.catalog.update', props.item.id), {
      data: formData,
      forceFormData: true,
    });
  } else {
    form.post(route('business.store.catalog.store'), {
      data: formData,
      forceFormData: true,
    });
  }
};
</script>
