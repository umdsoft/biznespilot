# BIZNESPILOT AI AGENT TIZIMI — TEXNIK TOPSHIRIQ (TZ)
## Versiya: 1.0 | Sana: 2026-aprel

---

> **MUHIM ESLATMA:** Loyiha faol rivojlanish bosqichida. Ushbu topshiriqni bajarishdan OLDIN loyihaning hozirgi holatini tekshiring. Mavjud kodlar, jadvallar, marshrutlar va xizmatlarni avval tahlil qilib, keyin shu asosda moslashtirib ishlang. Hech qanday ishlab turgan funksiyani buzmang. Yangi qo'shimchalar mavjud tuzilmaga mos ravishda qo'shilishi shart.

---

## 1. UMUMIY MA'LUMOT

### 1.1 Loyiha haqida

BiznesPilot AI — O'zbekistondagi kichik va o'rta bizneslar uchun marketing va sotuv platformasi. Platformaga sun'iy aql agentlari tizimi qo'shiladi. Bu tizim foydalanuvchiga marketing, sotuv, va biznes tahlili bo'yicha 24/7 yordam beradi.

### 1.2 Texnologiyalar

- **Orqa qism:** Laravel 12, PHP 8.3+, MySQL 8.0+, Redis 7.x
- **Old qism:** Vue 3.4+ (Composition API), Vue Router, Pinia, TailwindCSS, Inertia.js
- **Sun'iy aql:** Claude API (Haiku 4.5, Sonnet 4.6), Groq Whisper Turbo
- **Tashqi xizmatlar:** Telegram Bot API, Instagram Graph API, Facebook Messenger, Apify
- **To'lov:** Click, Payme (UZS)

### 1.3 Arxitektura asoslari

- Bir nechta foydalanuvchili tizim: `business_id` bilan ma'lumot ajratish
- Obuna tizimi bilan cheklovlarni nazorat qilish
- UUID asosiy kalitlar
- Event + Listener tizimi modullararo aloqa uchun
- Redis keshirlash va navbat (queue) tizimi
- Laravel Horizon navbat boshqaruvi

### 1.4 Asosiy shart

- Har bir foydalanuvchida **1 ta biznes, 1 ta Instagram**
- **AmoCRM integratsiyasi yo'q** — ichki lead boshqaruv tizimi ishlatiladi
- Barcha tariflarda foyda ulushi **60% dan kam bo'lmasligi** shart

---

## 2. GIBRID SUN'IY AQL BOSHQARUVI

### 2.1 Asosiy tamoyil

Sun'iy aqlga har bir so'rovni yuborish qimmat va sekin. Shuning uchun **gibrid yondashuv** qo'llanadi: avval arzon/bepul usullar sinab ko'riladi, faqat kerak bo'lganda sun'iy aqlga murojaat qilinadi.

### 2.2 Qaror qabul qilish tartibi (pastdan yuqoriga)

```
So'rov keldi
    │
    ▼
1-bosqich: Ma'lumotlar bazasidan javob bormi?
    ├── HA → javob qaytariladi (0 token, bepul)
    │         Misol: "Leadlar soni?" → SQL so'rov → "45 ta"
    │
    ▼
2-bosqich: Qoidalar asosida javob berish mumkinmi?
    ├── HA → qoida bo'yicha javob (0 token, bepul)
    │         Misol: "Salom" → shablon javob
    │         Misol: "Narx?" → mahsulot narxi bazadan
    │
    ▼
3-bosqich: Keshda tayyor javob bormi?
    ├── HA → keshlangan javob (0 token, bepul)
    │         Misol: shu sohada shu savolga oldingi javob bor
    │
    ▼
4-bosqich: Haiku bilan hal qilish mumkinmi?
    ├── HA → Haiku ga yuboriladi (kam token, arzon)
    │         Misol: e'tirozga javob, kontent tavsiya
    │
    ▼
5-bosqich: Sonnet kerak
    └── Sonnet ga yuboriladi (ko'proq token, sifatli)
              Misol: strategik qaror, murakkab tahlil, hisobot yaratish
```

### 2.3 Token tejash usullari

**Keshirlash (prompt caching):** Har bir agent uchun tizim ko'rsatmasi (system prompt) keshlanadi. Birinchi so'rovda yoziladi, keyingilarida 90% arzon o'qiladi. Redis da saqlanadi, TTL: 1 soat.

**Qisqa kontekst:** Agentga faqat kerakli ma'lumot yuboriladi. Butun suhbat tarixi emas, faqat oxirgi 5 ta xabar + biznes holati qisqartmasi.

**Qoidaga asoslangan qarorlar:** Oddiy so'rovlar uchun (salomlashish, menyu, mahsulot ma'lumoti, narx) sun'iy aqlga murojaat qilinmaydi. Bu xabarlarning 80% ini tashkil qiladi.

**Javob keshlash:** Agar bir xil sohada bir xil savolga javob berilgan bo'lsa, keyingi safar keshdan olinadi. Redis kalit: `agent_response:{soha}:{savol_xeshi}`, TTL: 24 soat.

### 2.4 Taxminiy taqsimot

| Usul | Ulushi | Token sarfi | Oylik xarajat |
|------|--------|-------------|---------------|
| Ma'lumotlar bazasi + qoidalar | 80% | 0 | Bepul |
| Keshdan javob | 5% | 0 | Bepul |
| Haiku (arzon model) | 12% | Kam | ~60% AI byudjetdan |
| Sonnet (sifatli model) | 3% | Ko'p | ~40% AI byudjetdan |

---

## 3. AGENTLAR TIZIMI ARXITEKTURASI

### 3.1 Umumiy tuzilish

Tizimda 7 ta komponent ishlaydi: 1 ta boshqaruvchi, 4 ta mutaxassis agent, 1 ta tekshiruvchi, va umumiy xotira tizimi.

Foydalanuvchi faqat bitta suhbat oynasini ko'radi. Ichkaridagi taqsimotni u bilmaydi.

### 3.2 So'rov oqimi

```
Foydalanuvchi savol beradi
    │
    ▼
Boshqaruvchi (Direktor) — Haiku
    │ Savolni tahlil qiladi
    │ Qaysi agent(lar)ga yuborish kerakligini aniqlaydi
    │ Agar oddiy savol bo'lsa — o'zi javob beradi
    │
    ├── Bitta agentga → to'g'ridan-to'g'ri
    ├── Bir nechta agentga → parallel (bir vaqtda)
    │
    ▼
Mutaxassis agent(lar) ishlaydi
    │ O'z vositalarini ishlatadi
    │ Xotiradan ma'lumot oladi
    │ Gibrid qaror qabul qiladi (bazadan yoki AI dan)
    │
    ▼
Tekshiruvchi (xavf darajasiga qarab)
    │ Past xavf → o'tkazib yuboradi
    │ O'rta xavf → Haiku tekshiradi
    │ Yuqori xavf → Sonnet tekshiradi
    │
    ▼
Boshqaruvchi natijalarni birlashtiradi
    │
    ▼
Foydalanuvchiga javob beriladi
```

---

## 4. BOSHQARUVCHI AGENT (DIREKTOR)

### 4.1 Vazifasi

Foydalanuvchi savolini qabul qiladi, tushunadi, va kerakli mutaxassis agentga yo'naltiradi. Bir nechta agent javobini birlashtiradi. O'zi hech qanday vosita ishlatmaydi — faqat yo'naltirish va birlashtirish.

