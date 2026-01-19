<!-- Header / Navigation -->
<header id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            <!-- Logo -->
            <a href="<?php echo e(route('landing')); ?>" class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold gradient-text">BiznesPilot</span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden lg:flex items-center space-x-8">
                <a href="#features" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    <?php echo e($translations['nav']['features']); ?>

                </a>
                <a href="#how-it-works" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    <?php echo e($translations['nav']['how_it_works']); ?>

                </a>
                <a href="#benefits" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    <?php echo e($translations['nav']['benefits']); ?>

                </a>
                <a href="#faq" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    <?php echo e($translations['nav']['faq']); ?>

                </a>
            </nav>

            <!-- Right side actions -->
            <div class="hidden lg:flex items-center space-x-4">
                <!-- Language Switcher with Flags -->
                <div class="flex items-center space-x-1 bg-gray-100 rounded-full p-1">
                    <a href="<?php echo e(route('landing.language', 'uz')); ?>"
                       class="flex items-center justify-center w-9 h-9 rounded-full transition-all <?php echo e($locale === 'uz' ? 'bg-white shadow-sm ring-2 ring-blue-500' : 'hover:bg-gray-200'); ?>"
                       title="O'zbekcha">
                        
                        <svg class="w-6 h-6 rounded-full shadow-sm" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                            <mask id="uzMask"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                            <g mask="url(#uzMask)">
                                <path fill="#0099b5" d="M0 0h512v167H0z"/>
                                <path fill="#fff" d="M0 178h512v156H0z"/>
                                <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                                <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                                <circle cx="163" cy="89" r="45" fill="#fff"/>
                                <circle cx="176" cy="89" r="40" fill="#0099b5"/>
                                <g fill="#fff">
                                    <path d="m224 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m272 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m320 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m368 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m416 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m248 89 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m296 89 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m344 89 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m392 89 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m272 125 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m320 125 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m368 125 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                </g>
                            </g>
                        </svg>
                    </a>
                    <a href="<?php echo e(route('landing.language', 'ru')); ?>"
                       class="flex items-center justify-center w-9 h-9 rounded-full transition-all <?php echo e($locale === 'ru' ? 'bg-white shadow-sm ring-2 ring-blue-500' : 'hover:bg-gray-200'); ?>"
                       title="Русский">
                        
                        <svg class="w-6 h-6 rounded-full shadow-sm" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                            <mask id="ruMask"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                            <g mask="url(#ruMask)">
                                <path fill="#fff" d="M0 0h512v170.7H0z"/>
                                <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                                <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
                            </g>
                        </svg>
                    </a>
                </div>

                <!-- Login Button -->
                <a href="<?php echo e(route('login')); ?>" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">
                    <?php echo e($translations['nav']['login']); ?>

                </a>

                <!-- CTA Button -->
                <a href="<?php echo e(route('register')); ?>"
                   class="relative group bg-gradient-to-r from-blue-600 to-indigo-600 text-white px-5 py-2.5 rounded-xl font-bold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 hover:-translate-y-0.5">
                    <span class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-violet-600 rounded-xl blur opacity-30 group-hover:opacity-50 transition-opacity"></span>
                    <span class="relative"><?php echo e($translations['nav']['get_started']); ?></span>
                </a>
            </div>

            <!-- Mobile menu button -->
            <button onclick="toggleMobileMenu()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="mobile-menu lg:hidden">
            <div class="py-4 space-y-2 border-t border-gray-100">
                <a href="#features" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <?php echo e($translations['nav']['features']); ?>

                </a>
                <a href="#how-it-works" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <?php echo e($translations['nav']['how_it_works']); ?>

                </a>
                <a href="#benefits" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <?php echo e($translations['nav']['benefits']); ?>

                </a>
                <a href="#faq" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                    <?php echo e($translations['nav']['faq']); ?>

                </a>

                <!-- Language switcher mobile with Flags -->
                <div class="px-4 py-2 flex items-center justify-center space-x-3">
                    <a href="<?php echo e(route('landing.language', 'uz')); ?>"
                       class="flex items-center justify-center w-12 h-12 rounded-xl transition-all <?php echo e($locale === 'uz' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-100 hover:bg-gray-200'); ?>"
                       title="O'zbekcha">
                        <svg class="w-8 h-8 rounded-full shadow-sm" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                            <mask id="uzMaskM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                            <g mask="url(#uzMaskM)">
                                <path fill="#0099b5" d="M0 0h512v167H0z"/>
                                <path fill="#fff" d="M0 178h512v156H0z"/>
                                <path fill="#1eb53a" d="M0 345h512v167H0z"/>
                                <path fill="#ce1126" d="M0 167h512v11H0zM0 334h512v11H0z"/>
                                <circle cx="163" cy="89" r="45" fill="#fff"/>
                                <circle cx="176" cy="89" r="40" fill="#0099b5"/>
                                <g fill="#fff">
                                    <path d="m224 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m272 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m320 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m368 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                    <path d="m416 53 4 12h13l-11 7 4 12-10-8-11 8 5-12-11-7h13z"/>
                                </g>
                            </g>
                        </svg>
                    </a>
                    <a href="<?php echo e(route('landing.language', 'ru')); ?>"
                       class="flex items-center justify-center w-12 h-12 rounded-xl transition-all <?php echo e($locale === 'ru' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-100 hover:bg-gray-200'); ?>"
                       title="Русский">
                        <svg class="w-8 h-8 rounded-full shadow-sm" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                            <mask id="ruMaskM"><circle cx="256" cy="256" r="256" fill="#fff"/></mask>
                            <g mask="url(#ruMaskM)">
                                <path fill="#fff" d="M0 0h512v170.7H0z"/>
                                <path fill="#0039a6" d="M0 170.7h512v170.6H0z"/>
                                <path fill="#d52b1e" d="M0 341.3h512V512H0z"/>
                            </g>
                        </svg>
                    </a>
                </div>

                <div class="pt-4 space-y-2 border-t border-gray-100">
                    <a href="<?php echo e(route('login')); ?>" class="block px-4 py-2 text-gray-600 hover:bg-gray-50 rounded-lg">
                        <?php echo e($translations['nav']['login']); ?>

                    </a>
                    <a href="<?php echo e(route('register')); ?>"
                       class="block mx-4 text-center bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-2.5 rounded-lg font-semibold">
                        <?php echo e($translations['nav']['get_started']); ?>

                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
<?php /**PATH D:\biznespilot\resources\views\landing\partials\header.blade.php ENDPATH**/ ?>