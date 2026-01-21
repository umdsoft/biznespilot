<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\HRSurvey;
use Inertia\Inertia;

class SurveyWebController extends Controller
{
    use HasCurrentBusiness;

    /**
     * So'rovnomalar ro'yxati
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('HR/Surveys/Index', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }

    /**
     * So'rovnoma tafsilotlari (admin uchun)
     */
    public function show(string $surveyId)
    {
        $business = $this->getCurrentBusiness();

        $survey = HRSurvey::where('business_id', $business?->id)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return redirect()->route('hr.surveys.index')
                ->with('error', "So'rovnoma topilmadi");
        }

        return Inertia::render('HR/Surveys/Show', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
            'surveyId' => $surveyId,
        ]);
    }

    /**
     * So'rovnoma natijalari
     */
    public function results(string $surveyId)
    {
        $business = $this->getCurrentBusiness();

        $survey = HRSurvey::where('business_id', $business?->id)
            ->where('id', $surveyId)
            ->first();

        if (!$survey) {
            return redirect()->route('hr.surveys.index')
                ->with('error', "So'rovnoma topilmadi");
        }

        return Inertia::render('HR/Surveys/Results', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
            'surveyId' => $surveyId,
        ]);
    }

    /**
     * So'rovnomani to'ldirish (xodimlar uchun)
     */
    public function fill(string $surveyId)
    {
        $business = $this->getCurrentBusiness();

        $survey = HRSurvey::where('business_id', $business?->id)
            ->where('id', $surveyId)
            ->where('status', HRSurvey::STATUS_ACTIVE)
            ->first();

        if (!$survey) {
            return redirect()->route('hr.surveys.index')
                ->with('error', "So'rovnoma topilmadi yoki faol emas");
        }

        return Inertia::render('HR/Surveys/Fill', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
            'surveyId' => $surveyId,
        ]);
    }

    /**
     * Xodim uchun mavjud so'rovnomalar
     */
    public function mySurveys()
    {
        $business = $this->getCurrentBusiness();

        return Inertia::render('HR/Surveys/MySurveys', [
            'currentBusiness' => $business ? [
                'id' => $business->id,
                'name' => $business->name,
            ] : null,
        ]);
    }
}