### 4.2 Model

Haiku 4.5 — tez va arzon. Keshirlangan tizim ko'rsatmasi bilan ishlaydi.

### 4.3 Yo'naltirish mantiqi

```
Savol turlari va yo'naltirilishi:

"Kontent reja tuzing" → Marketing agenti (yakka)
"Leadlar ro'yxati" → Sotuv agenti (yakka)
"Bu oylik hisobot" → Tahlil agenti (yakka)
"Oxirgi qo'ng'iroqlarni tahlil qil" → Qo'ng'iroq agenti (yakka)
"Nega sotuvlar tushdi?" → Tahlil + Sotuv + Marketing (parallel)
"Raqobatchi aksiya boshladi" → Tahlil + Marketing (parallel)
"Biznes holati" → Barcha 4 agent (parallel)
"Salom" / "Rahmat" → Boshqaruvchi o'zi javob beradi
```

### 4.4 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       ├── OrchestratorService.php      — boshqaruvchi mantiq
│       ├── AgentRouter.php              — qaysi agentga yuborish
│       └── AgentResponseMerger.php      — javoblarni birlashtirish
```

**OrchestratorService** — asosiy sinf:
- `handleUserMessage(string $message, int $businessId): AgentResponse` — kirish nuqtasi
- Avval `AgentRouter::route()` orqali qaysi agent(lar)ga yuborish aniqlanadi
- Agar bitta agent → sinxron chaqiriq
- Agar bir nechta → Laravel Jobs orqali parallel ishga tushirish
- Natijalar `AgentResponseMerger::merge()` orqali birlashtiriladi

**AgentRouter** — yo'naltirish mantiqi:
- Avval qoidaga asoslangan: kalit so'zlar bo'yicha (`lead`, `kontent`, `hisobot`, `qo'ng'iroq`)
- Agar qoida aniqlamasa → Haiku ga qisqa so'rov yuboriladi: "Bu savol qaysi sohaga tegishli: marketing, sotuv, tahlil, qo'ng'iroq?" → javobga qarab yo'naltiriladi
- **Token tejash:** 90%+ savollar qoida bilan aniqlanadi, faqat 10% Haiku kerak

### 4.5 Ma'lumotlar bazasi

```sql
CREATE TABLE agent_conversations (
    id CHAR(36) PRIMARY KEY,         -- UUID
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active','closed') DEFAULT 'active',
    started_at TIMESTAMP,
    closed_at TIMESTAMP NULL,
    message_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (business_id) REFERENCES businesses(id)
);

CREATE TABLE agent_messages (
    id CHAR(36) PRIMARY KEY,
    conversation_id CHAR(36) NOT NULL,
    business_id BIGINT UNSIGNED NOT NULL,
    role ENUM('user','agent','system') NOT NULL,
    content TEXT NOT NULL,
    agent_type ENUM('orchestrator','marketing','sales','analytics','call_center','evaluator') NULL,
    model_used ENUM('none','haiku','sonnet','groq_whisper') DEFAULT 'none',
    tokens_input INT DEFAULT 0,
    tokens_output INT DEFAULT 0,
    cost_usd DECIMAL(8,6) DEFAULT 0,
    routed_to JSON NULL,              -- ["marketing","sales"] kabi
    processing_time_ms INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_conversation (conversation_id),
    INDEX idx_business (business_id),
    FOREIGN KEY (conversation_id) REFERENCES agent_conversations(id)
);
```

---

## 5. MARKETING AGENTI

### 5.1 Vazifasi

Kontent strategiyasi, kanal boshqaruvi, reklama kampaniyalari, Instagram va Telegram tahlili, raqobatchi kuzatuvi, optimal vaqtlarni aniqlash, A/B sinov natijalarini tahlil qilish.

### 5.2 Model

- Kundalik vazifalar: Haiku (kontent tavsiya, vaqt aniqlash, qisqa tahlil)
- Strategik vazifalar: Sonnet (kampaniya rejasi, chuqur tahlil, haftalik strategiya)

### 5.3 Vositalari

| Vosita | Tavsif | Amalga oshirish |
|--------|--------|-----------------|
| KontentTahlil | Postlar natijasini bazadan oladi | SQL so'rov (bepul) |
| OptimalVaqt | Eng yaxshi joylash vaqtini hisoblaydi | Ma'lumotlar bazasidan (bepul) |
| RaqobatchiMa'lumot | Raqobatchi holatini oladi | Bazadan (bepul) + Apify (kerak bo'lsa) |
| KontentYaratish | Post matni, sarlavha tavsiya | Haiku/Sonnet (AI) |
| KampaniyaReja | Haftalik/oylik reja tuzadi | Sonnet (AI) |
| TrendTahlil | Sohadagi trendlarni ko'rsatadi | Bazadan + AI |

### 5.4 Gibrid mantiq

```
"Bugun nima post qilsam?" degan savolga:

1-qadam: Bazadan oxirgi 7 kun postlarini olish (bepul)
2-qadam: Bazadan shu hafta kuni uchun optimal vaqtni olish (bepul)
3-qadam: Bazadan shu sohadagi eng yaxshi kontent turini olish (bepul)
4-qadam: Shu ma'lumotlar asosida Haiku ga qisqa so'rov:
         "Ta'lim sohasida, seshanba kuni, video kontent yaxshi ishlaydi,
          oxirgi 3 post mahsulot haqida edi.
          Bugungi post uchun qisqa tavsiya ber."
         → Haiku: "Behind-the-scenes video, 19:00 da, daraja ko'rsatish"

Jami: 4 ta bazadan so'rov (bepul) + 1 ta Haiku chaqiriq (~500 token)
```

### 5.5 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Marketing/
│           ├── MarketingAgentService.php    — asosiy mantiq
│           ├── Tools/
│           │   ├── ContentAnalysisTool.php  — kontent natijalarini olish
│           │   ├── OptimalTimeTool.php       — eng yaxshi vaqtni aniqlash
│           │   ├── CompetitorDataTool.php    — raqobatchi ma'lumoti
│           │   ├── ContentGeneratorTool.php  — AI orqali kontent yaratish
│           │   └── CampaignPlannerTool.php   — kampaniya rejalashtirish
│           └── Prompts/
│               ├── content_suggestion.txt    — kontent tavsiya uchun shablon
│               ├── campaign_plan.txt         — kampaniya rejasi shabloni
│               └── competitor_analysis.txt   — raqobatchi tahlili shabloni
```

---

## 6. SOTUV AGENTI (YANGILANGAN — SUHBATCHI + BOSHQARUVCHI)

### 6.1 Vazifasi

Bu agent ikki xil ish bajaradi:

**Suhbatchi qismi:** Telegram, Instagram DM va Facebook Messenger da mijozlar bilan bevosita 24/7 gaplashadi. Salomlashadi, mahsulot haqida aytadi, narx savollariga javob beradi, e'tirozlarni bartaraf qiladi, buyurtma qabul qiladi, kerak bo'lsa operatorga uzatadi.

**Boshqaruvchi qismi:** Suhbat davomida bir vaqtda lead baholaydi (0-100 ball), savdo bosqichini yangilaydi, operatorga vazifa yaratadi, qayta aloqa ketma-ketligini boshqaradi, mijoz qaytarish kampaniyalarini yuritadi.

