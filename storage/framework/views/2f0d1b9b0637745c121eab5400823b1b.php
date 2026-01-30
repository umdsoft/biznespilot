<!-- CTA Section -->
<section class="py-24 lg:py-32 relative overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-600 to-violet-700"></div>

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
                <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-full text-sm font-bold shadow-lg">
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
<?php /**PATH D:\biznespilot\resources\views/landing/partials/cta.blade.php ENDPATH**/ ?>