<template>
  <Modal v-model="isOpen" title="Maxfiylik siyosati" size="xl" @close="$emit('close')">
    <div class="max-h-[70vh] overflow-y-auto pr-2 text-gray-700 text-sm leading-relaxed space-y-6">
      <!-- Header -->
      <div class="text-center pb-4 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-900">BiznesPilot AI Maxfiylik siyosati</h2>
        <p class="text-gray-500 text-xs mt-1">Oxirgi yangilanish: {{ currentDate }}</p>
      </div>

      <!-- Introduction -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">1. Kirish</h3>
        <p class="mb-2">
          BiznesPilot AI ("Biz", "Kompaniya") Sizning maxfiyligingizni hurmat qiladi va shaxsiy
          ma'lumotlaringizni himoya qilishga sodiq. Ushbu Maxfiylik siyosati qanday ma'lumotlarni
          to'plashimiz, ulardan qanday foydalanishimiz va qanday himoya qilishimizni tushuntiradi.
        </p>
        <p>
          Ushbu siyosat O'zbekiston Respublikasining "Shaxsiy ma'lumotlar to'g'risida"gi Qonuni va
          xalqaro ma'lumotlarni himoya qilish standartlariga muvofiq ishlab chiqilgan.
        </p>
      </section>

      <!-- Data We Collect -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">2. To'planadigan ma'lumotlar</h3>

        <div class="mb-4">
          <h4 class="font-medium text-gray-800 mb-2">2.1. Siz taqdim etgan ma'lumotlar:</h4>
          <ul class="list-disc pl-5 space-y-1">
            <li><strong>Identifikatsiya ma'lumotlari:</strong> ism, familiya, login, email, telefon raqami</li>
            <li><strong>Biznes ma'lumotlari:</strong> kompaniya nomi, sohasi, manzili, xodimlar soni</li>
            <li><strong>Moliyaviy ma'lumotlar:</strong> to'lov ma'lumotlari, tranzaksiya tarixi</li>
            <li><strong>Kommunikatsiya:</strong> qo'llab-quvvatlash xabarlari, fikr-mulohazalar</li>
            <li><strong>Kontent:</strong> Siz yuklagan hujjatlar, rasmlar va boshqa materiallar</li>
          </ul>
        </div>

        <div class="mb-4">
          <h4 class="font-medium text-gray-800 mb-2">2.2. Avtomatik to'planadigan ma'lumotlar:</h4>
          <ul class="list-disc pl-5 space-y-1">
            <li><strong>Qurilma ma'lumotlari:</strong> IP manzil, brauzer turi, operatsion tizim</li>
            <li><strong>Foydalanish ma'lumotlari:</strong> tashrif vaqti, ko'rilgan sahifalar, bosilgan tugmalar</li>
            <li><strong>Joylashuv:</strong> IP manzil asosidagi taxminiy joylashuv</li>
            <li><strong>Cookie va tracking:</strong> sessiya ma'lumotlari, preferenslar</li>
          </ul>
        </div>

        <div>
          <h4 class="font-medium text-gray-800 mb-2">2.3. Uchinchi tomonlardan olingan ma'lumotlar:</h4>
          <ul class="list-disc pl-5 space-y-1">
            <li>Ijtimoiy tarmoqlar integratsiyasi (Instagram, Telegram, Facebook)</li>
            <li>To'lov provayderlari (Click, Payme)</li>
            <li>Marketing platformalari (Google Analytics, Meta)</li>
            <li>CRM integratsiyalari (AmoCRM)</li>
          </ul>
        </div>
      </section>

      <!-- How We Use Data -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">3. Ma'lumotlardan foydalanish maqsadlari</h3>
        <p class="mb-2">Biz Sizning ma'lumotlaringizdan quyidagi maqsadlarda foydalanamiz:</p>

        <div class="space-y-3">
          <div class="bg-blue-50 rounded-lg p-3">
            <h4 class="font-medium text-blue-800 mb-1">Xizmat ko'rsatish</h4>
            <ul class="list-disc pl-5 text-blue-700 space-y-1">
              <li>Akkauntingizni yaratish va boshqarish</li>
              <li>Platforma funksiyalarini ta'minlash</li>
              <li>AI tahlillarini amalga oshirish</li>
              <li>Qo'llab-quvvatlash xizmatlarini ko'rsatish</li>
            </ul>
          </div>

          <div class="bg-green-50 rounded-lg p-3">
            <h4 class="font-medium text-green-800 mb-1">Xizmatni yaxshilash</h4>
            <ul class="list-disc pl-5 text-green-700 space-y-1">
              <li>Platformani takomillashtirish</li>
              <li>Yangi funksiyalar ishlab chiqish</li>
              <li>AI modellarini o'qitish (anonimizatsiya qilingan ma'lumotlar)</li>
              <li>Foydalanuvchi tajribasini optimallashtirish</li>
            </ul>
          </div>

          <div class="bg-purple-50 rounded-lg p-3">
            <h4 class="font-medium text-purple-800 mb-1">Kommunikatsiya</h4>
            <ul class="list-disc pl-5 text-purple-700 space-y-1">
              <li>Muhim bildirishnomalar yuborish</li>
              <li>Marketing xabarlari (roziligingiz bilan)</li>
              <li>Yangiliklar va taklif haqida xabar berish</li>
              <li>Xavfsizlik ogohlantirishlari</li>
            </ul>
          </div>

          <div class="bg-orange-50 rounded-lg p-3">
            <h4 class="font-medium text-orange-800 mb-1">Huquqiy maqsadlar</h4>
            <ul class="list-disc pl-5 text-orange-700 space-y-1">
              <li>Qonuniy talablarni bajarish</li>
              <li>Firibgarlikni aniqlash va oldini olish</li>
              <li>Nizolarni hal qilish</li>
              <li>Shartlarni bajarilishini ta'minlash</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- Data Sharing -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">4. Ma'lumotlarni ulashish</h3>
        <p class="mb-3">
          Biz Sizning shaxsiy ma'lumotlaringizni sotmaymiz. Quyidagi hollardagina ma'lumotlar
          uchinchi tomonlar bilan ulashilishi mumkin:
        </p>

        <div class="space-y-3">
          <div class="border-l-4 border-emerald-500 pl-4">
            <h4 class="font-medium text-gray-800">Xizmat ko'rsatuvchilar</h4>
            <p class="text-gray-600">Serverlar, to'lov tizimlari, email xizmatlari - faqat xizmat
            ko'rsatish uchun zarur bo'lgan ma'lumotlar</p>
          </div>

          <div class="border-l-4 border-blue-500 pl-4">
            <h4 class="font-medium text-gray-800">Integratsiyalar</h4>
            <p class="text-gray-600">Instagram, Telegram, AmoCRM - faqat Siz faollashtirgan
            integratsiyalar doirasida</p>
          </div>

          <div class="border-l-4 border-yellow-500 pl-4">
            <h4 class="font-medium text-gray-800">Huquqiy talablar</h4>
            <p class="text-gray-600">Sud qarori yoki qonuniy talab bo'lganda davlat organlariga</p>
          </div>

          <div class="border-l-4 border-red-500 pl-4">
            <h4 class="font-medium text-gray-800">Biznes transferi</h4>
            <p class="text-gray-600">Kompaniya sotilganda yoki qo'shilganda (oldindan xabardor qilamiz)</p>
          </div>
        </div>
      </section>

      <!-- Data Security -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">5. Ma'lumotlar xavfsizligi</h3>
        <p class="mb-3">Biz ma'lumotlaringizni himoya qilish uchun quyidagi choralarni ko'ramiz:</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <h4 class="font-medium text-gray-800">Shifrlash</h4>
            </div>
            <p class="text-gray-600 text-xs">SSL/TLS 256-bit shifrlash, AES-256 ma'lumotlar shifrlashi</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <h4 class="font-medium text-gray-800">Xavfsizlik auditi</h4>
            </div>
            <p class="text-gray-600 text-xs">Muntazam xavfsizlik tekshiruvlari va penetration testlar</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h4 class="font-medium text-gray-800">Kirish nazorati</h4>
            </div>
            <p class="text-gray-600 text-xs">Ikki faktorli autentifikatsiya, rol asosida kirish</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-emerald-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
              </svg>
              <h4 class="font-medium text-gray-800">Zaxira nusxa</h4>
            </div>
            <p class="text-gray-600 text-xs">Kunlik avtomatik zaxira nusxalash, geografik taqsimlangan</p>
          </div>
        </div>
      </section>

      <!-- User Rights -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">6. Sizning huquqlaringiz</h3>
        <p class="mb-3">O'zbekiston Respublikasi qonunchiligi va xalqaro standartlarga muvofiq
        Siz quyidagi huquqlarga egasiz:</p>

        <div class="space-y-2">
          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">1</span>
            <div>
              <h4 class="font-medium text-gray-800">Kirish huquqi</h4>
              <p class="text-gray-600 text-xs">Siz haqingizda qanday ma'lumotlar to'plangani bilan tanishish</p>
            </div>
          </div>

          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">2</span>
            <div>
              <h4 class="font-medium text-gray-800">Tuzatish huquqi</h4>
              <p class="text-gray-600 text-xs">Noto'g'ri yoki eskirgan ma'lumotlarni yangilash</p>
            </div>
          </div>

          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">3</span>
            <div>
              <h4 class="font-medium text-gray-800">O'chirish huquqi</h4>
              <p class="text-gray-600 text-xs">Ma'lumotlaringizni o'chirishni so'rash ("Unutilish huquqi")</p>
            </div>
          </div>

          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">4</span>
            <div>
              <h4 class="font-medium text-gray-800">Cheklash huquqi</h4>
              <p class="text-gray-600 text-xs">Ma'lumotlarni qayta ishlashni cheklash</p>
            </div>
          </div>

          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">5</span>
            <div>
              <h4 class="font-medium text-gray-800">Ko'chirish huquqi</h4>
              <p class="text-gray-600 text-xs">Ma'lumotlaringizni boshqa xizmatga ko'chirish</p>
            </div>
          </div>

          <div class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-xs font-bold mr-3">6</span>
            <div>
              <h4 class="font-medium text-gray-800">E'tiroz bildirish</h4>
              <p class="text-gray-600 text-xs">Marketing xabarlaridan voz kechish</p>
            </div>
          </div>
        </div>

        <p class="mt-3 text-gray-600">
          Huquqlaringizdan foydalanish uchun <strong>privacy@biznespilot.uz</strong> manziliga murojaat qiling.
          So'rovlar 30 kun ichida ko'rib chiqiladi.
        </p>
      </section>

      <!-- Cookies -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">7. Cookie va kuzatuv texnologiyalari</h3>
        <p class="mb-3">Biz quyidagi cookie turlaridan foydalanamiz:</p>

        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-700">Turi</th>
                <th class="px-3 py-2 text-left font-medium text-gray-700">Maqsad</th>
                <th class="px-3 py-2 text-left font-medium text-gray-700">Muddat</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr>
                <td class="px-3 py-2 font-medium">Zarur</td>
                <td class="px-3 py-2">Tizim ishlashi uchun majburiy</td>
                <td class="px-3 py-2">Sessiya</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-medium">Funksional</td>
                <td class="px-3 py-2">Preferenslarni saqlash</td>
                <td class="px-3 py-2">1 yil</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-medium">Analitik</td>
                <td class="px-3 py-2">Foydalanish statistikasi</td>
                <td class="px-3 py-2">2 yil</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-medium">Marketing</td>
                <td class="px-3 py-2">Maqsadli reklama</td>
                <td class="px-3 py-2">90 kun</td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="mt-3 text-gray-600">
          Cookie sozlamalarini brauzer parametrlari orqali boshqarishingiz mumkin.
        </p>
      </section>

      <!-- Data Retention -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">8. Ma'lumotlarni saqlash muddati</h3>
        <p class="mb-2">Ma'lumotlaringiz quyidagi muddatlarda saqlanadi:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li><strong>Akkaunt ma'lumotlari:</strong> Akkaunt faol bo'lguncha + 3 yil</li>
          <li><strong>Tranzaksiya tarixi:</strong> 7 yil (soliq qonunchiligi talabi)</li>
          <li><strong>Kommunikatsiya:</strong> 2 yil</li>
          <li><strong>Log fayllar:</strong> 1 yil</li>
          <li><strong>Zaxira nusxalar:</strong> 90 kun</li>
        </ul>
        <p class="mt-2 text-gray-600">
          Akkaunt o'chirilgandan so'ng, ma'lumotlar 30 kun ichida to'liq o'chiriladi
          (qonuniy saqlash talablari bundan mustasno).
        </p>
      </section>

      <!-- International Transfers -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">9. Xalqaro ma'lumot uzatish</h3>
        <p class="mb-2">
          Asosiy serverlarimiz O'zbekiston va Yevropa ma'lumotlar markazlarida joylashgan.
          Ma'lumotlar quyidagi hollarda xalqaro miqyosda uzatilishi mumkin:
        </p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Bulutli xizmatlar provayderlari (AWS, Google Cloud)</li>
          <li>Uchinchi tomon integratsiyalari (Meta, Google)</li>
          <li>To'lov tizimlari</li>
        </ul>
        <p class="mt-2 text-gray-600">
          Barcha xalqaro uzatishlar tegishli ma'lumotlarni himoya qilish shartnomalariga muvofiq amalga oshiriladi.
        </p>
      </section>

      <!-- Children's Privacy -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">10. Bolalar maxfiyligi</h3>
        <p>
          Platformamiz 18 yoshdan kichik shaxslar uchun mo'ljallanmagan. Biz ataylab bolalardan
          ma'lumot to'plamaymiz. Agar sizga ma'lum bo'lsa, darhol biz bilan bog'laning.
        </p>
      </section>

      <!-- Changes to Policy -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">11. Siyosatni o'zgartirish</h3>
        <p class="mb-2">
          Biz ushbu Maxfiylik siyosatini vaqti-vaqti bilan yangilashimiz mumkin. O'zgarishlar haqida:
        </p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Platformada e'lon qilamiz</li>
          <li>Email orqali xabardor qilamiz (muhim o'zgarishlar)</li>
          <li>Yangilangan sanani ko'rsatamiz</li>
        </ul>
        <p class="mt-2 text-gray-600">
          O'zgarishlardan keyin platformadan foydalanishni davom ettirish yangilangan
          siyosatga rozilik hisoblanadi.
        </p>
      </section>

      <!-- Contact -->
      <section class="pb-4">
        <h3 class="text-base font-semibold text-gray-900 mb-2">12. Bog'lanish</h3>
        <p class="mb-3">Maxfiylik bo'yicha savollar yoki so'rovlar uchun:</p>
        <div class="bg-emerald-50 rounded-lg p-4">
          <p class="font-medium text-emerald-800 mb-2">Ma'lumotlarni himoya qilish bo'yicha mas'ul:</p>
          <p><strong>Email:</strong> privacy@biznespilot.uz</p>
          <p><strong>Telefon:</strong> +998 71 200 00 00</p>
          <p><strong>Manzil:</strong> Toshkent shahri, O'zbekiston</p>
          <p class="mt-2 text-emerald-700 text-xs">
            So'rovlar 30 ish kuni ichida ko'rib chiqiladi va javob beriladi.
          </p>
        </div>
      </section>
    </div>

    <template #footer>
      <button
        type="button"
        class="px-6 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-700 transition-colors"
        @click="$emit('close')"
      >
        Tushundim
      </button>
    </template>
  </Modal>
</template>

<script setup>
import { computed } from 'vue';
import Modal from '@/components/Modal.vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'close']);

const isOpen = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const currentDate = computed(() => {
  const date = new Date();
  const months = [
    'Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun',
    'Iyul', 'Avgust', 'Sentyabr', 'Oktyabr', 'Noyabr', 'Dekabr'
  ];
  return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
});
</script>
