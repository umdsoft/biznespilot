<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\InstagramAccount;
use App\Models\MarketingChannel;
use App\Models\TelegramChannel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChannelController extends Controller
{
    use HasCurrentBusiness;

    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        // 1) Manual marketing channels (legacy)
        $manualChannels = MarketingChannel::where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($channel) {
                return [
                    'id' => 'manual_' . $channel->id,
                    'source' => 'manual',
                    'name' => $channel->name,
                    'type' => $channel->type,
                    'platform' => $channel->platform,
                    'status' => $channel->is_active ? 'active' : 'inactive',
                    'followers_count' => $channel->metrics['followers_count'] ?? null,
                    'monthly_reach' => $channel->metrics['monthly_reach'] ?? null,
                    'engagement_rate' => $channel->metrics['engagement_rate'] ?? null,
                    'url' => $channel->config['url'] ?? null,
                    'notes' => $channel->description,
                    'created_at' => $channel->created_at->format('d.m.Y'),
                ];
            });

        // 2) Live Telegram channels — System Bot orqali ulangan
        $telegramChannels = TelegramChannel::where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('connected_at', 'desc')
            ->get()
            ->map(function ($channel) {
                return [
                    'id' => 'telegram_' . $channel->id,
                    'source' => 'telegram',
                    'name' => $channel->title,
                    'type' => $channel->type, // channel | supergroup
                    'platform' => 'telegram',
                    'status' => $channel->admin_status === 'administrator' ? 'active' : 'inactive',
                    'followers_count' => (int) ($channel->subscriber_count ?? 0),
                    'monthly_reach' => null,
                    'engagement_rate' => null,
                    'url' => $channel->chat_username ? 'https://t.me/' . ltrim($channel->chat_username, '@') : null,
                    'notes' => $channel->description,
                    'created_at' => $channel->connected_at?->format('d.m.Y') ?? $channel->created_at->format('d.m.Y'),
                ];
            });

        // 3) Live Instagram accounts
        $instagramChannels = InstagramAccount::where('business_id', $business->id)
            ->where('is_active', true)
            ->orderBy('connected_at', 'desc')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => 'instagram_' . $account->id,
                    'source' => 'instagram',
                    'name' => $account->name ?: ('@' . $account->username),
                    'type' => 'business',
                    'platform' => 'instagram',
                    'status' => 'active',
                    'followers_count' => (int) ($account->followers_count ?? 0),
                    'monthly_reach' => null,
                    'engagement_rate' => $account->engagement_rate ? round((float) $account->engagement_rate, 2) : null,
                    'url' => $account->username ? 'https://instagram.com/' . $account->username : null,
                    'notes' => $account->biography,
                    'created_at' => optional($account->connected_at ?? $account->created_at)->format('d.m.Y'),
                ];
            });

        // Merge — live integrations birinchi (foydalanuvchi ulagan), keyin manual
        $channels = $telegramChannels->concat($instagramChannels)->concat($manualChannels)->values();

        return Inertia::render('Marketing/Channels', [
            'channels' => $channels,
        ]);
    }

    public function store(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:social_media,email,sms,advertising,other'],
            'platform' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:active,inactive,paused'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
            'engagement_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        MarketingChannel::create([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'platform' => $validated['platform'],
            'description' => $validated['notes'] ?? null,
            'is_active' => $validated['status'] === 'active',
            'config' => [
                'url' => $validated['url'] ?? null,
            ],
            'metrics' => [
                'followers_count' => $validated['followers_count'] ?? null,
                'monthly_reach' => $validated['monthly_reach'] ?? null,
                'engagement_rate' => $validated['engagement_rate'] ?? null,
            ],
        ]);

        return redirect()->back()
            ->with('success', 'Kanal muvaffaqiyatli qo\'shildi!');
    }

    public function show($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        // Get metrics based on channel type
        $metrics = [];
        $chartData = ['labels' => [], 'datasets' => []];
        $summary = [];

        $period = request('period', 30);

        // Fetch metrics based on channel type
        switch ($channel->type) {
            case 'instagram':
                $metricsData = $channel->instagramMetrics()
                    ->where('metric_date', '>=', now()->subDays($period))
                    ->orderBy('metric_date', 'desc')
                    ->get();
                break;
            case 'telegram':
                $metricsData = $channel->telegramMetrics()
                    ->where('metric_date', '>=', now()->subDays($period))
                    ->orderBy('metric_date', 'desc')
                    ->get();
                break;
            case 'facebook':
                $metricsData = $channel->facebookMetrics()
                    ->where('metric_date', '>=', now()->subDays($period))
                    ->orderBy('metric_date', 'desc')
                    ->get();
                break;
            case 'google_ads':
                $metricsData = $channel->googleAdsMetrics()
                    ->where('metric_date', '>=', now()->subDays($period))
                    ->orderBy('metric_date', 'desc')
                    ->get();
                break;
            default:
                $metricsData = collect();
        }

        $metrics = $metricsData->toArray();

        return Inertia::render('Marketing/ChannelDetail', [
            'channel' => [
                'id' => $channel->id,
                'name' => $channel->name,
                'type' => $channel->type,
                'platform' => $channel->platform,
            ],
            'metrics' => $metrics,
            'chartData' => $chartData,
            'summary' => $summary,
            'period' => $period,
        ]);
    }

    public function update(Request $request, $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:social_media,email,sms,advertising,other'],
            'platform' => ['required', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', 'in:active,inactive,paused'],
            'followers_count' => ['nullable', 'integer', 'min:0'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
            'engagement_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $channel->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'platform' => $validated['platform'],
            'description' => $validated['notes'] ?? null,
            'is_active' => $validated['status'] === 'active',
            'config' => array_merge($channel->config ?? [], [
                'url' => $validated['url'] ?? null,
            ]),
            'metrics' => array_merge($channel->metrics ?? [], [
                'followers_count' => $validated['followers_count'] ?? null,
                'monthly_reach' => $validated['monthly_reach'] ?? null,
                'engagement_rate' => $validated['engagement_rate'] ?? null,
            ]),
        ]);

        return redirect()->back()
            ->with('success', 'Kanal muvaffaqiyatli yangilandi!');
    }

    public function destroy($id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('id', $id)
            ->firstOrFail();

        $channel->delete();

        return redirect()->route('marketing.channels.index')
            ->with('success', 'Kanal muvaffaqiyatli o\'chirildi!');
    }
}
