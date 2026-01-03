<?php

namespace App\Http\Controllers;

use App\Models\Competitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompetitorsController extends Controller
{
    /**
     * Display competitors list.
     */
    public function index()
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Avval biznes yarating');
        }

        // Get stats with single optimized query (no N+1)
        $stats = Competitor::where('business_id', $currentBusiness->id)
            ->selectRaw('
                COUNT(*) as total_competitors,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_competitors,
                SUM(CASE WHEN threat_level >= 7 THEN 1 ELSE 0 END) as high_threat,
                AVG(threat_level) as avg_threat_level
            ')
            ->first();

        // Paginated competitors with eager loading
        $competitorsPaginated = Competitor::where('business_id', $currentBusiness->id)
            ->withCount('activities')
            ->latest()
            ->paginate(15);

        $competitors = $competitorsPaginated->getCollection()->map(function ($competitor) {
            return [
                'id' => $competitor->id,
                'name' => $competitor->name,
                'website' => $competitor->website,
                'description' => $competitor->description,
                'threat_level' => $competitor->threat_level,
                'is_active' => $competitor->is_active,
                'activities_count' => $competitor->activities_count,
                'created_at' => $competitor->created_at->format('d.m.Y'),
            ];
        });

        return Inertia::render('Business/Competitors/Index', [
            'competitors' => $competitors->values()->toArray(),
            'pagination' => [
                'current_page' => $competitorsPaginated->currentPage(),
                'last_page' => $competitorsPaginated->lastPage(),
                'per_page' => $competitorsPaginated->perPage(),
                'total' => $competitorsPaginated->total(),
            ],
            'stats' => [
                'total_competitors' => $stats->total_competitors ?? 0,
                'active_competitors' => $stats->active_competitors ?? 0,
                'high_threat' => $stats->high_threat ?? 0,
                'avg_threat_level' => round($stats->avg_threat_level ?? 0, 1),
            ],
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    /**
     * Show the form for creating a new competitor.
     */
    public function create()
    {
        return Inertia::render('Business/Competitors/Create');
    }

    /**
     * Store a newly created competitor.
     */
    public function store(Request $request)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'products' => ['nullable', 'array'],
            'pricing' => ['nullable', 'array'],
            'marketing_strategies' => ['nullable', 'array'],
            'threat_level' => ['required', 'integer', 'min:0', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $validated['business_id'] = $currentBusiness->id;

        Competitor::create($validated);

        return redirect()->route('business.competitors.index')
            ->with('success', 'Raqib muvaffaqiyatli qo\'shildi!');
    }

    /**
     * Display the specified competitor.
     */
    public function show(Competitor $competitor)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($competitor->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $competitor->load('activities');

        $activities = $competitor->activities()
            ->latest('detected_at')
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'activity_type' => $activity->activity_type,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'source_url' => $activity->source_url,
                    'detected_at' => $activity->detected_at->format('d.m.Y H:i'),
                ];
            });

        return Inertia::render('Business/Competitors/Show', [
            'competitor' => [
                'id' => $competitor->id,
                'name' => $competitor->name,
                'website' => $competitor->website,
                'description' => $competitor->description,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'products' => $competitor->products,
                'pricing' => $competitor->pricing,
                'marketing_strategies' => $competitor->marketing_strategies,
                'threat_level' => $competitor->threat_level,
                'is_active' => $competitor->is_active,
                'created_at' => $competitor->created_at->format('d.m.Y H:i'),
            ],
            'activities' => $activities,
        ]);
    }

    /**
     * Show the form for editing the specified competitor.
     */
    public function edit(Competitor $competitor)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($competitor->business_id !== $currentBusiness->id) {
            abort(403);
        }

        return Inertia::render('Business/Competitors/Edit', [
            'competitor' => [
                'id' => $competitor->id,
                'name' => $competitor->name,
                'website' => $competitor->website,
                'description' => $competitor->description,
                'strengths' => $competitor->strengths,
                'weaknesses' => $competitor->weaknesses,
                'products' => $competitor->products ?? [],
                'pricing' => $competitor->pricing ?? [],
                'marketing_strategies' => $competitor->marketing_strategies ?? [],
                'threat_level' => $competitor->threat_level,
                'is_active' => $competitor->is_active,
            ],
        ]);
    }

    /**
     * Update the specified competitor.
     */
    public function update(Request $request, Competitor $competitor)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($competitor->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'strengths' => ['nullable', 'string'],
            'weaknesses' => ['nullable', 'string'],
            'products' => ['nullable', 'array'],
            'pricing' => ['nullable', 'array'],
            'marketing_strategies' => ['nullable', 'array'],
            'threat_level' => ['required', 'integer', 'min:0', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        $competitor->update($validated);

        return redirect()->route('business.competitors.index')
            ->with('success', 'Raqib muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified competitor.
     */
    public function destroy(Competitor $competitor)
    {
        $currentBusiness = session('current_business_id')
            ? Auth::user()->businesses()->find(session('current_business_id'))
            : Auth::user()->businesses()->first();

        if ($competitor->business_id !== $currentBusiness->id) {
            abort(403);
        }

        $competitor->delete();

        return redirect()->route('business.competitors.index')
            ->with('success', 'Raqib muvaffaqiyatli o\'chirildi!');
    }
}