### 6.2 Model va gibrid mantiq

```
Xabar keldi (Telegram/Instagram/Facebook)
    │
    ▼
1-qadam: Xabar turini aniqlash (qoidaga asoslangan, bepul)
    ├── Salomlashish → shablon javob (bepul)
    ├── Menyu so'rovi → tugmali menyu (bepul)
    ├── Mahsulot so'rovi → bazadan mahsulot kartasi (bepul)
    ├── Narx so'rovi → bazadan narx (bepul)
    ├── Buyurtma → buyurtma oqimi (bepul)
    ├── Operator so'rovi → darhol uzatish (bepul)
    │
    ├── E'tiroz → Sohaviy bilim bazasidan eng yaxshi javob (bepul/Haiku)
    ├── Murakkab savol → Haiku ga yuboriladi
    └── Strategik savol → Sonnet ga yuboriladi

Taxminan 80% xabarlar AI siz hal qilinadi.
```

### 6.3 Suhbat kanallari

| Kanal | Webhook manzili | API |
|-------|----------------|-----|
| Telegram | `/webhook/telegram/{bot_token}` | Telegram Bot API 7.0 |
| Instagram DM | `/webhook/instagram` | Instagram Messaging API |
| Facebook | `/webhook/facebook` | Facebook Messenger Platform |

> **Eslatma:** Hozirgi tizimda chatbot moduli mavjud. Yangi sotuv agenti shu mavjud chatbot infratuzilmasini kengaytiradi — qaytadan yozmaydi. Mavjud `chatbot_configs`, `chatbot_conversations`, `chatbot_messages` jadvallari saqlanadi va yangi ustunlar qo'shiladi.

### 6.4 Real vaqtda lead baholash

Suhbat davomida har bir xabardan keyin lead bali qayta hisoblanadi:

| Mijoz harakati | Ball o'zgarishi |
|----------------|-----------------|
| Birinchi xabar yozdi | +5 |
| Mahsulot haqida so'radi | +10 |
| Narx so'radi | +15 |
| "Qanday to'layman?" dedi | +20 |
| Bepul materialni oldi | +10 |
| "O'ylab ko'raman" dedi | -5 |
| "Qimmat" dedi | -3 |
| E'tirozdan keyin suhbat davom etdi | +10 |
| Buyurtma berdi | +30 |

**Ball bo'yicha harakat:**
- 0-25: Sovuq → qayta aloqa ketma-ketligi (3 kundan keyin xabar)
- 26-50: Iliq → maqsadli kontent yuborish
- 51-75: Qiziq → sotuv yondashuvini kuchaytirish
- 76-100: Issiq → darhol operatorga xabar + vazifa yaratish

### 6.5 E'tirozlarni bartaraf qilish (sohaviy bilim asosida)

Agent e'tiroz aniqlansa, avval sohaviy bilim bazasidan eng yaxshi javobni qidiradi:

```
1. Xabarda e'tiroz bormi? → kalit so'zlar bilan aniqlash (bepul)
   "qimmat", "narx", "byudjet" → NARX e'tirozi
   "ishonch", "bilmayman" → ISHONCH e'tirozi
   "keyinroq", "o'ylab ko'raman" → VAQT e'tirozi

2. Sohaviy bilim bazasidan javob bormi?
   SELECT response, success_rate
   FROM industry_objection_responses
   WHERE industry = :soha AND objection_type = :tur
   ORDER BY success_rate DESC LIMIT 3

3. Agar bazada javob bor → eng yaxshisini ishlatish (bepul)
4. Agar yo'q → Haiku ga yuborish (AI)
5. Natijani kuzatish → muvaffaqiyatli bo'lsa bazaga yozish
```

### 6.6 Ma'lumotlar bazasi (yangi jadvallar va o'zgarishlar)

```sql
-- Mavjud chatbot_messages jadvaliga yangi ustunlar qo'shish
ALTER TABLE chatbot_messages ADD COLUMN lead_score_snapshot INT NULL;
ALTER TABLE chatbot_messages ADD COLUMN intent_detected VARCHAR(50) NULL;
ALTER TABLE chatbot_messages ADD COLUMN objection_type VARCHAR(50) NULL;
ALTER TABLE chatbot_messages ADD COLUMN ai_model_used VARCHAR(20) NULL;
ALTER TABLE chatbot_messages ADD COLUMN tokens_used INT DEFAULT 0;
ALTER TABLE chatbot_messages ADD COLUMN response_source ENUM('rule','cache','haiku','sonnet') DEFAULT 'rule';

-- Lead baholash tarixi
CREATE TABLE lead_score_history (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    lead_id BIGINT UNSIGNED NOT NULL,
    conversation_id CHAR(36) NULL,
    score_before INT NOT NULL,
    score_after INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    INDEX idx_lead (lead_id),
    INDEX idx_business (business_id)
);

-- Operatorga uzatish jadvali
CREATE TABLE agent_handoffs (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    conversation_id CHAR(36) NOT NULL,
    lead_id BIGINT UNSIGNED NULL,
    reason TEXT NOT NULL,
    lead_score INT NOT NULL,
    conversation_summary TEXT NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,      -- operator user_id
    status ENUM('pending','accepted','completed') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_status (status)
);

-- Qayta aloqa ketma-ketligi
CREATE TABLE nurture_sequences (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    lead_id BIGINT UNSIGNED NOT NULL,
    channel ENUM('telegram','instagram','facebook') NOT NULL,
    sequence_type ENUM('cold','warm','hot','retention') NOT NULL,
    current_step INT DEFAULT 0,
    total_steps INT NOT NULL,
    next_send_at TIMESTAMP NULL,
    status ENUM('active','paused','completed','cancelled') DEFAULT 'active',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_next_send (next_send_at, status)
);

CREATE TABLE nurture_sequence_steps (
    id CHAR(36) PRIMARY KEY,
    sequence_id CHAR(36) NOT NULL,
    step_number INT NOT NULL,
    message_template TEXT NOT NULL,
    delay_hours INT NOT NULL,           -- oldingi qadamdan keyin necha soat
    sent_at TIMESTAMP NULL,
    result ENUM('pending','sent','opened','clicked','replied') DEFAULT 'pending',
    created_at TIMESTAMP,
    FOREIGN KEY (sequence_id) REFERENCES nurture_sequences(id)
);
```

