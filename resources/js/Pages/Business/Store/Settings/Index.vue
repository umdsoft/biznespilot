<template>
  <Head title="Do'kon sozlamalari" />
  <BusinessLayout title="Do'kon sozlamalari">
    <div class="space-y-6">

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Do'kon sozlamalari</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Do'koningiz parametrlarini boshqaring</p>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700">
          <nav class="flex -mb-px overflow-x-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-5 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
              :class="activeTab === tab.id
                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hover:border-slate-300'"
            >
              <component :is="tab.icon" class="w-4 h-4 inline mr-1.5 -mt-0.5" />
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- General Tab -->
        <div v-if="activeTab === 'general'" class="p-5 sm:p-6">
          <form @submit.prevent="saveGeneral" class="space-y-5 max-w-2xl">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Do'kon nomi *</label>
              <input
                v-model="generalForm.name"
                type="text"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="generalForm.errors.name" class="mt-1 text-sm text-red-500">{{ generalForm.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Tavsif</label>
              <textarea
                v-model="generalForm.description"
                rows="3"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Telefon</label>
                <input
                  v-model="generalForm.phone"
                  type="text"
                  placeholder="+998 90 123 45 67"
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Manzil</label>
                <input
                  v-model="generalForm.address"
                  type="text"
                  class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
                />
              </div>
            </div>

            <!-- Logo Upload -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Logotip</label>
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-xl bg-slate-100 dark:bg-slate-700 overflow-hidden flex items-center justify-center border border-slate-200 dark:border-slate-600">
                  <img v-if="logoPreview || store?.logo" :src="logoPreview || store?.logo" alt="Logo" class="w-full h-full object-cover" />
                  <PhotoIcon v-else class="w-8 h-8 text-slate-400" />
                </div>
                <div>
                  <button
                    type="button"
                    @click="$refs.logoInput.click()"
                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                  >
                    {{ store?.logo ? "O'zgartirish" : 'Yuklash' }}
                  </button>
                  <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="handleLogoUpload" />
                  <p class="text-xs text-slate-400 mt-0.5">PNG, JPG - max 2MB</p>
                </div>
              </div>
            </div>

            <!-- Banner Upload -->
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Banner rasm</label>
              <div class="w-full h-32 rounded-xl bg-slate-100 dark:bg-slate-700 overflow-hidden border border-slate-200 dark:border-slate-600 relative group cursor-pointer"
                @click="$refs.bannerInput.click()"
              >
                <img v-if="bannerPreview || store?.banner" :src="bannerPreview || store?.banner" alt="Banner" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400">
                  <PhotoIcon class="w-8 h-8 mb-1" />
                  <span class="text-xs">Banner yuklash (1200x400)</span>
                </div>
                <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                  <span class="text-white text-sm font-medium">{{ store?.banner ? "O'zgartirish" : 'Yuklash' }}</span>
                </div>
              </div>
              <input ref="bannerInput" type="file" accept="image/*" class="hidden" @change="handleBannerUpload" />
            </div>

            <!-- Mini App QR Code -->
            <div v-if="store?.mini_app_url" class="p-5 bg-slate-50 dark:bg-slate-700/40 border border-slate-200 dark:border-slate-600 rounded-xl">
              <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                <QrCodeIcon class="w-4 h-4" />
                Telegram Mini App QR Kod
              </h4>
              <div class="flex flex-col sm:flex-row items-start gap-6">
                <!-- QR image -->
                <div class="flex-shrink-0">
                  <div class="w-44 h-44 bg-white rounded-xl p-2.5 shadow-sm border border-slate-200 dark:border-slate-600 flex items-center justify-center">
                    <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR Kod" class="w-full h-full object-contain" />
                    <div v-else class="animate-spin w-6 h-6 border-2 border-emerald-500 border-t-transparent rounded-full"></div>
                  </div>
                </div>

                <div class="flex-1 min-w-0 space-y-3">
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    QR kodni skaner qilib do'konga kirish mumkin. PNG yoki PDF sifatida yuklab chop eting.
                  </p>

                  <!-- URL copy -->
                  <div class="flex items-center gap-2">
                    <input
                      :value="store.mini_app_url"
                      readonly
                      class="flex-1 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-3 py-2 text-xs text-slate-700 dark:text-slate-300 font-mono min-w-0"
                    />
                    <button
                      type="button"
                      @click="copyLink"
                      class="flex-shrink-0 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-600 transition-colors"
                    >
                      {{ copied ? '✓ Nusxalandi' : 'Nusxalash' }}
                    </button>
                  </div>

                  <!-- Download buttons -->
                  <div class="flex items-center gap-2 flex-wrap">
                    <button
                      type="button"
                      @click="downloadPng"
                      :disabled="!qrDataUrl"
                      class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4" />
                      PNG yuklash
                    </button>
                    <button
                      type="button"
                      @click="printQr"
                      :disabled="!qrDataUrl"
                      class="inline-flex items-center gap-1.5 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600 text-sm font-medium rounded-lg transition-colors disabled:opacity-40"
                    >
                      <PrinterIcon class="w-4 h-4" />
                      PDF / Chop etish
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="pt-3">
              <button
                type="submit"
                :disabled="generalForm.processing"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
              >
                <svg v-if="generalForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saqlash
              </button>
            </div>
          </form>
        </div>

        <!-- Theme Tab -->
        <div v-if="activeTab === 'theme'" class="p-5 sm:p-6">
          <form @submit.prevent="saveTheme" class="space-y-5 max-w-2xl">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Asosiy rang</label>
              <div class="flex items-center gap-4">
                <input
                  v-model="themeForm.primary_color"
                  type="color"
                  class="w-12 h-12 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer"
                />
                <div class="flex-1">
                  <input
                    v-model="themeForm.primary_color"
                    type="text"
                    placeholder="#10b981"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors font-mono"
                  />
                </div>
              </div>
              <!-- Preset colors -->
              <div class="flex items-center gap-2 mt-3">
                <span class="text-xs text-slate-500 dark:text-slate-400">Tezkor:</span>
                <button
                  v-for="color in presetColors"
                  :key="color"
                  type="button"
                  @click="themeForm.primary_color = color"
                  class="w-7 h-7 rounded-full border-2 transition-transform hover:scale-110"
                  :class="themeForm.primary_color === color ? 'border-slate-900 dark:border-white scale-110' : 'border-transparent'"
                  :style="{ backgroundColor: color }"
                ></button>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Ikkinchi rang</label>
              <div class="flex items-center gap-4">
                <input
                  v-model="themeForm.secondary_color"
                  type="color"
                  class="w-12 h-12 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer"
                />
                <div class="flex-1">
                  <input
                    v-model="themeForm.secondary_color"
                    type="text"
                    placeholder="#6366f1"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors font-mono"
                  />
                </div>
              </div>
            </div>

            <!-- Preview -->
            <div class="p-4 rounded-xl border border-slate-200 dark:border-slate-600">
              <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Oldindan ko'rish</h4>
              <div class="flex gap-3">
                <button
                  class="px-4 py-2 text-white text-sm font-medium rounded-lg"
                  :style="{ backgroundColor: themeForm.primary_color }"
                >
                  Asosiy tugma
                </button>
                <button
                  class="px-4 py-2 text-white text-sm font-medium rounded-lg"
                  :style="{ backgroundColor: themeForm.secondary_color }"
                >
                  Ikkinchi tugma
                </button>
              </div>
            </div>

            <div class="pt-3">
              <button
                type="submit"
                :disabled="themeForm.processing"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
              >
                <svg v-if="themeForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saqlash
              </button>
            </div>
          </form>
        </div>

        <!-- Delivery Tab -->
        <div v-if="activeTab === 'delivery'" class="p-5 sm:p-6">
          <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-5">
              <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Yetkazish zonalari</h3>
              <button
                @click="openDeliveryModal(null)"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg transition-colors"
              >
                <PlusIcon class="w-4 h-4" />
                Zona qo'shish
              </button>
            </div>

            <div v-if="localDeliveryZones.length > 0" class="space-y-3">
              <div
                v-for="zone in localDeliveryZones"
                :key="zone.id"
                class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-900 dark:text-white">{{ zone.name }}</p>
                  <div class="flex items-center gap-4 mt-1 text-xs text-slate-500 dark:text-slate-400">
                    <span>Narx: {{ formatPrice(zone.fee) }}</span>
                    <span v-if="zone.min_amount">Min: {{ formatPrice(zone.min_amount) }}</span>
                    <span v-if="zone.delivery_time">{{ zone.delivery_time }}</span>
                  </div>
                </div>
                <div class="flex items-center gap-2 ml-4">
                  <button
                    @click="openDeliveryModal(zone)"
                    class="p-1.5 text-slate-400 hover:text-blue-600 transition-colors"
                  >
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteDeliveryZone(zone)"
                    class="p-1.5 text-slate-400 hover:text-red-600 transition-colors"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <TruckIcon class="w-12 h-12 mx-auto mb-3 text-slate-300 dark:text-slate-600" />
              <p class="text-sm text-slate-500 dark:text-slate-400">Yetkazish zonalari yo'q</p>
              <button
                @click="openDeliveryModal(null)"
                class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors"
              >
                <PlusIcon class="w-4 h-4" />
                Zona qo'shish
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery Zone Modal -->
      <Modal v-model="showDeliveryModal" :title="editingZone ? 'Zonani tahrirlash' : 'Yangi yetkazish zonasi'" max-width="lg">
        <form @submit.prevent="saveDeliveryZone" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Zona nomi *</label>
            <input
              v-model="deliveryForm.name"
              type="text"
              placeholder="Masalan: Toshkent shahri"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
            <p v-if="deliveryForm.errors.name" class="mt-1 text-sm text-red-500">{{ deliveryForm.errors.name }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Yetkazish narxi (so'm) *</label>
              <input
                v-model.number="deliveryForm.fee"
                type="number"
                min="0"
                step="1000"
                placeholder="0"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
              <p v-if="deliveryForm.errors.fee" class="mt-1 text-sm text-red-500">{{ deliveryForm.errors.fee }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Min. buyurtma (so'm)</label>
              <input
                v-model.number="deliveryForm.min_amount"
                type="number"
                min="0"
                step="1000"
                placeholder="Cheklanmagan"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Yetkazish vaqti</label>
            <input
              v-model="deliveryForm.delivery_time"
              type="text"
              placeholder="Masalan: 30-60 daqiqa"
              class="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 px-4 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-colors"
            />
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              @click="showDeliveryModal = false"
              class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors"
            >
              Bekor qilish
            </button>
            <button
              type="submit"
              :disabled="deliveryForm.processing"
              class="inline-flex items-center gap-2 px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              <svg v-if="deliveryForm.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ editingZone ? 'Saqlash' : "Qo'shish" }}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Modal from '@/components/Modal.vue';
import QRCode from 'qrcode';
import {
  Cog6ToothIcon,
  SwatchIcon,
  TruckIcon,
  PhotoIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  QrCodeIcon,
  ArrowDownTrayIcon,
  PrinterIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  store: { type: Object, default: null },
  deliveryZones: { type: Array, default: () => [] },
});

const activeTab = ref('general');
const copied = ref(false);
const qrDataUrl = ref(null);
const logoPreview = ref(null);
const bannerPreview = ref(null);
const showDeliveryModal = ref(false);
const editingZone = ref(null);
const localDeliveryZones = ref(props.deliveryZones ? [...props.deliveryZones] : []);

const tabs = [
  { id: 'general', label: 'Umumiy', icon: Cog6ToothIcon },
  { id: 'theme', label: 'Dizayn', icon: SwatchIcon },
  { id: 'delivery', label: 'Yetkazish', icon: TruckIcon },
];

const presetColors = [
  '#10b981', '#3b82f6', '#6366f1', '#8b5cf6',
  '#ec4899', '#f43f5e', '#f97316', '#eab308',
];

const generalForm = useForm({
  name: props.store?.name || '',
  description: props.store?.description || '',
  phone: props.store?.phone || '',
  address: props.store?.address || '',
  logo: null,
  banner: null,
});

const themeForm = useForm({
  primary_color: props.store?.primary_color || '#10b981',
  secondary_color: props.store?.secondary_color || '#6366f1',
});

const deliveryForm = useForm({
  name: '',
  fee: null,
  min_amount: null,
  delivery_time: '',
});

const formatPrice = (value) => {
  if (!value && value !== 0) return "0 so'm";
  return new Intl.NumberFormat('uz-UZ').format(value) + " so'm";
};

const handleLogoUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) return;
  generalForm.logo = file;
  const reader = new FileReader();
  reader.onload = (e) => { logoPreview.value = e.target.result; };
  reader.readAsDataURL(file);
};

const handleBannerUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  if (file.size > 5 * 1024 * 1024) return;
  generalForm.banner = file;
  const reader = new FileReader();
  reader.onload = (e) => { bannerPreview.value = e.target.result; };
  reader.readAsDataURL(file);
};

