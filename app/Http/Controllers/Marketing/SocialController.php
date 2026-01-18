<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SocialController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $accounts = [
            [
                'id' => 1,
                'platform' => 'instagram',
                'username' => '@biznespilot',
                'followers' => 8500,
                'connected' => true,
                'last_sync' => now()->subHours(2)->format('Y-m-d H:i'),
            ],
            [
                'id' => 2,
                'platform' => 'facebook',
                'username' => 'BiznesPilot',
                'followers' => 4200,
                'connected' => true,
                'last_sync' => now()->subHours(1)->format('Y-m-d H:i'),
            ],
            [
                'id' => 3,
                'platform' => 'telegram',
                'username' => '@biznespilot_uz',
                'followers' => 2800,
                'connected' => true,
                'last_sync' => now()->subMinutes(30)->format('Y-m-d H:i'),
            ],
            [
                'id' => 4,
                'platform' => 'youtube',
                'username' => 'BiznesPilot',
                'followers' => 1200,
                'connected' => false,
                'last_sync' => null,
            ],
        ];

        return Inertia::render('Marketing/Social/Index', [
            'accounts' => $accounts,
        ]);
    }

    public function connect(Request $request)
    {
        $platform = $request->get('platform');

        // OAuth connection logic here

        return redirect()->route('marketing.social.index')
            ->with('success', ucfirst($platform).' muvaffaqiyatli ulandi');
    }

    public function disconnect($id)
    {
        return redirect()->route('marketing.social.index')
            ->with('success', 'Akkaunt uzildi');
    }

    public function sync($id)
    {
        return redirect()->route('marketing.social.index')
            ->with('success', 'Ma\'lumotlar yangilandi');
    }
}
