<?php

namespace App\Http\Controllers;

use App\Models\CustdevAnswer;
use App\Models\CustdevResponse;
use App\Models\CustdevSurvey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;

class PublicSurveyController extends Controller
{
    /**
     * Show public survey form
     */
    public function show(string $slug)
    {
        $survey = CustdevSurvey::where('slug', $slug)
            ->with(['questions' => function ($query) {
                $query->orderBy('order');
            }, 'business:id,name,logo'])
            ->first();

        if (! $survey) {
            abort(404, 'So\'rovnoma topilmadi');
        }

        // Check if survey is active
        if (! $survey->isActive()) {
            return Inertia::render('Public/SurveyInactive', [
                'survey' => [
                    'title' => $survey->title,
                    'status' => $survey->status,
                    'expires_at' => $survey->expires_at,
                    'theme_color' => $survey->theme_color,
                ],
            ]);
        }

        // Get analytics tracking settings
        $trackingScripts = $this->getTrackingScripts($survey->business);

        return Inertia::render('Public/Survey', [
            'survey' => [
                'id' => $survey->id,
                'slug' => $survey->slug,
                'title' => $survey->title,
                'description' => $survey->description,
                'welcome_message' => $survey->welcome_message,
                'thank_you_message' => $survey->thank_you_message,
                'theme_color' => $survey->theme_color,
                'collect_contact' => $survey->collect_contact,
                'business_name' => $survey->business->name ?? null,
            ],
            'questions' => $survey->questions->map(function ($q) {
                return [
                    'id' => $q->id,
                    'type' => $q->type,
                    'question' => $q->question,
                    'options' => $q->options,
                    'is_required' => $q->is_required,
                    'category' => $q->category,
                    'order' => $q->order,
                ];
            }),
            'trackingScripts' => $trackingScripts,
        ]);
    }

    /**
     * Start a new response session
     */
    public function startResponse(Request $request, string $slug)
    {
        try {
            $survey = CustdevSurvey::where('slug', $slug)->firstOrFail();

            if (! $survey->isActive()) {
                return response()->json(['error' => 'Survey is not active'], 400);
            }

            // Detect device type
            $deviceType = 'desktop';
            try {
                $agent = new Agent;
                if ($agent->isMobile()) {
                    $deviceType = 'mobile';
                } elseif ($agent->isTablet()) {
                    $deviceType = 'tablet';
                }
            } catch (\Exception $e) {
                // Agent detection failed, use default
            }

            // Create response record
            $response = CustdevResponse::create([
                'survey_id' => $survey->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $deviceType,
                'status' => 'in_progress',
                'current_question' => 1,
                'started_at' => now(),
                'metadata' => [
                    'referrer' => $request->header('referer'),
                    'utm_source' => $request->input('utm_source'),
                    'utm_medium' => $request->input('utm_medium'),
                    'utm_campaign' => $request->input('utm_campaign'),
                ],
            ]);

            // Update survey response count
            $survey->increment('responses_count');

            return response()->json([
                'response_id' => $response->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Save answer for a question
     */
    public function saveAnswer(Request $request, string $slug)
    {
        $request->validate([
            'response_id' => 'required|exists:custdev_responses,id',
            'question_id' => 'required|exists:custdev_questions,id',
            'answer' => 'nullable|string',
            'selected_options' => 'nullable|array',
            'rating_value' => 'nullable|integer',
            'time_spent' => 'nullable|integer',
        ]);

        $response = CustdevResponse::findOrFail($request->response_id);

        // Make sure response belongs to this survey
        $survey = CustdevSurvey::where('slug', $slug)->firstOrFail();
        if ($response->survey_id !== $survey->id) {
            return response()->json(['error' => 'Invalid response'], 400);
        }

        // Update or create answer
        CustdevAnswer::updateOrCreate(
            [
                'response_id' => $response->id,
                'question_id' => $request->question_id,
            ],
            [
                'answer' => $request->answer,
                'selected_options' => $request->selected_options,
                'rating_value' => $request->rating_value,
                'time_spent' => $request->time_spent ?? 0,
            ]
        );

        // Update current question
        $questionOrder = $survey->questions()->where('id', $request->question_id)->value('order');
        if ($questionOrder) {
            $response->update([
                'current_question' => $questionOrder + 1,
                'time_spent' => $response->time_spent + ($request->time_spent ?? 0),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Complete the survey
     */
    public function complete(Request $request, string $slug)
    {
        $request->validate([
            'response_id' => 'required|exists:custdev_responses,id',
            'respondent_name' => 'nullable|string|max:255',
            'respondent_phone' => 'nullable|string|max:50',
            'respondent_region' => 'nullable|string|max:100',
            'total_time' => 'nullable|integer',
        ]);

        $response = CustdevResponse::findOrFail($request->response_id);

        // Verify survey ownership
        $survey = CustdevSurvey::where('slug', $slug)->firstOrFail();
        if ($response->survey_id !== $survey->id) {
            return response()->json(['error' => 'Invalid response'], 400);
        }

        // Update response as completed
        $response->update([
            'status' => 'completed',
            'respondent_name' => $request->respondent_name,
            'respondent_phone' => $request->respondent_phone,
            'respondent_region' => $request->respondent_region,
            'time_spent' => $request->total_time ?? $response->time_spent,
            'completed_at' => now(),
        ]);

        // Update survey completion stats
        $completedCount = $survey->completedResponses()->count();
        $totalCount = $survey->responses()->count();
        $completionRate = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        $survey->update(['completion_rate' => $completionRate]);

        return response()->json([
            'success' => true,
            'thank_you_message' => $survey->thank_you_message,
        ]);
    }

    /**
     * Show thank you page
     */
    public function thankYou(string $slug)
    {
        $survey = CustdevSurvey::where('slug', $slug)->first();

        return Inertia::render('Public/SurveyThankYou', [
            'survey' => $survey ? [
                'title' => $survey->title,
                'thank_you_message' => $survey->thank_you_message,
                'theme_color' => $survey->theme_color,
            ] : null,
        ]);
    }

    /**
     * Get tracking scripts for business
     */
    private function getTrackingScripts($business): array
    {
        if (!$business) {
            return [];
        }

        $settings = $business->settings ?? [];
        $scripts = [];

        // Google Analytics 4
        if (!empty($settings['ga4_enabled']) && !empty($settings['ga4_measurement_id'])) {
            $scripts['ga4'] = $settings['ga4_measurement_id'];
        }

        // Yandex Metrika
        if (!empty($settings['yandex_metrika_enabled']) && !empty($settings['yandex_metrika_id'])) {
            $scripts['yandex'] = $settings['yandex_metrika_id'];
        }

        // Facebook Pixel
        if (!empty($settings['facebook_pixel_enabled']) && !empty($settings['facebook_pixel_id'])) {
            $scripts['facebook'] = $settings['facebook_pixel_id'];
        }

        return $scripts;
    }
}