const copyLink = () => {
  if (props.store?.mini_app_url) {
    navigator.clipboard.writeText(props.store.mini_app_url);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  }
};

const downloadPng = () => {
  if (!qrDataUrl.value) return;
  const a = document.createElement('a');
  a.href = qrDataUrl.value;
  a.download = `${props.store?.name || 'qr-kod'}.png`;
  a.click();
};

const printQr = () => {
  if (!qrDataUrl.value) return;
  const storeName = props.store?.name || "Do'kon";
  const url = props.store?.mini_app_url || '';
  const win = window.open('', '_blank');
  win.document.write(`<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <title>QR Kod — ${storeName}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #fff; color: #111; }
    .page { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 40px; text-align: center; }
    .store-name { font-size: 28px; font-weight: 700; margin-bottom: 24px; letter-spacing: -0.5px; }
    .qr-wrap { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; display: inline-block; }
    .qr-wrap img { width: 400px; height: 400px; display: block; }
    .url { margin-top: 20px; font-size: 12px; color: #6b7280; word-break: break-all; max-width: 480px; }
    .hint { margin-top: 10px; font-size: 13px; color: #9ca3af; }
    .btn { margin-top: 28px; padding: 12px 28px; background: #10b981; color: #fff; border: none; border-radius: 10px; font-size: 16px; cursor: pointer; font-weight: 600; }
    @media print { .btn { display: none; } }
  </style>
</head>
<body>
  <div class="page">
    <p class="store-name">${storeName}</p>
    <div class="qr-wrap"><img src="${qrDataUrl.value}" alt="QR Kod" /></div>
    <p class="url">${url}</p>
    <p class="hint">QR kodni skaner qilib do'konga kiring</p>
    <button class="btn" onclick="window.print()">Chop etish / PDF saqlash</button>
  </div>
</body>
</html>`);
  win.document.close();
};

