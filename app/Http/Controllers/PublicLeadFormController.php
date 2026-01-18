<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadForm;
use App\Models\LeadFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;

class PublicLeadFormController extends Controller
{
    /**
     * Display the public lead form.
     */
    public function show(Request $request, string $slug)
    {
        $leadForm = LeadForm::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Record view (only for non-embed and non-preview)
        if (! $request->has('preview')) {
            $leadForm->recordView();
        }

        $isEmbed = $request->boolean('embed');

        return Inertia::render('Public/LeadForm', [
            'leadForm' => [
                'id' => $leadForm->id,
                'title' => $leadForm->title,
                'description' => $leadForm->description,
                'fields' => $leadForm->fields,
                'submit_button_text' => $leadForm->submit_button_text,
                'theme_color' => $leadForm->theme_color,
                'has_lead_magnet' => $leadForm->hasLeadMagnet(),
                'lead_magnet_title' => $leadForm->lead_magnet_title,
            ],
            'isEmbed' => $isEmbed,
            'slug' => $slug,
        ]);
    }

    /**
     * Submit the lead form.
     */
    public function submit(Request $request, string $slug)
    {
        $leadForm = LeadForm::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Build validation rules from form fields
        $rules = [];
        $fieldMapping = [];

        foreach ($leadForm->fields as $field) {
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($field['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'phone':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:50';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'text':
                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;
            }

            $rules[$field['id']] = $fieldRules;

            // Track field mapping to Lead model
            if (! empty($field['map_to'])) {
                $fieldMapping[$field['id']] = $field['map_to'];
            }
        }

        $validated = $request->validate($rules);

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

        // Create Lead from form data
        $leadData = [
            'business_id' => $leadForm->business_id,
            'source_id' => $leadForm->default_source_id,
            'status' => $leadForm->default_status,
            'score' => $leadForm->default_score,
        ];

        // Map form fields to lead fields
        $customFields = [];
        foreach ($validated as $fieldId => $value) {
            if (isset($fieldMapping[$fieldId])) {
                $mapTo = $fieldMapping[$fieldId];
                if (in_array($mapTo, ['name', 'email', 'phone', 'company', 'position', 'notes', 'estimated_value'])) {
                    $leadData[$mapTo] = $value;
                } else {
                    $customFields[$fieldId] = $value;
                }
            } else {
                $customFields[$fieldId] = $value;
            }
        }

        if (! empty($customFields)) {
            $leadData['custom_fields'] = $customFields;
        }

        // Source tracking from UTM
        if ($leadForm->track_utm && $request->has('utm_source')) {
            $leadData['source'] = $request->input('utm_source');
        }

        $lead = Lead::create($leadData);

        // Create submission record
        $submission = LeadFormSubmission::create([
            'lead_form_id' => $leadForm->id,
            'lead_id' => $lead->id,
            'business_id' => $leadForm->business_id,
            'form_data' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceType,
            'referrer' => $request->header('referer'),
            'utm_source' => $request->input('utm_source'),
            'utm_medium' => $request->input('utm_medium'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_term' => $request->input('utm_term'),
            'utm_content' => $request->input('utm_content'),
        ]);

        // Update form submission count
        $leadForm->recordSubmission();

        // Prepare response data
        $responseData = [
            'success' => true,
            'submission_id' => $submission->id,
            'message' => $leadForm->success_message,
        ];

        // Include lead magnet data if available
        if ($leadForm->hasLeadMagnet() && $leadForm->show_lead_magnet_on_success) {
            $responseData['lead_magnet'] = [
                'type' => $leadForm->lead_magnet_type,
                'title' => $leadForm->lead_magnet_title,
            ];

            switch ($leadForm->lead_magnet_type) {
                case 'file':
                    $responseData['lead_magnet']['download_url'] = route('lead-form.download', [
                        'slug' => $slug,
                        'submission' => $submission->id,
                    ]);
                    break;
                case 'video':
                case 'link':
                    $responseData['lead_magnet']['link'] = $leadForm->lead_magnet_link;
                    break;
                case 'coupon':
                case 'text':
                    $responseData['lead_magnet']['text'] = $leadForm->lead_magnet_text;
                    break;
            }
        }

        // Include redirect URL if set
        if ($leadForm->redirect_url) {
            $responseData['redirect_url'] = $leadForm->redirect_url;
        }

        return response()->json($responseData);
    }

    /**
     * Download lead magnet file.
     */
    public function download(Request $request, string $slug, string $submission)
    {
        $leadForm = LeadForm::where('slug', $slug)->firstOrFail();
        $submissionRecord = LeadFormSubmission::where('id', $submission)
            ->where('lead_form_id', $leadForm->id)
            ->firstOrFail();

        if ($leadForm->lead_magnet_type !== 'file' || ! $leadForm->lead_magnet_file) {
            abort(404);
        }

        // Mark as delivered
        $submissionRecord->markLeadMagnetDelivered();

        return Storage::disk('public')->download($leadForm->lead_magnet_file);
    }

    /**
     * Thank you page after submission.
     */
    public function thankYou(Request $request, string $slug)
    {
        $leadForm = LeadForm::where('slug', $slug)->first();

        return Inertia::render('Public/LeadFormThankYou', [
            'leadForm' => $leadForm ? [
                'title' => $leadForm->title,
                'success_message' => $leadForm->success_message,
                'theme_color' => $leadForm->theme_color,
                'has_lead_magnet' => $leadForm->hasLeadMagnet(),
                'lead_magnet_type' => $leadForm->lead_magnet_type,
                'lead_magnet_title' => $leadForm->lead_magnet_title,
            ] : null,
        ]);
    }

    /**
     * API endpoint for external integrations (webhooks)
     */
    public function apiSubmit(Request $request, string $slug)
    {
        $leadForm = LeadForm::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $leadForm) {
            return response()->json(['error' => 'Form not found'], 404);
        }

        // Accept data as JSON
        $data = $request->all();

        // Create Lead
        $leadData = [
            'business_id' => $leadForm->business_id,
            'source_id' => $leadForm->default_source_id,
            'status' => $leadForm->default_status,
            'score' => $leadForm->default_score,
            'name' => $data['name'] ?? $data['full_name'] ?? 'Unknown',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? $data['phone_number'] ?? null,
            'company' => $data['company'] ?? $data['company_name'] ?? null,
            'source' => $data['utm_source'] ?? $data['source'] ?? 'api',
        ];

        $lead = Lead::create($leadData);

        // Create submission
        $submission = LeadFormSubmission::create([
            'lead_form_id' => $leadForm->id,
            'lead_id' => $lead->id,
            'business_id' => $leadForm->business_id,
            'form_data' => $data,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
        ]);

        $leadForm->recordSubmission();

        return response()->json([
            'success' => true,
            'lead_id' => $lead->id,
            'submission_id' => $submission->id,
        ]);
    }
}
