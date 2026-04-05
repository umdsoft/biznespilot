# BIZNESPILOT AI AGENT — QO'SHIMCHA MODULLAR TEXNIK TOPSHIRIQ
## 7 ta noyob modul | Versiya: 1.0 | Asosiy TZ ga ilova

---

> **MUHIM:** Bu hujjat asosiy TZ (`BiznesPilot_AI_Agent_TZ_v1.md`) ga qo'shimcha hisoblanadi. Ushbu modullar mavjud agent tizimi ustiga quriladi va barcha umumiy xizmatlar (AIService, xotira tizimi, tekshiruvchi, sohaviy bilim) dan foydalanadi. Joriy etishda avval asosiy TZ dagi 4 ta bosqich bajariladi, keyin shu modullar qo'shiladi.

---

## QO'SHIMCHA MODUL 1: OVOZLI AI YORDAMCHI

### 1.1 Maqsad

Biznes egasiga Telegram orqali ovozli xabar yuborib, AI Agent bilan gaplashish imkonini berish. Agent ovozni matnga aylantiradi, so'rovni bajaradi, va ovozli javob qaytaradi. Bu O'zbekistondagi biznes egalari uchun juda qulay — ko'pchilik yozishdan ko'ra gaplashishni afzal ko'radi, ayniqsa haydash paytida yoki ish davomida.

### 1.2 Ishlash oqimi

```
Foydalanuvchi Telegram da ovozli xabar yuboradi
    │
    ▼
1. Telegram webhook ovozli faylni qabul qiladi
    │
    ▼
2. Audio fayl yuklab olinadi (Telegram getFile API)
    │ Format: .ogg (Telegram standart formati)
    │ Hajm chegarasi: 20 MB (Telegram limiti)
    │
    ▼
3. Groq Whisper Turbo orqali matnga aylantirish
    │ Model: whisper-large-v3-turbo
    │ Til: avtomatik aniqlash (o'zbek/rus/ingliz)
    │ Narx: $0.04/soat audio
    │ Tezlik: ~200x real vaqtdan tez (1 daqiqa audio = 0.3 sek)
    │
    ▼
4. Matn Boshqaruvchi agentga yuboriladi
    │ (Oddiy savollar bazadan, murakkablari AI dan)
    │
    ▼
5. Javob matni olinadi
    │
    ▼
6. Matndan ovozga aylantirish (TTS)
    │ Variant A: Groq PlayAI ($0.05/1000 belgi) — sifatli
    │ Variant B: Google TTS (bepul, 500K belgi/oy) — tejamkor
    │ Variant C: Edge TTS (bepul, ochiq manba) — eng arzon
    │
    │ Tavsiya: boshlang'ich bosqichda Edge TTS (bepul)
    │ Kelajakda: Groq PlayAI (sifatli o'zbek ovozi)
    │
    ▼
7. Ovozli javob Telegram ga yuboriladi
    │ sendVoice API orqali
    │
    ▼
Foydalanuvchi ovozli javobni tinglaydi
```

### 1.3 Gibrid mantiq — token tejash

```
Ovozli so'rov keldi → Matnga aylantirildi
    │
    ├── "Bugungi sotuvlar" → Bazadan (bepul) → Shablon javob
    ├── "Qancha lead bor" → Bazadan (bepul) → Shablon javob
    ├── "Hisobot ber" → Bazadan hisoblash → Shablon bilan formatlanadi
    │
    ├── "Nima qilsam yaxshi?" → Haiku ga yuboriladi (kam token)
    └── "Strategiya tuzing" → Sonnet ga yuboriladi (sifatli)

Oddiy savollarning 70% iga AI kerak emas — bazadan javob olinadi.
```

### 1.4 Til aniqlash va ko'p tillilik

