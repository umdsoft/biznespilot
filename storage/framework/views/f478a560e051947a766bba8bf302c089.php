<!-- FAQ Section -->
<section id="faq" class="py-24 lg:py-32 bg-white relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
        <!-- Grid pattern -->
        <svg class="absolute inset-0 w-full h-full opacity-[0.02]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="faqGrid" width="60" height="60" patternUnits="userSpaceOnUse">
                    <path d="M 60 0 L 0 0 0 60" fill="none" stroke="#6366f1" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#faqGrid)"/>
        </svg>

        <!-- Decorative circles -->
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-orange-100 rounded-full filter blur-3xl opacity-40"></div>
        <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-blue-100 rounded-full filter blur-3xl opacity-40"></div>

        <!-- Question mark decorations -->
        <svg class="absolute top-20 right-20 w-24 h-24 text-orange-100 opacity-50" viewBox="0 0 100 100" fill="currentColor">
            <path d="M50 10 C30 10 20 25 20 40 C20 50 25 55 35 60 L35 70 L45 70 L45 55 C45 50 35 48 35 40 C35 30 42 25 50 25 C58 25 65 30 65 40 L75 40 C75 25 70 10 50 10 Z M40 80 L40 90 L50 90 L50 80 Z"/>
        </svg>
        <svg class="absolute bottom-32 left-16 w-16 h-16 text-blue-100 opacity-50 transform rotate-12" viewBox="0 0 100 100" fill="currentColor">
            <path d="M50 10 C30 10 20 25 20 40 C20 50 25 55 35 60 L35 70 L45 70 L45 55 C45 50 35 48 35 40 C35 30 42 25 50 25 C58 25 65 30 65 40 L75 40 C75 25 70 10 50 10 Z M40 80 L40 90 L50 90 L50 80 Z"/>
        </svg>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-orange-100 to-amber-100 rounded-full text-sm font-semibold text-orange-700 mb-6">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo e($translations['faq']['badge']); ?>

            </div>
            <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-6">
                <?php echo e($translations['faq']['title']); ?>

            </h2>
            <p class="text-xl text-gray-600">
                <?php echo e($translations['faq']['subtitle']); ?>

            </p>
        </div>

        <!-- FAQ Items with visual enhancements -->
        <div class="space-y-4">
            <?php
            $faqIcons = [
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>',
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>',
                '<svg viewBox="0 0 24 24" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            ];
            $faqStyles = [
                ['iconBg' => 'bg-blue-100', 'iconText' => 'text-blue-600', 'hoverBorder' => 'hover:border-blue-200', 'hoverShadow' => 'hover:shadow-blue-100/50', 'hoverIconBg' => 'group-hover:bg-blue-100', 'hoverIconText' => 'group-hover:text-blue-600'],
                ['iconBg' => 'bg-emerald-100', 'iconText' => 'text-emerald-600', 'hoverBorder' => 'hover:border-emerald-200', 'hoverShadow' => 'hover:shadow-emerald-100/50', 'hoverIconBg' => 'group-hover:bg-emerald-100', 'hoverIconText' => 'group-hover:text-emerald-600'],
                ['iconBg' => 'bg-violet-100', 'iconText' => 'text-violet-600', 'hoverBorder' => 'hover:border-violet-200', 'hoverShadow' => 'hover:shadow-violet-100/50', 'hoverIconBg' => 'group-hover:bg-violet-100', 'hoverIconText' => 'group-hover:text-violet-600'],
                ['iconBg' => 'bg-orange-100', 'iconText' => 'text-orange-600', 'hoverBorder' => 'hover:border-orange-200', 'hoverShadow' => 'hover:shadow-orange-100/50', 'hoverIconBg' => 'group-hover:bg-orange-100', 'hoverIconText' => 'group-hover:text-orange-600'],
                ['iconBg' => 'bg-pink-100', 'iconText' => 'text-pink-600', 'hoverBorder' => 'hover:border-pink-200', 'hoverShadow' => 'hover:shadow-pink-100/50', 'hoverIconBg' => 'group-hover:bg-pink-100', 'hoverIconText' => 'group-hover:text-pink-600'],
                ['iconBg' => 'bg-cyan-100', 'iconText' => 'text-cyan-600', 'hoverBorder' => 'hover:border-cyan-200', 'hoverShadow' => 'hover:shadow-cyan-100/50', 'hoverIconBg' => 'group-hover:bg-cyan-100', 'hoverIconText' => 'group-hover:text-cyan-600'],
            ];
            ?>

            <?php $__currentLoopData = $translations['faq']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $style = $faqStyles[$index % count($faqStyles)]; ?>
                <div class="animate-on-scroll faq-item group" style="animation-delay: <?php echo e($index * 0.1); ?>s;">
                    <div class="bg-gradient-to-r from-gray-50 to-white rounded-2xl overflow-hidden border border-gray-100 <?php echo e($style['hoverBorder']); ?> hover:shadow-lg <?php echo e($style['hoverShadow']); ?> transition-all duration-300">
                        <!-- Question -->
                        <button onclick="toggleFaq(this)" class="w-full flex items-center p-6 text-left">
                            <!-- Icon -->
                            <div class="flex-shrink-0 w-12 h-12 <?php echo e($style['iconBg']); ?> rounded-xl flex items-center justify-center <?php echo e($style['iconText']); ?> mr-4 group-hover:scale-110 transition-transform duration-300">
                                <?php echo $faqIcons[$index % count($faqIcons)]; ?>

                            </div>

                            <span class="flex-1 text-lg font-bold text-gray-900 pr-4">
                                <?php echo e($faq['question']); ?>

                            </span>

                            <span class="faq-icon flex-shrink-0 w-10 h-10 bg-gray-100 <?php echo e($style['hoverIconBg']); ?> rounded-xl flex items-center justify-center transition-all duration-300">
                                <svg class="w-5 h-5 text-gray-500 <?php echo e($style['hoverIconText']); ?> transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>

                        <!-- Answer -->
                        <div class="faq-answer">
                            <div class="px-6 pb-6 pl-[88px] text-gray-600 leading-relaxed">
                                <?php echo e($faq['answer']); ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- Contact CTA -->
        <div class="mt-16 text-center animate-on-scroll">
            <div class="inline-flex flex-col sm:flex-row items-center gap-4 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="text-gray-900 font-bold"><?php echo e($locale === 'ru' ? 'Остались вопросы?' : "Savollaringiz bormi?"); ?></p>
                        <p class="text-sm text-gray-600"><?php echo e($locale === 'ru' ? 'Мы всегда рады помочь' : "Biz yordam berishga tayyormiz"); ?></p>
                    </div>
                </div>
                <a href="https://t.me/biznespilot" target="_blank" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/>
                    </svg>
                    <?php echo e($locale === 'ru' ? 'Написать в Telegram' : "Telegramga yozish"); ?>

                </a>
            </div>
        </div>
    </div>
</section>
<?php /**PATH D:\biznespilot\resources\views/landing/partials/faq.blade.php ENDPATH**/ ?>