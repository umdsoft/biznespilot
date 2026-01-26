
<?php
    $plans = $translations['pricing']['plans'];
    $isUz = $locale !== 'ru';

    // Define all features grouped by category (5 plans: Free, Start, Standard, Business, Premium)
    $categories = [
        [
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>',
            'name' => $isUz ? 'ASOSIY IMKONIYATLAR' : 'ОСНОВНЫЕ ВОЗМОЖНОСТИ',
            'color' => 'blue',
            'features' => [
                [
                    'name' => $isUz ? 'Oylik to\'lov' : 'Ежемесячная плата',
                    'values' => [$isUz ? 'Bepul' : 'Бесплатно', '299,000', '599,000', '799,000', '1,499,000'],
                    'type' => 'price'
                ],
                [
                    'name' => $isUz ? 'Foydalanuvchilar soni' : 'Количество пользователей',
                    'values' => [$isUz ? '1 xodim' : '1 сотр.', $isUz ? '2 xodim' : '2 сотр.', $isUz ? '5 xodim' : '5 сотр.', $isUz ? '10 xodim' : '10 сотр.', $isUz ? '15 xodim' : '15 сотр.'],
                    'type' => 'text'
                ],
                [
                    'name' => $isUz ? 'Filiallar soni' : 'Количество филиалов',
                    'values' => ['1', '1', '1', '2', '5'],
                    'type' => 'text'
                ],
                [
                    'name' => 'Instagram + Telegram',
                    'values' => [$isUz ? 'Faqat Telegram' : 'Только Telegram', $isUz ? '1 tadan' : 'По 1', $isUz ? '1 tadan' : 'По 1', $isUz ? '2 tadan' : 'По 2', $isUz ? '5 tadan' : 'По 5'],
                    'type' => 'text'
                ],
            ]
        ],
        [
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>',
            'name' => $isUz ? 'CHATBOT & SOTUV' : 'ЧАТБОТ & ПРОДАЖИ',
            'color' => 'emerald',
            'features' => [
                [
                    'name' => 'Flow Builder',
                    'values' => [
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'yes', 'text' => $isUz ? 'Vizual' : 'Визуальный'],
                        ['type' => 'yes', 'text' => 'Pro'],
                        ['type' => 'premium', 'text' => 'AI Bot']
                    ],
                    'type' => 'mixed'
                ],
                [
                    'name' => $isUz ? 'Avto-javob' : 'Авто-ответ',
                    'values' => [false, false, true, true, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => $isUz ? 'Operatorga uzatish' : 'Передача оператору',
                    'values' => [false, false, true, true, true],
                    'type' => 'boolean'
                ],
            ]
        ],
        [
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
            'name' => $isUz ? 'MARKETING & CRM' : 'МАРКЕТИНГ & CRM',
            'color' => 'violet',
            'features' => [
                [
                    'name' => $isUz ? 'Lidlar bazasi (CRM)' : 'База лидов (CRM)',
                    'values' => [true, true, true, true, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => $isUz ? 'Umumiy lidlar soni' : 'Всего лидов',
                    'values' => ['100', '500', '5,000', '50,000', $isUz ? 'Cheksiz' : 'Безлимит'],
                    'type' => 'text'
                ],
                [
                    'name' => 'Marketing ROI',
                    'values' => [false, true, true, true, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => 'Cross-Posting',
                    'values' => [false, false, true, true, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => $isUz ? 'Excel export' : 'Экспорт в Excel',
                    'values' => [false, false, false, true, true],
                    'type' => 'boolean'
                ],
            ]
        ],
        [
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
            'name' => $isUz ? 'HR & BOSHQARUV' : 'HR & УПРАВЛЕНИЕ',
            'color' => 'amber',
            'features' => [
                [
                    'name' => $isUz ? 'Vazifalar (Tasks)' : 'Задачи (Tasks)',
                    'values' => [false, true, true, true, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => $isUz ? 'HR Bot (Ishga olish)' : 'HR Бот (Найм)',
                    'values' => [
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'yes', 'text' => $isUz ? 'Asosiy' : 'Базовый'],
                        ['type' => 'premium', 'text' => $isUz ? 'Avto-suhbat' : 'Авто-интервью']
                    ],
                    'type' => 'mixed'
                ],
                [
                    'name' => $isUz ? 'Xodim KPI tahlili' : 'KPI анализ сотрудников',
                    'values' => [false, false, false, false, true],
                    'type' => 'boolean'
                ],
                [
                    'name' => 'Anti-Fraud (SMS)',
                    'values' => [
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'no', 'text' => ''],
                        ['type' => 'premium', 'text' => $isUz ? 'Himoya' : 'Защита']
                    ],
                    'type' => 'mixed'
                ],
            ]
        ],
        [
            'icon' => '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>',
            'name' => 'CALL CENTER AI',
            'color' => 'rose',
            'features' => [
                [
                    'name' => $isUz ? 'Bepul tahlil (daqiqa)' : 'Бесплатный анализ (мин)',
                    'values' => ['-', '100', '250', '600', '1,200'],
                    'type' => 'text'
                ],
                [
                    'name' => $isUz ? 'Qo\'shimcha daqiqa narxi' : 'Цена доп. минуты',
                    'values' => ['-', $isUz ? '900 so\'m/daq' : '900 сум/мин', $isUz ? '700 so\'m/daq' : '700 сум/мин', $isUz ? '500 so\'m/daq' : '500 сум/мин', $isUz ? '350 so\'m/daq' : '350 сум/мин'],
                    'type' => 'text'
                ],
                [
                    'name' => $isUz ? 'Skript tekshiruvi' : 'Проверка скрипта',
                    'values' => [false, false, true, true, true],
                    'type' => 'boolean'
                ],
            ]
        ],
    ];

    $planHeaders = [
        ['name' => 'Free', 'color' => 'gray', 'gradient' => 'from-gray-400 to-gray-500', 'free' => true],
        ['name' => 'Start', 'color' => 'blue', 'gradient' => 'from-blue-500 to-blue-600'],
        ['name' => 'Standard', 'color' => 'emerald', 'gradient' => 'from-emerald-500 to-teal-600'],
        ['name' => 'Business', 'color' => 'emerald', 'gradient' => 'from-emerald-500 to-teal-600', 'best_value' => true],
        ['name' => 'Premium', 'color' => 'amber', 'gradient' => 'from-amber-500 to-orange-500'],
    ];
?>

<div class="overflow-x-auto">
    <table class="w-full border-separate border-spacing-0">
        
        <thead>
            <tr>
                <th class="text-left py-5 px-6 bg-gray-50/80 rounded-tl-xl border-b-2 border-gray-200">
                    <span class="text-gray-500 font-medium text-sm"><?php echo e($isUz ? 'Imkoniyatlar' : 'Возможности'); ?></span>
                </th>
                <?php $__currentLoopData = $planHeaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $isBestValue = $header['best_value'] ?? false;
                        $isFree = $header['free'] ?? false;
                    ?>
                    <th class="py-5 px-4 text-center relative <?php echo e($isBestValue ? '' : 'bg-gray-50/50 border-b-2 border-gray-200'); ?> <?php echo e($index === 4 ? 'rounded-tr-xl' : ''); ?>">
                        <?php if($isBestValue): ?>
                            
                            <div class="absolute inset-0 bg-gradient-to-b from-emerald-500 via-emerald-600 to-teal-700 rounded-t-2xl -top-4 shadow-2xl shadow-emerald-500/30"></div>
                            
                            <div class="absolute inset-0 bg-gradient-to-b from-white/20 to-transparent rounded-t-2xl -top-4"></div>
                            
                            <div class="relative z-10 pt-2">
                                
                                <div class="flex justify-center mb-2">
                                    <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-yellow-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                </div>
                                
                                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold bg-white text-emerald-700 shadow-lg mb-2">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo e($isUz ? 'ENG FOYDALI' : 'ЛУЧШИЙ ВЫБОР'); ?>

                                </span>
                                
                                <div class="text-xl font-bold text-white mt-1">
                                    <?php echo e($header['name']); ?>

                                </div>
                            </div>
                        <?php elseif($isFree): ?>
                            <div class="flex flex-col items-center gap-2">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 mb-1">
                                    <?php echo e($isUz ? 'BEPUL' : 'БЕСПЛАТНО'); ?>

                                </span>
                                <span class="text-lg font-bold text-gray-600">
                                    <?php echo e($header['name']); ?>

                                </span>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-lg font-bold bg-gradient-to-r <?php echo e($header['gradient']); ?> bg-clip-text text-transparent">
                                    <?php echo e($header['name']); ?>

                                </span>
                            </div>
                        <?php endif; ?>
                    </th>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catIndex => $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                
                <tr class="bg-gradient-to-r from-gray-50 to-white">
                    <td colspan="6" class="py-4 px-6">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-<?php echo e($category['color']); ?>-100 text-<?php echo e($category['color']); ?>-600 flex items-center justify-center">
                                <?php echo $category['icon']; ?>

                            </div>
                            <span class="font-semibold text-gray-900"><?php echo e($category['name']); ?></span>
                        </div>
                    </td>
                </tr>

                
                <?php $__currentLoopData = $category['features']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="py-4 px-6 text-gray-600"><?php echo e($feature['name']); ?></td>
                        <?php $__currentLoopData = $feature['values']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vIndex => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $isBestValueCol = $planHeaders[$vIndex]['best_value'] ?? false; ?>
                            <td class="py-4 px-4 text-center relative <?php echo e($isBestValueCol ? 'bg-gradient-to-b from-emerald-50 to-teal-50/50' : ''); ?>">
                                <?php if($isBestValueCol): ?>
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-400 to-teal-500"></div>
                                    <div class="absolute right-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-400 to-teal-500"></div>
                                <?php endif; ?>
                                <?php if($feature['type'] === 'boolean'): ?>
                                    <?php if($value): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100">
                                            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                <?php elseif($feature['type'] === 'mixed'): ?>
                                    <?php if($value['type'] === 'yes'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo e($value['text']); ?>

                                        </span>
                                    <?php elseif($value['type'] === 'limited'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-medium">
                                            <?php echo e($value['text']); ?>

                                        </span>
                                    <?php elseif($value['type'] === 'premium'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gradient-to-r from-amber-100 to-orange-100 text-amber-700 text-xs font-medium">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            <?php echo e($value['text']); ?>

                                        </span>
                                    <?php elseif($value['type'] === 'no'): ?>
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100">
                                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                <?php elseif($feature['type'] === 'price'): ?>
                                    <div class="flex flex-col items-center">
                                        <?php if($value === 'Bepul' || $value === 'Бесплатно' || $value === '0'): ?>
                                            <span class="text-xl font-bold text-green-600"><?php echo e($value === '0' ? ($isUz ? 'Bepul' : 'Бесплатно') : $value); ?></span>
                                        <?php else: ?>
                                            <span class="text-xl font-bold text-gray-900"><?php echo e($value); ?></span>
                                            <span class="text-xs text-gray-500"><?php echo e($isUz ? 'so\'m' : 'сум'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php if(str_contains($value, 'Cheksiz') || str_contains($value, 'Безлимит')): ?>
                                        <span class="inline-flex items-center gap-1 text-green-600 font-semibold">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo e($value); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-700 font-medium"><?php echo e($value); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>

        
        <tfoot>
            <tr class="border-t-2 border-gray-200 bg-gray-50/80">
                <td class="py-6 px-6"></td>
                <?php $__currentLoopData = $plans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $isBestValueCol = $planHeaders[$index]['best_value'] ?? false; ?>
                    <td class="py-6 px-4 text-center relative <?php echo e($isBestValueCol ? 'bg-gradient-to-b from-emerald-50 to-teal-100 rounded-b-2xl' : ''); ?>">
                        <?php if($isBestValueCol): ?>
                            <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-400 to-teal-500 rounded-bl-2xl"></div>
                            <div class="absolute right-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-emerald-400 to-teal-500 rounded-br-2xl"></div>
                            
                            <div class="absolute left-0 right-0 bottom-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-b-2xl"></div>
                        <?php endif; ?>
                        <a href="<?php echo e(route('register')); ?>?plan=<?php echo e(strtolower($plan['name'])); ?>"
                           class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 transform hover:scale-105 hover:shadow-lg
                           <?php echo e($index === 3
                               ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/40'
                               : ($index === 4
                                   ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-amber-500/25'
                                   : ($index === 0
                                       ? 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-md shadow-green-500/25'
                                       : 'bg-white text-gray-700 border-2 border-gray-200 hover:border-gray-300 hover:bg-gray-50'))); ?>">
                            <?php echo e($plan['cta']); ?>

                        </a>
                    </td>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tr>
        </tfoot>
    </table>
</div>
<?php /**PATH D:\marketing startap\biznespilot\resources\views/landing/partials/pricing-comparison-table.blade.php ENDPATH**/ ?>