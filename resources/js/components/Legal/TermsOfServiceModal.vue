<template>
  <Modal v-model="isOpen" title="Foydalanish shartlari" size="xl" @close="$emit('close')">
    <div class="max-h-[70vh] overflow-y-auto pr-2 text-gray-700 text-sm leading-relaxed space-y-6">
      <!-- Header -->
      <div class="text-center pb-4 border-b border-gray-200">
        <h2 class="text-xl font-bold text-gray-900">BiznesPilot AI Foydalanish shartlari</h2>
        <p class="text-gray-500 text-xs mt-1">Oxirgi yangilanish: {{ currentDate }}</p>
      </div>

      <!-- Introduction -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">1. Kirish</h3>
        <p class="mb-2">
          Ushbu Foydalanish shartlari (keyingi o'rinlarda "Shartlar") BiznesPilot AI platformasi
          (keyingi o'rinlarda "Platforma", "Xizmat" yoki "Biz") bilan Siz (keyingi o'rinlarda "Foydalanuvchi" yoki "Siz")
          o'rtasidagi huquqiy munosabatlarni tartibga soladi.
        </p>
        <p>
          Platformadan foydalanishni boshlash orqali Siz ushbu Shartlarni to'liq o'qib chiqqaningizni va
          ularga rozilik bildirishingizni tasdiqlaysiz. Agar Siz ushbu Shartlarga rozi bo'lmasangiz,
          iltimos, Platformadan foydalanmang.
        </p>
      </section>

      <!-- Service Description -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">2. Xizmat tavsifi</h3>
        <p class="mb-2">BiznesPilot AI quyidagi xizmatlarni taqdim etadi:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Sun'iy intellekt asosida marketing strategiyalarini ishlab chiqish</li>
          <li>Mijozlar bilan munosabatlarni boshqarish (CRM) tizimlari</li>
          <li>Sotuvlarni kuzatish va tahlil qilish</li>
          <li>Raqobatchilar tahlili va bozor tadqiqotlari</li>
          <li>Chatbot va avtomatlashtirish yechimlari</li>
          <li>Ijtimoiy tarmoqlar integratsiyasi (Instagram, Telegram, WhatsApp)</li>
          <li>Marketing kampaniyalarini boshqarish</li>
          <li>Biznes tahlillari va hisobotlar</li>
        </ul>
      </section>

      <!-- Account Registration -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">3. Akkaunt va ro'yxatdan o'tish</h3>
        <p class="mb-2"><strong>3.1.</strong> Platformadan foydalanish uchun Siz:</p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>Kamida 18 yoshda bo'lishingiz kerak</li>
          <li>To'g'ri va aniq ma'lumotlarni taqdim etishingiz shart</li>
          <li>Akkauntingiz xavfsizligini ta'minlashingiz lozim</li>
          <li>Parolingizni maxfiy saqlashingiz kerak</li>
        </ul>
        <p class="mb-2"><strong>3.2.</strong> Siz quyidagilarga mas'ulsiz:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Akkauntingiz orqali amalga oshirilgan barcha harakatlar uchun</li>
          <li>Akkauntga ruxsatsiz kirishni oldini olish uchun</li>
          <li>Har qanday xavfsizlik buzilishi haqida bizni zudlik bilan xabardor qilish uchun</li>
        </ul>
      </section>

      <!-- Subscription and Payment -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">4. Obuna va to'lovlar</h3>
        <p class="mb-2"><strong>4.1. Tarif rejalari:</strong></p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>Starter - 299,000 UZS/oy (asosiy funksiyalar)</li>
          <li>Basic - 499,000 UZS/oy (kengaytirilgan funksiyalar)</li>
          <li>Growth - 999,000 UZS/oy (professional funksiyalar)</li>
          <li>Scale - 2,490,000 UZS/oy (korporativ yechimlar)</li>
        </ul>
        <p class="mb-2"><strong>4.2. To'lov shartlari:</strong></p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>To'lovlar har oyning boshida avtomatik yechib olinadi</li>
          <li>Yillik to'lovda 2 oy bepul taqdim etiladi</li>
          <li>To'lovlar qaytarilmaydi, faqat maxsus holatlar bundan mustasno</li>
        </ul>
        <p><strong>4.3. Bepul sinov davri:</strong> Yangi foydalanuvchilarga 14 kunlik bepul sinov davri taqdim etiladi.</p>
      </section>

      <!-- User Obligations -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">5. Foydalanuvchi majburiyatlari</h3>
        <p class="mb-2">Platformadan foydalanishda Siz quyidagilarni bajarmaslikka rozilik bildirasiz:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Noqonuniy yoki zararli maqsadlarda foydalanish</li>
          <li>Boshqa foydalanuvchilarning huquqlarini buzish</li>
          <li>Viruslar yoki zararli kodlarni tarqatish</li>
          <li>Platformaning normal ishlashiga xalaqit berish</li>
          <li>Ruxsatsiz ma'lumotlarga kirish yoki ularni o'zgartirish</li>
          <li>Spam yoki istalmagan xabarlar yuborish</li>
          <li>Soxta yoki noto'g'ri ma'lumotlarni tarqatish</li>
          <li>Uchinchi shaxslarning intellektual mulk huquqlarini buzish</li>
        </ul>
      </section>

      <!-- Intellectual Property -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">6. Intellektual mulk</h3>
        <p class="mb-2"><strong>6.1.</strong> Platforma va uning barcha tarkibiy qismlari, jumladan:</p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>Dasturiy ta'minot kodi</li>
          <li>Dizayn va foydalanuvchi interfeysi</li>
          <li>Logotiplar va savdo belgilari</li>
          <li>AI modellari va algoritmlar</li>
        </ul>
        <p class="mb-2">BiznesPilot AI ning mutlaq mulki hisoblanadi va mualliflik huquqi bilan himoyalangan.</p>
        <p><strong>6.2.</strong> Siz Platformaga yuklagan ma'lumotlar ustidan to'liq huquqni saqlab qolasiz,
        lekin bizga ularni xizmat ko'rsatish maqsadida ishlatish huquqini berasiz.</p>
      </section>

      <!-- Data and Privacy -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">7. Ma'lumotlar va maxfiylik</h3>
        <p class="mb-2">
          Shaxsiy ma'lumotlaringizni qayta ishlash Maxfiylik siyosatimizga muvofiq amalga oshiriladi.
          Platformadan foydalanish orqali Siz:
        </p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Ma'lumotlaringizni to'plash va qayta ishlashga rozilik bildirasiz</li>
          <li>Xizmat sifatini yaxshilash uchun analitik ma'lumotlardan foydalanishga ruxsat berasiz</li>
          <li>AI modellarini o'qitish uchun anonimizatsiya qilingan ma'lumotlardan foydalanishga rozilik bildirasiz</li>
        </ul>
      </section>

      <!-- Service Availability -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">8. Xizmat mavjudligi</h3>
        <p class="mb-2"><strong>8.1.</strong> Biz Platformaning uzluksiz ishlashini ta'minlashga harakat qilamiz,
        lekin quyidagi hollarda xizmat vaqtincha to'xtatilishi mumkin:</p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>Texnik xizmat ko'rsatish ishlari</li>
          <li>Tizimni yangilash</li>
          <li>Kutilmagan texnik nosozliklar</li>
          <li>Fors-major holatlari</li>
        </ul>
        <p><strong>8.2.</strong> Rejalashtirilgan texnik ishlar haqida kamida 24 soat oldin xabardor qilamiz.</p>
      </section>

      <!-- Limitation of Liability -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">9. Javobgarlikni cheklash</h3>
        <p class="mb-2"><strong>9.1.</strong> BiznesPilot AI quyidagilar uchun javobgar emas:</p>
        <ul class="list-disc pl-5 space-y-1 mb-3">
          <li>Foydalanuvchi harakatlari natijasida yuzaga kelgan zararlar</li>
          <li>Uchinchi tomon xizmatlari bilan bog'liq muammolar</li>
          <li>Internet ulanishi yoki qurilma nosozliklari</li>
          <li>Foydalanuvchi tomonidan noto'g'ri kiritilgan ma'lumotlar</li>
          <li>Biznes qarorlari natijasida yuzaga kelgan yo'qotishlar</li>
        </ul>
        <p><strong>9.2.</strong> Bizning umumiy javobgarligimiz Siz tomonidan to'langan oxirgi 12 oylik
        obuna summasi bilan cheklanadi.</p>
      </section>

      <!-- Termination -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">10. Shartlarni bekor qilish</h3>
        <p class="mb-2"><strong>10.1.</strong> Siz istalgan vaqtda akkauntingizni o'chirishingiz mumkin.</p>
        <p class="mb-2"><strong>10.2.</strong> Biz quyidagi hollarda akkauntingizni to'xtatib qo'yish yoki
        o'chirish huquqini saqlab qolamiz:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Ushbu Shartlarni buzganingizda</li>
          <li>Noqonuniy faoliyat aniqlanganda</li>
          <li>Boshqa foydalanuvchilarga zarar yetkazganda</li>
          <li>To'lovlarni amalga oshirmaganda</li>
        </ul>
      </section>

      <!-- Dispute Resolution -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">11. Nizolarni hal qilish</h3>
        <p class="mb-2"><strong>11.1.</strong> Barcha nizolar avvalo muzokaralar yo'li bilan hal qilinadi.</p>
        <p class="mb-2"><strong>11.2.</strong> Muzokaralar natija bermaganda, nizolar O'zbekiston Respublikasi
        qonunchiligiga muvofiq sud tartibida ko'rib chiqiladi.</p>
        <p><strong>11.3.</strong> Sud joylashuvi: Toshkent shahri.</p>
      </section>

      <!-- Changes to Terms -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">12. Shartlarni o'zgartirish</h3>
        <p class="mb-2">
          Biz ushbu Shartlarni istalgan vaqtda o'zgartirish huquqini saqlab qolamiz. O'zgarishlar haqida
          Sizni quyidagi usullar orqali xabardor qilamiz:
        </p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Platformada e'lon qilish</li>
          <li>Elektron pochta orqali xabar yuborish</li>
          <li>Tizimga kirishda bildirishnoma ko'rsatish</li>
        </ul>
      </section>

      <!-- Contact Information -->
      <section>
        <h3 class="text-base font-semibold text-gray-900 mb-2">13. Bog'lanish ma'lumotlari</h3>
        <p class="mb-2">Savollar yoki takliflar bo'lsa, biz bilan bog'laning:</p>
        <div class="bg-gray-50 rounded-lg p-4">
          <p><strong>Kompaniya:</strong> BiznesPilot AI</p>
          <p><strong>Manzil:</strong> Toshkent shahri, O'zbekiston</p>
          <p><strong>Email:</strong> support@biznespilot.uz</p>
          <p><strong>Telefon:</strong> +998 71 200 00 00</p>
          <p><strong>Ish vaqti:</strong> Dushanba - Juma, 09:00 - 18:00</p>
        </div>
      </section>

      <!-- Final Provisions -->
      <section class="pb-4">
        <h3 class="text-base font-semibold text-gray-900 mb-2">14. Yakuniy qoidalar</h3>
        <p class="mb-2"><strong>14.1.</strong> Ushbu Shartlar O'zbekiston Respublikasi qonunchiligiga bo'ysunadi.</p>
        <p class="mb-2"><strong>14.2.</strong> Agar Shartlarning biror qismi haqiqiy emas deb topilsa,
        qolgan qismlar o'z kuchini saqlab qoladi.</p>
        <p><strong>14.3.</strong> Ushbu Shartlar Siz va BiznesPilot AI o'rtasidagi to'liq kelishuvni ifodalaydi.</p>
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
