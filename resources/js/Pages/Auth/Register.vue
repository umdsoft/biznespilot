<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-primary-900">BiznesPilot AI</h1>
        <p class="mt-2 text-gray-600">O'zbekiston startaplari uchun Marketing Intelligence</p>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-8 py-6">
        <div class="text-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Ro'yxatdan o'tish</h2>
          <p class="mt-2 text-sm text-gray-600">
            Akkauntingiz bormi?
            <Link href="/login" class="text-primary-600 hover:text-primary-500 font-medium">
              Tizimga kirish
            </Link>
          </p>
        </div>

        <form @submit.prevent="form.post('/register')" class="space-y-4">
          <Input
            v-model="form.name"
            label="To'liq ism"
            type="text"
            placeholder="Ism Familiya"
            :error="form.errors.name"
            required
          />

          <Input
            v-model="form.login"
            label="Login (username)"
            type="text"
            placeholder="username"
            :error="form.errors.login"
            hint="Faqat harflar, raqamlar, tire va pastki chiziq"
            required
          />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input
              v-model="form.email"
              label="Email (ixtiyoriy)"
              type="email"
              placeholder="email@example.com"
              :error="form.errors.email"
            />

            <Input
              v-model="form.phone"
              label="Telefon (ixtiyoriy)"
              type="tel"
              placeholder="+998901234567"
              :error="form.errors.phone"
            />
          </div>

          <Input
            v-model="form.password"
            label="Parol"
            type="password"
            placeholder="••••••••"
            :error="form.errors.password"
            hint="Kamida 8 ta belgi"
            required
          />

          <Input
            v-model="form.password_confirmation"
            label="Parolni tasdiqlash"
            type="password"
            placeholder="••••••••"
            :error="form.errors.password_confirmation"
            required
          />

          <div class="flex items-start">
            <input
              v-model="form.terms"
              type="checkbox"
              class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <label class="ml-2 text-sm text-gray-600">
              Men
              <a href="#" class="text-primary-600 hover:text-primary-500">Foydalanish shartlari</a>
              va
              <a href="#" class="text-primary-600 hover:text-primary-500">Maxfiylik siyosati</a>
              ga roziman
            </label>
          </div>

          <Button
            type="submit"
            variant="primary"
            :loading="form.processing"
            full-width
          >
            Ro'yxatdan o'tish
          </Button>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-gray-600">
        &copy; {{ new Date().getFullYear() }} BiznesPilot AI. Barcha huquqlar himoyalangan.
      </p>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import Input from '@/components/Input.vue';
import Button from '@/components/Button.vue';

const form = useForm({
  name: '',
  login: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  terms: false,
});
</script>
