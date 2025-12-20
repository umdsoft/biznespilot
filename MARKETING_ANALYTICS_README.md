# Marketing Analytics Module

BiznesPilot AI Marketing Analytics tizimi - Instagram, Telegram, Facebook va Google Ads platformalari uchun real-time analytics va metrikalar.

## Yaratilgan Komponentlar

### 1. Database Migrations
Quyidagi migratsiyalar yaratildi va ishga tushirildi:

- `2025_12_19_102626_create_instagram_metrics_table.php` - Instagram metrikalar jadvali
- `2025_12_19_102628_create_telegram_metrics_table.php` - Telegram metrikalar jadvali
- `2025_12_19_102629_create_facebook_metrics_table.php` - Facebook metrikalar jadvali
- `2025_12_19_102631_create_google_ads_metrics_table.php` - Google Ads metrikalar jadvali
- `2025_12_19_105225_add_api_fields_to_marketing_channels_table.php` - MarketingChannel jadvali yangilandi

### 2. Eloquent Models
Har bir platforma uchun alohida model:

- **InstagramMetric** (`app/Models/InstagramMetric.php`)
  - Followers, reach, impressions, engagement
  - Stories va Reels metrikalar
  - Engagement rate hisoblash

- **TelegramMetric** (`app/Models/TelegramMetric.php`)
  - Members count, views, forwards
  - Bot statistikalari
  - Growth rate va engagement rate

- **FacebookMetric** (`app/Models/FacebookMetric.php`)
  - Page likes, followers, reach
  - Video statistikalari
  - CTA clicks va website clicks

- **GoogleAdsMetric** (`app/Models/GoogleAdsMetric.php`)
  - Campaign performance
  - Cost metrics (kopeklarda saqlanadi)
  - CTR, conversion rate, ROAS hisoblash

### 3. API Integration Services
Har bir platforma uchun to'liq API integration:

#### InstagramService (`app/Services/InstagramService.php`)
```php
// Metrikalarni sinchlash
$service = app(InstagramService::class);
$metric = $service->syncMetrics($channel, Carbon::today());

// Access token yangilash
$newToken = $service->refreshAccessToken($currentToken);

// Davr uchun sinchlash
$synced = $service->syncMetricsRange($channel, $startDate, $endDate);
```

**Funksionallik:**
- Instagram Graph API v18.0 integratsiyasi
- Account insights (followers, reach, profile views)
- Media insights (posts, stories, reels)
- Automatic token refresh
- Error handling va logging

#### TelegramService (`app/Services/TelegramService.php`)
```php
// Metrikalarni sinchlash
$service = app(TelegramService::class);
$metric = $service->syncMetrics($channel, Carbon::today());

// Webhook sozlash
$service->setWebhook($botToken, $webhookUrl);

// Xabar yuborish
$service->sendMessage($botToken, $chatId, $text);
```

**Funksionallik:**
- Telegram Bot API integratsiyasi
- Channel/group statistikalari
- Bot messages tracking
- Webhook support
- Real-time updates

#### FacebookService (`app/Services/FacebookService.php`)
```php
// Metrikalarni sinchlash
$service = app(FacebookService::class);
$metric = $service->syncMetrics($channel, Carbon::today());

// Token yangilash
$newToken = $service->refreshAccessToken($currentToken);
```

**Funksionallik:**
- Facebook Graph API v18.0
- Page insights
- Post engagement metrics
- Video statistics
- CTA tracking

#### GoogleAdsService (`app/Services/GoogleAdsService.php`)
```php
// Metrikalarni sinchlash
$service = app(GoogleAdsService::class);
$metrics = $service->syncMetrics($channel, Carbon::today());

// Campaign ro'yxati
$campaigns = $service->getCampaigns($accessToken, $customerId);

// Conversion actions
$conversions = $service->getConversionActions($accessToken, $customerId);
```

**Funksionallik:**
- Google Ads API v15
- Campaign performance metrics
- Cost tracking (micros to kopeks conversion)
- ROAS calculation
- Multiple campaigns support

### 4. Background Jobs
Avtomatik metrikalar sinchlash uchun joblar:

