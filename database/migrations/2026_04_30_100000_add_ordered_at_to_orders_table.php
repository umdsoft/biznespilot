<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `ordered_at` ustun orders jadvaliga qo'shish.
 *
 * Bu ustun OrderObserver, InsightEngineService va boshqa joylarda
 * ishlatiladi (kunlik biznes hisoboti, customer first/last purchase
 * timeline, marketing attribution analytics).
 *
 * Avval migration unutilgan edi → SQLSTATE[42S22] xatoligi → kunlik
 * Telegram brief yuborilmasdan silent fail bo'lardi.
 *
 * Mavjud orderlarda backfill: ordered_at = created_at
 * (Eloquent default timestamp). Yangi orderlarda Observer yoki Order
 * controller paid_at/created_at bilan to'ldirishi mumkin.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'ordered_at')) {
            return; // already exists
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('ordered_at')->nullable()->after('payment_method')
                ->comment('Buyurtma berilgan vaqt (createDated_at bilan boshlanadi, lekin sales analytics uchun semantik aniqroq)');
            $table->index(['business_id', 'ordered_at'], 'orders_business_ordered_idx');
        });

        // Backfill: existing orders → created_at
        DB::statement('UPDATE orders SET ordered_at = created_at WHERE ordered_at IS NULL');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'ordered_at')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_business_ordered_idx');
            $table->dropColumn('ordered_at');
        });
    }
};
