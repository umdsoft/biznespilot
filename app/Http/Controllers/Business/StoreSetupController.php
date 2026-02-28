<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\TelegramStore;
use App\Models\TelegramBot;
use App\Services\Store\BotTypeRegistry;
use App\Services\Store\StoreSetupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreSetupController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected StoreSetupService $storeSetupService
    ) {}

    /**
     * Show the store setup wizard page
     */
    public function wizard(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Query param dan pre-selected bot type (telegram bot create sahifasidan keladi)
        $preSelectedType = $request->query('type');
        $validTypes = array_keys(config('store_bot_types', []));
        if ($preSelectedType && ! in_array($preSelectedType, $validTypes)) {
            $preSelectedType = null;
        }

        // Get existing bots for this business (for step 3 selection)
        $existingBots = TelegramBot::where('business_id', $business->id)
            ->select('id', 'bot_username', 'bot_first_name', 'is_active')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($bot) => [
                'id' => $bot->id,
                'username' => $bot->bot_username,
                'first_name' => $bot->bot_first_name,
                'is_active' => $bot->is_active,
            ]);

        // Biznesda allaqachon ishlatilgan do'kon turlari (har turdan faqat 1 ta ruxsat)
        $usedTypes = TelegramStore::where('business_id', $business->id)
            ->whereNotNull('store_type')
            ->where('store_type', '!=', '')
            ->pluck('store_type')
            ->unique()
            ->values()
            ->toArray();

        // Wizard always starts fresh — step 1, empty forms, no completed steps
        return Inertia::render('Business/Store/Setup', [
            'store' => null,
            'bot' => null,
            'step' => 1,
            'completedSteps' => [],
            'existingBots' => $existingBots,
            'botTypes' => app(BotTypeRegistry::class)->getAllTypesForSelect(),
            'preSelectedType' => $preSelectedType,
            'usedTypes' => $usedTypes,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Create or update a store (Step 2 - store info, receives store_type from Step 1)
     *
     * Har bir wizard run o'z alohida do'konini yaratadi.
     * Agar hali faollashtirilmagan do'kon bo'lsa (setup jarayonida) — uni yangilaydi.
     * Faol do'konga tegmaydi — yangi do'kon yaratadi.
     */
    public function storeSetup(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'currency' => 'required|string|in:UZS,USD,RUB',
            'store_type' => 'required|string|in:ecommerce,service,delivery,course,fitness,realestate,auto,event,travel,ondemand,subscription,custom',
            'enabled_features' => 'nullable|array',
        ]);

        // Har bir turdan faqat 1 ta do'kon ruxsat — dublikatni tekshirish
        $duplicateExists = TelegramStore::where('business_id', $business->id)
            ->where('store_type', $validated['store_type'])
            ->when(session('active_store_id'), function ($q) {
                // Hozirgi setup dagi do'konni istisno qilish (o'zi bilan conflict bo'lmasin)
                $q->where('id', '!=', session('active_store_id'));
            })
            ->exists();

        if ($duplicateExists) {
            return back()->withErrors([
                'store_type' => 'Bu turdagi do\'kon allaqachon mavjud. Har turdan faqat bitta do\'kon yaratish mumkin.',
            ]);
        }

        // Active store dan qidiruv — faqat hali faollashtirilmagan do'konni yangilaymiz
        $activeStoreId = session('active_store_id');
        $existingStore = null;

        if ($activeStoreId) {
            $store = TelegramStore::where('id', $activeStoreId)
                ->where('business_id', $business->id)
                ->first();

            // Faqat hali faollashtirilmagan do'konni yangilash (setup jarayonida)
            // Faol do'konga tegmaymiz — yangi yaratamiz
            if ($store && ! $store->is_active) {
                $existingStore = $store;
            }
        }

        if ($existingStore) {
            // Setup jarayonidagi do'konni yangilash (step 2 ga qayta kirganda)
            $existingStore->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'currency' => $validated['currency'],
                'store_type' => $validated['store_type'],
                'enabled_features' => $validated['enabled_features'] ?? [],
            ]);
            $store = $existingStore;
            $message = 'Do\'kon ma\'lumotlari yangilandi.';
        } else {
            // Yangi do'kon yaratish (boshqa do'konlarga tegmaymiz)
            $store = $this->storeSetupService->createStore($business, $validated);
            $message = 'Do\'kon muvaffaqiyatli yaratildi. Endi Telegram botni ulang.';
        }

        // Active store session ni yangilash — keyingi steplar shu do'konni topadi
        session(['active_store_id' => $store->id]);

        return redirect()->route('business.store.setup.wizard')
            ->with('success', $message);
    }

    /**
     * Connect a Telegram bot to the store (Step 3)
     */
    public function connectBot(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Avval do\'kon yarating.');
        }

        $validated = $request->validate([
            'bot_token' => 'required|string|min:20|max:100',
        ]);

        $result = $this->storeSetupService->connectBot($store, $validated['bot_token']);

        if (! $result['success']) {
            return back()->withErrors(['bot_token' => $result['error']]);
        }

        $message = 'Telegram bot muvaffaqiyatli ulandi.';
        if (! ($result['webhook_set'] ?? false)) {
            $message .= ' Webhook sozlashda xatolik bo\'ldi, lekin bot ulangan.';
        }

        return redirect()->route('business.store.setup.wizard')
            ->with('success', $message);
    }

    /**
     * Connect an existing Telegram bot to the store (Step 3 — select from list)
     */
    public function connectExistingBot(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Avval do\'kon yarating.');
        }

        $validated = $request->validate([
            'bot_id' => 'required|uuid',
        ]);

        $bot = TelegramBot::where('business_id', $business->id)
            ->where('id', $validated['bot_id'])
            ->first();

        if (! $bot) {
            return back()->with('error', 'Bot topilmadi.');
        }

        // Link bot to store
        $store->update(['telegram_bot_id' => $bot->id]);

        // Set up MiniApp webhook and menu button
        $this->storeSetupService->setupBotForStore($store, $bot);

        return redirect()->route('business.store.setup.wizard')
            ->with('success', 'Telegram bot muvaffaqiyatli ulandi.');
    }

    /**
     * Configure payment settings (Step 4)
     */
    public function configurePayment(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Avval do\'kon yarating.');
        }

        $validated = $request->validate([
            'payment_methods' => 'required|array|min:1',
            'payment_methods.*' => 'string|in:cash,card,click,payme',
            'click_merchant_id' => 'nullable|required_if:payment_methods.*,click|string|max:100',
            'click_service_id' => 'nullable|required_if:payment_methods.*,click|string|max:100',
            'payme_merchant_id' => 'nullable|required_if:payment_methods.*,payme|string|max:100',
        ]);

        $paymentSettings = [
            'payment_methods' => $validated['payment_methods'],
        ];

        if (in_array('click', $validated['payment_methods'])) {
            $paymentSettings['click'] = [
                'merchant_id' => $validated['click_merchant_id'] ?? null,
                'service_id' => $validated['click_service_id'] ?? null,
            ];
        }

        if (in_array('payme', $validated['payment_methods'])) {
            $paymentSettings['payme'] = [
                'merchant_id' => $validated['payme_merchant_id'] ?? null,
            ];
        }

        $settings = $store->settings ?? [];
        $settings['payment_methods'] = $paymentSettings['payment_methods'];
        $settings['payment_providers'] = $paymentSettings;

        $store->update(['settings' => $settings]);

        return redirect()->route('business.store.setup.wizard')
            ->with('success', 'To\'lov sozlamalari saqlandi.');
    }

    /**
     * Activate the store (Step 4)
     */
    public function activate()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Avval bot yarating.');
        }

        if (! $store->telegram_bot_id) {
            return back()->with('error', 'Avval Telegram botni ulang.');
        }

        $activated = $this->storeSetupService->activateStore($store);

        if (! $activated) {
            return back()->with('error', 'Botni faollashtirish imkoni bo\'lmadi. Barcha sozlamalarni tekshiring.');
        }

        return redirect()->route($this->getStorePanelType() . '.store.dashboard')
            ->with('success', 'Telegram bot muvaffaqiyatli faollashtirildi!');
    }
}
