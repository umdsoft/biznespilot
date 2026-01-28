<!-- How It Works Section -->
<section id="how-it-works" class="py-24 lg:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-b from-gray-50 to-white"></div>

    <!-- Decorative elements -->
    <div class="absolute left-0 top-1/4 w-72 h-72 bg-blue-100 rounded-full filter blur-3xl opacity-30"></div>
    <div class="absolute right-0 bottom-1/4 w-96 h-96 bg-violet-100 rounded-full filter blur-3xl opacity-30"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-20 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-100 to-violet-100 rounded-full text-sm font-semibold text-indigo-700 mb-6">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?php echo e($translations['how_it_works']['badge']); ?>

            </div>
            <h2 class="text-4xl sm:text-5xl font-black text-gray-900 mb-6">
                <?php echo e($translations['how_it_works']['title']); ?>

            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                <?php echo e($translations['how_it_works']['subtitle']); ?>

            </p>
        </div>

        <!-- Steps -->
        <div class="relative">
            <!-- Connection line for desktop -->
            <div class="hidden lg:block absolute top-32 left-[16%] right-[16%] h-1">
                <div class="h-full bg-gradient-to-r from-blue-200 via-indigo-300 to-violet-200 rounded-full"></div>
                <div class="absolute top-0 left-0 h-full w-1/3 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full animate-pulse"></div>
            </div>

            <div class="grid lg:grid-cols-3 gap-12 lg:gap-8">
                <?php $__currentLoopData = $translations['how_it_works']['steps']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="animate-on-scroll relative" style="animation-delay: <?php echo e($index * 0.2); ?>s;">
                        <div class="text-center">
                            <!-- Step illustration -->
                            <div class="relative inline-block mb-8">
                                <?php if($index === 0): ?>
                                    <!-- Step 1: Connect all business processes -->
                                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-48 mx-auto">
                                        <circle cx="100" cy="100" r="90" fill="url(#step1Bg)" opacity="0.1"/>
                                        <!-- Main hub -->
                                        <circle cx="100" cy="100" r="40" fill="white" filter="url(#stepShadow)"/>
                                        <circle cx="100" cy="100" r="30" fill="url(#step1Grad)"/>
                                        <text x="100" y="106" text-anchor="middle" fill="white" font-size="12" font-weight="bold">BiznesPilot</text>

                                        <!-- Module icons around -->
                                        <g transform="translate(100, 35)">
                                            <circle cx="0" cy="0" r="18" fill="#3b82f6"/>
                                            <text x="0" y="5" text-anchor="middle" fill="white" font-size="10" font-weight="bold">M</text>
                                        </g>
                                        <g transform="translate(155, 100)">
                                            <circle cx="0" cy="0" r="18" fill="#10b981"/>
                                            <text x="0" y="5" text-anchor="middle" fill="white" font-size="10" font-weight="bold">S</text>
                                        </g>
                                        <g transform="translate(100, 165)">
                                            <circle cx="0" cy="0" r="18" fill="#8b5cf6"/>
                                            <text x="0" y="5" text-anchor="middle" fill="white" font-size="10" font-weight="bold">F</text>
                                        </g>
                                        <g transform="translate(45, 100)">
                                            <circle cx="0" cy="0" r="18" fill="#f59e0b"/>
                                            <text x="0" y="5" text-anchor="middle" fill="white" font-size="10" font-weight="bold">J</text>
                                        </g>

                                        <!-- Connectors -->
                                        <path d="M100 60 L100 70 M100 130 L100 140 M60 100 L70 100 M130 100 L140 100" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" stroke-dasharray="4 2"/>

                                        <defs>
                                            <linearGradient id="step1Bg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#3b82f6"/>
                                                <stop offset="100%" stop-color="#6366f1"/>
                                            </linearGradient>
                                            <linearGradient id="step1Grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#3b82f6"/>
                                                <stop offset="100%" stop-color="#6366f1"/>
                                            </linearGradient>
                                            <filter id="stepShadow" x="-20" y="-20" width="180" height="200">
                                                <feDropShadow dx="0" dy="4" stdDeviation="10" flood-color="#6366f1" flood-opacity="0.15"/>
                                            </filter>
                                        </defs>
                                    </svg>
                                <?php elseif($index === 1): ?>
                                    <!-- Step 2: AI configures everything -->
                                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-48 mx-auto">
                                        <circle cx="100" cy="100" r="90" fill="url(#step2Bg)" opacity="0.1"/>

                                        <!-- AI Brain with gears -->
                                        <ellipse cx="100" cy="90" rx="50" ry="45" fill="white" filter="url(#stepShadow2)"/>
                                        <ellipse cx="100" cy="90" rx="40" ry="35" fill="url(#step2Grad)"/>
                                        <text x="100" y="96" text-anchor="middle" fill="white" font-size="18" font-weight="bold">AI</text>

                                        <!-- Settings being configured -->
                                        <g transform="translate(55, 140)">
                                            <rect width="90" height="35" rx="8" fill="white" filter="url(#stepShadow2)"/>
                                            <circle cx="20" cy="17" r="10" fill="#22c55e"/>
                                            <path d="M15 17 L19 21 L25 13" stroke="white" stroke-width="2" fill="none" stroke-linecap="round"/>
                                            <rect x="38" y="10" width="40" height="6" rx="2" fill="#e2e8f0"/>
                                            <rect x="38" y="20" width="30" height="4" rx="2" fill="#dcfce7"/>
                                        </g>

                                        <!-- Sparkles and magic -->
                                        <g fill="#fbbf24">
                                            <path d="M45 55 L47 50 L49 55 L54 57 L49 59 L47 64 L45 59 L40 57 Z"/>
                                            <path d="M155 65 L157 60 L159 65 L164 67 L159 69 L157 74 L155 69 L150 67 Z"/>
                                            <path d="M150 115 L152 110 L154 115 L159 117 L154 119 L152 124 L150 119 L145 117 Z"/>
                                        </g>

                                        <!-- Small gears -->
                                        <circle cx="50" cy="90" r="8" stroke="#10b981" stroke-width="2" fill="none"/>
                                        <circle cx="150" cy="90" r="8" stroke="#10b981" stroke-width="2" fill="none"/>

                                        <defs>
                                            <linearGradient id="step2Bg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#10b981"/>
                                                <stop offset="100%" stop-color="#06b6d4"/>
                                            </linearGradient>
                                            <linearGradient id="step2Grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#10b981"/>
                                                <stop offset="100%" stop-color="#06b6d4"/>
                                            </linearGradient>
                                            <filter id="stepShadow2" x="-20" y="-20" width="180" height="180">
                                                <feDropShadow dx="0" dy="4" stdDeviation="10" flood-color="#10b981" flood-opacity="0.2"/>
                                            </filter>
                                        </defs>
                                    </svg>
                                <?php else: ?>
                                    <!-- Step 3: Unified Dashboard -->
                                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-48 mx-auto">
                                        <circle cx="100" cy="100" r="90" fill="url(#step3Bg)" opacity="0.1"/>

                                        <!-- Dashboard screen -->
                                        <rect x="35" y="40" width="130" height="120" rx="12" fill="white" filter="url(#stepShadow3)"/>

                                        <!-- Header -->
                                        <rect x="45" y="50" width="110" height="20" rx="6" fill="#f1f5f9"/>
                                        <circle cx="58" cy="60" r="6" fill="url(#step3Grad)"/>
                                        <rect x="70" y="56" width="40" height="4" rx="2" fill="#e2e8f0"/>
                                        <rect x="70" y="62" width="25" height="3" rx="1" fill="#f1f5f9"/>

                                        <!-- Three mini cards -->
                                        <rect x="45" y="78" width="32" height="28" rx="6" fill="#dbeafe"/>
                                        <text x="61" y="96" text-anchor="middle" fill="#3b82f6" font-size="8" font-weight="bold">M</text>

                                        <rect x="84" y="78" width="32" height="28" rx="6" fill="#dcfce7"/>
                                        <text x="100" y="96" text-anchor="middle" fill="#10b981" font-size="8" font-weight="bold">S</text>

                                        <rect x="123" y="78" width="32" height="28" rx="6" fill="#f3e8ff"/>
                                        <text x="139" y="96" text-anchor="middle" fill="#8b5cf6" font-size="8" font-weight="bold">F</text>

                                        <!-- Chart -->
                                        <rect x="45" y="112" width="110" height="40" rx="6" fill="#f8fafc"/>
                                        <path d="M55 138 Q75 130, 90 135 T120 125 T145 120" stroke="#3b82f6" stroke-width="2" fill="none" stroke-linecap="round"/>
                                        <path d="M55 142 Q75 138, 90 140 T120 130 T145 128" stroke="#10b981" stroke-width="2" fill="none" stroke-linecap="round"/>
                                        <path d="M55 136 Q75 132, 90 130 T120 122 T145 118" stroke="#8b5cf6" stroke-width="2" fill="none" stroke-linecap="round"/>

                                        <!-- Success badge -->
                                        <circle cx="155" cy="50" r="18" fill="#22c55e"/>
                                        <path d="M147 50 L153 56 L163 44" stroke="white" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>

                                        <defs>
                                            <linearGradient id="step3Bg" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#8b5cf6"/>
                                                <stop offset="100%" stop-color="#d946ef"/>
                                            </linearGradient>
                                            <linearGradient id="step3Grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#8b5cf6"/>
                                                <stop offset="100%" stop-color="#a855f7"/>
                                            </linearGradient>
                                            <filter id="stepShadow3" x="-20" y="-20" width="180" height="180">
                                                <feDropShadow dx="0" dy="4" stdDeviation="10" flood-color="#8b5cf6" flood-opacity="0.2"/>
                                            </filter>
                                        </defs>
                                    </svg>
                                <?php endif; ?>

                                <!-- Step number badge -->
                                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-12 h-12 bg-gradient-to-br <?php echo e($index === 0 ? 'from-blue-500 to-indigo-600' : ($index === 1 ? 'from-emerald-500 to-teal-600' : 'from-violet-500 to-purple-600')); ?> rounded-2xl flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    <?php echo e($step['number']); ?>

                                </div>
                            </div>

                            <!-- Content -->
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">
                                <?php echo e($step['title']); ?>

                            </h3>
                            <p class="text-gray-600 max-w-xs mx-auto leading-relaxed">
                                <?php echo e($step['description']); ?>

                            </p>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <!-- Guarantee Message -->
        <?php if(isset($translations['how_it_works']['guarantee'])): ?>
        <div class="text-center mt-12 animate-on-scroll">
            <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-green-800 font-semibold"><?php echo e($translations['how_it_works']['guarantee']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="text-center mt-10 animate-on-scroll">
            <a href="<?php echo e(route('register')); ?>"
               class="group inline-flex items-center px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-blue-500/25 hover:shadow-2xl hover:shadow-blue-500/30 transition-all duration-300 hover:-translate-y-1 hover:scale-105">
                <?php echo e($translations['nav']['get_started']); ?>

                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
            <p class="mt-4 text-sm text-gray-500">
                <?php echo e($locale === 'ru' ? 'Бесплатно • Без кредитной карты' : "Bepul • Kredit karta kerak emas"); ?>

            </p>
        </div>
    </div>
</section>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\landing\partials\how-it-works.blade.php ENDPATH**/ ?>