<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
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
    use HasCurrentBusiness, HasStorePanelType;

    public function __construct(
        protected StoreSetupService $storeSetupService
    ) {}

    /**
     * Get the store for the current business
     */
    protected function getStore(): ?TelegramStore
    {
        $business = $this->getCurrentBusiness();

        return TelegramStore::where('business_id', $business->id)->first();
    }

    /**
     * Show the store setup wizard page
     */
    public function wizard(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        // Determine current setup step (5-step wizard)
        // Step 1: Bot type, Step 2: Store info, Step 3: Bot connection, Step 4: Payment, Step 5: Activation
        $currentStep = 1; // Bot type selection
        $completedSteps = [];

        if ($store) {
            $completedSteps[] = 1; // Bot type selected (store exists with store_type)
            $completedSteps[] = 2; // Store info completed
            $currentStep = 3; // Bot connection

            if ($store->telegram_bot_id) {
                $completedSteps[] = 3;
                $currentStep = 4; // Payment settings

                $paymentConfigured = ! empty($store->getSetting('payment_methods'));
                if ($paymentConfigured) {
                    $completedSteps[] = 4;
                    $currentStep = 5; // Activation
                }

                if ($store->is_active) {
                    $completedSteps[] = 5;
                }
            }
        }

        // Get connected bot info if available
        $connectedBot = null;
        if ($store && $store->telegram_bot_id) {
            $bot = TelegramBot::find($store->telegram_bot_id);
            if ($bot) {
                $connectedBot = [
                    'id' => $bot->id,
                    'username' => $bot->bot_username,
                    'first_name' => $bot->bot_first_name,
                    'is_active' => $bot->is_active,
                ];
            }
        }

        // Query param dan pre-selected bot type (telegram bot create sahifasidan keladi)
        $preSelectedType = $request->query('type');
        $validTypes = array_keys(config('store_bot_types', []));
        if ($preSelectedType && ! in_array($preSelectedType, $validTypes)) {
            $preSelectedType = null;
        }

        return Inertia::render('Business/Store/Setup', [
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'phone' => $store->phone,
                'address' => $store->address,
                'currency' => $store->currency,
                'store_type' => $store->store_type,
                'enabled_features' => $store->enabled_features,
                'is_active' => $store->is_active,
                'settings' => $store->settings,
                'mini_app_url' => $store->getMiniAppUrl(),
                'products_count' => $store->getActiveCatalogItemsCount(),
            ] : null,
            'bot' => $connectedBot,
            'step' => $currentStep,
            'completedSteps' => $completedSteps,
            'botTypes' => app(BotTypeRegistry::class)->getAllTypesForSelect(),
            'preSelectedType' => $preSelectedType,
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Create a new store (Step 2 - store info, receives store_type from Step 1)
     */
    public function storeSetup(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // Check if business already has a store
        $existingStore = TelegramStore::where('business_id', $business->id)->first();
        if ($existingStore) {
            return back()->with('error', 'Bu biznes uchun do\'kon allaqachon yaratilgan.');
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

        $store = $this->storeSetupService->createStore($business, $validated);

        return redirect()->route('business.store.setup.wizard')
            ->with('success', 'Do\'kon muvaffaqiyatli yaratildi. Endi Telegram botni ulang.');
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
     * Activate the store (Step 5)
     */
    public function activate()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Avval do\'kon yarating.');
        }

        if (! $store->telegram_bot_id) {
            return back()->with('error', 'Avval Telegram botni ulang.');
        }

        $activated = $this->storeSetupService->activateStore($store);

        if (! $activated) {
            return back()->with('error', 'Do\'konni faollashtirish imkoni bo\'lmadi. Barcha sozlamalarni tekshiring.');
        }

        return redirect()->route('business.store.setup.wizard')
            ->with('success', 'Do\'kon muvaffaqiyatli faollashtirildi! Mini App orqali mijozlar xarid qilishlari mumkin.');
    }
}
