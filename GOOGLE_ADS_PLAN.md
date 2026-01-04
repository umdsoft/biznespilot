# Google Ads Boshqaruv Tizimi - Implementatsiya Rejasi

## Umumiy Ma'lumot

Google Ads kampaniyalarini to'liq boshqarish tizimi - kampaniya yaratish, tahrirlash, kalit so'zlar boshqarish, targeting va byudjet sozlash.

**Muhim:** Developer Token hali mavjud emas, shuning uchun avval mock data bilan ishlaydigan UI yaratiladi. Token olingandan keyin haqiqiy API ga ulanadi.

---

## Faza 1: Database (Migrations)

### 1.1 google_ads_campaigns jadvali
- `id` (uuid) - Primary key
- `ad_integration_id` - AdIntegration ga bog'lanish
- `business_id` - Business ga bog'lanish
- `google_campaign_id` - Google'dagi kampaniya ID
- `name` - Kampaniya nomi
- `advertising_channel_type` - SEARCH, DISPLAY, VIDEO, SHOPPING
- `status` - ENABLED, PAUSED, REMOVED
- `daily_budget` - Kunlik byudjet
- `start_date`, `end_date` - Boshlanish/tugash sanasi
- `geo_targets` (JSON) - Manzil targeting
- `device_targets` (JSON) - Qurilma targeting
- Aggregated metrics: total_cost, impressions, clicks, conversions, CTR, CPC

### 1.2 google_ads_ad_groups jadvali
- Kampaniya ichidagi reklama guruhlari
- `name`, `status`, `cpc_bid`
- Targeting va audience settings

### 1.3 google_ads_keywords jadvali
- `keyword_text` - Kalit so'z
- `match_type` - EXACT, PHRASE, BROAD
- `quality_score` - Sifat bali
- Metrics: cost, impressions, clicks

### 1.4 google_ads_campaign_insights jadvali
- Kunlik statistika (Meta Campaign Insights ga o'xshash)
- cost, impressions, clicks, conversions, CTR, CPC

---

## Faza 2: Backend (Models + Services)

### 2.1 Models
- `GoogleAdsCampaign` - Kampaniya modeli
- `GoogleAdsAdGroup` - Reklama guruhi modeli
- `GoogleAdsKeyword` - Kalit so'z modeli
- `GoogleAdsCampaignInsight` - Kunlik statistika

### 2.2 Services
- `GoogleAdsSyncService` - Ma'lumotlarni sinxronlash (mock + real API)
- `GoogleAdsCampaignService` - CRUD operatsiyalar

### 2.3 Controller
- `GoogleAdsCampaignController`:
  - `index()` - Kampaniyalar ro'yxati (filter, sort, pagination)
  - `store()` - Yangi kampaniya yaratish
  - `update()` - Kampaniyani tahrirlash
  - `updateStatus()` - Pauza/Davom ettirish
  - `destroy()` - O'chirish
  - `getAdGroups()` - Reklama guruhlari
  - `getKeywords()` - Kalit so'zlar
  - `addKeywords()` - Kalit so'z qo'shish
  - `removeKeyword()` - Kalit so'z o'chirish
  - `sync()` - Sinxronlash

---

## Faza 3: Frontend (Vue Components)

### 3.1 GoogleAdsAnalytics/Index.vue yangilash
- "Kampaniyalar" tab qo'shish
- CampaignsTab komponenti import qilish

### 3.2 GoogleAdsCampaignsTab.vue
- Summary kartalar (jami kampaniyalar, faol, xarajat, kliklar)
- Filterlar (status, channel type, search)
- Kampaniyalar jadvali
- Pagination

### 3.3 CampaignModal.vue
- Kampaniya yaratish/tahrirlash formasi
- Nom, byudjet, sana, targeting sozlamalari

### 3.4 KeywordManager.vue
- Kalit so'zlar ro'yxati
- Qo'shish/o'chirish
- Match type tanlash

### 3.5 GoogleAdsCampaigns/Show.vue
- Kampaniya detail sahifasi
- Statistika, reklama guruhlari, kalit so'zlar

---

## Faza 4: Routes

```php
// web.php ga qo'shiladi
Route::prefix('google-ads-campaigns')->name('google-ads-campaigns.')->group(function () {
    Route::get('/{id}', [GoogleAdsCampaignController::class, 'showPage'])->name('show');
});

Route::prefix('api/google-ads-campaigns')->name('api.google-ads-campaigns.')->group(function () {
    Route::get('/', [GoogleAdsCampaignController::class, 'index'])->name('index');
    Route::post('/', [GoogleAdsCampaignController::class, 'store'])->name('store');
    Route::get('/{id}', [GoogleAdsCampaignController::class, 'show'])->name('show');
    Route::put('/{id}', [GoogleAdsCampaignController::class, 'update'])->name('update');
    Route::patch('/{id}/status', [GoogleAdsCampaignController::class, 'updateStatus'])->name('status');
    Route::delete('/{id}', [GoogleAdsCampaignController::class, 'destroy'])->name('destroy');
    Route::post('/sync', [GoogleAdsCampaignController::class, 'sync'])->name('sync');
    Route::get('/{id}/ad-groups', [GoogleAdsCampaignController::class, 'getAdGroups'])->name('ad-groups');
    Route::get('/ad-groups/{adGroupId}/keywords', [GoogleAdsCampaignController::class, 'getKeywords'])->name('keywords');
    Route::post('/ad-groups/{adGroupId}/keywords', [GoogleAdsCampaignController::class, 'addKeywords'])->name('keywords.add');
    Route::delete('/keywords/{keywordId}', [GoogleAdsCampaignController::class, 'removeKeyword'])->name('keywords.remove');
});
```

---

## UI Labels (O'zbekcha)

| Inglizcha | O'zbekcha |
|-----------|-----------|
| Campaign | Kampaniya |
| Ad Group | Reklama guruhi |
| Keyword | Kalit so'z |
| Status | Holat |
| Active | Faol |
| Paused | Pauza |
| Budget | Byudjet |
| Daily Budget | Kunlik byudjet |
| Impressions | Ko'rishlar |
| Clicks | Kliklar |
| Conversions | Konversiyalar |
| Cost | Xarajat |
| Match Type | Moslik turi |
| Exact Match | Aniq moslik |
| Phrase Match | Ibora mosligi |
| Broad Match | Keng moslik |
| Create Campaign | Kampaniya yaratish |
| Pause | To'xtatish |
| Resume | Davom ettirish |

---

## Bajarish Tartibi

1. **Database** - 4 ta migration yaratish
2. **Models** - 4 ta model yaratish
3. **Services** - 2 ta service yaratish
4. **Controller** - GoogleAdsCampaignController
5. **Routes** - web.php ga qo'shish
6. **Frontend** - Vue komponentlar

---

## Developer Token Olingandan Keyin

1. `.env` ga token qo'shish: `GOOGLE_ADS_DEVELOPER_TOKEN=xxx`
2. `GoogleAdsSyncService` da `$useMockData = false` qilish
3. Haqiqiy API ulanishini test qilish
4. Sinxronlashni ishga tushirish
