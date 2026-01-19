<!-- Testimonials Section -->
<section class="py-24 lg:py-32 relative overflow-hidden">
    <!-- Background with gradient -->
    <div class="absolute inset-0 bg-gradient-to-b from-gray-50 via-white to-gray-50"></div>

    <!-- Decorative background elements -->
    <div class="absolute inset-0 overflow-hidden">
        <!-- Large quote marks -->
        <svg class="absolute top-20 left-10 w-32 h-32 text-blue-100 opacity-50" viewBox="0 0 100 100" fill="currentColor">
            <path d="M30 40 L30 70 L10 70 L10 50 Q10 40 20 40 Z M70 40 L70 70 L50 70 L50 50 Q50 40 60 40 Z"/>
        </svg>
        <svg class="absolute bottom-20 right-10 w-32 h-32 text-violet-100 opacity-50 transform rotate-180" viewBox="0 0 100 100" fill="currentColor">
            <path d="M30 40 L30 70 L10 70 L10 50 Q10 40 20 40 Z M70 40 L70 70 L50 70 L50 50 Q50 40 60 40 Z"/>
        </svg>

        <!-- Floating circles -->
        <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-blue-100 rounded-full filter blur-3xl opacity-30"></div>
        <div class="absolute bottom-1/4 left-1/4 w-48 h-48 bg-violet-100 rounded-full filter blur-3xl opacity-30"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-20 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-100 to-orange-100 rounded-full text-sm font-bold text-yellow-800 mb-6">
                <?php echo e($translations['testimonials']['badge']); ?>

            </div>
            <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-6">
                <?php echo e($translations['testimonials']['title']); ?>

            </h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                <?php echo e($translations['testimonials']['subtitle']); ?>

            </p>
        </div>

        <!-- Testimonials grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $avatarStyles = [
                [
                    'gradient' => 'from-blue-500 to-indigo-600',
                    'icon' => '<svg viewBox="0 0 40 40" fill="none" class="w-full h-full p-2"><circle cx="20" cy="14" r="8" fill="white"/><path d="M8 36 C8 26 14 22 20 22 C26 22 32 26 32 36" fill="white" opacity="0.8"/></svg>',
                    'statColor' => 'bg-blue-500',
                ],
                [
                    'gradient' => 'from-emerald-500 to-teal-600',
                    'icon' => '<svg viewBox="0 0 40 40" fill="none" class="w-full h-full p-2"><circle cx="20" cy="14" r="8" fill="white"/><path d="M8 36 C8 26 14 22 20 22 C26 22 32 26 32 36" fill="white" opacity="0.8"/></svg>',
                    'statColor' => 'bg-emerald-500',
                ],
                [
                    'gradient' => 'from-violet-500 to-purple-600',
                    'icon' => '<svg viewBox="0 0 40 40" fill="none" class="w-full h-full p-2"><circle cx="20" cy="14" r="8" fill="white"/><path d="M8 36 C8 26 14 22 20 22 C26 22 32 26 32 36" fill="white" opacity="0.8"/></svg>',
                    'statColor' => 'bg-violet-500',
                ],
            ];
            ?>

            <?php $__currentLoopData = $translations['testimonials']['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $testimonial): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $style = $avatarStyles[$index % count($avatarStyles)]; ?>
                <div class="animate-on-scroll group" style="animation-delay: <?php echo e($index * 0.15); ?>s;">
                    <div class="relative h-full bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 border border-gray-100 hover:shadow-xl hover:shadow-gray-200/60 transition-all duration-500 hover:-translate-y-2">
                        <!-- Stat badge -->
                        <div class="absolute -top-3 right-6">
                            <div class="<?php echo e($style['statColor']); ?> text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                                <?php echo e($testimonial['stat'] ?? ''); ?>

                            </div>
                        </div>

                        <!-- Quote icon -->
                        <div class="absolute top-6 left-6">
                            <svg class="w-10 h-10 text-gray-100" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                            </svg>
                        </div>

                        <!-- Stars with animation -->
                        <div class="flex space-x-1 mb-6 mt-4">
                            <?php for($i = 0; $i < 5; $i++): ?>
                                <svg class="w-5 h-5 text-yellow-400 group-hover:scale-110 transition-transform" style="transition-delay: <?php echo e($i * 50); ?>ms;" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>

                        <!-- Quote -->
                        <blockquote class="text-gray-700 mb-8 leading-relaxed text-lg relative z-10">
                            "<?php echo e($testimonial['quote']); ?>"
                        </blockquote>

                        <!-- Divider -->
                        <div class="h-px bg-gradient-to-r from-transparent via-gray-200 to-transparent mb-6"></div>

                        <!-- Author -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <!-- SVG Avatar -->
                                <div class="relative">
                                    <div class="w-14 h-14 bg-gradient-to-br <?php echo e($style['gradient']); ?> rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <?php echo $style['icon']; ?>

                                    </div>
                                    <!-- Online indicator -->
                                    <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full border-3 border-white flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="font-bold text-gray-900"><?php echo e($testimonial['author']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo e($testimonial['role']); ?></div>
                                </div>
                            </div>

                            <!-- Company size -->
                            <div class="hidden sm:block text-right">
                                <div class="text-xs text-gray-400"><?php echo e($testimonial['company_size'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- Stats Summary bar -->
        <div class="mt-20 animate-on-scroll">
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-violet-600 rounded-3xl p-8 sm:p-12 shadow-2xl shadow-indigo-500/25">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                    <?php
                    $stats = $translations['testimonials']['stats_summary'] ?? [
                        ['value' => '500+', 'label' => $locale === 'ru' ? 'Активных бизнесов' : 'Faol bizneslar'],
                        ['value' => '60%', 'label' => $locale === 'ru' ? 'Средняя экономия времени' : "O'rtacha vaqt tejash"],
                        ['value' => '$2M+', 'label' => $locale === 'ru' ? 'Обработанных транзакций' : 'Qayta ishlangan tranzaksiya'],
                        ['value' => '4.9/5', 'label' => $locale === 'ru' ? 'Рейтинг клиентов' : 'Mijozlar reytingi'],
                    ];

                    $icons = [
                        '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>',
                        '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                        '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>',
                    ];
                    ?>

                    <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="text-center" style="animation-delay: <?php echo e($index * 0.1); ?>s;">
                            <div class="inline-flex items-center justify-center w-14 h-14 bg-white/10 rounded-2xl text-white mb-4">
                                <?php echo $icons[$index] ?? $icons[0]; ?>

                            </div>
                            <div class="text-3xl sm:text-4xl font-black text-white mb-2"><?php echo e($stat['value']); ?></div>
                            <div class="text-blue-100 text-sm"><?php echo e($stat['label']); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php /**PATH D:\marketing startap\biznespilot\resources\views/landing/partials/testimonials.blade.php ENDPATH**/ ?>