#### SyncMarketingMetrics (`app/Jobs/SyncMarketingMetrics.php`)
Bitta channel uchun metrikalarni sinchlaydi.

```php
// Dispatchlarash
SyncMarketingMetrics::dispatch($channel, Carbon::today());
```

**Xususiyatlar:**
- 3 marta retry qilish
- 60 soniya backoff
- Automatic token refresh
- Error logging
- Failed job handling

#### SyncAllChannelsMetrics (`app/Jobs/SyncAllChannelsMetrics.php`)
Barcha aktiv channellar uchun sync joblarni dispatch qiladi.

```php
// Dispatchlarash
SyncAllChannelsMetrics::dispatch(Carbon::yesterday());
```

### 5. Scheduled Tasks
`routes/console.php` da avtomatik schedule:

```php
// Har kuni soat 02:00 da ishga tushadi
Schedule::job(new SyncAllChannelsMetrics())
    ->dailyAt('02:00')
    ->timezone('Asia/Tashkent')
    ->name('sync-marketing-metrics')
    ->onOneServer();
```

**Scheduler ishga tushirish:**
```bash
# Development
php artisan schedule:work

# Production (crontab)
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Frontend Components (Vue 3)

#### Dashboard.vue (`resources/js/Pages/Marketing/Dashboard.vue`)
Marketing analytics asosiy sahifa:
- KPI cards (Total Reach, Engagement, Channels)
- Channel grid with platform icons
- Quick stats overview
- Responsive design

#### ChannelDetail.vue (`resources/js/Pages/Marketing/ChannelDetail.vue`)
Har bir channel uchun batafsil analytics:
- Chart.js line charts
- Period filter (7, 14, 30, 60, 90 kun)
- Platform-specific metrics
- Summary statistics cards
- Recent metrics table
- Google Ads xarajat va konversiya bo'limi

### 7. Controller
`app/Http/Controllers/MarketingAnalyticsController.php` - To'liq CRUD va analytics:

**Routes:**
```php
GET  /marketing                    - Dashboard
GET  /marketing/channels           - Channels list
POST /marketing/channels           - Create channel
GET  /marketing/channels/{id}      - Channel detail
PUT  /marketing/channels/{id}      - Update channel
DELETE /marketing/channels/{id}    - Delete channel
```

## Konfiguratsiya

### Environment Variables

`.env` fayliga qo'shing:

```env
# Facebook/Instagram API
FACEBOOK_CLIENT_ID=your_app_id
FACEBOOK_CLIENT_SECRET=your_app_secret
FACEBOOK_REDIRECT_URI=https://yourdomain.com/auth/facebook/callback

# Google Ads API
GOOGLE_ADS_CLIENT_ID=your_client_id
GOOGLE_ADS_CLIENT_SECRET=your_client_secret
GOOGLE_ADS_DEVELOPER_TOKEN=your_developer_token
GOOGLE_ADS_REDIRECT_URI=https://yourdomain.com/auth/google/callback
```

### Queue Configuration

`config/queue.php` da `marketing-sync` queue yarating yoki default queue ishlatiladi.

**Queue worker ishga tushirish:**
```bash
php artisan queue:work --queue=marketing-sync
```

## Ishlatish

### 1. Marketing Channel Yaratish

```php
use App\Models\MarketingChannel;

$channel = MarketingChannel::create([
    'business_id' => $businessId,
    'name' => 'Instagram Business Account',
    'type' => 'instagram', // instagram, telegram, facebook, google_ads
    'platform' => 'instagram',
    'platform_account_id' => 'instagram_account_id',
    'access_token' => 'long_lived_access_token',
    'is_active' => true,
]);
```

### 2. Metrikalarni Qo'lda Sinchlash

```php
use App\Jobs\SyncMarketingMetrics;

// Bitta channel
SyncMarketingMetrics::dispatch($channel);

// Barcha channellar
SyncAllChannelsMetrics::dispatch();
```

### 3. Metrikalarni O'qish

```php
// Eng so'nggi metrika
$latestMetric = $channel->latestMetrics();

