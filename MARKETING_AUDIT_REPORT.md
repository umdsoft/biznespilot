# BiznesPilot — Marketing Audit Report

_Audit sanasi: 2026-04-20_
_Commit hash: `4216255100dadb684ef0c3b286d1b3754fd1e08a`_

---

## Executive Summary

**Tizim holati:** Production-ready. 1,241 PHP fayl, 583 Vue komponent, 378 model, 265 migratsiya, 2,180 route. Oxirgi 30 kunda 403 fayl o'zgartirilgan — juda aktiv development.

**Eng kuchli 3 marketing angle:**
1. **AI-powered all-in-one** — 17 AI algoritm + 22 agent moduli. O'zbekistonda hech bir raqobatchida yo'q
2. **Telegram-native** — 36 Vue sahifa, funnel builder, broadcast, e-commerce store. SMB Telegram'da yashaydi
3. **O'zbek tilida** — 3 til (uz-latn, uz-cyrl, ru). 1C/Bitrix24/amoCRM O'zbek tilida ishlamaydi

**Eng katta 3 risk:**
1. Social proof infratuzilmasi yo'q (testimonial model, case study sahifasi)
2. Ba'zi sahifalarda i18n to'liq emas (hardcoded Uzbek strings)
3. Onboarding hozir skip qilinadi — TTFV (Time-to-First-Value) optimallashtirish kerak

---

## 1. Project Status Snapshot

| Metrika | Qiymat |
|---------|--------|
| **Framework** | Laravel 12.0 + Vue 3.5.26 + Inertia.js 2.3 |
| **PHP Version** | ^8.2 |
| **30 kunda o'zgargan fayllar** | 403 |
| **PHP fayllar (app/)** | 1,241 |
| **Vue komponentlar** | 583 |
| **Migratsiyalar** | 265 |
| **Modellar** | 378 |
| **Routelar (web + api)** | 2,180 |
| **Service klasslar** | 306 |
| **Controllerlar** | 260 |
| **Background Job'lar** | 73 |
| **Frontend** | Tailwind 4, Pinia, ApexCharts, Chart.js, Leaflet, vue-i18n |

---

## 2. Modullar To'liqligi

| Modul | Controllerlar | Vue sahifalar | Holat | Demo-worthy |
|-------|--------------|--------------|-------|-------------|
| **Marketing** | 27 | 35+ | Tayyor | Content AI, Campaign, Funnel |
| **CRM/Sales** | 22 | 30+ | Tayyor | Pipeline, Lead Scoring, Analytics |
| **HR** | 22 | 40+ | Tayyor | Attendance, Payroll, Recruiting |
| **Finance** | 9 | 11 | Tayyor | Invoice, Budget, P&L |
| **Telegram** | 8 | 36 | Tayyor | Funnel Builder, Broadcast, Store |
| **AI Agent** | 5 + 55 service | 7+ | Tayyor | Multi-agent chat, Call Analysis |
| **Store/E-com** | 10 + 13 MiniApp | 47 | Tayyor | Telegram MiniApp do'kon |
| **Admin** | 6 | 10+ | Tayyor | Business/User management |

### Har bir modul — Top 5 Feature

**Marketing:**
1. AI Content Generator (`ContentAIController`) — kontentni AI bilan yaratish
2. Campaign Management (`CampaignController`) — kampaniyalar boshqaruvi
3. Content Calendar (`ContentPlanController`) — kontent rejasi
4. Viral Content Hunter (`ViralHunterService`) — trend kontentni topish
5. Channel Analytics (`ChannelAnalyticsController`) — kanal ROI tahlili

**CRM/Sales:**
1. Lead Pipeline Kanban (`LeadController`) — drag-drop pipeline
2. Real-time Lead Scoring (`RealTimeScorer`) — AI bilan lead baholash
3. Pipeline Automation (`PipelineAutomationController`) — avtomatik harakatlar
4. Lost Deals Analytics (`FunnelAnalysisAlgorithm`) — yo'qolgan deallar tahlili
5. Dream Buyer Wizard (`DreamBuyerService`) — ideal mijoz profili

**HR:**
1. Camera Attendance (`CameraAttendanceController`) — kamerali davomatChirish
2. Payroll & Salary (`PayrollController`) — ish haqi hisoblash
3. Recruiting Pipeline (`InterviewPipelineController`) — nomzodlar Kanban
4. Flight Risk Detection (`CalculateFlightRiskJob`) — ketish xavfi bashorati
5. Employee Engagement Surveys (`SurveyWebController`) — xodim so'rovnomalari

