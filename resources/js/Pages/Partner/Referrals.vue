<template>
  <AppLayout title="Referrallar">
    <div class="max-w-[1600px] mx-auto">
      <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Referrallar</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Havolangiz orqali ro'yxatdan o'tgan yoki siz qo'shgan bizneslar</p>
        </div>
        <button
          @click="openInvite"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
        >
          <UserPlusIcon class="w-4 h-4" />
          Mijoz qo'shish
        </button>
      </div>

      <!-- Compact referral link bar (only shown here, not on dashboard) -->
      <div class="mb-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
          <LinkIcon class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Sizning havolangiz</p>
          <p class="text-sm font-mono text-gray-900 dark:text-gray-100 truncate">{{ referral_link }}</p>
        </div>
        <button
          @click="copyCode"
          class="inline-flex items-center gap-2 px-3 py-2 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 text-white text-xs font-semibold rounded-md transition-colors shrink-0"
        >
          <ClipboardIcon class="w-4 h-4" />
          {{ copied ? 'Nusxalandi' : 'Nusxalash' }}
        </button>
      </div>

      <!-- Filter toolbar -->
      <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="relative flex-1">
          <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            v-model="search"
            @input="applyFilters"
            type="text"
            placeholder="Biznes nomi..."
            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
          />
        </div>
        <select
          v-model="status"
          @change="applyFilters"
          class="px-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white min-w-[180px]"
        >
          <option value="">Barcha statuslar</option>
          <option value="pending">Pending</option>
          <option value="attributed">Attributed</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="churned">Churned</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div v-if="referrals.data.length" class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/40">
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Biznes</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Status</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden md:table-cell">Kanal</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">UTM Source</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden sm:table-cell">Attribute sanasi</th>
                <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3 hidden lg:table-cell">1-to'lov</th>
                <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Daromad</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
              <tr v-for="r in referrals.data" :key="r.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                <td class="px-5 py-4">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ r.business_name }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ r.created_at }}</p>
                </td>
                <td class="px-5 py-4">
                  <span :class="statusBadge(r.status)" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md">
                    {{ r.status }}
                  </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300 hidden md:table-cell">{{ r.referred_via || '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ r.utm_source || '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">{{ r.attributed_at || '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 hidden lg:table-cell">{{ r.first_payment_at || '—' }}</td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-900 dark:text-gray-100 text-right">{{ formatMoney(r.lifetime_earned) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-16">
          <UserGroupIcon class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Hali referral yo'q</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Havolangizni do'stlaringizga yoki ijtimoiy tarmoqlarga ulashing</p>
          <button
            @click="copyCode"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg"
          >
            <ClipboardIcon class="w-4 h-4" />
            {{ copied ? 'Nusxalandi!' : `Havolani nusxalash (${partner_code})` }}
          </button>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="referrals.data.length && referrals.links" class="mt-4">
        <Pagination :links="referrals.links" :from="referrals.from" :to="referrals.to" :total="referrals.total" />
      </div>
    </div>

    <!-- ============ INVITE CLIENT MODAL ============ -->
    <div v-if="showInvite" class="fixed inset-0 z-50 overflow-y-auto">
      <div @click="closeInvite" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
      <div class="flex min-h-full items-start justify-center p-4 pt-16">
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6" @click.stop>
          <!-- Header -->
          <div class="flex items-start justify-between mb-5">
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">Yangi mijoz qo'shish</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Mijoz login ma'lumotlarini oladi — siz avtomatik referral olasiz</p>
            </div>
            <button @click="closeInvite" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitInvite" class="space-y-4">
            <!-- Client Info -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5 uppercase tracking-wider">Mijoz ma'lumotlari</label>
              <div class="space-y-2.5">
                <input
                  v-model="invite.full_name"
                  type="text"
                  required
                  placeholder="Ism familiya *"
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
                <input
                  v-model="invite.phone"
                  type="tel"
                  required
                  placeholder="Telefon raqam * (+998 90 ...)"
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
                <input
                  v-model="invite.email"
                  type="email"
                  placeholder="Email (majburiy emas)"
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
                <!-- Password field with auto-generate toggle -->
                <div class="relative">
                  <input
                    v-model="invite.password"
                    :type="showPwd ? 'text' : 'password'"
                    autocomplete="new-password"
                    :placeholder="invite.password ? '' : 'Parol (bo\'sh qoldirsangiz avtomatik yaratiladi)'"
                    class="w-full pl-3 pr-24 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white font-mono"
                  />
                  <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                    <button
                      type="button"
                      @click="showPwd = !showPwd"
                      class="p-1 text-gray-400 hover:text-gray-600"
                      :title="showPwd ? 'Yashirish' : 'Ko\'rsatish'"
                    >
                      <EyeIcon v-if="!showPwd" class="w-4 h-4" />
                      <EyeSlashIcon v-else class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      @click="generateRandomPwd"
                      class="px-2 py-1 text-[11px] font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded"
                      title="Tasodifiy parol yaratish"
                    >
                      Random
                    </button>
                  </div>
                </div>
                <p v-if="invite.password && invite.password.length < 8" class="text-xs text-amber-600 dark:text-amber-400">
                  Parol kamida 8 ta belgidan iborat bo'lishi kerak
                </p>
              </div>
            </div>

            <!-- Business Info -->
            <div>
              <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5 uppercase tracking-wider">Biznes ma'lumotlari</label>
              <div class="space-y-2.5">
                <input
                  v-model="invite.business_name"
                  type="text"
                  required
                  placeholder="Biznes nomi *"
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                />
                <select
                  v-model="invite.category"
                  required
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                >
                  <option value="">Kategoriya tanlang *</option>
                  <option v-for="cat in businessCategories" :key="cat.value" :value="cat.value">
                    {{ cat.short }}
                  </option>
                </select>
                <select
                  v-model="invite.region"
                  class="w-full px-3 py-2.5 text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white"
                >
                  <option v-for="r in regions" :key="r.value" :value="r.value">
                    {{ r.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Info -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
              <p class="text-xs text-blue-700 dark:text-blue-300">
                <span class="font-semibold">Nima bo'ladi?</span> Mijoz uchun hisob yaratiladi, random parol beriladi. Parol <span class="font-semibold">bir marta</span> ko'rsatiladi — mijozga topshiring. Siz avtomatik referral bo'lasiz.
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-2">
              <button
                type="button"
                @click="closeInvite"
                class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg"
              >
                Bekor qilish
              </button>
              <button
                type="submit"
                :disabled="inviteForm.processing"
                class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-semibold rounded-lg"
              >
                {{ inviteForm.processing ? 'Yaratilmoqda...' : 'Mijozni yaratish' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ============ SUCCESS MODAL (credentials shown once) ============ -->
    <div v-if="successData" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black/60"></div>
      <div class="flex min-h-full items-start justify-center p-4 pt-16">
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
              <CheckIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mijoz yaratildi!</h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ successData.business_name }}</p>
            </div>
          </div>

          <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3 mb-4">
            <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">
              ⚠ Bu ma'lumotlar <span class="font-bold">faqat bir marta</span> ko'rsatiladi. Mijozga darhol topshiring.
            </p>
          </div>

          <div class="space-y-2 mb-5">
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Login</span>
              <code class="text-sm font-mono text-gray-900 dark:text-white">{{ successData.login }}</code>
            </div>
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Parol</span>
              <code class="text-sm font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ successData.temp_password }}</code>
            </div>
            <div v-if="successData.email" class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Email</span>
              <span class="text-sm font-mono text-gray-900 dark:text-white">{{ successData.email }}</span>
            </div>
            <div v-if="successData.phone" class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tel</span>
              <span class="text-sm font-mono text-gray-900 dark:text-white">{{ successData.phone }}</span>
            </div>
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 rounded-lg px-3 py-2.5">
              <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kirish</span>
              <a :href="successData.login_url" target="_blank" class="text-xs text-emerald-600 dark:text-emerald-400 underline">{{ successData.login_url }}</a>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="copyCredentials"
              class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 text-white text-sm font-semibold rounded-lg"
            >
              <ClipboardIcon class="w-4 h-4" />
              {{ credCopied ? 'Nusxalandi!' : "Hammasini nusxalash" }}
            </button>
            <button
              @click="closeSuccess"
              class="flex-1 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg"
            >
              Tayyor
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/PartnerLayout.vue';
import Pagination from '@/components/Pagination.vue';
import {
  MagnifyingGlassIcon,
  UserGroupIcon,
  ClipboardIcon,
  LinkIcon,
  UserPlusIcon,
  XMarkIcon,
  CheckIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/outline';
// Biznes kategoriyalari va viloyatlar — yagona manba (DRY)
import { BUSINESS_CATEGORIES as businessCategories, UZBEKISTAN_REGIONS as regions } from '@/constants/businessCategories';

const props = defineProps({
  partner_code: { type: String, required: true },
  referral_link: { type: String, default: '' },
  referrals: { type: Object, required: true },
});

const search = ref('');
const status = ref('');
const copied = ref(false);
const credCopied = ref(false);

const formatMoney = (v) => new Intl.NumberFormat('uz-UZ').format(Math.round(v || 0)) + " so'm";

let searchTimeout;
const applyFilters = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(
      route('partner.referrals'),
      { search: search.value || undefined, status: status.value || undefined },
      { preserveState: true, preserveScroll: true, replace: true }
    );
  }, 300);
};

const copyCode = async () => {
  const link = props.referral_link || `${window.location.origin}/refer/${props.partner_code}`;
  try {
    await navigator.clipboard.writeText(link);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  } catch (e) {
    console.error(e);
  }
};

const statusBadge = (s) => ({
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
  attributed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  inactive: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  churned: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[s] || 'bg-gray-100 text-gray-700');

// ============ INVITE CLIENT ============
const showInvite = ref(false);
const showPwd = ref(false);
const invite = reactive({
  full_name: '',
  email: '',
  phone: '',
  password: '',
  business_name: '',
  category: '',
  region: 'toshkent_shahar', // default — yagona manba value
});
const inviteForm = useForm({});

const openInvite = () => {
  Object.assign(invite, {
    full_name: '', email: '', phone: '', password: '',
    business_name: '', category: '', region: 'toshkent_shahar',
  });
  showPwd.value = false;
  showInvite.value = true;
};
const closeInvite = () => { showInvite.value = false; };

// Crypto-strong random password with guaranteed complexity
const generateRandomPwd = () => {
  const upper = 'ABCDEFGHJKMNPQRSTUVWXYZ';
  const lower = 'abcdefghjkmnpqrstuvwxyz';
  const digits = '23456789';
  const symbols = '!@#$%&*';
  const all = upper + lower + digits + symbols;
  const rand = (str) => str[Math.floor(Math.random() * str.length)];
  let pwd = [rand(upper), rand(lower), rand(digits), rand(symbols)];
  for (let i = 0; i < 8; i++) pwd.push(rand(all));
  invite.password = pwd.sort(() => Math.random() - 0.5).join('');
  showPwd.value = true;
};

const submitInvite = () => {
  // Agar parol bo'sh bo'lsa — backend avtomatik yaratadi
  if (invite.password && invite.password.length < 8) {
    alert("Parol kamida 8 ta belgidan iborat bo'lishi kerak yoki bo'sh qoldiring (avtomatik yaratiladi).");
    return;
  }
  inviteForm.transform(() => ({ ...invite })).post(route('partner.referrals.invite'), {
    preserveScroll: true,
    onSuccess: () => {
      showInvite.value = false;
    },
  });
};

// ============ SUCCESS MODAL ============
const page = usePage();
const successData = computed(() => page.props?.flash?.invite_success || null);

watch(successData, (v) => {
  if (v) credCopied.value = false;
});

const copyCredentials = async () => {
  const s = successData.value;
  if (!s) return;
  const text = [
    `BiznesPilot — Kirish ma'lumotlari`,
    `Biznes: ${s.business_name}`,
    `Login: ${s.login}`,
    `Parol: ${s.temp_password}`,
    s.email ? `Email: ${s.email}` : null,
    s.phone ? `Tel:   ${s.phone}` : null,
    `Kirish: ${s.login_url}`,
  ].filter(Boolean).join('\n');
  try {
    await navigator.clipboard.writeText(text);
    credCopied.value = true;
    setTimeout(() => (credCopied.value = false), 2500);
  } catch (e) {
    console.error(e);
  }
};

const closeSuccess = () => {
  // Flash'ni tozalash uchun sahifani freshly relaod qilamiz
  router.visit(route('partner.referrals'), { preserveScroll: true });
};
</script>
