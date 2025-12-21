<template>
  <BusinessLayout title="Yangi Ideal Mijoz">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <Link href="/dream-buyer" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Orqaga
        </Link>
        <h2 class="text-2xl font-bold text-gray-900">Yangi Ideal Mijoz Yaratish</h2>
        <p class="mt-1 text-sm text-gray-600">
          Ideal mijozingizning to'liq profilini yarating
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Demografik Ma'lumotlar -->
        <Card title="Demografik Ma'lumotlar">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              v-model="form.name"
              label="Segment nomi"
              placeholder="Masalan: Yosh tadbirkorlar"
              :error="form.errors.name"
              required
              class="md:col-span-2"
            />

            <Input
              v-model="form.age_range"
              label="Yosh oralig'i"
              placeholder="25-35 yosh"
              :error="form.errors.age_range"
              required
            />

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Jinsi *
              </label>
              <select
                v-model="form.gender"
                class="w-full rounded-lg border-gray-300 focus:ring-primary-500 focus:border-primary-500"
                required
              >
                <option value="">Tanlang</option>
                <option value="male">Erkak</option>
                <option value="female">Ayol</option>
                <option value="other">Boshqa/Aralash</option>
              </select>
              <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600">
                {{ form.errors.gender }}
              </p>
            </div>

            <Input
              v-model="form.location"
              label="Joylashuv"
              placeholder="Toshkent, O'zbekiston"
              :error="form.errors.location"
            />

            <Input
              v-model="form.occupation"
              label="Kasb"
              placeholder="Tadbirkor, Dasturchi"
              :error="form.errors.occupation"
            />

            <Input
              v-model="form.income_level"
              label="Daromad darajasi"
              placeholder="5-10 million/oy"
              :error="form.errors.income_level"
            />

            <Input
              v-model="form.education"
              label="Ta'lim"
              placeholder="Oliy, Magistr"
              :error="form.errors.education"
            />

            <Input
              v-model="form.marital_status"
              label="Oilaviy holati"
              placeholder="Turmush qurgan"
              :error="form.errors.marital_status"
            />

            <Input
              v-model="form.budget_range"
              label="Byudjet oralig'i"
              placeholder="500K - 2M"
              :error="form.errors.budget_range"
            />
          </div>
        </Card>

        <!-- Psixografik Ma'lumotlar -->
        <Card title="Psixografik Ma'lumotlar">
          <div class="space-y-4">
            <TagInput
              v-model="form.interests"
              label="Qiziqishlar"
              placeholder="Biznes, Texnologiya, Sport"
              :error="form.errors.interests"
            />

            <TagInput
              v-model="form.pain_points"
              label="Og'riqli Nuqtalar (Pain Points)"
              placeholder="Vaqt yetishmasligi, Marketing bilimi yo'qligi"
              :error="form.errors.pain_points"
            />

            <TagInput
              v-model="form.goals"
              label="Maqsadlar"
              placeholder="Biznesni o'stirish, Daromadni oshirish"
              :error="form.errors.goals"
            />

            <TagInput
              v-model="form.values"
              label="Qadriyatlar"
              placeholder="Sifat, Ishonch, Tezkorlik"
              :error="form.errors.values"
            />
          </div>
        </Card>

        <!-- Xaridor Xulqi -->
        <Card title="Xaridor Xulqi">
          <div class="space-y-4">
            <TagInput
              v-model="form.buying_triggers"
              label="Xarid Trigerlari"
              placeholder="Chegirma, Cheklangan takliflar"
              :error="form.errors.buying_triggers"
            />

            <TagInput
              v-model="form.preferred_channels"
              label="Afzal Kanallar"
              placeholder="Instagram, Telegram, WhatsApp"
              :error="form.errors.preferred_channels"
            />

            <TagInput
              v-model="form.decision_factors"
              label="Qaror Qabul Qilish Omillari"
              placeholder="Narx, Sifat, Brend"
              :error="form.errors.decision_factors"
            />
          </div>
        </Card>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6">
          <Link
            href="/dream-buyer"
            class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors"
          >
            Bekor qilish
          </Link>
          <Button
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            Ideal Mijoz Yaratish
          </Button>
        </div>
      </form>
    </div>
  </BusinessLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import Card from '@/components/Card.vue';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';
import TagInput from '@/components/TagInput.vue';

const form = useForm({
  name: '',
  age_range: '',
  gender: '',
  location: '',
  occupation: '',
  income_level: '',
  education: '',
  marital_status: '',
  interests: [],
  pain_points: [],
  goals: [],
  values: [],
  buying_triggers: [],
  preferred_channels: [],
  budget_range: '',
  decision_factors: [],
});

const submit = () => {
  form.post('/dream-buyer');
};
</script>