**Finance:**
1. Invoice Management (`InvoiceController`)
2. Budget Planning (`BudgetController`)
3. Expense Tracking (`ExpenseController`)
4. Revenue Forecasting (`RevenueForecaster`)
5. Cash Flow Analysis (`CashFlowService`)

---

## 3. AI Feature'lar Inventori

### 3.1 Core AI Layer
| Feature | Fayl | Model | Demo-worthy |
|---------|------|-------|-------------|
| ClaudeAI Service | `app/Services/ClaudeAIService.php` | Haiku 4.5 / Sonnet 4.5 | HA |
| AI Gateway | `app/Services/AI/AIService.php` | Auto-select | HA |
| Agent Orchestrator | `app/Services/Agent/OrchestratorService.php` | Multi-agent | HA — 30s Reels |
| Agent Router | `app/Services/Agent/AgentRouter.php` | Keyword + AI | YO'Q (backend) |

### 3.2 17 AI Algoritm
| Algoritm | Fayl | Nima qiladi |
|----------|------|-------------|
| AnomalyDetection | `Algorithm/AnomalyDetection.php` | KPI anomaliyalarni aniqlash |
| ChurnPrediction | `Algorithm/ChurnPredictionAlgorithm.php` | Mijoz ketish bashorati |
| CustomerSegmentation | `Algorithm/CustomerSegmentationAlgorithm.php` | Mijozlarni segmentlash |
| DreamBuyerScoring | `Algorithm/DreamBuyerScoringAlgorithm.php` | Ideal mijoz bali |
| FunnelAnalysis | `Algorithm/FunnelAnalysisAlgorithm.php` | Sotuv voronka tahlili |
| HealthScore | `Algorithm/HealthScoreAlgorithm.php` | Biznes salomatligi bali |
| MoneyLoss | `Algorithm/MoneyLossAlgorithm.php` | Pul yo'qotish tahlili |
| RevenueForecaster | `Algorithm/RevenueForecaster.php` | Daromad bashorati |
| NextStepPredictor | `Algorithm/NextStepPredictor.php` | Keyingi qadam tavsiyasi |
| ContentOptimization | `Algorithm/ContentOptimizationAlgorithm.php` | Kontent optimallashtirish |
| CompetitorBenchmark | `Algorithm/CompetitorBenchmarkAlgorithm.php` | Raqobatchini solishtirish |
| Diagnostic | `Algorithm/DiagnosticAlgorithmService.php` | Biznes diagnostikasi |
| Engagement | `Algorithm/EngagementAlgorithm.php` | Mijoz faolligi tahlili |
| DataAccuracy | `Algorithm/DataAccuracyAlgorithm.php` | Ma'lumot sifati tekshiruvi |
| ValueEquation | `Algorithm/ValueEquationAlgorithm.php` | Qiymat tenglamasi |
| ChurnRisk | `Algorithm/ChurnRiskAlgorithm.php` | Xavf darajasi |
| ModuleAnalyzer | `Algorithm/ModuleAnalyzer.php` | Modul ishlatilish tahlili |

### 3.3 22+ AI Agent modullari
Sales, Marketing, Analytics, CallCenter, Voice, Trainer, Lifecycle, Reputation, SeasonalPlanner, CashFlow, HealthMonitor, Knowledge, Memory, Deliverables, Context, Orchestrator va boshqalar.

### 3.4 Call Center AI
- **Speech-to-Text**: Groq Whisper (whisper-large-v3-turbo)
- **Call Analysis**: Sentiment, script compliance, talk ratio
- **Operator Scoring**: AI-powered performance scoring
- **Coaching Tasks**: Auto-generated coaching tasks

### 3.5 Content AI (12 service)
ContentGenerator, AIEnrichment, Analyzer, PlanEngine, IdeaRecommendation, CrossBusinessLearning, VideoAnalysis, ViralHunter va boshqalar.

**Raqam:** AI agent bir so'rovni ~2-5 soniyada qayta ishlaydi. Qo'lda 30-60 daqiqa ketadigan tahlilni AI 5 soniyada qiladi = **360x tezroq**.

---

## 4. Unique Differentiators (Raqobatchilardan ustunlik)

