<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadForm;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LeadFormController extends Controller
{
    /**
     * Display a listing of lead forms.
     */
    public function index(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $leadForms = LeadForm::where('business_id', $business->id)
            ->with('defaultSource')
            ->latest()
            ->get()
            ->map(function ($form) {
                return [
                    'id' => $form->id,
                    'name' => $form->name,
                    'title' => $form->title,
                    'slug' => $form->slug,
                    'public_url' => $form->public_url,
                    'theme_color' => $form->theme_color,
                    'lead_magnet_type' => $form->lead_magnet_type,
                    'is_active' => $form->is_active,
                    'views_count' => $form->views_count,
                    'submissions_count' => $form->submissions_count,
                    'conversion_rate' => $form->conversion_rate,
                    'default_source' => $form->defaultSource ? [
                        'id' => $form->defaultSource->id,
                        'name' => $form->defaultSource->name,
                    ] : null,
                    'created_at' => $form->created_at->format('d.m.Y'),
                ];
            });

        // Get stats
        $stats = [
            'total_forms' => $leadForms->count(),
            'active_forms' => $leadForms->where('is_active', true)->count(),
            'total_views' => $leadForms->sum('views_count'),
            'total_submissions' => $leadForms->sum('submissions_count'),
        ];

        return Inertia::render('Business/LeadForms/Index', [
            'leadForms' => $leadForms,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new lead form.
     */
    public function create(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $leadSources = LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($sources) {
                return $sources->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'icon' => $s->icon,
                ]);
            });

        return Inertia::render('Business/LeadForms/Create', [
            'leadSources' => $leadSources,
            'fieldTypes' => LeadForm::getFieldTypes(),
            'defaultFields' => LeadForm::getDefaultFields(),
        ]);
    }

    /**
     * Store a newly created lead form.
     */
    public function store(Request $request)
    {
        $business = $request->user()->currentBusiness;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array|min:1',
            'fields.*.id' => 'required|string',
            'fields.*.type' => 'required|string',
            'fields.*.label' => 'required|string',
            'fields.*.required' => 'boolean',
            'fields.*.map_to' => 'nullable|string',
            'submit_button_text' => 'nullable|string|max:50',
            'theme_color' => 'nullable|string|max:20',
            'default_source_id' => 'nullable|exists:lead_sources,id',
            'default_status' => 'nullable|in:new,contacted,qualified',
            'default_score' => 'nullable|integer|min:0|max:100',
            'lead_magnet_type' => 'nullable|in:none,file,video,link,coupon,text',
            'lead_magnet_title' => 'nullable|string|max:255',
            'lead_magnet_file' => 'nullable|file|max:10240', // 10MB max
            'lead_magnet_link' => 'nullable|url',
            'lead_magnet_text' => 'nullable|string',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
            'show_lead_magnet_on_success' => 'boolean',
            'track_utm' => 'boolean',
        ]);

        // Handle file upload for lead magnet
        $leadMagnetFile = null;
        if ($request->hasFile('lead_magnet_file')) {
            $leadMagnetFile = $request->file('lead_magnet_file')
                ->store("lead-magnets/{$business->id}", 'public');
        }

        $leadForm = LeadForm::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'fields' => $validated['fields'],
            'submit_button_text' => $validated['submit_button_text'] ?? 'Yuborish',
            'theme_color' => $validated['theme_color'] ?? '#6366f1',
            'default_source_id' => $validated['default_source_id'] ?? null,
            'default_status' => $validated['default_status'] ?? 'new',
            'default_score' => $validated['default_score'] ?? 50,
            'lead_magnet_type' => $validated['lead_magnet_type'] ?? 'none',
            'lead_magnet_title' => $validated['lead_magnet_title'] ?? null,
            'lead_magnet_file' => $leadMagnetFile,
            'lead_magnet_link' => $validated['lead_magnet_link'] ?? null,
            'lead_magnet_text' => $validated['lead_magnet_text'] ?? null,
            'success_message' => $validated['success_message'] ?? 'Rahmat! Ma\'lumotlaringiz qabul qilindi.',
            'redirect_url' => $validated['redirect_url'] ?? null,
            'show_lead_magnet_on_success' => $validated['show_lead_magnet_on_success'] ?? true,
            'track_utm' => $validated['track_utm'] ?? true,
        ]);

        return redirect()->route('business.lead-forms.show', $leadForm)
            ->with('success', 'Lead forma muvaffaqiyatli yaratildi!');
    }

    /**
     * Display the specified lead form.
     */
    public function show(Request $request, LeadForm $leadForm)
    {
        $this->authorize('view', $leadForm);

        $leadForm->load(['defaultSource', 'submissions' => function ($query) {
            $query->latest()->limit(10);
        }, 'submissions.lead']);

        // Get recent leads from this form
        $recentLeads = Lead::whereHas('submissions', function ($query) use ($leadForm) {
            $query->where('lead_form_id', $leadForm->id);
        })->latest()->limit(10)->get();

        // Get submission stats by day (last 7 days)
        $dailyStats = $leadForm->submissions()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // UTM source breakdown
        $utmStats = $leadForm->submissions()
            ->selectRaw('utm_source, COUNT(*) as count')
            ->whereNotNull('utm_source')
            ->groupBy('utm_source')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return Inertia::render('Business/LeadForms/Show', [
            'leadForm' => [
                'id' => $leadForm->id,
                'name' => $leadForm->name,
                'title' => $leadForm->title,
                'description' => $leadForm->description,
                'slug' => $leadForm->slug,
                'public_url' => $leadForm->public_url,
                'fields' => $leadForm->fields,
                'submit_button_text' => $leadForm->submit_button_text,
                'theme_color' => $leadForm->theme_color,
                'lead_magnet_type' => $leadForm->lead_magnet_type,
                'lead_magnet_title' => $leadForm->lead_magnet_title,
                'lead_magnet_file' => $leadForm->lead_magnet_file,
                'lead_magnet_link' => $leadForm->lead_magnet_link,
                'lead_magnet_text' => $leadForm->lead_magnet_text,
                'success_message' => $leadForm->success_message,
                'redirect_url' => $leadForm->redirect_url,
                'is_active' => $leadForm->is_active,
                'views_count' => $leadForm->views_count,
                'submissions_count' => $leadForm->submissions_count,
                'conversion_rate' => $leadForm->conversion_rate,
                'default_source' => $leadForm->defaultSource,
                'default_status' => $leadForm->default_status,
                'default_score' => $leadForm->default_score,
                'created_at' => $leadForm->created_at->format('d.m.Y H:i'),
            ],
            'recentSubmissions' => $leadForm->submissions->map(fn($s) => [
                'id' => $s->id,
                'form_data' => $s->form_data,
                'utm_source' => $s->utm_source,
                'utm_campaign' => $s->utm_campaign,
                'device_type' => $s->device_type,
                'lead' => $s->lead ? [
                    'id' => $s->lead->id,
                    'name' => $s->lead->name,
                    'status' => $s->lead->status,
                ] : null,
                'created_at' => $s->created_at->format('d.m.Y H:i'),
            ]),
            'dailyStats' => $dailyStats,
            'utmStats' => $utmStats,
        ]);
    }

    /**
     * Show the form for editing the specified lead form.
     */
    public function edit(Request $request, LeadForm $leadForm)
    {
        $this->authorize('update', $leadForm);

        $business = $request->user()->currentBusiness;

        $leadSources = LeadSource::forBusiness($business->id)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category')
            ->map(function ($sources) {
                return $sources->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'icon' => $s->icon,
                ]);
            });

        return Inertia::render('Business/LeadForms/Edit', [
            'leadForm' => $leadForm,
            'leadSources' => $leadSources,
            'fieldTypes' => LeadForm::getFieldTypes(),
        ]);
    }

    /**
     * Update the specified lead form.
     */
    public function update(Request $request, LeadForm $leadForm)
    {
        $this->authorize('update', $leadForm);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fields' => 'required|array|min:1',
            'submit_button_text' => 'nullable|string|max:50',
            'theme_color' => 'nullable|string|max:20',
            'default_source_id' => 'nullable|exists:lead_sources,id',
            'default_status' => 'nullable|in:new,contacted,qualified',
            'default_score' => 'nullable|integer|min:0|max:100',
            'lead_magnet_type' => 'nullable|in:none,file,video,link,coupon,text',
            'lead_magnet_title' => 'nullable|string|max:255',
            'lead_magnet_file' => 'nullable|file|max:10240',
            'lead_magnet_link' => 'nullable|url',
            'lead_magnet_text' => 'nullable|string',
            'success_message' => 'nullable|string',
            'redirect_url' => 'nullable|url',
            'show_lead_magnet_on_success' => 'boolean',
            'track_utm' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('lead_magnet_file')) {
            // Delete old file
            if ($leadForm->lead_magnet_file) {
                Storage::disk('public')->delete($leadForm->lead_magnet_file);
            }
            $validated['lead_magnet_file'] = $request->file('lead_magnet_file')
                ->store("lead-magnets/{$leadForm->business_id}", 'public');
        }

        $leadForm->update($validated);

        return redirect()->route('business.lead-forms.show', $leadForm)
            ->with('success', 'Lead forma yangilandi!');
    }

    /**
     * Remove the specified lead form.
     */
    public function destroy(Request $request, LeadForm $leadForm)
    {
        $this->authorize('delete', $leadForm);

        // Delete lead magnet file
        if ($leadForm->lead_magnet_file) {
            Storage::disk('public')->delete($leadForm->lead_magnet_file);
        }

        $leadForm->delete();

        return redirect()->route('business.lead-forms.index')
            ->with('success', 'Lead forma o\'chirildi!');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Request $request, LeadForm $leadForm)
    {
        $this->authorize('update', $leadForm);

        $leadForm->update([
            'is_active' => !$leadForm->is_active,
        ]);

        return back()->with('success', $leadForm->is_active ? 'Forma aktivlashtirildi!' : 'Forma o\'chirildi!');
    }

    /**
     * Duplicate a lead form
     */
    public function duplicate(Request $request, LeadForm $leadForm)
    {
        $this->authorize('view', $leadForm);

        $newForm = $leadForm->replicate();
        $newForm->name = $leadForm->name . ' (nusxa)';
        $newForm->slug = LeadForm::generateUniqueSlug($newForm->name);
        $newForm->views_count = 0;
        $newForm->submissions_count = 0;
        $newForm->save();

        return redirect()->route('business.lead-forms.edit', $newForm)
            ->with('success', 'Forma nusxalandi!');
    }

    /**
     * Get embed code
     */
    public function getEmbedCode(Request $request, LeadForm $leadForm)
    {
        $this->authorize('view', $leadForm);

        $embedCode = '<iframe src="' . $leadForm->public_url . '?embed=1" width="100%" height="500" frameborder="0"></iframe>';

        return response()->json([
            'embed_code' => $embedCode,
            'public_url' => $leadForm->public_url,
        ]);
    }
}
