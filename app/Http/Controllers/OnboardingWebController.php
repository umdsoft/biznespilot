<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Industry;
use App\Services\OnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingWebController extends Controller
{
    protected $onboardingService;

    public function __construct(OnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Show the onboarding wizard page
     */
    public function index()
    {
        $user = Auth::user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('business.business.create');
        }

        // Get onboarding progress
        $progress = $this->onboardingService->getProgress($business);

        // Get maturity score if available
        $maturityScore = $this->onboardingService->getMaturityScore($business);

        // Get steps
        $steps = $this->onboardingService->getSteps($business);

        // Get industries for dropdown
        $industries = Industry::orderBy('name_uz')->get();

        return Inertia::render('Onboarding/Index', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
                'industry_id' => $business->industry_id,
                'description' => $business->description,
                'onboarding_status' => $business->onboarding_status,
                'onboarding_current_step' => $business->onboarding_current_step,
            ],
            'progress' => $progress,
            'maturityScore' => $maturityScore,
            'steps' => $steps,
            'industries' => $industries,
        ]);
    }

    /**
     * Show a specific onboarding step
     */
    public function step(string $stepCode)
    {
        $user = Auth::user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('business.business.create');
        }

        $stepDetails = $this->onboardingService->getStepDetails($business, $stepCode);

        if (!$stepDetails) {
            return redirect()->route('onboarding.index');
        }

        return Inertia::render('Onboarding/Step', [
            'business' => $business,
            'step' => $stepDetails,
            'stepCode' => $stepCode,
        ]);
    }

    /**
     * Complete onboarding and redirect to diagnostic (FAZA 2)
     */
    public function complete()
    {
        $user = Auth::user();
        $business = $user->businesses()->first();

        if (!$business) {
            return redirect()->route('business.business.create');
        }

        // Check if onboarding is complete
        $progress = $this->onboardingService->getProgress($business);

        if ($progress['overall_percent'] < 100) {
            return back()->with('error', 'Iltimos, avval barcha onboarding bosqichlarini yakunlang.');
        }

        // Update business status
        $business->update([
            'onboarding_status' => 'completed',
        ]);

        // Redirect to diagnostic (FAZA 2)
        return redirect()->route('business.diagnostic.index')
            ->with('success', 'Onboarding muvaffaqiyatli yakunlandi! Endi AI diagnostikasini boshlashingiz mumkin.');
    }
}