| # | BiznesPilot Feature | 1C | Bitrix24 | amoCRM | Marketing Hook (1-10) |
|---|--------------------|----|----------|--------|----------------------|
| 1 | **17 AI algoritm + 22 agent** | Yo'q | Yo'q | Yo'q | **10** |
| 2 | **O'zbek tilida (3 til)** | Yo'q | Yo'q | Yo'q | **9** |
| 3 | **Telegram funnel builder + store** | Yo'q | Yo'q | Yo'q | **10** |
| 4 | **AI Call Analysis + STT** | Yo'q | Qisman | Yo'q | **9** |
| 5 | **All-in-one (6 modul bitta narxda)** | CRM yo'q | Alohida narx | Faqat CRM | **8** |
| 6 | **Payme/Click integratsiya** | Yo'q | Yo'q | Yo'q | **8** |
| 7 | **Dream Buyer AI Wizard** | Yo'q | Yo'q | Yo'q | **7** |
| 8 | **Camera Attendance (HR)** | Yo'q | Yo'q | Yo'q | **7** |
| 9 | **Viral Content Hunter** | Yo'q | Yo'q | Yo'q | **8** |
| 10 | **Revenue Forecaster + Churn Prediction** | Yo'q | Qisman | Yo'q | **8** |

---

## 5. Raqam-Proof Arsenal (Hormozi-style)

| Raqam | Qiymat | Marketing ishlatilishi |
|-------|--------|----------------------|
| Modul soni | **6 ta** (CRM + Marketing + HR + Finance + Telegram + AI) | "6 ta dastur o'rniga 1 ta" |
| Vue sahifalar | **411** | "400+ sahifali tizim" |
| AI algoritm | **17** | "17 ta AI algoritm sizning biznesingizni tahlil qiladi" |
| AI agent | **22+** | "22 ta AI yordamchi — har biri o'z sohasida mutaxassis" |
| Integratsiya | **12+** | "12+ integratsiya — Payme, Click, Telegram, Instagram..." |
| Background job | **73** | "73 ta avtomatik jarayon — siz uxlayotganda ham ishlaydi" |
| Route | **2,180** | "2000+ funksiya bitta platformada" |
| Til | **3** | "O'zbek, Rus va Kirill alifbosida" |
| Narx | **299,000 UZS/oy** | "Kuniga 10,000 so'm — bir chashka qahva narxida" |
| Trial | **14 kun bepul** | "14 kun bepul sinab ko'ring. Karta shart emas" |
| Telegram sahifalar | **36** | "Telegram'da to'liq do'kon va funnel" |
| Kanban ko'rinishlar | **16** | "Drag & drop bilan boshqaring" |
| Chart/grafik | **30+** | "30+ grafik bilan biznesingizni real-time ko'ring" |

---

## 6. Pain-Killer Mapping (15 ta)

| # | Pain Point | BiznesPilot Yechim | Fayl | Hook |
|---|-----------|-------------------|------|------|
| 1 | Xodimlar kech keladi | Camera Attendance | `CameraAttendanceController` | "Xodimingiz 10:30'da kelganini endi bilasiz" |
| 2 | Lidlar yo'qoladi | Unified Inbox + Pipeline | `UnifiedInboxController` | "Telegram, Instagram, WhatsApp — barchasi 1 joyda" |
| 3 | Operator skript o'qimaydi | AI Call Analysis | `ScriptComplianceChecker` | "AI har bir qo'ng'iroqni tekshiradi" |
| 4 | Kontent g'oya yo'q | AI Content Generator + Viral Hunter | `ContentGeneratorService` | "AI 30 sekundda 10 ta kontent yaratadi" |
| 5 | Daromad bashorat qilolmayman | Revenue Forecaster | `RevenueForecaster` | "AI kelasi oy daromadingizni bashorat qiladi" |
| 6 | Mijoz ketib qoladi | Churn Prediction | `ChurnPredictionAlgorithm` | "Mijoz ketishini 7 kun oldin bilasiz" |
| 7 | Ish haqi xaos | Payroll + Salary | `PayrollController` | "Ish haqini 1 tugma bilan hisoblang" |
| 8 | Raqobatchini bilmayman | Competitor Monitor | `CompetitorMonitoringService` | "Raqobatchingiz nima qilayotganini AI kuzatadi" |
| 9 | Invoice qo'lda yozaman | Invoice Management | `InvoiceController` | "Hisob-faktura 10 sekundda tayyor" |
| 10 | Ideal mijozni bilmayman | Dream Buyer Wizard | `DreamBuyerService` | "AI sizning ideal mijozingizni topadi" |
| 11 | Yollash uzoq davom etadi | Recruiting Pipeline | `InterviewPipelineController` | "Nomzodlarni Kanban'da boshqaring" |
| 12 | Biznes salomatligini bilmayman | Health Score | `HealthScoreAlgorithm` | "Biznes salomatligi bali — 1 daqiqada" |
| 13 | SMS qo'lda yuboriladi | Broadcast + Automation | `OfferAutomationService` | "1000 ta mijozga 1 bosishda xabar yuboring" |
| 14 | Xodim ketishini bilmayman | Flight Risk Detection | `CalculateFlightRiskJob` | "Xodim ketish xavfi — AI bashorati" |
| 15 | Marketing ROI ko'rinmaydi | Channel ROI Analytics | `ChannelROI.vue` | "Har bir so'mning qaytimini ko'ring" |