O'zbekistonda biznes egalari o'zbek, rus, va aralash (o'zbek+rus) tilda gaplashadi. Whisper barcha 3 tilni avtomatik aniqlaydi. Javob foydalanuvchi qaysi tilda gaplashgan bo'lsa, shu tilda qaytariladi.

```php
// Til aniqlash
$transcription = $aiService->transcribe($audioPath);
$detectedLanguage = $transcription->language; // 'uz', 'ru', yoki 'en'

// Javobni shu tilda yaratish
$response = $agent->handle($transcription->text, [
    'response_language' => $detectedLanguage,
    'format' => 'spoken', // qisqa, suhbat uslubida
]);
```

### 1.5 Ovozli javob formatlash

Ovozli javob yozma javobdan farqlanishi kerak — u qisqa, aniq va gaplashish uslubida bo'lishi lozim:

```
Yozma javob: "Bugungi sotuv natijalari: jami buyurtmalar soni — 12, 
umumiy daromad — 4,200,000 so'm, o'tgan kunga nisbatan +15%. 
Eng ko'p sotilgan mahsulot — IELTS tayyorlov kursi (4 ta)."

Ovozli javob: "Bugun yaxshi kun bo'ldi! 12 ta buyurtma, 
4 million 200 ming so'm tushdi. Kechagidan 15 foiz ko'p.
Eng ko'p IELTS kursi sotildi — 4 ta."
```

AI ga yuboriladigan ko'rsatmada quyidagi qo'shiladi:
```
Javobni ovozli suhbat uchun formatla:
- Qisqa gaplar (10-15 so'z)
- Raqamlarni so'z bilan ayt (4,200,000 → "4 million 200 ming")
- Tabiiy gaplashish uslubi
- Ortiqcha tafsilot yo'q — eng muhim 2-3 ta fakt
```

### 1.6 Ma'lumotlar bazasi

```sql
CREATE TABLE voice_interactions (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    conversation_id CHAR(36) NULL,
    audio_input_url TEXT NULL,
    audio_input_duration_sec INT NULL,
    transcript_text TEXT NULL,
    detected_language VARCHAR(5) NULL,          -- 'uz', 'ru', 'en'
    response_text TEXT NULL,
    audio_output_url TEXT NULL,
    audio_output_duration_sec INT NULL,
    whisper_cost_usd DECIMAL(8,6) DEFAULT 0,
    tts_cost_usd DECIMAL(8,6) DEFAULT 0,
    total_cost_usd DECIMAL(8,6) DEFAULT 0,
    processing_time_ms INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_user (user_id)
);
```

### 1.7 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Voice/
│           ├── VoiceAgentService.php         — asosiy mantiq
│           ├── SpeechToText/
│           │   └── GroqWhisperSTT.php        — ovozdan matnga (Groq)
│           ├── TextToSpeech/
│           │   ├── TTSInterface.php           — umumiy interfeys
│           │   ├── EdgeTTS.php                — bepul variant
│           │   └── GroqPlayAI.php             — sifatli variant
│           └── VoiceResponseFormatter.php    — javobni ovozli formatga
├── Jobs/
│   └── ProcessVoiceMessage.php               — ovozli xabar qayta ishlash
```

**Webhook qo'shimchasi** (mavjud Telegram webhook ga):
```php
// Mavjud Telegram webhook ichida
if ($update->message->voice) {
    // Ovozli xabar — VoiceAgentService ga yuborish
    ProcessVoiceMessage::dispatch($update, $businessId);
    return;
}
// Qolgan mantiq o'zgarishsiz davom etadi
```

### 1.8 Cheklovlar tarif bo'yicha

| Tarif | Ovozli xabar/oy | Javob turi |
|-------|----------------|------------|
| Boshlang'ich | Yo'q (faqat matnli) | — |
| O'sish | 100 ta ovozli xabar | Matnli + ovozli javob |
| Kengaytirilgan | Cheksiz | Matnli + ovozli javob |

---

## QO'SHIMCHA MODUL 2: BIZNES SOG'LIGI MONITORI

### 2.1 Maqsad

Har hafta biznesning "sog'lig'ini" 0-100 ball bilan baholash. 4 ta soha bo'yicha alohida ball: Marketing, Sotuv, Moliya, Mijoz. Har bir soha uchun muammo aniqlansa — aniq yechim tavsiya qiladi. Oddiy tilda, biznes egasi tushunadi.

### 2.2 Baholash formulasi

**Umumiy sog'lik bali = Marketing (25%) + Sotuv (30%) + Moliya (25%) + Mijoz (20%)**

Har bir soha 0-100 ball bilan baholanadi:

**Marketing sog'ligi (0-100):**
```
post_regularity = oxirgi 7 kunda nechta post / maqsad × 100 (maks 100)
engagement_trend = shu hafta engagement / o'tgan hafta × 100
reach_growth = shu hafta reach / o'tgan hafta × 100
competitor_position = biznes reytingi sohadagi o'rtachaga nisbatan

Marketing = post_regularity × 0.30 + engagement_trend × 0.25 
          + reach_growth × 0.25 + competitor_position × 0.20
```

**Sotuv sog'ligi (0-100):**
```
lead_response_time = agar < 2 soat = 100, < 24 soat = 60, > 24 soat = 20
funnel_conversion = haqiqiy conversion / sohaviy o'rtacha × 100
hot_leads_handled = javob berilgan issiq leadlar / jami issiq leadlar × 100
pipeline_health = har bir bosqichda lead bor = 100, bo'sh bosqich bor = -20

Sotuv = lead_response_time × 0.35 + funnel_conversion × 0.25
      + hot_leads_handled × 0.25 + pipeline_health × 0.15
```

**Moliyaviy sog'lik (0-100):**
```
revenue_trend = shu oy daromad / o'tgan oy × 100
cac_efficiency = sohaviy o'rtacha CAC / haqiqiy CAC × 100
roas_health = haqiqiy ROAS / maqsad ROAS × 100
spend_balance = daromad / xarajat nisbati (2x dan yuqori = 100)

Moliya = revenue_trend × 0.30 + cac_efficiency × 0.25 
       + roas_health × 0.25 + spend_balance × 0.20
```

**Mijoz sog'ligi (0-100):**
```
repeat_rate = qayta xarid qilgan mijozlar / jami × 100
complaint_rate = 100 - (shikoyatlar / jami mijozlar × 100)
review_sentiment = ijobiy izohlar / jami izohlar × 100
churn_risk = 100 - (60 kun xarid qilmagan / jami × 100)

Mijoz = repeat_rate × 0.30 + complaint_rate × 0.20 
      + review_sentiment × 0.25 + churn_risk × 0.25
```

### 2.3 Sog'lik darajalari

| Ball | Daraja | Rang | Harakat |
|------|--------|------|---------|
| 90-100 | A'lo | Yashil | Davom eting, kichik optimizatsiya |
| 70-89 | Yaxshi | Yashil-sariq | 1-2 ta soha e'tiborga muhtoj |
| 50-69 | O'rtacha | Sariq | Bir nechta muammo — tezda hal qiling |
| 30-49 | Xavfli | To'q sariq | Jiddiy muammolar — darhol harakat |
| 0-29 | Tanazzul | Qizil | Favqulodda holat — barcha kuchlarni yo'naltiring |

### 2.4 Gibrid mantiq

```
Haftalik cron (dushanba 08:00):

1-qadam: Bazadan barcha ko'rsatkichlarni olish (SQL so'rovlar, bepul)
    - Oxirgi 7 kun: postlar, engagement, reach
    - Oxirgi 7 kun: leadlar, javob vaqti, conversion
    - Oxirgi 30 kun: daromad, xarajat, CAC, ROAS
    - Oxirgi 30 kun: qayta xarid, shikoyatlar, izohlar

2-qadam: Formulalar bo'yicha ball hisoblash (PHP, bepul)

