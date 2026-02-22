<template>
  <Head title="Mahsulotlar" />
  <BusinessLayout title="Mahsulotlar">
    <div class="space-y-6">

      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Mahsulotlar</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Jami: {{ products?.total || 0 }} ta mahsulot
          </p>
        </div>
        <Link
          :href="route('business.store.products.create')"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Yangi mahsulot
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
              <input
                v-model="search"
                type="text"
                placeholder="Mahsulot qidirish..."
                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                @input="debouncedSearch"
              />
            </div>
          </div>

          <!-- Category Filter -->
          <div class="w-full sm:w-48">
            <select
              v-model="selectedCategory"
              @change="applyFilters"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            >
              <option value="">Barcha kategoriyalar</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>

          <!-- Status Filter -->
          <div class="w-full sm:w-40">
            <select
              v-model="selectedStatus"
              @change="applyFilters"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            >
              <option value="">Barchasi</option>
              <option value="active">Faol</option>
              <option value="inactive">Nofaol</option>
            </select>
          </div>

          <!-- View Toggle -->
          <div class="flex items-center rounded-lg border border-slate-300 dark:border-slate-600 overflow-hidden">
            <button
              @click="viewMode = 'grid'"
              class="p-2.5 transition-colors"
              :class="viewMode === 'grid' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-white dark:bg-slate-700 text-slate-400 hover:text-slate-600'"
            >
              <Squares2X2Icon class="w-5 h-5" />
            </button>
            <button
              @click="viewMode = 'table'"
              class="p-2.5 transition-colors border-l border-slate-300 dark:border-slate-600"
              :class="viewMode === 'table' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600' : 'bg-white dark:bg-slate-700 text-slate-400 hover:text-slate-600'"
            >
              <ListBulletIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Grid View -->
      <div v-if="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        <div
          v-for="product in products.data"
          :key="product.id"
          class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-shadow group"
        >
          <!-- Image -->
          <Link :href="route('business.store.products.edit', product.id)" class="block">
            <div class="aspect-square bg-slate-100 dark:bg-slate-700 relative overflow-hidden">
              <img
                v-if="product.primary_image"
                :src="product.primary_image"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <PhotoIcon class="w-12 h-12 text-slate-300 dark:text-slate-600" />
              </div>
              <!-- Featured Badge -->
              <div v-if="product.is_featured" class="absolute top-2 left-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                  <StarIcon class="w-3 h-3 mr-1" />
                  Top
                </span>
              </div>
            </div>
          </Link>

          <!-- Info -->
          <div class="p-3">
            <Link :href="route('business.store.products.edit', product.id)">
              <h3 class="text-sm font-medium text-slate-900 dark:text-white truncate hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
                {{ product.name }}
              </h3>
            </Link>
            <div class="flex items-center justify-between mt-2">
              <div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ formatPrice(product.price) }}</p>
                <p v-if="product.compare_price" class="text-xs text-slate-400 line-through">{{ formatPrice(product.compare_price) }}</p>
              </div>
              <span class="text-xs text-slate-500 dark:text-slate-400">
                {{ product.stock_quantity != null ? product.stock_quantity + ' dona' : '' }}
              </span>
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
              <span class="text-xs" :class="product.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'">
                {{ product.is_active ? 'Faol' : 'Nofaol' }}
              </span>
              <button
                @click.prevent="toggleActive(product)"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                :class="product.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
              >
                <span
                  class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow-sm"
                  :class="product.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Table View -->
      <div v-else class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-700/50">
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Mahsulot</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Kategoriya</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Narx</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Zaxira</th>
                <th class="px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Holat</th>
                <th class="px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Amallar</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
              <tr
                v-for="product in products.data"
                :key="product.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
              >
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100 dark:bg-slate-700">
                      <img v-if="product.primary_image" :src="product.primary_image" :alt="product.name" class="w-full h-full object-cover" />
                      <div v-else class="w-full h-full flex items-center justify-center">
                        <PhotoIcon class="w-5 h-5 text-slate-400" />
                      </div>
                    </div>
                    <div class="min-w-0">
                      <Link :href="route('business.store.products.edit', product.id)" class="text-sm font-medium text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 truncate block">
                        {{ product.name }}
                      </Link>
                      <p v-if="product.sku" class="text-xs text-slate-400">SKU: {{ product.sku }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm text-slate-600 dark:text-slate-400">{{ product.category?.name || '-' }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ formatPrice(product.price) }}</span>
                  <span v-if="product.compare_price" class="block text-xs text-slate-400 line-through">{{ formatPrice(product.compare_price) }}</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <span v-if="product.track_stock" class="text-sm" :class="product.stock_quantity <= 0 ? 'text-red-500 font-medium' : product.stock_quantity <= 5 ? 'text-amber-500' : 'text-slate-600 dark:text-slate-400'">
                    {{ product.stock_quantity }} dona
                  </span>
                  <span v-else class="text-sm text-slate-400">-</span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap">
                  <button
                    @click="toggleActive(product)"
                    class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                    :class="product.is_active ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-600'"
                  >
                    <span
                      class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform shadow-sm"
                      :class="product.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                    />
                  </button>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right">
                  <Link
                    :href="route('business.store.products.edit', product.id)"
                    class="text-sm text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 font-medium"
                  >
                    Tahrirlash
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="products.data && products.data.length === 0" class="text-center py-16">
        <CubeIcon class="w-16 h-16 mx-auto mb-4 text-slate-300 dark:text-slate-600" />
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Mahsulotlar yo'q</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Birinchi mahsulotingizni qo'shing</p>
        <Link
          :href="route('business.store.products.create')"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors"
        >
          <PlusIcon class="w-4 h-4" />
          Mahsulot qo'shish
        </Link>
      </div>

      <!-- Pagination -->
      <Pagination
        v-if="products.links && products.links.length > 3"
        :links="products.links"
        :from="products.from"
        :to="products.to"
        :total="products.total"
      />
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Pagination from '@/components/Pagination.vue';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  Squares2X2Icon,
  ListBulletIcon,
  PhotoIcon,
  StarIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  products: { type: Object, default: () => ({ data: [], links: [] }) },
  categories: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const selectedStatus = ref(props.filters?.status || '');
const viewMode = ref('grid');

let searchTimeout = null;

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 400);
};

const applyFilters = () => {
  router.get(route('business.store.products.index'), {
    search: search.value || undefined,
    category: selectedCategory.value || undefined,
    status: selectedStatus.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const toggleActive = (product) => {
  router.put(route('business.store.products.toggle-active', product.id), {}, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>