### 6.7 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Sales/
│           ├── SalesAgentService.php          — asosiy mantiq
│           ├── ChatHandler/
│           │   ├── MessageClassifier.php      — xabar turini aniqlash (qoidaga asoslangan)
│           │   ├── RuleBasedResponder.php      — qoidaga asoslangan javoblar
│           │   ├── ObjectionHandler.php        — e'tirozlarni bartaraf qilish
│           │   └── HandoffManager.php          — operatorga uzatish
│           ├── LeadScoring/
│           │   ├── RealTimeScorer.php          — real vaqtda baholash
│           │   └── ScoreActionTrigger.php      — ball bo'yicha harakat
│           ├── NurtureSequence/
│           │   ├── SequenceManager.php         — ketma-ketlik boshqaruvi
│           │   └── SequenceScheduler.php       — vaqtli yuborish (cron)
│           ├── Tools/
│           │   ├── LeadDataTool.php            — lead ma'lumotlarini olish
│           │   ├── ProductCatalogTool.php      — mahsulot ma'lumotlari
│           │   ├── OrderTool.php               — buyurtma yaratish
│           │   └── TaskCreatorTool.php         — operatorga vazifa yaratish
│           └── Prompts/
│               ├── objection_response.txt      — e'tiroz javob shabloni
│               ├── personalized_message.txt    — shaxsiy xabar shabloni
│               └── conversation_summary.txt    — suhbat xulosasi shabloni
├── Jobs/
│   ├── ProcessChatMessage.php                  — xabarni qayta ishlash (navbat)
│   ├── SendNurtureMessage.php                  — qayta aloqa xabari yuborish
│   └── NotifyOperatorHotLead.php              — issiq lead haqida xabar
```

---

## 7. TAHLIL AGENTI

### 7.1 Vazifasi

Asosiy ko'rsatkichlarni hisoblash va taqdim etish, raqobatchi kuzatuvi, sohaviy o'rtacha ko'rsatkichlar bilan solishtirish, moliyaviy diagnostika, 6 turdagi avtomatik hisobot yaratish, maqsadlarni kuzatish, g'ayrioddiy o'zgarishlarni aniqlash.

### 7.2 Model

- Ko'rsatkichlar hisoblash: bazadan (bepul)
- Oddiy tahlil: Haiku
- Chuqur strategik tahlil va hisobot yaratish: Sonnet

### 7.3 Gibrid mantiq

```
"Nega sotuvlar tushdi?" degan savolga:

1-qadam: Bazadan oxirgi 30 kun KPI larni olish (bepul)
2-qadam: Bazadan oldingi 30 kun bilan solishtirish (bepul)
3-qadam: Bazadan kanal bo'yicha taqsimot (bepul)
4-qadam: Bazadan lead konversiya o'zgarishi (bepul)
5-qadam: Yuqoridagi ma'lumotlarni Haiku ga qisqa formatda yuborish:
         "Oxirgi 30 kunda sotuvlar 15% tushdi.
          Instagram reach 40% kamaydi.
          3 ta issiq lead ga javob berilmagan.
          Raqobatchi 20% chegirma boshladi.
          Qisqa tahlil va tavsiya ber."
         → Haiku javob beradi

Jami: 4 ta SQL so'rov (bepul) + 1 ta Haiku chaqiriq (~800 token)
```

### 7.4 Vositalari

| Vosita | Tavsif | Turi |
|--------|--------|------|
| KPICalculator | CAC, CLV, ROAS, churn hisoblash | SQL (bepul) |
| FunnelAnalysis | Savdo bosqichlari tahlili | SQL (bepul) |
| ChannelPerformance | Kanal samaradorligi | SQL (bepul) |
| CompetitorTracker | Raqobatchi holati | Bazadan + Apify |
| ReportGenerator | 6 turdagi hisobot yaratish | Sonnet (AI) |
| AnomalyDetector | G'ayrioddiy o'zgarishlarni aniqlash | Qoida + Haiku |
| BenchmarkCompare | Sohaviy o'rtacha bilan solishtirish | SQL (bepul) |

### 7.5 Hisobot turlari (avtomatik, cron bo'yicha)

| Hisobot | Chastota | Model | Taxminiy token |
|---------|----------|-------|----------------|
| Kundalik qisqa ma'lumot | Har kuni | Bepul (shablonli) | 0 |
| Haftalik samaradorlik | Haftalik | Haiku | ~1,000 |
| Haftalik kontent strategiyasi | Haftalik | Haiku | ~1,200 |
| Haftalik sotuv suhbati | Haftalik | Haiku | ~800 |
| Oylik strategik tavsiya | Oylik | Sonnet | ~3,000 |
| Oylik sohaviy solishtirish | Oylik | Sonnet | ~2,500 |

### 7.6 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── Analytics/
│           ├── AnalyticsAgentService.php
│           ├── Tools/
│           │   ├── KPICalculatorTool.php
│           │   ├── FunnelAnalysisTool.php
│           │   ├── ChannelPerformanceTool.php
│           │   ├── CompetitorTrackerTool.php
│           │   ├── AnomalyDetectorTool.php
│           │   └── BenchmarkCompareTool.php
│           ├── Reports/
│           │   ├── DailyBriefReport.php
│           │   ├── WeeklyPerformanceReport.php
│           │   ├── WeeklyContentReport.php
│           │   ├── WeeklySalesReport.php
│           │   ├── MonthlyStrategyReport.php
│           │   └── MonthlyBenchmarkReport.php
│           └── Prompts/
│               ├── kpi_analysis.txt
│               ├── anomaly_explanation.txt
│               └── strategy_recommendation.txt
├── Console/
│   └── Commands/
│       ├── GenerateDailyBrief.php          -- php artisan agent:daily-brief
│       ├── GenerateWeeklyReports.php       -- php artisan agent:weekly-reports
│       └── GenerateMonthlyReports.php      -- php artisan agent:monthly-reports
```

---

## 8. QO'NG'IROQ AGENTI

### 8.1 Vazifasi

Telefon qo'ng'iroqlarini matnga aylantirish (Groq Whisper), 7 bosqichli sotuv suhbati tahlili, operator uchun shaxsiy maslahatlar, eng yaxshi gaplarni aniqlash va tavsiya qilish, xatolarni aniqlash va ogohlantirish, jamoa reytingini yuritish.

### 8.2 Modellar