3-qadam: O'tgan hafta bilan solishtirish (bepul)

4-qadam: Agar ball 70 dan past bo'lsa:
    → Haiku ga qisqa so'rov: "Marketing bali 45, sabab: 
       engagement 30% tushdi, 5 kun post qilinmadi.
       Oddiy tilda 2-3 ta aniq maslahat ber."
    → Haiku javob beradi (~300 token)

5-qadam: Natija saqlanadi + foydalanuvchiga yuboriladi
    - Platforma ichida bildirish
    - Telegram xabar
    - O'sish+ tariflarda: ovozli xulosa
```

### 2.5 Ma'lumotlar bazasi

```sql
CREATE TABLE business_health_scores (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    overall_score INT NOT NULL,                -- 0-100
    marketing_score INT NOT NULL,
    marketing_details JSON NOT NULL,            -- har bir komponent bali
    sales_score INT NOT NULL,
    sales_details JSON NOT NULL,
    finance_score INT NOT NULL,
    finance_details JSON NOT NULL,
    customer_score INT NOT NULL,
    customer_details JSON NOT NULL,
    previous_overall_score INT NULL,            -- o'tgan hafta bali
    change_from_previous INT NULL,             -- +5 yoki -3 kabi
    top_issues JSON NULL,                      -- eng muhim muammolar
    recommendations JSON NULL,                 -- AI tavsiyalari
    ai_tokens_used INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_business_period (business_id, period_start),
    INDEX idx_score (overall_score)
);
```

### 2.6 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── HealthMonitor/
│           ├── BusinessHealthService.php        — asosiy mantiq
│           ├── Calculators/
│           │   ├── MarketingHealthCalculator.php
│           │   ├── SalesHealthCalculator.php
│           │   ├── FinanceHealthCalculator.php
│           │   └── CustomerHealthCalculator.php
│           ├── HealthReportGenerator.php        — hisobot yaratish
│           └── HealthAlertService.php           — ogohlantirish yuborish
├── Console/
│   └── Commands/
│       └── CalculateBusinessHealth.php          -- php artisan agent:health-check
```

### 2.7 Cheklovlar

| Tarif | Chastota | Tavsiya | Yuborish kanali |
|-------|----------|---------|-----------------|
| Boshlang'ich | Haftalik | Faqat ball (AI tavsiyasiz) | Platforma ichida |
| O'sish | Haftalik | Ball + AI tavsiya | Platforma + Telegram |
| Kengaytirilgan | Kundalik | Ball + AI tavsiya + tendentsiya | Platforma + Telegram + ovozli |

---

## QO'SHIMCHA MODUL 3: AI XODIM O'QITUVCHI (TRENER)

### 3.1 Maqsad

Yangi sotuv xodimlarini sun'iy aql orqali o'qitish. Agent mijoz rolini o'ynaydi, xodim sotuvchi bo'lib mashq qiladi. Agent turli e'tirozlar qo'yadi, xodimning javoblarini baholaydi, va aniq maslahat beradi. Sohaviy bilim bazasidagi eng yaxshi gaplar asosida o'rgatadi.

### 3.2 Mashq turlari

| Mashq turi | Tavsif | Davomiyligi |
|------------|--------|-------------|
| Salomlashish mashqi | To'g'ri birinchi taassurot | 5-10 daqiqa |
| Mahsulot taqdimoti | Qiymatni to'g'ri ko'rsatish | 10-15 daqiqa |
| E'tiroz bartaraf qilish | Turli e'tirozlarga javob | 15-20 daqiqa |
| Yakunlash mashqi | Sotuvni to'g'ri yakunlash | 10-15 daqiqa |
| To'liq suhbat | Boshidan oxirigacha | 20-30 daqiqa |

### 3.3 Ishlash oqimi

```
Xodim mashq boshlaydi (platforma ichida yoki Telegram da)
    │
    ▼
Agent mashq turini so'raydi yoki rahbar belgilaydi
    │
    ▼
Agent mijoz rolini boshlaydi:
    │ "Salom, IELTS kurslaringiz haqida bilgim keladi"
    │
    │ Xodim javob beradi
    │     │
    │     ▼
    │ Agent javobni real vaqtda baholaydi:
    │     ├── Javob yaxshi → suhbat davom etadi + ball oshadi
    │     ├── Javob o'rtacha → kichik maslahat + davom
    │     └── Javob yomon → to'xtatadi + tushuntiradi + qayta mashq
    │
    │ Agent e'tiroz qo'yadi (sohaviy bilimdan):
    │     "Bu juda qimmat ekan..."
    │
    │ Xodim javob beradi
    │     │
    │     ▼
    │ Agent baholaydi:
    │     "Bu javob 4/10. Eng yaxshi javob: 'Oyiga atigi X so'm...'"
    │
    ▼
Mashq tugaydi → yakuniy hisobot:
    - Umumiy ball: 65/100
    - Kuchli tomonlar: mahsulot bilimi yaxshi
    - Yaxshilash kerak: e'tiroz bartaraf qilish, yakunlash
    - Kerakli mashqlar: e'tiroz mashqi (ertaga)
    - Sohaviy eng yaxshi gaplar: [ro'yxat]
```

### 3.4 Gibrid mantiq

```
Agent (mijoz) gaplashishi → Haiku (suhbat yuritish uchun)
Xodim javobini baholash → 60% qoidaga asoslangan + 40% Haiku
Yakuniy hisobot → Haiku (qisqa xulosa)

Qoidaga asoslangan baholash:
- Xodim narxni birinchi aytdimi? → -5 ball (qoida: avval qiymat)
- Xodim savol berdimi? → +3 ball (qoida: ehtiyoj aniqlash)
- Xodim "o'ylab ko'ring" dedimi? → -10 ball (qoida: hech qachon bu gap)
- Xodim kafolat aytdimi? → +5 ball (qoida: risk kamaytirish)
```

