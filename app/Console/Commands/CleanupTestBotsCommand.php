<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Barcha Telegram botlar, do'konlar va bog'liq datani tozalash.
 * Faqat test/development muhitida ishlatish uchun.
 */
class CleanupTestBotsCommand extends Command
{
    protected $signature = 'store:cleanup
                            {--force : Tasdiqlashsiz tozalash}';

    protected $description = 'Barcha Telegram botlar, do\'konlar va bog\'liq datani tozalash (test uchun)';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->warn('⚠️  DIQQAT: Bu barcha botlar, do\'konlar va ularning ma\'lumotlarini O\'CHIRADI!');
            $this->warn('   Bu amalni qaytarib bo\'lmaydi.');

            if (! $this->confirm('Davom etasizmi?')) {
                $this->info('Bekor qilindi.');

                return self::SUCCESS;
            }
        }

        $this->info('Tozalash boshlandi...');

        // FK constraintlarni vaqtincha o'chirish
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Tozalanadigan jadvallar (tartib muhim emas — FK checks off)
        $tables = [
            // Store — child jadvallar
            'store_product_images',
            'store_product_variants',
            'store_order_items',
            'store_order_status_history',
            'store_cart_items',
            'store_menu_modifiers',
            'store_modifier_options',
            'store_course_lessons',
            'store_property_images',
            'store_vehicle_images',
            'store_event_tickets',
            'store_tour_days',
            'store_content_items',
            'store_staff_schedules',
            'store_staff_time_off',
            'store_customer_addresses',
            'store_review_replies',
            'store_loyalty_redemptions',
            'store_loyalty_rewards',
            'store_loyalty_transactions',
            'store_loyalty_tiers',
            'store_loyalty_programs',

            // Store — asosiy jadvallar
            'store_products',
            'store_categories',
            'store_customers',
            'store_orders',
            'store_carts',
            'store_promo_codes',
            'store_reviews',
            'store_payment_transactions',
            'store_delivery_zones',
            'store_analytics_daily',
            'store_staff',
            'store_bookings',
            'store_wishlists',
            'store_refunds',

            // Store — catalog-specific
            'store_services',
            'store_menu_items',
            'store_courses',
            'store_memberships',
            'store_group_classes',
            'store_properties',
            'store_vehicles',
            'store_events',
            'store_tours',
            'store_service_requests',
            'store_content_plans',
            'store_custom_items',

            // Delivery bot
            'delivery_order_items',
            'delivery_item_variants',
            'delivery_item_addons',
            'delivery_orders',
            'delivery_categories',
            'delivery_menu_items',
            'delivery_addresses',
            'delivery_settings',
            'delivery_daily_stats',

            // Queue bot
            'queue_bookings',
            'queue_daily_stats',
            'queue_time_slots',
            'queue_branch_services',
            'queue_specialist_services',
            'queue_specialists',
            'queue_branches',
            'queue_services',
            'queue_settings',

            // Service bot
            'service_requests',
            'service_daily_stats',
            'service_master_categories',
            'service_masters',
            'service_types',
            'service_categories',
            'service_settings',

            // Telegram stores
            'telegram_stores',

            // Telegram bot child jadvallar
            'telegram_messages',
            'telegram_conversations',
            'telegram_user_states',
            'telegram_users',
            'telegram_funnel_steps',
            'telegram_funnels',
            'telegram_triggers',
            'telegram_broadcasts',
            'telegram_daily_stats',

            // Telegram bots (eng oxirida)
            'telegram_bots',
        ];

        $cleaned = 0;

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    DB::table($table)->truncate();
                    $this->line("  ✓ {$table}: {$count} ta yozuv o'chirildi");
                    $cleaned++;
                }
            }
        }

        // FK constraintlarni qayta yoqish
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Active store sessionni tozalash
        if (session()->has('active_store_id')) {
            session()->forget('active_store_id');
        }

        $this->newLine();
        $this->info("✅ Tozalash tugadi. {$cleaned} ta jadval tozalandi.");
        $this->info('Endi yangi botlarni yaratishingiz mumkin.');

        return self::SUCCESS;
    }
}