onMounted(async () => {
  if (!props.store?.mini_app_url) return;
  qrDataUrl.value = await QRCode.toDataURL(props.store.mini_app_url, {
    width: 1024,
    margin: 2,
    color: { dark: '#000000', light: '#ffffff' },
    errorCorrectionLevel: 'H',
  });
});

const saveGeneral = () => {
  generalForm.post(route('business.store.settings.update-general'), {
    preserveScroll: true,
    forceFormData: true,
  });
};

const saveTheme = () => {
  themeForm.put(route('business.store.settings.update-theme'), {
    preserveScroll: true,
  });
};

const openDeliveryModal = (zone) => {
  editingZone.value = zone;
  if (zone) {
    deliveryForm.name = zone.name;
    deliveryForm.fee = zone.fee;
    deliveryForm.min_amount = zone.min_amount;
    deliveryForm.delivery_time = zone.delivery_time || '';
  } else {
    deliveryForm.reset();
  }
  showDeliveryModal.value = true;
};

const saveDeliveryZone = () => {
  if (editingZone.value) {
    deliveryForm.put(route('business.store.delivery-zones.update', editingZone.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showDeliveryModal.value = false;
      },
    });
  } else {
    deliveryForm.post(route('business.store.delivery-zones.store'), {
      preserveScroll: true,
      onSuccess: () => {
        showDeliveryModal.value = false;
      },
    });
  }
};

const deleteDeliveryZone = (zone) => {
  if (confirm("'" + zone.name + "' zonasini o'chirishni xohlaysizmi?")) {
    router.delete(route('business.store.delivery-zones.destroy', zone.id), {
      preserveScroll: true,
    });
  }
};
</script>