### 3.5 Ma'lumotlar bazasi

```sql
CREATE TABLE training_sessions (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    trainee_user_id BIGINT UNSIGNED NOT NULL,
    trainer_user_id BIGINT UNSIGNED NULL,       -- rahbar tayinlagan bo'lsa
    session_type ENUM('greeting','presentation','objection','closing','full') NOT NULL,
    status ENUM('active','completed','abandoned') DEFAULT 'active',
    messages JSON NOT NULL,                     -- suhbat tarixi
    overall_score INT NULL,                     -- 0-100
    stage_scores JSON NULL,                     -- har bir bosqich bali
    strengths JSON NULL,
    improvements JSON NULL,
    recommended_next_session VARCHAR(50) NULL,
    duration_seconds INT NULL,
    ai_tokens_used INT DEFAULT 0,
    created_at TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_business (business_id),
    INDEX idx_trainee (trainee_user_id)
);

CREATE TABLE trainee_progress (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    total_sessions INT DEFAULT 0,
    avg_score DECIMAL(5,2) DEFAULT 0,
    best_score INT DEFAULT 0,
    weakest_area VARCHAR(50) NULL,
    strongest_area VARCHAR(50) NULL,
    ready_for_live BOOLEAN DEFAULT FALSE,       -- haqiqiy mijozlarga tayyor
    last_session_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE INDEX idx_business_user (business_id, user_id)
);
```

### 3.6 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Trainer/
│           ├── TrainerAgentService.php           — asosiy mantiq
│           ├── Scenarios/
│           │   ├── GreetingScenario.php          — salomlashish mashqi
│           │   ├── PresentationScenario.php      — taqdimot mashqi
│           │   ├── ObjectionScenario.php         — e'tiroz mashqi
│           │   ├── ClosingScenario.php           — yakunlash mashqi
│           │   └── FullConversationScenario.php  — to'liq suhbat
│           ├── Evaluation/
│           │   ├── RuleBasedEvaluator.php        — qoidaga asoslangan baholash
│           │   ├── AIEvaluator.php               — Haiku bilan baholash
│           │   └── ScoreCalculator.php           — ball hisoblash
│           ├── ProgressTracker.php               — xodim yutuqlarini kuzatish
│           └── Prompts/
│               ├── customer_role.txt             — agent mijoz rolida
│               ├── evaluate_response.txt         — javob baholash
│               └── session_report.txt            — yakuniy hisobot
```

### 3.7 Cheklovlar

| Tarif | Mashq soni/oy | Mashq turlari |
|-------|--------------|---------------|
| Boshlang'ich | Yo'q | — |
| O'sish | 20 ta mashq | Barcha turlar |
| Kengaytirilgan | Cheksiz | Barcha turlar + maxsus ssenariylar |

---

## QO'SHIMCHA MODUL 4: PUL OQIMI BASHORATCHI

### 4.1 Maqsad

Biznesning kirim-chiqimini kuzatib, 30-60 kunlik pul oqimi bashoratini ko'rsatish. Xavfli sanalarni (pul yetishmasligi mumkin bo'lgan kunlarni) oldindan aniqlash va tavsiyalar berish.

### 4.2 Ma'lumot manbalari

```
Kirim manbalari (mavjud bazadan olinadi):
    - Sotuvlar (orders jadvali) → kunlik daromad
    - Oldindan to'lovlar → rejalashtirilgan kirim
    - Mavsumiy trend → o'tgan oylar asosida bashorat

Chiqim manbalari (foydalanuvchi kiritadi + avtomatik):
    - Doimiy xarajatlar: ijara, maosh, internet (foydalanuvchi kiritadi)
    - Reklama xarajati: marketing_spends jadvalidan
    - To'lov komissiyalari: avtomatik hisob
    - Bir martalik xarajatlar: foydalanuvchi kiritadi
```

### 4.3 Bashorat algoritmi

```
30 kunlik bashorat:

1. Oxirgi 90 kun kirim ma'lumotlarini olish (bazadan, bepul)
2. Hafta kunlari bo'yicha o'rtacha hisoblash (bepul)
   Misol: Dushanba o'rtacha 800K, Seshanba 1.2M, ...