// Davr bo'yicha
$metrics = $channel->instagramMetrics()
    ->whereBetween('metric_date', [$startDate, $endDate])
    ->orderBy('metric_date', 'desc')
    ->get();

// Aggregated stats
$totalReach = $channel->instagramMetrics()
    ->where('metric_date', '>=', Carbon::now()->subDays(30))
    ->sum('reach');
```

### 4. Chart Data Tayyorlash

```php
// Controller da
$chartData = $this->prepareInstagramChartData($metrics);

return Inertia::render('Marketing/ChannelDetail', [
    'channel' => $channel,
    'chartData' => $chartData,
]);
```

## API Endpoints

### Instagram
- Base URL: `https://graph.facebook.com/v18.0`
- Required scopes: `instagram_basic`, `instagram_manage_insights`, `pages_read_engagement`
- Rate limits: 200 calls per hour per user

### Telegram
- Base URL: `https://api.telegram.org/bot{token}`
- Required: Bot token
- Rate limits: 30 messages per second

### Facebook
- Base URL: `https://graph.facebook.com/v18.0`
- Required scopes: `pages_read_engagement`, `pages_show_list`, `read_insights`
- Rate limits: 200 calls per hour per user

### Google Ads
- Base URL: `https://googleads.googleapis.com/v15`
- Required: Developer token, OAuth2 credentials
- Rate limits: 15,000 operations per day

## Ma'lumotlar Strukturasi

### Instagram Metrics
- **Daily metrics**: followers_count, reach, impressions
- **Engagement**: likes, comments, shares, saves
- **Stories**: posted count, reach, impressions, replies
- **Reels**: posted count, plays, reach, engagement

### Telegram Metrics
- **Growth**: members_count, new_members, left_members
- **Content**: posts_count, total_views, average_views
- **Engagement**: reactions, comments, forwards, shares
- **Bot**: messages_sent, messages_received, commands_used, active_users

### Facebook Metrics
- **Page**: likes, followers, views
- **Content**: posts, reach, impressions, engagement
- **Video**: views, reach, watch time
- **Actions**: CTA clicks, website clicks, phone calls

### Google Ads Metrics
- **Performance**: impressions, clicks, conversions
- **Cost**: cost, avg_cpc, avg_cpm, avg_cpa (kopeklarda)
- **Quality**: quality_score, ctr, conversion_rate
- **ROI**: conversion_value, roas

## Xatoliklarni Bartaraf Etish

### Token Expired
```php
// Automatic token refresh services ichida
$refreshedToken = $service->refreshAccessToken($channel->access_token);
if ($refreshedToken) {
    $channel->update(['access_token' => $refreshedToken]);
}
```

### API Rate Limits
Joblar 3 marta retry qiladi va 60 soniya backoff bilan:
```php
public int $tries = 3;
public int $backoff = 60;
```

### Sync Failed
Loglarni tekshiring:
```bash
tail -f storage/logs/laravel.log
```

## Testing

```bash
# Bitta channel test
php artisan tinker
>>> $channel = App\Models\MarketingChannel::first();
>>> App\Jobs\SyncMarketingMetrics::dispatch($channel);

# Schedule test
php artisan schedule:test

# Barcha channels sync
php artisan tinker
>>> App\Jobs\SyncAllChannelsMetrics::dispatch();
```

## Keyingi Qadamlar

### OAuth Integration
- [ ] Instagram OAuth flow
- [ ] Facebook OAuth flow
- [ ] Google Ads OAuth flow
- [ ] Telegram Bot setup wizard

### Advanced Features
- [ ] AI-powered insights
- [ ] Competitor analysis
- [ ] Automated reporting
- [ ] Custom alerts
- [ ] Export to PDF/Excel

### Optimizations
- [ ] Batch API requests
- [ ] Redis caching
- [ ] Database indexing
- [ ] Query optimization

## Ma'lumotnomalar

- [Instagram Graph API](https://developers.facebook.com/docs/instagram-api)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Facebook Graph API](https://developers.facebook.com/docs/graph-api)
- [Google Ads API](https://developers.google.com/google-ads/api/docs/start)

---

**Version:** 1.0.0
**Created:** 2025-12-19
**Author:** BiznesPilot AI Development Team
