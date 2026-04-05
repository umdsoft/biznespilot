<?php

namespace App\Services\Agent\Sales\ChatHandler;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Qoidaga asoslangan javoblar — AI chaqirilmaydi (bepul).
 * Salomlashish, menyu, narx va buyurtma uchun shablon javoblar.
 */
class RuleBasedResponder
{
    /**
     * Salomlashish javob
     */
    public function greetingResponse(string $businessId, ?string $customerName = null): string
    {
        $business = DB::table('businesses')->where('id', $businessId)->first(['name']);
        $bizName = $business->name ?? 'biznes';
        $name = $customerName ? ", {$customerName}" : '';

        return "Assalomu alaykum{$name}! 👋\n\n"
            . "{$bizName} ga xush kelibsiz!\n"
            . "Sizga qanday yordam bera olaman?\n\n"
            . "📋 Mahsulotlar — \"menyu\" yozing\n"
            . "💰 Narxlar — \"narx\" yozing\n"
            . "🛒 Buyurtma — \"buyurtma\" yozing\n"
            . "👨‍💼 Operator — \"operator\" yozing";
    }

    /**
     * Mahsulot ro'yxati (menyu)
     */
    public function menuResponse(string $businessId): string
    {
        try {
            // Mavjud mahsulotlarni olish (agar products jadvali bo'lsa)
            $products = DB::table('products')
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->select(['name', 'price', 'description'])
                ->orderBy('sort_order')
                ->limit(10)
                ->get();

            if ($products->isEmpty()) {
                return "Hozircha mahsulotlar ro'yxati yo'q. Operator bilan bog'laning: \"operator\" yozing.";
            }

            $lines = ["📋 **Mahsulotlarimiz:**\n"];
            foreach ($products as $i => $product) {
                $price = number_format($product->price, 0, '.', ',');
                $lines[] = ($i + 1) . ". **{$product->name}** — {$price} so'm";
                if ($product->description) {
                    $desc = mb_substr($product->description, 0, 60);
                    $lines[] = "   _{$desc}_";
                }
            }
            $lines[] = "\nBatafsil ma'lumot uchun mahsulot nomini yozing.";

            return implode("\n", $lines);
        } catch (\Exception $e) {
            return "Mahsulotlar haqida ma'lumot olishda xatolik. Iltimos qayta urinib ko'ring.";
        }
    }

    /**
     * Narx so'roviga javob
     */
    public function priceResponse(string $businessId, ?string $productQuery = null): string
    {
        try {
            if ($productQuery) {
                // Mahsulot nomiga qarab qidirish
                $product = DB::table('products')
                    ->where('business_id', $businessId)
                    ->where('name', 'LIKE', "%{$productQuery}%")
                    ->first(['name', 'price', 'description']);

                if ($product) {
                    $price = number_format($product->price, 0, '.', ',');
                    return "💰 **{$product->name}**\nNarxi: **{$price} so'm**\n\n{$product->description}\n\nBuyurtma berish uchun \"buyurtma\" yozing.";
                }
            }

            // Umumiy narxlar
            return $this->menuResponse($businessId);
        } catch (\Exception $e) {
            return "Narx ma'lumotini olishda xatolik. Operator bilan bog'laning: \"operator\" yozing.";
        }
    }

    /**
     * Operator ga uzatish javob
     */
    public function operatorHandoffResponse(): string
    {
        return "👨‍💼 Siz bilan operatorimiz tez orada bog'lanadi.\n"
            . "Ish vaqti: Du-Ju 09:00-18:00, Sha 09:00-15:00\n"
            . "Kutganingiz uchun rahmat! 🙏";
    }

    /**
     * Buyurtma boshlanish javob
     */
    public function orderStartResponse(): string
    {
        return "🛒 Buyurtma bermoqchimisiz? Ajoyib!\n\n"
            . "Iltimos quyidagilarni yuboring:\n"
            . "1. Mahsulot nomi\n"
            . "2. Soni\n"
            . "3. Telefon raqamingiz\n\n"
            . "Yoki operator bilan bog'lanish uchun \"operator\" yozing.";
    }
}