3. Mavsumiy koeffitsient qo'llash (bepul)
   Misol: Aprel = 0.95x (o'rtachadan 5% past)
4. Doimiy xarajatlarni kalendar bo'yicha joylashtirish (bepul)
   Misol: Ijara 1-sanada, maosh 5 va 20-sanada
5. Kunma-kun qoldiq hisoblash (bepul)
   Balans[kun] = Balans[kun-1] + Kirim[kun] - Chiqim[kun]

6. Xavfli sanalarni aniqlash (bepul):
   Agar Balans[kun] < minimal_chegara → OGOHLANTIRISH

7. Agar xavfli sana aniqlansa → Haiku tavsiya:
   "20-aprelda balans 1.2M ga tushadi. 3 ta tavsiya: ..."
```

### 4.4 Ma'lumotlar bazasi

```sql
CREATE TABLE cash_flow_settings (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    minimum_balance DECIMAL(15,2) DEFAULT 1000000, -- minimal qoldiq (UZS)
    recurring_expenses JSON NOT NULL,               -- doimiy xarajatlar
    -- [{"name":"Ijara","amount":5000000,"day_of_month":1},
    --  {"name":"Maosh","amount":15000000,"day_of_month":5}]
    alert_days_ahead INT DEFAULT 7,                -- necha kun oldin ogohlantirish
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE INDEX idx_business (business_id)
);

CREATE TABLE cash_flow_forecasts (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    forecast_date DATE NOT NULL,
    predicted_income DECIMAL(15,2) NOT NULL,
    predicted_expense DECIMAL(15,2) NOT NULL,
    predicted_balance DECIMAL(15,2) NOT NULL,
    confidence_level DECIMAL(3,2) NOT NULL,         -- 0.0-1.0
    is_danger BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    INDEX idx_business_date (business_id, forecast_date),
    INDEX idx_danger (is_danger)
);

CREATE TABLE cash_flow_alerts (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    danger_date DATE NOT NULL,
    predicted_balance DECIMAL(15,2) NOT NULL,
    recommendations JSON NULL,
    status ENUM('active','resolved','ignored') DEFAULT 'active',
    created_at TIMESTAMP,
    INDEX idx_business (business_id)
);
```

### 4.5 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── CashFlow/
│           ├── CashFlowService.php              — asosiy mantiq
│           ├── IncomePredictor.php               — kirim bashorati
│           ├── ExpenseTracker.php                — chiqim kuzatuvi
│           ├── BalanceForecaster.php             — qoldiq bashorati
│           ├── DangerDetector.php                — xavfli sanalarni aniqlash
│           └── CashFlowAlertService.php         — ogohlantirish yuborish
├── Console/
│   └── Commands/
│       └── ForecastCashFlow.php                 -- php artisan agent:forecast-cash
│       -- Har kuni ertalab ishga tushadi
```

### 4.6 Cheklovlar

| Tarif | Bashorat davri | Ogohlantirish |
|-------|---------------|---------------|
| Boshlang'ich | Yo'q | — |
| O'sish | 30 kun | Telegram xabar |
| Kengaytirilgan | 60 kun | Telegram + ovozli + batafsil tavsiya |

---

## QO'SHIMCHA MODUL 5: MIJOZ UMR YO'LI BOSHQARUVCHISI

### 5.1 Maqsad

Mijozning birinchi xabaridan doimiy mijozga aylanguncha butun yo'lni avtomatik boshqarish. Har bir bosqichda to'g'ri harakatni agent o'zi bajaradi.

### 5.2 Umr yo'li bosqichlari

```
BOSQICH 1: TANISHISH (birinchi aloqa)
    Trigger: yangi lead yaratildi
    Harakat: "Xush kelibsiz!" xabari + 10% chegirma kodi
    Vaqt: darhol

BOSQICH 2: QIZIQTIRISH (1-3 kun)
    Trigger: chegirma ishlatilmagan + xarid yo'q
    Harakat: "Savolingiz bormi?" xabari + mashhur mahsulot tavsiyasi
    Vaqt: 3 kun keyin

BOSQICH 3: BIRINCHI XARID
    Trigger: buyurtma yaratildi
    Harakat: "Rahmat!" + buyurtma holati + mos qo'shimcha mahsulot tavsiyasi
    Vaqt: darhol

BOSQICH 4: FIKR SO'RASH (7-14 kun)
    Trigger: buyurtma yetkazildi + 7 kun o'tdi
    Harakat: "Mahsulot yoqdimi? Izoh qoldirsangiz 5% chegirma"
    Vaqt: yetkazishdan 7 kun keyin

BOSQICH 5: QAYTA XARID (30-45 kun)
    Trigger: oxirgi xariddan 30 kun o'tdi
    Harakat: "Yangi mahsulotlarimiz bor! Sizga maxsus taklif"
    Vaqt: 30 kun keyin

BOSQICH 6: SODIQLIK (60+ kun)
    Trigger: 3+ marta xarid qilgan
    Harakat: "Siz bizning doimiy mijozimiz! Maxsus imtiyozlar"
    Vaqt: 3-xariddan keyin

BOSQICH 7: QAYTARISH (60+ kun xarid yo'q)
    Trigger: oxirgi xariddan 60 kun o'tdi
    Harakat: "Siz bizni sog'indingiz! Maxsus 20% chegirma"
    Vaqt: 60 kun keyin

BOSQICH 8: TUG'ILGAN KUN
    Trigger: mijoz tug'ilgan kuni
    Harakat: "Tabriklaymiz! Bugungi xaridda 15% chegirma"
    Vaqt: tug'ilgan kunda
```

### 5.3 Gibrid mantiq

```
Har bir bosqich uchun:

1. Trigger tekshirish — cron (har 30 daqiqada) yoki event asosida
   Bu bazadan so'rov — bepul

2. Xabar shabloni tanlash — bazadan (bepul)
   Har bir bosqich uchun tayyor shablon bor

3. Shaxsiylashtirish kerakmi?
   ├── Oddiy: shablon + mijoz ismi + mahsulot nomi (bepul)
   └── Murakkab: Haiku orqali shaxsiy xabar (AI)
       Misol: "Aziz aka, siz IELTS kursini tamomladingiz.
              CEFR B2 kursi ham sizga mos keladi"

4. Xabar yuborish — mavjud kanal orqali (Telegram/Instagram/Facebook)

Xabarlarning 80% shablonli (bepul), 20% AI bilan shaxsiylashtirilgan.
```

### 5.4 Ma'lumotlar bazasi

```sql
CREATE TABLE customer_lifecycle (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    current_stage ENUM(
        'new','interested','first_purchase','feedback',
        'repeat','loyal','churning','win_back','birthday'
    ) NOT NULL DEFAULT 'new',
    previous_stage VARCHAR(30) NULL,
    stage_entered_at TIMESTAMP NOT NULL,
    next_action_at TIMESTAMP NULL,
    next_action_type VARCHAR(50) NULL,
    total_purchases INT DEFAULT 0,
    total_spent DECIMAL(15,2) DEFAULT 0,
    last_purchase_at TIMESTAMP NULL,
    birthday DATE NULL,
    preferred_channel ENUM('telegram','instagram','facebook') NULL,
    lifecycle_score INT DEFAULT 0,              -- 0-100 (sodiqlik bali)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_stage (current_stage),
    INDEX idx_next_action (next_action_at)
);

CREATE TABLE lifecycle_actions (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    lifecycle_id CHAR(36) NOT NULL,
    stage VARCHAR(30) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    channel ENUM('telegram','instagram','facebook','sms') NOT NULL,
    message_template_id CHAR(36) NULL,
    message_content TEXT NULL,
    personalized_by_ai BOOLEAN DEFAULT FALSE,
    status ENUM('scheduled','sent','delivered','opened','clicked','converted') DEFAULT 'scheduled',
    scheduled_at TIMESTAMP NOT NULL,
    sent_at TIMESTAMP NULL,
    result_action VARCHAR(50) NULL,             -- 'purchased', 'replied', 'ignored'
    created_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_scheduled (scheduled_at, status)
);
```

### 5.5 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Lifecycle/
│           ├── LifecycleManagerService.php       — asosiy mantiq
│           ├── StageDetector.php                 — bosqichni aniqlash
│           ├── ActionScheduler.php               — harakatni rejalashtirish
│           ├── MessagePersonalizer.php           — xabarni shaxsiylashtirish
│           └── LifecycleAnalytics.php           — natija tahlili
├── Events/
│   ├── CustomerPurchased.php                     — xarid qildi
│   ├── CustomerStageChanged.php                  — bosqich o'zgardi
│   └── CustomerBirthdayToday.php                 — tug'ilgan kun
├── Listeners/
│   ├── UpdateLifecycleOnPurchase.php
│   ├── ScheduleLifecycleAction.php
│   └── SendBirthdayGreeting.php
├── Console/
│   └── Commands/
│       ├── ProcessLifecycleActions.php           -- php artisan agent:lifecycle
│       └── DetectChurningCustomers.php           -- php artisan agent:churn-detect
```

### 5.6 Cheklovlar

| Tarif | Bosqichlar | Shaxsiylashtirish |
|-------|-----------|-------------------|
| Boshlang'ich | 3 ta (tanishish, xarid, qaytarish) | Faqat shablon |
| O'sish | Barcha 8 ta | Shablon + AI |
| Kengaytirilgan | Barcha + maxsus bosqichlar | To'liq AI |

---

## QO'SHIMCHA MODUL 6: AI MAVSUMIY REJALASHTIRUVCHI

### 6.1 Maqsad

O'zbekiston bayramlari va mavsumiy o'zgarishlarga moslashtirilgan avtomatik kampaniya rejalari. Har bir bayram uchun 2-3 hafta oldindan reja tayyorlaydi va agent orqali amalga oshiradi.

### 6.2 O'zbekiston kalendari

```sql
CREATE TABLE local_calendar (
    id CHAR(36) PRIMARY KEY,
    event_name VARCHAR(100) NOT NULL,
    event_type ENUM('national_holiday','religious','seasonal','commercial','education') NOT NULL,
    fixed_date VARCHAR(10) NULL,                 -- '03-08' (8-mart) yoki NULL agar o'zgaruvchan
    is_lunar BOOLEAN DEFAULT FALSE,              -- Hijriy kalendar bo'yicha
    typical_month INT NULL,                      -- taxminiy oy (hijriy bayramlar uchun)
    year_date DATE NULL,                         -- shu yil uchun aniq sana
    preparation_days INT DEFAULT 14,             -- necha kun oldin tayyorlanish
    impact_industries JSON NOT NULL,             -- qaysi sohalarga ta'sir qiladi
    -- ["education","retail","food","beauty","tourism"]
    impact_description TEXT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP
);

-- Boshlang'ich ma'lumotlar
INSERT INTO local_calendar (event_name, event_type, fixed_date, impact_industries, impact_description) VALUES
('Xalqaro xotin-qizlar kuni', 'national_holiday', '03-08', '["beauty","retail","flowers","restaurants"]', 'Sovg''a sotuvlari 3-5x oshadi, restoran bron 2x'),
('Navruz', 'national_holiday', '03-21', '["food","retail","tourism","education"]', 'Bayram oldi savdo, oilaviy xaridlar, sumalyak tayyorlash'),
('Ro''za hayit', 'religious', NULL, '["food","retail","clothing"]', 'Hayit oldi kiyim va oziq-ovqat sotuvlari 2-3x'),
('Qurbon hayit', 'religious', NULL, '["food","retail","clothing","tourism"]', 'Qurbonlik va sayohat xarajatlari oshadi'),
('Mustaqillik kuni', 'national_holiday', '09-01', '["retail","education"]', 'Maktab tayyorgarligi, bayram savdolari'),
('Bilimlar kuni', 'education', '09-01', '["education","retail","stationery"]', 'Maktab va kurs ro''yxatga olish 5x oshadi'),
('O''qituvchilar kuni', 'national_holiday', '10-01', '["education","retail","flowers"]', 'Sovg''a sotuvlari, ta''lim sohasi aksiyalari'),
('Yangi yil', 'commercial', '12-31', '["retail","food","beauty","entertainment"]', 'Yilning eng katta savdo davri, 4-7x o''sish');
```

### 6.3 Ishlash oqimi

```
Har hafta tekshirish (cron):

1. Keyingi 30 kun ichida qaysi bayramlar bor? (bazadan, bepul)
2. Bu bayram shu biznesning sohasiga ta'sir qiladimi? (bepul)
3. Agar ha:
   a. Sohaviy bilim bazasidan o'tgan yilgi natijalarni olish (bepul)
   b. Kampaniya reja shablonini tanlash (bepul)
   c. AI bilan shaxsiylashtirish (Haiku):
      "Navruzga 14 kun qoldi. Ta'lim sohasi.
       O'tgan yili eng yaxshi ishlagan taklif: 'Ikkinchi kurs 50%'
       Bu biznes uchun 3 bosqichli kampaniya reja tuzing."
   d. Reja agent xotirasiga saqlanadi
   e. Har bir bosqichda agent avtomatik bajaradi:
      - Kontent tayyorlash va tavsiya (Marketing agenti)
      - Maxsus taklif yaratish (Sotuv agenti)
      - Natijani kuzatish (Tahlil agenti)
```

### 6.4 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── SeasonalPlanner/
│           ├── SeasonalPlannerService.php        — asosiy mantiq
│           ├── CalendarChecker.php               — bayramlar tekshiruvi
│           ├── CampaignTemplates.php             — kampaniya shablonlari
│           ├── CampaignGenerator.php             — AI bilan reja yaratish
│           └── CampaignExecutor.php             — rejani bajarish
├── Console/
│   └── Commands/
│       └── CheckUpcomingSeasons.php              -- php artisan agent:check-seasons
```

---

## QO'SHIMCHA MODUL 7: OBRO' VA SHARHLAR BOSHQARUVCHISI

### 7.1 Maqsad

Google Maps, Instagram, Telegram va boshqa platformalardagi izoh va sharhlarni bitta joyda kuzatish. Yangi izoh kelsa darhol xabar berish, salbiy izohga javob tavsiya qilish, umumiy kayfiyatni kuzatish.

### 7.2 Izoh manbalari

| Manba | Qanday yig'iladi | Chastota |
|-------|-------------------|----------|
| Google Maps | Google Places API (bepul, 1000 so'rov/kun) | Har 6 soatda |
| Instagram | Instagram Graph API (mavjud integratsiya) | Real vaqtda (webhook) |
| Telegram | Bot orqali (mavjud) | Real vaqtda |
| Qo'lda kiritish | Foydalanuvchi o'zi kiritadi | Har qachon |

### 7.3 Kayfiyat tahlili

```
Yangi izoh keldi:
    │
    ▼
1. Til aniqlash (avtomatik)
    │
    ▼
2. Kayfiyat aniqlash:
    ├── Qoidaga asoslangan (80% holat, bepul):
    │   Ijobiy so'zlar: "zo'r", "ajoyib", "rahmat", "yoqdi" → IJOBIY
    │   Salbiy so'zlar: "yomon", "shikoyat", "ishlamaydi" → SALBIY
    │   Neytral: qolgan hammasi → NEYTRAL
    │
    └── AI tahlil (20% murakkab holat, Haiku):
        "Kurs yaxshi lekin o'qituvchi e'tiborsiz" → ARALASH (60% ijobiy)

3. Ball berish: 1-5 (1=juda salbiy, 5=juda ijobiy)

4. Agar salbiy (1-2):
   → Darhol biznes egasiga xabar
   → Javob tavsiyasi tayyorlash
   → Agent javob yozadi (Haiku)
```

### 7.4 Javob tavsiya qilish

```
Salbiy izohga javob shablonlari:

XIZMAT_SHIKOYAT:
"Hurmatli [ism], fikringiz uchun rahmat. Bu holat bizning standartlarimizga 
mos kelmaydi. Iltimos, [telefon] ga qo'ng'iroq qiling — muammoingizni 
hal qilamiz va qo'shimcha [kompensatsiya] beramiz."

NARX_SHIKOYAT:
"Hurmatli [ism], tushunaman. Biz sifatga e'tibor beramiz va [qiymat]. 
Bo'lib to'lash imkoniyati ham bor. Batafsil ma'lumot uchun yozing."

UMUMIY_SALBIY:
"Hurmatli [ism], fikringiz biz uchun muhim. Muammoni hal qilishga 
tayyor turamiz. Iltimos, [aloqa] ga yozing."
```

### 7.5 Ma'lumotlar bazasi

```sql
CREATE TABLE customer_reviews (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    source ENUM('google_maps','instagram','telegram','manual','facebook') NOT NULL,
    source_id VARCHAR(255) NULL,                 -- platformadagi izoh ID si
    reviewer_name VARCHAR(100) NULL,
    rating INT NULL,                             -- 1-5 (agar mavjud bo'lsa)
    review_text TEXT NOT NULL,
    language VARCHAR(5) NULL,
    sentiment ENUM('positive','negative','neutral','mixed') NOT NULL,
    sentiment_score DECIMAL(3,2) NOT NULL,       -- 0.0-1.0
    categories JSON NULL,                        -- ["service","price","quality"]
    response_text TEXT NULL,                     -- berilgan javob
    response_status ENUM('pending','suggested','sent','skipped') DEFAULT 'pending',
    suggested_response TEXT NULL,                -- AI tavsiya qilgan javob
    flagged BOOLEAN DEFAULT FALSE,              -- muhim — e'tibor kerak
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_sentiment (sentiment),
    INDEX idx_source (source),
    INDEX idx_flagged (flagged)
);

CREATE TABLE reputation_scores (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    overall_sentiment DECIMAL(3,2) NOT NULL,     -- 0.0-1.0
    total_reviews INT NOT NULL,
    positive_count INT NOT NULL,
    negative_count INT NOT NULL,
    neutral_count INT NOT NULL,
    avg_rating DECIMAL(3,2) NULL,
    sentiment_trend DECIMAL(5,2) NULL,           -- +5.2% yoki -3.1%
    top_praise_topics JSON NULL,                 -- eng ko'p maqtalgan narsalar
    top_complaint_topics JSON NULL,              -- eng ko'p shikoyat qilingan
    created_at TIMESTAMP,
    INDEX idx_business_period (business_id, period_start)
);
```

### 7.6 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Reputation/
│           ├── ReputationService.php             — asosiy mantiq
│           ├── ReviewCollectors/
│           │   ├── GoogleMapsCollector.php        — Google Maps izohlarini yig'ish
│           │   ├── InstagramCollector.php         — Instagram izohlarini yig'ish
│           │   └── TelegramCollector.php          — Telegram izohlarini yig'ish
│           ├── SentimentAnalyzer.php              — kayfiyat tahlili
│           ├── ResponseGenerator.php              — javob tavsiya qilish
│           ├── ReputationScorer.php              — obro' bali hisoblash
│           └── ReviewAlertService.php            — ogohlantirish yuborish
├── Console/
│   └── Commands/
│       ├── CollectGoogleReviews.php              -- php artisan agent:collect-reviews
│       └── CalculateReputationScore.php          -- php artisan agent:reputation-score
```

### 7.7 Cheklovlar

| Tarif | Manbalar | Javob tavsiyasi | Kayfiyat tahlili |
|-------|----------|-----------------|------------------|
| Boshlang'ich | Faqat qo'lda kiritish | Yo'q | Asosiy (qoida) |
| O'sish | Google Maps + Instagram | AI tavsiya | To'liq (AI) |
| Kengaytirilgan | Barcha manbalar | AI tavsiya + avtomatik | To'liq + tendentsiya |

---

## UMUMIY QO'SHIMCHALAR

### Yangi cron vazifalari

```php
// Mavjud cron jadvaliga qo'shiladi
$schedule->command('agent:health-check')->weeklyOn(1, '08:00');
$schedule->command('agent:forecast-cash')->dailyAt('07:00');
$schedule->command('agent:lifecycle')->everyThirtyMinutes();
$schedule->command('agent:churn-detect')->dailyAt('09:00');
$schedule->command('agent:check-seasons')->weeklyOn(0, '10:00');
$schedule->command('agent:collect-reviews')->everySixHours();
$schedule->command('agent:reputation-score')->weeklyOn(1, '07:00');
```

### Yangi event va listener lari

```php
// Yangi eventlar
CustomerPurchased::class          → UpdateLifecycleOnPurchase
CustomerStageChanged::class       → ScheduleLifecycleAction
CustomerBirthdayToday::class      → SendBirthdayGreeting
NegativeReviewReceived::class     → AlertBusinessOwner + SuggestResponse
HealthScoreDropped::class         → NotifyBusinessOwner
CashFlowDangerDetected::class     → SendCashFlowAlert
TrainingSessionCompleted::class   → UpdateTraineeProgress
```

### Yangi model lari (barcha jadvallar uchun)

```
app/Models/
├── VoiceInteraction.php
├── BusinessHealthScore.php
├── TrainingSession.php
├── TraineeProgress.php
├── CashFlowSetting.php
├── CashFlowForecast.php
├── CashFlowAlert.php
├── CustomerLifecycle.php
├── LifecycleAction.php
├── LocalCalendar.php
├── CustomerReview.php
└── ReputationScore.php
```

### Tarif cheklovlari qo'shimchasi

```php
// config/subscription_limits.php ga qo'shiladigan yangi cheklovlar
'voice_messages_monthly' => [
    'starter' => 0,
    'growth' => 100,
    'extended' => -1,
],
'training_sessions_monthly' => [
    'starter' => 0,
    'growth' => 20,
    'extended' => -1,
],
'cash_flow_forecast_days' => [
    'starter' => 0,
    'growth' => 30,
    'extended' => 60,
],
'lifecycle_stages' => [
    'starter' => 3,       // tanishish, xarid, qaytarish
    'growth' => 8,        // barcha bosqichlar
    'extended' => -1,     // barcha + maxsus
],
'review_sources' => [
    'starter' => 1,       // faqat qo'lda
    'growth' => 3,        // google + instagram + qo'lda
    'extended' => -1,     // barchasi
],
```

### Qo'shimcha xarajat xulosasi

| Modul | Oylik xarajat (1 biznes) | UZS da |
|-------|-------------------------|--------|
| Ovozli AI yordamchi | $0.50 | 6,100 |
| Biznes sog'ligi monitori | $0.15 | 1,800 |
| AI xodim o'qituvchi | $0.80 | 9,800 |
| Pul oqimi bashoratchi | $0.30 | 3,700 |
| Mijoz umr yo'li boshqaruvchisi | $0.40 | 4,900 |
| AI mavsumiy rejalashtiruvchi | $0.20 | 2,400 |
| Obro' va sharhlar boshqaruvchisi | $0.35 | 4,300 |
| **JAMI** | **$2.70** | **33,000** |

Bu O'sish tarifining (1,399,000 so'm) atigi **2.4%** ini tashkil qiladi. Foyda ulushi barcha tariflarda 85%+ — talabdan ancha yuqori.

---

### Joriy etish tartibi

Bu modullar asosiy TZ dagi 4 ta bosqichdan KEYIN qo'shiladi:

| Bosqich | Muddat | Modullar |
|---------|--------|----------|
| 5-bosqich | 2 hafta | Biznes sog'ligi monitori + Mijoz umr yo'li + Mavsumiy rejalashtiruvchi (bu uchta eng sodda va tez qo'shiladi) |
| 6-bosqich | 2 hafta | Pul oqimi bashoratchi + Obro' boshqaruvchisi (ma'lumot yig'ish va tahlil) |
| 7-bosqich | 3 hafta | Ovozli AI yordamchi + AI xodim o'qituvchi (eng murakkab, Whisper va TTS integratsiya) |

**Jami qo'shimcha: 7 hafta**
**Umumiy loyiha: 14 + 7 = 21 hafta (5.25 oy)**

---

> **Eslatma:** Bu modullarni ishlab chiqishda ham gibrid yondashuv asosiy tamoyil — hamma joyda avval bazadan va qoidalardan foydalanish, faqat kerak bo'lganda sun'iy aqlga murojaat qilish. Har bir modul uchun token sarfi qayd qilinadi va kuzatiladi.

*Bu hujjat asosiy TZ ga qo'shimcha hisoblanadi. Ikkisi birgalikda BiznesPilot AI Agent tizimining to'liq texnik topshirig'ini tashkil qiladi.*