- Ovozdan matnga: Groq Whisper Large V3 Turbo ($0.04/soat)
- Suhbat tahlili: Haiku (tez, arzon)
- Chuqur coaching: Sonnet (murakkab tahlil kerak bo'lganda)

### 8.3 Qo'ng'iroq qayta ishlash oqimi

```
Qo'ng'iroq tugadi (Sipuni/Utel webhook)
    │
    ▼
Audio fayl yuklab olinadi
    │
    ▼
Groq Whisper Turbo → matn (transkripsiya)
    │ Narx: $0.04/soat, tezlik: 200x real vaqtdan tez
    │
    ▼
Matnni bosqichlarga ajratish (qoidaga asoslangan, bepul):
    ├── Ochilish (salomlashish)
    ├── Ehtiyoj aniqlash
    ├── Moslik tekshirish
    ├── Taqdimot
    ├── E'tiroz bartaraf qilish
    ├── Yakunlash
    └── Keyingi qadamlar
    │
    ▼
Haiku bilan tahlil:
    "Shu suhbat matnini 7 bosqich bo'yicha 1-10 ball bilan bahola.
     Eng yaxshi va eng yomon momentlarni ko'rsat.
     Operator uchun 3 ta aniq maslahat ber."
    │
    ▼
Natija saqlanadi + operatorga yuboriladi
```

### 8.4 Ma'lumotlar bazasi

```sql
CREATE TABLE call_analyses (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    call_id VARCHAR(100) NOT NULL,          -- Sipuni/Utel dan
    operator_id BIGINT UNSIGNED NULL,
    lead_id BIGINT UNSIGNED NULL,
    duration_seconds INT NOT NULL,
    audio_url TEXT NULL,
    transcript TEXT NULL,
    analysis_result JSON NULL,               -- 7 bosqich baholari
    overall_score INT NULL,                  -- 0-100
    strengths JSON NULL,                     -- kuchli tomonlar
    improvements JSON NULL,                  -- yaxshilash kerak
    coaching_tips JSON NULL,                 -- maslahatlar
    detected_objections JSON NULL,           -- aniqlangan e'tirozlar
    outcome ENUM('sale','lead','callback','lost') NULL,
    model_used VARCHAR(20) NULL,
    tokens_used INT DEFAULT 0,
    cost_usd DECIMAL(8,6) DEFAULT 0,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_operator (operator_id),
    INDEX idx_outcome (outcome)
);

CREATE TABLE operator_performance (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    operator_id BIGINT UNSIGNED NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    total_calls INT DEFAULT 0,
    avg_score DECIMAL(5,2) DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    top_strengths JSON NULL,
    top_improvements JSON NULL,
    rank_in_team INT NULL,
    created_at TIMESTAMP,
    INDEX idx_business_period (business_id, period_start)
);
```

### 8.5 Laravel amalga oshirish

```
app/
├── Services/
│   └── Agent/
│       └── CallCenter/
│           ├── CallCenterAgentService.php
│           ├── Transcription/
│           │   ├── GroqWhisperService.php       — ovozdan matnga
│           │   └── TranscriptSegmenter.php      — bosqichlarga ajratish
│           ├── Analysis/
│           │   ├── CallAnalyzer.php              — suhbat tahlili
│           │   ├── CoachingGenerator.php         — maslahat yaratish
│           │   └── ObjectionDetector.php         — e'tirozlarni aniqlash
│           ├── Performance/
│           │   ├── OperatorScorer.php            — operator baholash
│           │   └── TeamLeaderboard.php           — jamoa reytingi
│           └── Prompts/
│               ├── call_analysis.txt
│               └── coaching_tips.txt
├── Jobs/
│   ├── ProcessCallRecording.php                  — audio qayta ishlash (navbat)
│   └── GenerateOperatorReport.php                — haftalik operator hisoboti
```

---

## 9. TEKSHIRUVCHI TIZIMI

### 9.1 Vazifasi

Barcha agentlarning qarorlari va javoblarini tekshiradi: ma'lumot to'g'riligi, mantiq izchilligi, xavfsizlik, xayoliy ma'lumot aniqlash. Xato topsa agentga qaytaradi va sabab tushuntiradi. Vaqt o'tishi bilan agent o'rganishi uchun xatolar bazasiga yozadi.

### 9.2 Xavf darajalari va tekshiruv qoidalari

| Xavf darajasi | Ulushi | Tekshiruv | Misol |
|---------------|--------|-----------|-------|
| Past xavf | 60% | Tekshiruvsiz o'tadi | KPI ko'rsatish, hisobot, lead ro'yxati, bazadan javob |
| O'rta xavf | 30% | Qoidalar + Haiku | Lead holati o'zgartirish, xabar yuborish, kontent tavsiya |
| Yuqori xavf | 10% | Qoidalar + Sonnet | Narx o'zgartirish tavsiyasi, kampaniya rejasi, katta qaror |

### 9.3 Tekshiruv turlari

**Qoidaga asoslangan tekshiruvlar (bepul):**
- Ma'lumot to'g'riligi: Agent "CAC 45,000" dedi → bazadan tekshirish → to'g'rimi?
- Xavfsizlik: Agent "50% chegirma" tavsiya qildi → margin manfiy bo'lib qolmaydimi?
- Chegaralar: Agent narxni o'zgartirmoqchi → foydalanuvchining ruxsati bormi?

**Haiku tekshiruvi (o'rta xavf):**
- Mantiq izchilligi: "Narxni oshir" + "Sotuvlar pasaygan" — mantiqiymi?
- Xayoliy ma'lumot: Agent "raqobatchi X qildi" dedi → bazada shu ma'lumot bormi?

**Sonnet tekshiruvi (yuqori xavf):**
- Strategik to'g'rilik: kampaniya rejasi mantiqiymi, byudjet mos keladimi?
- Moliyaviy ta'sir: bu qaror foydalanuvchiga zarar keltirmaydimi?

### 9.4 O'rganish halqasi

```
Agent qaror qildi → Tekshiruvchi tekshirdi
    │
    ├── TASDIQLANDI → qaror bajariladi
    │   └── Natija kuzatiladi (muvaffaqiyatli/muvaffaqiyatsiz)
    │       └── agent_decision_outcomes jadvaliga yoziladi
    │
    └── RAD ETILDI → agentga sabab bilan qaytariladi
        ├── Agent qayta ishlaydi (tuzatilgan variant)
        └── Xato evaluator_feedback jadvaliga yoziladi
            └── Agent keyingi safar shu xatoni takrorlamaydi
```

### 9.5 Ma'lumotlar bazasi

```sql
CREATE TABLE evaluator_checks (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    agent_type VARCHAR(50) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    risk_level ENUM('low','medium','high') NOT NULL,
    check_method ENUM('skip','rule','haiku','sonnet') NOT NULL,
    input_data JSON NOT NULL,
    result ENUM('approved','rejected','modified') NOT NULL,
    rejection_reason TEXT NULL,
    model_used VARCHAR(20) NULL,
    tokens_used INT DEFAULT 0,
    processing_time_ms INT DEFAULT 0,
    created_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_result (result),
    INDEX idx_agent_type (agent_type)
);

CREATE TABLE agent_decision_outcomes (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    agent_type VARCHAR(50) NOT NULL,
    decision_type VARCHAR(100) NOT NULL,
    decision_data JSON NOT NULL,
    expected_outcome TEXT NULL,
    actual_outcome TEXT NULL,
    success BOOLEAN NULL,
    measured_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_agent_success (agent_type, success)
);
```

---

## 10. XOTIRA TIZIMI

### 10.1 Birinchi qatlam: Lahzalik xotira

**Saqlanadigan joy:** Redis
**Muddat:** 15 daqiqa (suhbat davomida)
**Kalit:** `agent:session:{business_id}:{conversation_id}`
**Mazmun:** Hozirgi suhbat xabarlari (oxirgi 10 ta), agent rejasi, oraliq natijalar

```php
// Redis da saqlash
Redis::setex(
    "agent:session:{$businessId}:{$conversationId}",
    900, // 15 daqiqa
    json_encode([
        'messages' => $lastMessages,
        'agent_plan' => $currentPlan,
        'intermediate_results' => $results,
    ])
);
```

### 10.2 Ikkinchi qatlam: Biznes xotirasi

**Saqlanadigan joy:** MySQL
**Muddat:** 30 kunlik (eski ma'lumotlar arxivlanadi)

```sql
CREATE TABLE agent_business_context (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    context_type ENUM('decision','preference','snapshot','feedback') NOT NULL,
    context_key VARCHAR(100) NOT NULL,
    context_value JSON NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_business_type (business_id, context_type),
    INDEX idx_expires (expires_at)
);
```

**Saqlanadigan ma'lumotlar:**
- `decision`: Agent qarorlari va natijalari
- `preference`: Foydalanuvchi afzalliklari ("qisqa hisobot yoqtiradi")
- `snapshot`: Biznesning haftalik holati (KPI lari)
- `feedback`: Tekshiruvchi izohlari

### 10.3 Uchinchi qatlam: Uzoq muddatli o'rganish

**Saqlanadigan joy:** MySQL
**Muddat:** Cheksiz (hech qachon o'chirilmaydi)

```sql
CREATE TABLE success_patterns (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,       -- NULL = umumiy
    industry VARCHAR(50) NOT NULL,
    agent_type VARCHAR(50) NOT NULL,
    pattern_type VARCHAR(50) NOT NULL,      -- content_time, objection_response, etc.
    pattern_data JSON NOT NULL,
    success_rate DECIMAL(5,2) NOT NULL,
    sample_count INT DEFAULT 1,
    last_validated_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_industry (industry, agent_type),
    INDEX idx_success (success_rate DESC)
);

CREATE TABLE failure_patterns (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    industry VARCHAR(50) NOT NULL,
    agent_type VARCHAR(50) NOT NULL,
    pattern_type VARCHAR(50) NOT NULL,
    pattern_data JSON NOT NULL,
    failure_description TEXT NOT NULL,
    times_detected INT DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_industry (industry, agent_type)
);
```

---

## 11. SOHAVIY JAMOAVIY BILIM TIZIMI

### 11.1 Asosiy tamoyil

Bir xil sohadagi bizneslarning nomsiz, umumlashtirilgan natijalari birlashtiriladi. Hech kimning shaxsiy ma'lumoti almashilmaydi — faqat raqamlar va umumiy natijalar.

### 11.2 Almashiladigan va almashilmaydigan ma'lumotlar

**HECH QACHON almashilmaydi:**
- Mijozlar ismi, telefoni, ma'lumotlari
- Lead va buyurtma tafsilotlari
- Moliyaviy raqamlar
- Suhbat matnlari
- Biznes nomi

**Nomsiz holda almashiladi:**
- Kontent turlari va natijalari (raqam sifatida)
- Optimal vaqtlar
- E'tiroz javoblari va muvaffaqiyat foizi
- Xatolar namunalari
- Savdo usullari va natijalari

### 11.3 Ma'lumotlar bazasi

```sql
CREATE TABLE industry_content_benchmarks (
    id CHAR(36) PRIMARY KEY,
    industry VARCHAR(50) NOT NULL,
    content_type VARCHAR(50) NOT NULL,      -- video, image, carousel, text
    platform VARCHAR(30) NOT NULL,          -- instagram, telegram
    avg_engagement_rate DECIMAL(8,4) NOT NULL,
    avg_reach_rate DECIMAL(8,4) NOT NULL,
    sample_count INT NOT NULL,
    optimal_times JSON NULL,                -- [{"day":"tuesday","hour":19,"score":4.2}]
    optimal_caption_length JSON NULL,       -- {"min":100,"max":180,"best":140}
    optimal_hashtag_count JSON NULL,
    best_ctas JSON NULL,                    -- [{"cta":"DM yozing","rate":3.5}]
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_industry (industry, platform)
);

CREATE TABLE industry_objection_responses (
    id CHAR(36) PRIMARY KEY,
    industry VARCHAR(50) NOT NULL,
    objection_type ENUM('price','trust','timing','product','need','authority') NOT NULL,
    response_text TEXT NOT NULL,
    success_rate DECIMAL(5,2) NOT NULL,
    usage_count INT DEFAULT 0,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_industry_type (industry, objection_type),
    INDEX idx_success (success_rate DESC)
);

CREATE TABLE industry_funnel_benchmarks (
    id CHAR(36) PRIMARY KEY,
    industry VARCHAR(50) NOT NULL,
    stage_from VARCHAR(50) NOT NULL,
    stage_to VARCHAR(50) NOT NULL,
    avg_conversion_rate DECIMAL(5,2) NOT NULL,
    avg_time_hours DECIMAL(8,2) NULL,
    sample_count INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    created_at TIMESTAMP,
    INDEX idx_industry (industry)
);

CREATE TABLE industry_call_benchmarks (
    id CHAR(36) PRIMARY KEY,
    industry VARCHAR(50) NOT NULL,
    stage VARCHAR(50) NOT NULL,             -- 7 bosqichdan biri
    avg_score DECIMAL(5,2) NOT NULL,
    best_phrases JSON NULL,
    worst_phrases JSON NULL,
    sample_count INT NOT NULL,
    created_at TIMESTAMP,
    INDEX idx_industry (industry)
);

-- Sohaviy bilimni yangilash uchun cron
-- php artisan agent:aggregate-industry-data (haftalik)
```

### 11.4 Umumlashtirish jarayoni

```
Haftalik cron ishi:
1. Har bir soha uchun oxirgi 7 kunlik ma'lumotlarni yig'ish
2. Nomsizlashtirish (biznes nomi, mijoz ma'lumotlari olib tashlanadi)
3. O'rtacha hisoblash (engagement, conversion, vaqtlar)
4. Sohaviy jadvallarni yangilash
5. Agar yetarli ma'lumot bo'lsa (5+ biznes) — statistik ishonchlilik tekshiriladi
```

---

## 12. TARIF TIZIMI VA CHEKLOVLAR

### 12.1 Tarif rejalar

| Xususiyat | Boshlang'ich (499,000) | O'sish (1,399,000) | Kengaytirilgan (2,790,000) |
|-----------|----------------------|-------------------|--------------------------|
| Biznes | 1 | 1 | 1 |
| Instagram | 1 | 1 | 1 |
| Jamoa | 2 | 5 | 15 |
| Suhbat kanallari | 1 (Telegram) | 3 (TG+IG+FB) | Cheksiz |
| Suhbat xabarlari/oy | 1,000 | 5,000 | 15,000 |
| AI Agent savollari | 10/kun | Cheksiz | Cheksiz + maxsus |
| Lead limiti/oy | 300 | 2,000 | Cheksiz |
| Raqobatchi kuzatuvi | 3 | 5 | Cheksiz |
| Qo'ng'iroq tahlili | Yo'q | 30 soat/oy | 100 soat/oy |
| Avtomatik harakatlar | Yo'q | Ha | To'liq |
| Sohaviy bilim | Yo'q | Ha | Ha + statistika |
| Hisobotlar | Asosiy | To'liq (6 tur) | To'liq + maxsus |
| Ma'lumot saqlash | 90 kun | 1 yil | 3 yil |

### 12.2 Cheklov tekshirish

> **Eslatma:** Loyihada mavjud cheklov tekshirish tizimi bor (`UsageLimitCheck`). Yangi agent cheklovlari shu mavjud tizimga qo'shiladi.

Yangi cheklov turlari:

```php
// config/subscription_limits.php ga qo'shiladigan yangi cheklovlar
'agent_questions_daily' => [
    'starter' => 10,
    'growth' => -1,      // -1 = cheksiz
    'extended' => -1,
],
'chat_messages_monthly' => [
    'starter' => 1000,
    'growth' => 5000,
    'extended' => 15000,
],
'call_analysis_hours_monthly' => [
    'starter' => 0,
    'growth' => 30,
    'extended' => 100,
],
'chat_channels' => [
    'starter' => 1,       // faqat Telegram
    'growth' => 3,        // TG + IG + FB
    'extended' => -1,     // cheksiz
],
'competitors_tracked' => [
    'starter' => 3,
    'growth' => 5,
    'extended' => -1,
],
'auto_actions_enabled' => [
    'starter' => false,
    'growth' => true,
    'extended' => true,
],
'industry_knowledge_enabled' => [
    'starter' => false,
    'growth' => true,
    'extended' => true,
],
```

### 12.3 Moliyaviy natijalar

| Ko'rsatkich | Boshlang'ich | O'sish | Kengaytirilgan |
|-------------|-------------|--------|----------------|
| Narx | 499,000 | 1,399,000 | 2,790,000 |
| AI xarajat | ~23,100 | ~90,400 | ~212,200 |
| Jami xarajat | ~68,600 | ~154,400 | ~274,500 |
| Foyda | ~430,400 | ~1,244,600 | ~2,515,500 |
| Foyda ulushi | 86% | 89% | 90% |

---

## 13. SUN'IY AQL BILAN MULOQOT XIZMATI

### 13.1 Umumiy AI xizmat sinfi

Barcha agentlar bitta umumiy xizmat orqali sun'iy aql bilan muloqot qiladi:

```php
// app/Services/AI/AIService.php

class AIService
{
    /**
     * Sun'iy aqlga so'rov yuborish
     * Gibrid mantiq: avval keshdan, keyin model tanlash
     */
    public function ask(
        string $prompt,
        string $systemPrompt,
        string $preferredModel = 'haiku',  // 'haiku' yoki 'sonnet'
        int $maxTokens = 1000,
        ?string $cacheKey = null,
        int $cacheTTL = 3600
    ): AIResponse {
        // 1. Keshdan tekshirish
        if ($cacheKey && $cached = Redis::get("ai_cache:{$cacheKey}")) {
            return AIResponse::fromCache($cached);
        }

        // 2. Model tanlash
        $model = $preferredModel === 'sonnet'
            ? 'claude-sonnet-4-6'
            : 'claude-haiku-4-5-20251001';

        // 3. Claude API ga so'rov
        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.key'),
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        // 4. Natijani keshlash
        if ($cacheKey) {
            Redis::setex("ai_cache:{$cacheKey}", $cacheTTL, $response->body());
        }

        // 5. Token va xarajatni qayd qilish
        $this->logUsage($model, $response);

        return AIResponse::fromAPI($response);
    }

    /**
     * Groq Whisper orqali ovozdan matnga
     */
    public function transcribe(string $audioPath): TranscriptionResult
    {
        $response = Http::attach(
            'file', file_get_contents($audioPath), 'audio.mp3'
        )->post('https://api.groq.com/openai/v1/audio/transcriptions', [
            'model' => 'whisper-large-v3-turbo',
            'language' => 'uz', // o'zbek + rus + ingliz avtomatik
            'response_format' => 'verbose_json',
        ]);

        return TranscriptionResult::from($response);
    }
}
```

### 13.2 Token va xarajat kuzatuvi

```sql
CREATE TABLE ai_usage_log (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    agent_type VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    tokens_input INT NOT NULL,
    tokens_output INT NOT NULL,
    cost_usd DECIMAL(10,6) NOT NULL,
    cache_hit BOOLEAN DEFAULT FALSE,
    prompt_hash VARCHAR(64) NULL,
    created_at TIMESTAMP,
    INDEX idx_business_date (business_id, created_at),
    INDEX idx_model (model)
);

-- Oylik umumlashtirish
CREATE TABLE ai_usage_monthly (
    id CHAR(36) PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    month DATE NOT NULL,
    total_requests INT DEFAULT 0,
    cache_hit_count INT DEFAULT 0,
    total_tokens_input INT DEFAULT 0,
    total_tokens_output INT DEFAULT 0,
    total_cost_usd DECIMAL(10,4) DEFAULT 0,
    model_breakdown JSON NULL,             -- {"haiku":{"count":100,"cost":0.5},...}
    created_at TIMESTAMP,
    UNIQUE INDEX idx_business_month (business_id, month)
);
```

---

## 14. MARSHRUT VA CONTROLLER LARI

### 14.1 API marshrutlari

```php
// routes/api.php ga qo'shiladigan yangi marshrutlar

Route::prefix('v1')->middleware(['auth:sanctum', 'business.scope'])->group(function () {

    // Agent suhbat
    Route::prefix('agent')->group(function () {
        Route::post('/ask', [AgentController::class, 'ask']);
        Route::get('/conversations', [AgentController::class, 'conversations']);
        Route::get('/conversations/{id}', [AgentController::class, 'conversation']);
        Route::get('/conversations/{id}/messages', [AgentController::class, 'messages']);
    });

    // Qo'ng'iroq tahlili
    Route::prefix('call-analysis')->group(function () {
        Route::get('/', [CallAnalysisController::class, 'index']);
        Route::get('/{id}', [CallAnalysisController::class, 'show']);
        Route::get('/operator/{operatorId}/performance', [CallAnalysisController::class, 'operatorPerformance']);
        Route::get('/leaderboard', [CallAnalysisController::class, 'leaderboard']);
    });

    // Sohaviy bilim
    Route::prefix('industry')->group(function () {
        Route::get('/benchmarks', [IndustryController::class, 'benchmarks']);
        Route::get('/best-practices', [IndustryController::class, 'bestPractices']);
        Route::get('/objection-responses', [IndustryController::class, 'objectionResponses']);
    });

    // AI xarajat kuzatuvi
    Route::prefix('ai-usage')->group(function () {
        Route::get('/summary', [AIUsageController::class, 'summary']);
        Route::get('/daily', [AIUsageController::class, 'daily']);
    });
});

// Webhooklar (autentifikatsiyasiz)
Route::prefix('webhook')->group(function () {
    Route::post('/sipuni/call-complete', [SipuniWebhookController::class, 'callComplete']);
    Route::post('/utel/call-complete', [UtelWebhookController::class, 'callComplete']);
});
```

---

## 15. CRON VAZIFALARI

```php
// app/Console/Kernel.php ga qo'shiladigan yangi vazifalar

$schedule->command('agent:daily-brief')->dailyAt('08:00');
$schedule->command('agent:check-anomalies')->everyFourHours();
$schedule->command('agent:process-nurture-sequences')->everyThirtyMinutes();
$schedule->command('agent:weekly-reports')->weeklyOn(1, '09:00');  // Dushanba
$schedule->command('agent:monthly-reports')->monthlyOn(1, '09:00');
$schedule->command('agent:aggregate-industry-data')->weeklyOn(0, '03:00');  // Yakshanba tunda
$schedule->command('agent:cleanup-expired-context')->daily();
$schedule->command('agent:calculate-operator-performance')->weeklyOn(5, '18:00');  // Juma kechqurun
```

---

## 16. FAYL TUZILISHI (UMUMIY)

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── AgentController.php
│           ├── CallAnalysisController.php
│           ├── IndustryController.php
│           └── AIUsageController.php
├── Services/
│   ├── AI/
│   │   ├── AIService.php                    -- umumiy AI muloqot xizmati
│   │   ├── AIResponse.php                   -- javob formati
│   │   ├── TokenCounter.php                 -- token hisoblash
│   │   └── CostCalculator.php              -- xarajat hisoblash
│   └── Agent/
│       ├── OrchestratorService.php          -- boshqaruvchi
│       ├── AgentRouter.php                  -- yo'naltirish
│       ├── AgentResponseMerger.php          -- birlashtirish
│       ├── EvaluatorService.php             -- tekshiruvchi
│       ├── Marketing/
│       │   ├── MarketingAgentService.php
│       │   ├── Tools/ ...
│       │   └── Prompts/ ...
│       ├── Sales/
│       │   ├── SalesAgentService.php
│       │   ├── ChatHandler/ ...
│       │   ├── LeadScoring/ ...
│       │   ├── NurtureSequence/ ...
│       │   ├── Tools/ ...
│       │   └── Prompts/ ...
│       ├── Analytics/
│       │   ├── AnalyticsAgentService.php
│       │   ├── Tools/ ...
│       │   ├── Reports/ ...
│       │   └── Prompts/ ...
│       ├── CallCenter/
│       │   ├── CallCenterAgentService.php
│       │   ├── Transcription/ ...
│       │   ├── Analysis/ ...
│       │   ├── Performance/ ...
│       │   └── Prompts/ ...
│       └── Memory/
│           ├── ShortTermMemory.php          -- Redis (lahzalik)
│           ├── BusinessContextMemory.php    -- MySQL (30 kunlik)
│           ├── LongTermLearning.php         -- MySQL (cheksiz)
│           └── IndustryKnowledge.php        -- sohaviy bilim
├── Models/
│   ├── AgentConversation.php
│   ├── AgentMessage.php
│   ├── CallAnalysis.php
│   ├── OperatorPerformance.php
│   ├── EvaluatorCheck.php
│   ├── AgentDecisionOutcome.php
│   ├── AgentBusinessContext.php
│   ├── SuccessPattern.php
│   ├── FailurePattern.php
│   ├── IndustryContentBenchmark.php
│   ├── IndustryObjectionResponse.php
│   ├── IndustryFunnelBenchmark.php
│   ├── IndustryCallBenchmark.php
│   ├── LeadScoreHistory.php
│   ├── AgentHandoff.php
│   ├── NurtureSequence.php
│   ├── NurtureSequenceStep.php
│   ├── AIUsageLog.php
│   └── AIUsageMonthly.php
├── Jobs/
│   ├── ProcessChatMessage.php
│   ├── ProcessCallRecording.php
│   ├── SendNurtureMessage.php
│   ├── NotifyOperatorHotLead.php
│   ├── GenerateOperatorReport.php
│   └── AggregateIndustryData.php
├── Events/
│   ├── AgentDecisionMade.php
│   ├── HotLeadDetected.php
│   ├── CallAnalysisCompleted.php
│   └── IndustryDataUpdated.php
├── Listeners/
│   ├── LogAgentDecision.php
│   ├── NotifyOnHotLead.php
│   ├── UpdateLeadScore.php
│   └── UpdateIndustryBenchmarks.php
└── Console/
    └── Commands/
        ├── AgentDailyBrief.php
        ├── AgentCheckAnomalies.php
        ├── AgentProcessNurtureSequences.php
        ├── AgentWeeklyReports.php
        ├── AgentMonthlyReports.php
        ├── AgentAggregateIndustryData.php
        ├── AgentCleanupExpiredContext.php
        └── AgentCalculateOperatorPerformance.php
```

---

## 17. BOSQICHMA-BOSQICH JORIY ETISH REJASI

### 17.1 Rejadagi bosqichlar

| Bosqich | Muddat | Asosiy ishlar |
|---------|--------|---------------|
| **1-bosqich: Asos** | 4 hafta | AIService, Boshqaruvchi, Tahlil agenti, Agent suhbat interfeysi, Xotira 1-2 qatlam, Token kuzatuv |
| **2-bosqich: Mutaxassislar** | 4 hafta | Sotuv agenti (suhbat+CRM), Marketing agenti, Qo'ng'iroq agenti (Whisper+tahlil), Tekshiruvchi tizimi |
| **3-bosqich: O'rganish** | 3 hafta | Uzoq muddatli xotira, Sohaviy bilim to'plash va umumlashtirish, Muvaffaqiyat/xato namunalari bazasi |
| **4-bosqich: Optimallashtirish** | 3 hafta | Sohaviy o'rtachalar, Agent sifat ko'rsatkichlari, Avtomatik hisobot generatsiya, Qayta aloqa ketma-ketligi |

**Jami: 14 hafta (3.5 oy)**

### 17.2 Har bir bosqich oxirida tekshiruv mezonlari

**1-bosqich tugaganda:**
- Foydalanuvchi AI Agent bilan suhbatlasha oladi
- Agent oddiy savollarni bazadan javob beradi (bepul)
- Murakkab savollarni Haiku/Sonnet ga yuboradi
- Token sarfi qayd qilinadi
- Tahlil agenti KPI larni ko'rsatadi

**2-bosqich tugaganda:**
- Sotuv agenti Telegram da mijozlar bilan gaplashadi
- Lead ball real vaqtda hisoblanadi
- Qo'ng'iroqlar tahlil qilinadi va operator coaching oladi
- Tekshiruvchi xatolarni aniqlaydi

**3-bosqich tugaganda:**
- Agent oldingi qarorlarni eslab qoladi
- Sohaviy bilim to'plana boshlaydi (agar yetarli foydalanuvchi bo'lsa)
- Muvaffaqiyat namunalari bazasi ishlaydi

**4-bosqich tugaganda:**
- Haftalik va oylik hisobotlar avtomatik generatsiya qilinadi
- Qayta aloqa ketma-ketligi ishlaydi
- Agent sifat ko'rsatkichlari ko'rsatiladi
- Tizim to'liq tayyor

---

## 18. MUHIM ESLATMALAR

1. **Hozirgi kodni buzmang.** Har bir o'zgarishdan oldin mavjud fayllarni tekshiring. Yangi jadvallar va ustunlar qo'shing, mavjudlarini o'zgartirmang.

2. **Ma'lumotlar bazasi o'zgarishlari faqat migratsiya orqali.** `php artisan make:migration` ishlatib, barcha o'zgarishlarni migratsiya fayllarida yozing.

3. **`business_id` majburiy.** Barcha yangi jadvallar va so'rovlarda `business_id` bo'lishi shart. Bitta biznesning ma'lumoti boshqasiga ko'rinmasligi kerak.

4. **Obuna cheklovlari tekshirilishi shart.** Har bir agent harakatdan oldin foydalanuvchining tarif cheklovlarini tekshiring.

5. **Token sarfi qayd qilinishi shart.** Har bir AI chaqiriq uchun model nomi, token soni va xarajat qayd qilinadi.

6. **Gibrid yondashuv eng muhim.** Avval bazadan, keyin keshdan, oxirida AI dan javob izlang. AI ga faqat chindan kerak bo'lganda murojaat qiling.

7. **Xatolarni yutmang.** Barcha AI chaqiriqlar `try-catch` ichida bo'lsin. AI javob bermasa, foydalanuvchiga tushunarli xabar ko'rsating.

8. **Prompt fayllarni alohida saqlang.** Har bir agentning sun'iy aql uchun ko'rsatmalari `Prompts/` papkasidagi `.txt` fayllarida bo'lsin. Kodga qo'shmang.

9. **Navbat tizimini ishlating.** Og'ir vazifalar (qo'ng'iroq tahlili, hisobot yaratish) Laravel Jobs orqali navbatga qo'yiladi. Laravel Horizon bilan boshqariladi.

10. **Testlar yozing.** Har bir agent xizmati uchun kamida asosiy testlar bo'lsin. Gibrid mantiqni test qiling — bazadan javob, keshdan javob, va AI dan javob holatlari.

---

*Ushbu texnik topshiriq BiznesPilot AI Agent tizimining to'liq loyihasini qamrab oladi. Bajarish jarayonida loyihaning hozirgi holatini tekshirib, shu asosda moslashtirib ishlang.*