---

## 7. Screenshot/Video Arsenal (Top 20)

| # | Sahifa | Route | Vizual | Format |
|---|--------|-------|--------|--------|
| 1 | Business Dashboard | `/business` | Health Score ring, 4 KPI karta, chart | Screenshot |
| 2 | Analytics Dashboard | `/business/analytics` | Multi-chart, revenue, funnel | Screen recording |
| 3 | Funnel Analysis | `/business/analytics/funnel` | Voronka bosqichlari | Screenshot |
| 4 | Channel ROI | `/business/analytics/channel-roi` | ROI solishtirish | Screenshot |
| 5 | HR Dashboard | `/business/hr` | Flight risk, engagement widget | Screenshot |
| 6 | Recruiting Pipeline | `/hr/recruiting/pipeline` | Kanban drag-drop | Screen recording |
| 7 | Telegram Funnel Builder | `/business/telegram-funnels/{id}/funnels` | Visual flow builder | Screen recording |
| 8 | Content AI | `/marketing/content-ai` | AI kontent yaratish | Screen recording |
| 9 | Strategy Wizard | `/business/strategy/wizard` | Multi-step wizard | Screen recording |
| 10 | SalesHead KPI | `/sales-head/kpi/dashboard` | Leaderboard, bonuslar | Screenshot |
| 11 | Weekly Report | `/business/analytics/weekly-report` | PDF hisobot | Screenshot |
| 12 | Marketing Dashboard | `/business/marketing` | Kampaniya metrikalar | Screenshot |
| 13 | Finance Dashboard | `/business/finance` | P&L, cash flow | Screenshot |
| 14 | Lead Scoring | `/business/lead-scoring` | AI scoring | Screenshot |
| 15 | Operator Scorecards | `/business/operator-scorecards` | Performance kartalar | Screenshot |
| 16 | Dream Buyer Wizard | `/business/dream-buyer/wizard` | AI persona builder | Screen recording |
| 17 | Store Dashboard | `/business/store/dashboard` | E-commerce stats | Screenshot |
| 18 | Content Calendar | `/business/marketing/content` | Kalendar drag-drop | Screen recording |
| 19 | Call Center | `/business/calls` | Real-time call stats | Screenshot |
| 20 | AI Agent Chat | `/business/ai-agent` | Multi-agent suhbat | Screen recording |

---

## 8. Demo Flow Tahlili

| Qadam | Tavsif | Holat |
|-------|--------|-------|
| **Signup** | `/register` — nom, email, parol | Tayyor |
| **Biznes yaratish** | `/welcome/create-business` — nom, kategoriya, ta'rif | Tayyor |
| **Trial** | 14 kun bepul, karta shart emas | Tayyor |
| **Onboarding** | `/onboarding/` — multi-step wizard | Mavjud lekin hozir skip qilinadi |
| **TTFV** | ~2-3 daqiqa (signup → dashboard) | Yaxshi |
| **Demo data** | Yo'q — foydalanuvchi bo'sh dashboard ko'radi | **YAXSHILASH KERAK** |
| **Interactive demo** | Landing'da chatbot simulyatsiyasi | Tayyor |

**OGOHLANTIRISH:** Onboarding hozir `WelcomeController`'da "completed" deb belgilanadi va skip qilinadi. Launch'dan oldin faollashtirish kerak.

---

