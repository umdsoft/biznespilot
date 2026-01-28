<!-- CTA Section -->
<section class="py-24 lg:py-32 relative overflow-hidden">
    <!-- Animated gradient background -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700">
        <!-- Animated gradient overlay -->
        <div class="absolute inset-0 opacity-50">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-500/50 to-transparent animate-gradient-x"></div>
        </div>
    </div>

    <!-- Decorative SVG elements -->
    <div class="absolute inset-0 overflow-hidden">
        <svg class="absolute top-0 left-0 w-full h-full" viewBox="0 0 1440 600" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <!-- Floating circles -->
            <circle cx="100" cy="100" r="80" fill="white" opacity="0.05"/>
            <circle cx="1350" cy="500" r="120" fill="white" opacity="0.05"/>
            <circle cx="200" cy="450" r="60" fill="white" opacity="0.03"/>
            <circle cx="1200" cy="150" r="100" fill="white" opacity="0.04"/>
            <circle cx="700" cy="50" r="40" fill="white" opacity="0.06"/>
            <circle cx="900" cy="550" r="50" fill="white" opacity="0.04"/>

            <!-- Wave pattern -->
            <path d="M0 400 Q360 350 720 400 T1440 350 V600 H0 Z" fill="white" opacity="0.03"/>
            <path d="M0 450 Q360 400 720 450 T1440 400 V600 H0 Z" fill="white" opacity="0.02"/>

            <!-- Sparkle elements -->
            <g fill="white" opacity="0.3">
                <path d="M150 200 L152 195 L154 200 L159 202 L154 204 L152 209 L150 204 L145 202 Z"/>
                <path d="M1300 100 L1302 95 L1304 100 L1309 102 L1304 104 L1302 109 L1300 104 L1295 102 Z"/>
                <path d="M400 500 L402 495 L404 500 L409 502 L404 504 L402 509 L400 504 L395 502 Z"/>
                <path d="M1100 400 L1102 395 L1104 400 L1109 402 L1104 404 L1102 409 L1100 404 L1095 402 Z"/>
                <path d="M800 150 L802 145 L804 150 L809 152 L804 154 L802 159 L800 154 L795 152 Z"/>
            </g>
        </svg>
    </div>

    <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="animate-on-scroll">
            <!-- Badge -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center px-5 py-2.5 bg-yellow-400 text-yellow-900 rounded-full text-sm font-bold shadow-lg">
                    <?php echo e($translations['cta']['badge']); ?>

                </div>
            </div>

            <!-- Title -->
            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white mb-6 leading-tight text-center">
                <?php echo e($translations['cta']['title']); ?>

            </h2>

            <!-- Subtitle -->
            <p class="text-xl sm:text-2xl text-blue-100 mb-8 max-w-2xl mx-auto leading-relaxed text-center">
                <?php echo e($translations['cta']['subtitle']); ?>

            </p>

            <!-- Features Grid -->
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10 max-w-3xl mx-auto">
                <?php $__currentLoopData = $translations['cta']['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex items-center justify-center sm:justify-start gap-2 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 text-white/90 text-sm font-medium">
                        <?php echo e($feature); ?>

                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <!-- CTA Button - Main -->
            <div class="text-center mb-8">
                <a href="<?php echo e(route('register')); ?>"
                   class="group inline-flex items-center justify-center px-12 py-6 bg-white text-blue-600 rounded-2xl font-bold text-xl shadow-2xl hover:shadow-white/25 transition-all duration-300 hover:-translate-y-2 hover:scale-105">
                    <?php echo e($translations['cta']['button']); ?>

                </a>
            </div>

            <!-- Urgency Message -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-full text-sm font-bold shadow-lg animate-pulse">
                    <?php echo e($translations['cta']['urgency']); ?>

                </div>
            </div>

            <!-- Social Proof -->
            <p class="text-center text-white/80 text-sm">
                <?php echo e($translations['cta']['note']); ?>

            </p>

            <!-- Trust indicators -->
            <div class="flex flex-wrap items-center justify-center gap-6 text-white/70 text-sm mt-8">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo e($locale === 'ru' ? 'Без кредитной карты' : "Kredit karta kerak emas"); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo e($locale === 'ru' ? 'Настройка за 10 минут' : "10 daqiqada sozlash"); ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php echo e($locale === 'ru' ? 'Отмена в любое время' : "Istalgan vaqtda bekor qilish"); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php /**PATH D:\marketing startap\biznespilot\resources\views\landing\partials\cta.blade.php ENDPATH**/ ?>