<template>
  <div>
    <div class="mb-6">
      <Link :href="route('business.offers.index')" class="text-blue-600 hover:text-blue-700 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Takliflarga qaytish
      </Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">Taklifni Tahrirlash</h1>
    </div>

    <form @submit.prevent="submit">
      <div class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Asosiy Ma'lumotlar</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Taklif Nomi <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                class="input"
                :class="{ 'border-red-500': form.errors.name }"
                placeholder="Masalan: Premium Ta'lim Paketi"
                required
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="input"
                :class="{ 'border-red-500': form.errors.description }"
                placeholder="Taklif haqida qisqacha ma'lumot..."
              ></textarea>
              <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>
          </div>
        </Card>

        <!-- Value Proposition -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Qiymat Taklifi (USP)</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Noyob Sotish Taklifi <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="form.value_proposition"
              rows="4"
              class="input"
              :class="{ 'border-red-500': form.errors.value_proposition }"
              placeholder="Sizning taklifingiz raqobatchilardan nimasi bilan ajralib turadi? Mijozlar nima uchun aynan sizdan sotib olishlari kerak?"
              required
            ></textarea>
            <p v-if="form.errors.value_proposition" class="mt-1 text-sm text-red-600">{{ form.errors.value_proposition }}</p>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Maqsadli Auditoriya</label>
            <textarea
              v-model="form.target_audience"
              rows="3"
              class="input"
              :class="{ 'border-red-500': form.errors.target_audience }"
              placeholder="Bu taklif kim uchun mo'ljallangan? (masalan: yoshlar, tadbirkorlar, etc.)"
            ></textarea>
            <p v-if="form.errors.target_audience" class="mt-1 text-sm text-red-600">{{ form.errors.target_audience }}</p>
          </div>
        </Card>

        <!-- Pricing -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Narxlash</h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Narx (so'm)</label>
              <input
                v-model="form.pricing"
                type="number"
                step="0.01"
                min="0"
                class="input"
                :class="{ 'border-red-500': form.errors.pricing }"
                placeholder="0.00"
              />
              <p v-if="form.errors.pricing" class="mt-1 text-sm text-red-600">{{ form.errors.pricing }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Narxlash Modeli</label>
              <input
                v-model="form.pricing_model"
                type="text"
                class="input"
                :class="{ 'border-red-500': form.errors.pricing_model }"
                placeholder="Masalan: bir martalik, oylik, yillik"
              />
              <p v-if="form.errors.pricing_model" class="mt-1 text-sm text-red-600">{{ form.errors.pricing_model }}</p>
            </div>
          </div>
        </Card>

        <!-- Guarantees & Bonuses -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Kafolatlar va Bonuslar</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kafolatlar</label>
              <textarea
                v-model="form.guarantees"
                rows="3"
                class="input"
                :class="{ 'border-red-500': form.errors.guarantees }"
                placeholder="Masalan: 30 kunlik pul qaytarish kafolati, 100% natija kafolati"
              ></textarea>
              <p v-if="form.errors.guarantees" class="mt-1 text-sm text-red-600">{{ form.errors.guarantees }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Bonuslar</label>
              <textarea
                v-model="form.bonuses"
                rows="3"
                class="input"
                :class="{ 'border-red-500': form.errors.bonuses }"
                placeholder="Masalan: Bepul konsultatsiya, qo'shimcha darsliklar"
              ></textarea>
              <p v-if="form.errors.bonuses" class="mt-1 text-sm text-red-600">{{ form.errors.bonuses }}</p>
            </div>
          </div>
        </Card>

        <!-- Scarcity & Urgency -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Kamlik va Shoshilinchlik</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kamlik (Scarcity)</label>
              <textarea
                v-model="form.scarcity"
                rows="2"
                class="input"
                :class="{ 'border-red-500': form.errors.scarcity }"
                placeholder="Masalan: Faqat 50 ta joy, cheklangan miqdorda"
              ></textarea>
              <p v-if="form.errors.scarcity" class="mt-1 text-sm text-red-600">{{ form.errors.scarcity }}</p>
              <p class="mt-1 text-xs text-gray-500">Cheklangan miqdor yoki joy haqida ma'lumot</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Shoshilinchlik (Urgency)</label>
              <textarea
                v-model="form.urgency"
                rows="2"
                class="input"
                :class="{ 'border-red-500': form.errors.urgency }"
                placeholder="Masalan: Chegirma 3 kungacha amal qiladi"
              ></textarea>
              <p v-if="form.errors.urgency" class="mt-1 text-sm text-red-600">{{ form.errors.urgency }}</p>
              <p class="mt-1 text-xs text-gray-500">Vaqt chegarasi yoki muddat haqida ma'lumot</p>
            </div>
          </div>
        </Card>

        <!-- Status -->
        <Card>
          <h2 class="text-lg font-semibold text-gray-900 mb-4">Holat</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Taklif Holati <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.status"
              class="input"
              :class="{ 'border-red-500': form.errors.status }"
              required
            >
              <option value="draft">Qoralama</option>
              <option value="active">Faol</option>
              <option value="paused">To'xtatilgan</option>
              <option value="archived">Arxivlangan</option>
            </select>
            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
          </div>
        </Card>
      </div>

      <!-- Form Actions -->
      <div class="mt-6 flex items-center justify-between">
        <button
          type="button"
          @click="deleteOffer"
          :disabled="deleteForm.processing"
          class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
        >
          <span v-if="deleteForm.processing">O'chirilmoqda...</span>
          <span v-else>O'chirish</span>
        </button>

        <div class="flex items-center space-x-3">
          <Link :href="route('business.offers.index')" class="btn-secondary">
            Bekor qilish
          </Link>
          <button type="submit" :disabled="form.processing" class="btn-primary">
            <span v-if="form.processing">Saqlanmoqda...</span>
            <span v-else>Saqlash</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import Card from '@/components/Card.vue';

const props = defineProps({
  offer: Object,
});

const form = useForm({
  name: props.offer.name,
  description: props.offer.description || '',
  value_proposition: props.offer.value_proposition,
  target_audience: props.offer.target_audience || '',
  pricing: props.offer.pricing,
  pricing_model: props.offer.pricing_model || '',
  guarantees: props.offer.guarantees || '',
  bonuses: props.offer.bonuses || '',
  scarcity: props.offer.scarcity || '',
  urgency: props.offer.urgency || '',
  status: props.offer.status,
});

const deleteForm = useForm({});

const submit = () => {
  form.put(route('business.offers.update', props.offer.id));
};

const deleteOffer = () => {
  if (confirm('Haqiqatan ham bu taklifni o\'chirmoqchimisiz?')) {
    deleteForm.delete(route('business.offers.destroy', props.offer.id));
  }
};
</script>