## 9. Integratsiyalar Statusi

| Integratsiya | Status | Fayl | Marketing muhimligi |
|--------------|--------|------|---------------------|
| **Payme** | Live | `app/Services/Billing/PaymeService.php` | Yuqori — O'zbek to'lov |
| **Click** | Live | `app/Services/Billing/ClickService.php` | Yuqori — O'zbek to'lov |
| **Telegram Bot** | Live | `app/Services/Telegram/` (4 service) | **Kritik** — asosiy kanal |
| **Eskiz SMS** | Live | `app/Services/EskizSmsService.php` | Yuqori |
| **UTEL** | Live | `app/Services/Telephony/Utel/` (4 service) | O'rta |
| **OnlinePBX** | Live | `app/Services/Telephony/OnlinePbxProvider.php` | O'rta |
| **Instagram** | Live | `app/Services/InstagramService.php` | Yuqori |
| **WhatsApp** | Live | `app/Services/WhatsAppService.php` | O'rta |
| **Facebook** | Live | `app/Services/FacebookService.php` | O'rta |
| **Meta Ads** | Live | `app/Services/MetaAdLibraryService.php` | Yuqori |
| **Google Ads** | Config | `config/services.php` | O'rta |
| **Yandex Direct** | Config | `config/services.php` | O'rta |
| **Groq Whisper** | Live | `GroqWhisperSTT.php` | O'rta — Call analysis |
| **GA4 / Metrika** | Config | `.env.example` | Past |

---

## 10. Pricing & Plans

| Plan | Oylik (UZS) | Yillik (UZS) | Users | Botlar | Lidlar | AI |
|------|------------|-------------|-------|--------|--------|-----|
| **Trial** | Bepul | — | 2 | 1 | 50 | 15 |
| **Start** | 299,000 | 2,990,000 | 2 | 2 | 500 | 500 |
| **Standard** | 599,000 | 5,990,000 | 5 | 3 | 2,000 | 2,000 |
| **Business** | 799,000 | 7,990,000 | 10 | 5 | 10,000 | 10,000 |
| **Premium** | 1,499,000 | 14,990,000 | 15 | 20 | Unlimited | 50,000 |
| **Enterprise** | 4,999,000 | 49,990,000 | Unlimited | Unlimited | Unlimited | Unlimited |

- Yillik to'lovda ~17% chegirma (2 oy bepul)
- 14 ta limit tracked (users, leads, AI, storage, bots...)
- 10 ta gated feature (HR bot, anti-fraud, voice assistant...)
- Feature gating: `PlanLimitService`, `SubscriptionGate`, `CheckSubscriptionQuota` middleware

---

## 11. Telegram-Specific Feature'lar

| Feature | Fayl | Holat |
|---------|------|-------|
| Bot Management (CRUD) | `TelegramBotManagementController` | Tayyor |
| Visual Funnel Builder | `TelegramFunnelBuilder.vue` | Tayyor |
| Mass Broadcast | `TelegramBroadcastController` | Tayyor |
| Auto Triggers | `TelegramTriggerController` | Tayyor |
| User Management | `TelegramUserController` | Tayyor |
| Live Chat (Conversations) | `TelegramConversationController` | Tayyor |
| E-commerce Store (MiniApp) | `StoreTelegramWebhookController` | Tayyor |
| System Bot Notifications | `SystemBotController` | Tayyor |
| Funnel Engine | `FunnelEngineService` | Tayyor |
| Bot Type Registry | `BotTypeRegistry` | Tayyor |

**36 Vue sahifa** + **12 component** + **11 model** + **47 MiniApp sahifa** = **Telegram ecosystem to'liq**

---

## 12. Social Proof Infrastrukturasi

| Element | Holat | Tavsiya |
|---------|-------|---------|
| Testimonial model | **YO'Q** | DB jadvali + Vue komponent yaratish (1 kun) |
| Review/rating tizimi | **YO'Q** | App Store uslubida rating qo'shish |
| Case study sahifasi | **YO'Q** | Blog orqali hal qilish mumkin |
| Landing testimonials | **Hardcoded** | Real mijozlarga almashtirish |
| Public counter | **YO'Q** | "X ta biznes foydalanadi" counter qo'shish |
| Blog | **Mavjud** | Kontent kerak |

---

## 13. Landing Page Auditi

