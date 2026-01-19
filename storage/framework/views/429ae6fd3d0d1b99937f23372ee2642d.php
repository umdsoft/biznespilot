<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('landing.partials.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <main class="pt-24 pb-16 min-h-screen gradient-bg">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Foydalanish shartlari</h1>
                <p class="text-gray-600">Oxirgi yangilanish: <?php echo e(now()->format('d.m.Y')); ?></p>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 space-y-8">
                <!-- Introduction -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">1</span>
                        Kirish
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Ushbu Foydalanish shartlari (keyingi o'rinlarda "Shartlar") BiznesPilot AI platformasi
                        (keyingi o'rinlarda "Platforma", "Xizmat" yoki "Biz") bilan Siz (keyingi o'rinlarda "Foydalanuvchi" yoki "Siz")
                        o'rtasidagi huquqiy munosabatlarni tartibga soladi.
                    </p>
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <p class="text-amber-800 text-sm">
                            <strong>Muhim:</strong> Platformadan foydalanishni boshlash orqali Siz ushbu Shartlarni to'liq o'qib chiqqaningizni va
                            ularga rozilik bildirishingizni tasdiqlaysiz.
                        </p>
                    </div>
                </section>

                <!-- Service Description -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">2</span>
                        Xizmat tavsifi
                    </h2>
                    <p class="text-gray-700 mb-4">BiznesPilot AI quyidagi xizmatlarni taqdim etadi:</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <?php $__currentLoopData = [
                            "Sun'iy intellekt asosida marketing strategiyalari",
                            "Mijozlar bilan munosabatlarni boshqarish (CRM)",
                            "Sotuvlarni kuzatish va tahlil qilish",
                            "Raqobatchilar tahlili va bozor tadqiqotlari",
                            "Chatbot va avtomatlashtirish yechimlari",
                            "Ijtimoiy tarmoqlar integratsiyasi",
                            "Marketing kampaniyalarini boshqarish",
                            "Biznes tahlillari va hisobotlar"
                        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <svg class="w-5 h-5 text-violet-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-gray-700 text-sm"><?php echo e($service); ?></span>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </section>

                <!-- Account Registration -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">3</span>
                        Akkaunt va ro'yxatdan o'tish
                    </h2>

                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-xl p-5">
                            <h3 class="font-semibold text-gray-900 mb-3">3.1. Platformadan foydalanish uchun Siz:</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2"></span>
                                    Kamida 18 yoshda bo'lishingiz kerak
                                </li>
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2"></span>
                                    To'g'ri va aniq ma'lumotlarni taqdim etishingiz shart
                                </li>
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2"></span>
                                    Akkauntingiz xavfsizligini ta'minlashingiz lozim
                                </li>
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-violet-500 rounded-full mr-3 mt-2"></span>
                                    Parolingizni maxfiy saqlashingiz kerak
                                </li>
                            </ul>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-5">
                            <h3 class="font-semibold text-gray-900 mb-3">3.2. Siz quyidagilarga mas'ulsiz:</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2"></span>
                                    Akkauntingiz orqali amalga oshirilgan barcha harakatlar uchun
                                </li>
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2"></span>
                                    Akkauntga ruxsatsiz kirishni oldini olish uchun
                                </li>
                                <li class="flex items-start">
                                    <span class="w-2 h-2 bg-red-500 rounded-full mr-3 mt-2"></span>
                                    Har qanday xavfsizlik buzilishi haqida bizni zudlik bilan xabardor qilish uchun
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- User Obligations -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">4</span>
                        Foydalanuvchi majburiyatlari
                    </h2>
                    <p class="text-gray-700 mb-4">Platformadan foydalanishda Siz quyidagilarni bajarmaslikka rozilik bildirasiz:</p>

                    <div class="bg-red-50 rounded-xl p-5 border border-red-100">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <?php $__currentLoopData = [
                                "Noqonuniy yoki zararli maqsadlarda foydalanish",
                                "Boshqa foydalanuvchilarning huquqlarini buzish",
                                "Viruslar yoki zararli kodlarni tarqatish",
                                "Platformaning normal ishlashiga xalaqit berish",
                                "Ruxsatsiz ma'lumotlarga kirish",
                                "Spam yoki istalmagan xabarlar yuborish",
                                "Soxta ma'lumotlarni tarqatish",
                                "Intellektual mulk huquqlarini buzish"
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $obligation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-red-800 text-sm"><?php echo e($obligation); ?></span>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </section>

                <!-- Intellectual Property -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">5</span>
                        Intellektual mulk
                    </h2>

                    <div class="space-y-4">
                        <p class="text-gray-700">
                            <strong>5.1.</strong> Platforma va uning barcha tarkibiy qismlari (dasturiy ta'minot, dizayn,
                            logotiplar, AI modellari) BiznesPilot AI ning mutlaq mulki hisoblanadi va mualliflik huquqi bilan himoyalangan.
                        </p>
                        <p class="text-gray-700">
                            <strong>5.2.</strong> Siz Platformaga yuklagan ma'lumotlar ustidan to'liq huquqni saqlab qolasiz,
                            lekin bizga ularni xizmat ko'rsatish maqsadida ishlatish huquqini berasiz.
                        </p>
                    </div>
                </section>

                <!-- Service Availability -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">6</span>
                        Xizmat mavjudligi
                    </h2>

                    <p class="text-gray-700 mb-4">
                        Biz Platformaning uzluksiz ishlashini ta'minlashga harakat qilamiz, lekin quyidagi hollarda xizmat vaqtincha to'xtatilishi mumkin:
                    </p>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-amber-800 text-sm">Texnik xizmat ko'rsatish</span>
                        </div>
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span class="text-amber-800 text-sm">Tizimni yangilash</span>
                        </div>
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span class="text-amber-800 text-sm">Texnik nosozliklar</span>
                        </div>
                        <div class="flex items-center p-3 bg-amber-50 rounded-lg border border-amber-100">
                            <svg class="w-5 h-5 text-amber-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                            </svg>
                            <span class="text-amber-800 text-sm">Fors-major holatlari</span>
                        </div>
                    </div>

                    <p class="text-gray-600 text-sm mt-4">
                        Rejalashtirilgan texnik ishlar haqida kamida 24 soat oldin xabardor qilamiz.
                    </p>
                </section>

                <!-- Limitation of Liability -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">7</span>
                        Javobgarlikni cheklash
                    </h2>

                    <div class="bg-gray-50 rounded-xl p-5 space-y-3">
                        <p class="text-gray-700"><strong>7.1.</strong> BiznesPilot AI quyidagilar uchun javobgar emas:</p>
                        <ul class="space-y-2 text-gray-700 ml-4">
                            <li>• Foydalanuvchi harakatlari natijasida yuzaga kelgan zararlar</li>
                            <li>• Uchinchi tomon xizmatlari bilan bog'liq muammolar</li>
                            <li>• Internet ulanishi yoki qurilma nosozliklari</li>
                            <li>• Biznes qarorlari natijasida yuzaga kelgan yo'qotishlar</li>
                        </ul>
                        <p class="text-gray-700">
                            <strong>7.2.</strong> Bizning umumiy javobgarligimiz Siz tomonidan to'langan oxirgi 12 oylik
                            obuna summasi bilan cheklanadi.
                        </p>
                    </div>
                </section>

                <!-- Termination -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">8</span>
                        Shartlarni bekor qilish
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="bg-emerald-50 rounded-xl p-5 border border-emerald-100">
                            <h4 class="font-semibold text-emerald-800 mb-2">Sizning huquqingiz</h4>
                            <p class="text-emerald-700 text-sm">Siz istalgan vaqtda akkauntingizni o'chirishingiz mumkin.</p>
                        </div>

                        <div class="bg-red-50 rounded-xl p-5 border border-red-100">
                            <h4 class="font-semibold text-red-800 mb-2">Bizning huquqimiz</h4>
                            <p class="text-red-700 text-sm mb-2">Quyidagi hollarda akkauntingizni to'xtatishimiz mumkin:</p>
                            <ul class="text-red-700 text-sm space-y-1">
                                <li>• Shartlarni buzganingizda</li>
                                <li>• Noqonuniy faoliyat aniqlanganda</li>
                                <li>• To'lovlarni amalga oshirmaganda</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Dispute Resolution -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">9</span>
                        Nizolarni hal qilish
                    </h2>

                    <div class="space-y-3">
                        <p class="text-gray-700"><strong>9.1.</strong> Barcha nizolar avvalo muzokaralar yo'li bilan hal qilinadi.</p>
                        <p class="text-gray-700"><strong>9.2.</strong> Muzokaralar natija bermaganda, nizolar O'zbekiston Respublikasi qonunchiligiga muvofiq sud tartibida ko'rib chiqiladi.</p>
                        <p class="text-gray-700"><strong>9.3.</strong> Sud joylashuvi: Toshkent shahri.</p>
                    </div>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4 flex items-center">
                        <span class="w-8 h-8 bg-violet-100 text-violet-600 rounded-lg flex items-center justify-center text-sm font-bold mr-3">10</span>
                        Bog'lanish
                    </h2>

                    <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-6 border border-violet-100">
                        <p class="text-gray-700 mb-4">Savollar yoki takliflar bo'lsa, biz bilan bog'laning:</p>
                        <div class="space-y-2">
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <strong>Kompaniya:</strong>&nbsp; BiznesPilot AI
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <strong>Email:</strong>&nbsp; support@biznespilot.uz
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <strong>Telefon:</strong>&nbsp; +998 71 200 00 00
                            </p>
                            <p class="flex items-center text-gray-800">
                                <svg class="w-5 h-5 text-violet-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <strong>Ish vaqti:</strong>&nbsp; Dushanba - Juma, 09:00 - 18:00
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Final Note -->
                <div class="bg-gray-100 rounded-xl p-5 text-center">
                    <p class="text-gray-600 text-sm">
                        Ushbu Shartlar O'zbekiston Respublikasi qonunchiligiga bo'ysunadi va
                        Siz va BiznesPilot AI o'rtasidagi to'liq kelishuvni ifodalaydi.
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-8">
                <a href="<?php echo e(route('landing')); ?>" class="inline-flex items-center text-violet-600 hover:text-violet-700 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Bosh sahifaga qaytish
                </a>
            </div>
        </div>
    </main>

    <?php echo $__env->make('landing.partials.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('landing.layouts.landing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\marketing startap\biznespilot\resources\views/pages/terms.blade.php ENDPATH**/ ?>