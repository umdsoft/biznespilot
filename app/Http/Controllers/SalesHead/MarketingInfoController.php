<?php

namespace App\Http\Controllers\SalesHead;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Competitor;
use App\Models\DreamBuyer;
use App\Models\Offer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketingInfoController extends Controller
{
    use HasCurrentBusiness;

    /**
     * Marketing ma'lumotlari - tab-based sahifa
     * Ideal Mijoz, Takliflar, Raqobatchilar bitta sahifada
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $activeTab = $request->get('tab', 'dream-buyer');

        // Dream Buyers
        $dreamBuyers = DreamBuyer::where('business_id', $business->id)
            ->select([
                'id',
                'name',
                'age_range',
                'gender',
                'location',
                'income_level',
                'pain_points',
                'goals',
                'created_at',
            ])
            ->latest()
            ->get();

        // Offers
        $offers = Offer::where('business_id', $business->id)
            ->select([
                'id',
                'title',
                'name',
                'description',
                'offer_type',
                'price',
                'original_price',
                'benefits',
                'is_active',
                'created_at',
            ])
            ->latest()
            ->get();

        // Competitors
        $competitors = Competitor::where('business_id', $business->id)
            ->select([
                'id',
                'name',
                'website',
                'logo',
                'threat_level',
                'market_share',
                'employee_count',
                'strengths',
                'weaknesses',
                'created_at',
            ])
            ->latest()
            ->get();

        return Inertia::render('SalesHead/MarketingInfo/Index', [
            'dreamBuyers' => $dreamBuyers,
            'offers' => $offers,
            'competitors' => $competitors,
            'activeTab' => $activeTab,
        ]);
    }
}