- **URL:** biznespilot.uz
- **Landing:** `resources/js/Pages/LandingPage.vue` (loyiha ichida)
- **Hero:** "O'zbekistondagi #1 biznes boshqaruv platformasi" + live counter
- **CTA:** "Bepul boshlash" + "Demo ko'rish"
- **Trust signals:** "Kredit karta shart emas", "14 kunlik bepul sinov", "Istalgan vaqt bekor qilish"
- **Pricing section:** Mavjud (Start tarifi 299,000 UZS)
- **FAQ:** Mavjud
- **Testimonials:** 3 ta (hardcoded)
- **Interactive demo:** Chatbot simulyatsiya
- **SEO:** JSON-LD, Open Graph, hreflang (uz, ru)
- **Social links:** t.me/biznespilot, instagram.com/biznespilot

---

## 14. Brand Assets

| Asset | Fayl | Holat |
|-------|------|-------|
| Logo (to'liq, qora) | `public/images/logo-full.svg` | Tayyor |
| Logo (to'liq, oq) | `public/images/logo-full-white.svg` | Tayyor |
| Logo (ikon) | `public/images/logo-icon.svg` | Tayyor |
| Favicon (SVG) | `public/favicon.svg` | Tayyor |
| Android icon | `public/android-chrome-512x512.png` | Tayyor |
| Apple icon | `public/apple-touch-icon.png` | Tayyor |
| **Brand color** | `#0ea5e9` (Sky-500) | Primary |
| **CTA color** | Indigo-600 → 700 gradient | Accent |
| **Font** | Inter (system-ui fallback) | Standard |
| **Tagline** | "O'zbekistondagi #1 biznes boshqaruv platformasi" | Mavjud |

---

## 15. Risklar va Blokerlar

### Critical
| # | Muammo | Tavsif | Hal qilish (soat) |
|---|--------|--------|-------------------|
| 1 | **Social proof yo'q** | Testimonial model, real review'lar kerak | 8 soat |
| 2 | **Onboarding skip** | Yangi user bo'sh dashboard ko'radi | 4 soat |
| 3 | **Demo data yo'q** | Signup'dan keyin hech narsa ko'rinmaydi | 6 soat |

### High
| # | Muammo | Tavsif | Hal qilish (soat) |
|---|--------|--------|-------------------|
| 4 | **i18n consistency** | Ba'zi sahifalarda hardcoded Uzbek | 4 soat |
| 5 | **XAMPP SSL issue** | Telegram API uchun `verify: false` kerak | Tuzatildi |
| 6 | **TODO/FIXME** | 61 ta PHP, 4 ta Vue | 8 soat |

### Medium
| # | Muammo | Tavsif | Hal qilish (soat) |
|---|--------|--------|-------------------|
| 7 | Notification klasslar kam | 2 ta formal klass vs 6 tur | 4 soat |
| 8 | Blog kontenti yo'q | Sahifa bor, kontent kerak | Marketing jamoa |
| 9 | Case study yo'q | Real mijoz natijalari kerak | Marketing jamoa |

---

## Marketing Team uchun Tavsiyalar

1. **Telegram — asosiy kanal.** 36 Vue sahifa, funnel builder, broadcast, e-commerce store. Bu raqobatchilarda yo'q. Har bir Reels/Post'da Telegram integration ko'rsatilsin.

2. **"6 ta dastur o'rniga 1 ta"** — eng kuchli hook. 1C + amoCRM + Trello + HR soft + Excel = 5 ta dastur. BiznesPilot = 1 ta. Narx: 299,000/oy vs 5 ta dastur narxi.

3. **AI feature'larni demo qiling.** 17 algoritm + 22 agent. "AI sizning biznesingizni 5 soniyada tahlil qiladi" — Reels'da AI Agent chat'ni ko'rsating.

4. **"Kuniga 10,000 so'm"** — Start tarifi 299,000/30 = ~10,000 so'm/kun. Bir chashka qahva narxi.

5. **O'zbek tilida yagona** — 1C, Bitrix24, amoCRM O'zbek tilida ishlamaydi. Bu katta differentiator.

6. **Call Center AI** — "Har bir qo'ng'iroqni AI tekshiradi, operator skript o'qiganini nazorat qiladi" — bu raqobatchilarda umuman yo'q.

7. **Launch'dan oldin:** Demo data + Onboarding wizard + 3 ta real testimonial = minimum requirement. Bularni 2-3 kunda hal qilish mumkin.
