<template>
  <Head :title="isEditing ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot'" />
  <BusinessLayout :title="isEditing ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot'">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

      <!-- Header -->
      <div class="mb-6">
        <Link
          :href="route('business.store.products.index')"
          class="inline-flex items-center text-sm text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-400 transition-colors mb-3"
        >
          <ArrowLeftIcon class="w-4 h-4 mr-2" />
          Mahsulotlarga qaytish
        </Link>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
          {{ isEditing ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot qo\'shish' }}
        </h1>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">

        <!-- Basic Info -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Asosiy ma'lumotlar</h2>

          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Mahsulot nomi *</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Masalan: Premium poyabzal"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tavsif</label>
              <textarea
                v-model="form.description"
                rows="4"
                placeholder="Mahsulot haqida batafsil ma'lumot"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              ></textarea>
              <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Kategoriya</label>
              <select
                v-model="form.category_id"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              >
                <option value="">Kategoriya tanlang</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
              <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-500">{{ form.errors.category_id }}</p>
            </div>
          </div>
        </div>

        <!-- Pricing -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Narx va zaxira</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Narx (so'm) *</label>
              <input
                :value="formatPrice(form.price)"
                @input="onPriceInput($event, 'price')"
                type="text"
                inputmode="numeric"
                placeholder="0"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.errors.price" class="mt-1 text-sm text-red-500">{{ form.errors.price }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Taqqoslash narxi (so'm)</label>
              <input
                :value="formatPrice(form.compare_price)"
                @input="onPriceInput($event, 'compare_price')"
                type="text"
                inputmode="numeric"
                placeholder="Eski narx"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.compare_price && form.compare_price <= form.price" class="mt-1 text-sm text-amber-500">
                Taqqoslash narxi asosiy narxdan katta bo'lishi kerak
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">SKU (artikul)</label>
              <input
                v-model="form.sku"
                type="text"
                placeholder="SKU-001"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="form.errors.sku" class="mt-1 text-sm text-red-500">{{ form.errors.sku }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Zaxira miqdori</label>
              <input
                v-model.number="form.stock_quantity"
                type="number"
                min="0"
                placeholder="0"
                :disabled="!form.track_stock"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              />
              <p v-if="form.errors.stock_quantity" class="mt-1 text-sm text-red-500">{{ form.errors.stock_quantity }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3 mt-5">
            <button
              type="button"
              @click="form.track_stock = !form.track_stock"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
              :class="form.track_stock ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
            >
              <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"
                :class="form.track_stock ? 'translate-x-6' : 'translate-x-1'"
              />
            </button>
            <span class="text-sm text-slate-700 dark:text-slate-300">Zaxirani kuzatish</span>
          </div>

          <div class="flex items-center gap-3 mt-4">
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
            <span class="text-sm text-slate-700 dark:text-slate-300">Tanlangan mahsulot (bosh sahifada ko'rsatiladi)</span>
          </div>
        </div>

        <!-- Images -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-5">Rasmlar</h2>

          <!-- Existing images -->
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

          <!-- Upload zone -->
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
            <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
              Rasmlarni shu yerga tashlang yoki bosing
            </p>
            <p class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP - max 5MB</p>
          </div>

          <!-- Preview new images -->
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
          <p v-if="form.errors.images" class="mt-2 text-sm text-red-500">{{ form.errors.images }}</p>
        </div>

        <!-- Variants -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Variantlar</h2>
            <button
              type="button"
              @click="addVariant"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors"
            >
              <PlusIcon class="w-4 h-4" />
              Variant qo'shish
            </button>
          </div>

          <div v-if="form.variants.length === 0" class="text-center py-8 text-slate-400 dark:text-slate-500">
            <CubeIcon class="w-10 h-10 mx-auto mb-2" />
            <p class="text-sm">Variantlar yo'q. Agar mahsulotning turli o'lcham yoki ranglari bo'lsa, variant qo'shing.</p>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="(variant, index) in form.variants"
              :key="index"
              class="p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600"
            >
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300">Variant #{{ index + 1 }}</h4>
                <button
                  type="button"
                  @click="removeVariant(index)"
                  class="text-red-500 hover:text-red-600 transition-colors"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nomi *</label>
                  <input
                    v-model="variant.name"
                    type="text"
                    placeholder="Masalan: Katta o'lcham"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Narx (so'm)</label>
                  <input
                    :value="formatPrice(variant.price)"
                    @input="onVariantPriceInput($event, index)"
                    type="text"
                    inputmode="numeric"
                    placeholder="0"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Zaxira</label>
                  <input
                    v-model.number="variant.stock_quantity"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Atributlar</label>
                  <input
                    v-model="variant.attributes"
                    type="text"
                    placeholder="rang: qora, o'lcham: XL"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 transition-colors"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-3">
          <Link
            :href="route('business.store.products.index')"
            class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
          >
            Bekor qilish
          </Link>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
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
import { ref, computed, nextTick } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
  ArrowLeftIcon,
  PlusIcon,
  XMarkIcon,
  TrashIcon,
  CloudArrowUpIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  product: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
});

const isEditing = computed(() => !!props.product);
const isDragging = ref(false);
const isSubmitting = ref(false);
const existingImages = ref(
  props.product?.images
    ? props.product.images.map(img => ({ id: img.id, url: img.image_url, is_primary: img.is_primary }))
    : []
);
const newImagePreviews = ref([]);
const newImageFiles = ref([]);
const removedImageIds = ref([]);

const form = useForm({
  name: props.product?.name || '',
  description: props.product?.description || '',
  price: props.product?.price || null,
  compare_price: props.product?.compare_price || null,
  sku: props.product?.sku || '',
  stock_quantity: props.product?.stock_quantity || 0,
  track_stock: props.product?.track_stock ?? true,
  category_id: props.product?.category_id || '',
  is_featured: props.product?.is_featured || false,
  variants: props.product?.variants || [],
});

const formatPrice = (value) => {
  if (value === null || value === undefined || value === '') return '';
  return String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
};

const onPriceInput = (event, field) => {
  const input = event.target;
  const cursorPos = input.selectionStart;
  const oldLen = input.value.length;
  const raw = input.value.replace(/\s/g, '').replace(/\D/g, '');
  form[field] = raw ? parseInt(raw, 10) : null;
  nextTick(() => {
    const newLen = input.value.length;
    const newPos = Math.max(0, cursorPos + (newLen - oldLen));
    input.setSelectionRange(newPos, newPos);
  });
};

const onVariantPriceInput = (event, index) => {
  const input = event.target;
  const cursorPos = input.selectionStart;
  const oldLen = input.value.length;
  const raw = input.value.replace(/\s/g, '').replace(/\D/g, '');
  form.variants[index].price = raw ? parseInt(raw, 10) : null;
  nextTick(() => {
    const newLen = input.value.length;
    const newPos = Math.max(0, cursorPos + (newLen - oldLen));
    input.setSelectionRange(newPos, newPos);
  });
};

const addVariant = () => {
  form.variants.push({
    name: '',
    price: null,
    stock_quantity: 0,
    sku: '',
    attributes: null,
  });
};

const removeVariant = (index) => {
  form.variants.splice(index, 1);
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  processFiles(files);
};

const handleDrop = (event) => {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files).filter(f => f.type.startsWith('image/'));
  processFiles(files);
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
  if (removed[0]?.id) removedImageIds.value.push(removed[0].id);
};

const removeNewImage = (index) => {
  newImagePreviews.value.splice(index, 1);
  newImageFiles.value.splice(index, 1);
};

const buildFormData = () => {
  const fd = new FormData();
  if (isEditing.value) fd.append('_method', 'PUT');

  const data = form.data();
  for (const [key, val] of Object.entries(data)) {
    if (val === null || val === undefined || val === '') continue;
    if (key === 'variants') {
      if (Array.isArray(val) && val.length) fd.append('variants', JSON.stringify(val));
    } else if (Array.isArray(val)) {
      fd.append(key, JSON.stringify(val));
    } else if (typeof val === 'boolean') {
      fd.append(key, val ? '1' : '0');
    } else {
      fd.append(key, val);
    }
  }

  newImageFiles.value.forEach(file => fd.append('images[]', file));
  removedImageIds.value.forEach(id => fd.append('removed_images[]', id));

  return fd;
};

const submitForm = () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;

  const url = isEditing.value
    ? route('business.store.products.update', props.product.id)
    : route('business.store.products.store');

  router.post(url, buildFormData(), {
    onError: (errors) => {
      form.clearErrors();
      for (const [k, v] of Object.entries(errors)) form.setError(k, v);
    },
    onFinish: () => { isSubmitting.value = false; },
  });
};
</script>
