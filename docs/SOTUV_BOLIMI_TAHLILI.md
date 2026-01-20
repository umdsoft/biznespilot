# BiznesPilot - Sotuv Bo'limi Tahlili

> **Tahlil sanasi:** 2026-01-20
> **Loyiha:** BiznesPilot CRM
> **Modul:** Sotuv (Sales)

---

## Mundarija

1. [Mavjud Rollar](#1-mavjud-rollar)
2. [Mavjud Funksionallar](#2-mavjud-funksionallar)
3. [Database Strukturasi](#3-database-strukturasi)
4. [API Endpoints](#4-api-endpoints)
5. [Yo'q Funksionallar](#5-hozirda-yoq-lekin-kerak-bolishi-mumkin)
6. [Fayl Joylashuvlari](#6-fayl-joylashuvlari)

---

## 1. MAVJUD ROLLAR

### 1.1 Rollar jadvali

| Rol | Tizim nomi | Vazifasi | Panel |
|-----|------------|----------|-------|
| **Sotuv bo'limi rahbari** | `sales_head` | Barcha lidlar, jamoani boshqarish, hisobotlar | `/sales-head/*` |
| **Sotuv operatori (ROP)** | `sales_operator` | Faqat o'ziga biriktirilgan lidlar bilan ishlash | `/operator/*` |
| **Biznes egasi** | `owner` | To'liq kirish (SalesHead paneliga ham) | Barcha |
| **Administrator** | `admin` | view/create/update/delete-sales | Ko'p |
| **Menejer** | `manager` | view/create/update-sales (delete yo'q) | O'rtacha |
| **Xodim** | `member` | view/create/update-sales | Cheklangan |
| **Ko'ruvchi** | `viewer` | Faqat view-sales | Minimal |

### 1.2 Ruxsatlar (Permissions)

```
view-sales      → Sotuvlarni ko'rish
create-sales    → Yangi sotuv yaratish
update-sales    → Sotuvni tahrirlash
delete-sales    → Sotuvni o'chirish
```

### 1.3 SalesHead (Rahbar) paneli funksiyalari

- Dashboard - Umumiy ko'rsatkichlar
- Leads (Kanban) - Lidlar boshqaruvi
- Calls - Qo'ng'iroqlar
- Deals - Bitimlar
- Tasks - Vazifalar
- Analytics - Tahlillar
- KPI - Samaradorlik
- Performance - Xodimlar ishlashi
- Team - Jamoa boshqaruvi
- Reports - Hisobotlar
- SalesScript - Sotuv skriptlari
- Settings - Sozlamalar
- Inbox - Xabarlar

### 1.4 Operator paneli funksiyalari

- Dashboard - Shaxsiy ko'rsatkichlar
- Leads - Faqat o'ziga biriktirilgan lidlar
- Tasks - Vazifalar
- Todos - Shaxsiy rejalar
- KPI - Shaxsiy samaradorlik
- SalesScript - Sotuv skriptlari
- Inbox - Xabarlar

---

## 2. MAVJUD FUNKSIONALLAR

### 2.1 Lead Management (Lidlar bilan ishlash)

| Funksiya | Tavsif | Rol | Fayl |
|----------|--------|-----|------|
| Lidlar ro'yxati (Kanban) | Pipeline ko'rinishida lidlar | SalesHead | `SalesHead/LeadController.php` |
| Lid yaratish | Yangi lid qo'shish | SalesHead | `SalesHead/LeadController.php` |
| Lid tahrirlash | Ma'lumotlarni yangilash | SalesHead, Operator | `LeadController.php` |
| Lid biriktirish | Operatorga tayinlash | SalesHead | `LeadController@assign` |
| Status o'zgartirish | Pipeline bosqichini o'zgartirish | SalesHead, Operator | `LeadController@updateStatus` |
| Lid yo'qotish | Lost reason bilan yopish | SalesHead | `LeadController@markLost` |
| Lid manbasi | Qayerdan kelganini kuzatish | SalesHead | `LeadSource` model |
| Lid skoring | Ball berish | SalesHead | `Lead.score` |
| Lead activities | Faoliyat tarixi | SalesHead, Operator | `LeadActivity` model |
| Eslatma qo'shish | Izohlar | SalesHead, Operator | `LeadController@addNote` |

#### Lost Reasons (Yo'qotish sabablari)

| Kod | Nomi |
|-----|------|
| `price` | Narx qimmat |
| `competitor` | Raqobatchini tanladi |
| `no_budget` | Byudjet yo'q |
| `no_need` | Ehtiyoj yo'q |
| `no_response` | Javob bermadi |
| `wrong_contact` | Noto'g'ri kontakt |
| `low_quality` | Sifatsiz lid |
| `timing` | Vaqt mos kelmadi |
| `other` | Boshqa sabab |

#### Lead maydonlari

```php
- uuid, business_id, source_id, assigned_to
- name, email, phone, phone2, company
- birth_date, region, district, address, gender
- status, lost_reason, lost_reason_details
- score, estimated_value, data, notes
- last_contacted_at, converted_at
```

---

### 2.2 Pipeline Management (Bitimlar)

#### Standart bosqichlar

| Bosqich | Rang | Tartib | Turi |
|---------|------|--------|------|
| Yangi | ko'k (blue) | 1 | Tizim |
| Bog'lanildi | indigo | 2 | Oddiy |
| Keyinroq bog'lanish | binafsha (purple) | 3 | Oddiy |
| O'ylab ko'radi | to'q sariq (orange) | 4 | Oddiy |
| Uchrashuv belgilandi | sariq (yellow) | 5 | Oddiy |
| Uchrashuvga keldi | teal | 6 | Oddiy |
| **Sotuv** | yashil (green) | 100 | Won ✅ |
| **Sifatsiz lid** | qizil (red) | 101 | Lost ❌ |

#### Pipeline Stage maydonlari

```php
- business_id, name, slug, color, order
- is_system, is_won, is_lost, is_active
```

---

### 2.3 Task Management (Vazifalar)

| Funksiya | Tavsif | Rol |
|----------|--------|-----|
| Vazifa yaratish | Lid uchun task | SalesHead, Operator |
| Vazifa tahrirlash | Ma'lumotlarni o'zgartirish | SalesHead, Operator |
| Vazifa bajarish | Completed qilish | SalesHead, Operator |
| Reminder | Eslatma vaqti | SalesHead, Operator |
| Overdue tracking | Muddati o'tganlar | SalesHead, Operator |

#### Vazifa turlari (Types)

| Kod | Nomi |
|-----|------|
| `call` | Qo'ng'iroq |
| `meeting` | Uchrashuv |
| `email` | Email |
| `task` | Vazifa |
| `follow_up` | Qayta aloqa |
| `other` | Boshqa |

#### Prioritetlar

| Kod | Nomi |
|-----|------|
| `low` | Past |
| `medium` | O'rtacha |
| `high` | Yuqori |
| `urgent` | Shoshilinch |

#### Statuslar

| Kod | Nomi |
|-----|------|
| `pending` | Kutilmoqda |
| `in_progress` | Jarayonda |
| `completed` | Bajarildi |
| `cancelled` | Bekor qilindi |

#### Task maydonlari

```php
- business_id, lead_id, user_id, assigned_to
- title, description, type, priority, status
- due_date, reminder_at, completed_at, result
```

---

### 2.4 Communication (Aloqa)

| Funksiya | Tavsif | Rol |
|----------|--------|-----|
| Qo'ng'iroqlar ro'yxati | Kunlik/Haftalik/Oylik | SalesHead |
| Call logging | Qo'ng'iroq qayd qilish | Operator |
| Call recording | Yozuvni tinglash | SalesHead |
| Call sync | PBX dan sync | SalesHead |
| Call stats | Statistika | SalesHead |
| Operator stats | Xodim bo'yicha statistika | SalesHead |

#### Provayderlar

| Kod | Nomi |
|-----|------|
| `pbx` | OnlinePBX |
| `sipuni` | Sipuni |
| `moizvonki` | MoiZvonki |
| `utel` | UTEL |

#### Call statuslari

| Kod | Tavsif |
|-----|--------|
| `initiated` | Boshlandi |
| `ringing` | Jiringlamoqda |
| `answered` | Javob berildi |
| `completed` | Tugallandi |
| `failed` | Muvaffaqiyatsiz |
| `missed` | O'tkazib yuborildi |
| `busy` | Band |
| `no_answer` | Javob yo'q |
| `cancelled` | Bekor qilindi |

#### Call yo'nalishlari

| Kod | Tavsif |
|-----|--------|
| `inbound` | Kiruvchi |
| `outbound` | Chiquvchi |

#### CallLog maydonlari

```php
- business_id, lead_id, user_id, provider
- provider_call_id, direction, from_number, to_number
- status, duration, wait_time, recording_url, notes
- metadata, started_at, answered_at, ended_at
```

---

### 2.5 Reporting (Hisobotlar)

| Funksiya | Tavsif | Rol |
|----------|--------|-----|
| Dashboard | Umumiy ko'rsatkichlar | SalesHead |
| Analytics | Chuqur tahlillar | SalesHead |
| KPI | Performance ko'rsatkichlari | SalesHead, Operator |
| Operator stats | Xodim samaradorligi | SalesHead |
| Daily breakdown | Kunlik hisobot | SalesHead |
| Call statistics | Qo'ng'iroq statistikasi | SalesHead |

---

### 2.6 Settings (Sozlamalar)

| Funksiya | Tavsif | Rol |
|----------|--------|-----|
| Pipeline stages | Bosqichlarni sozlash | SalesHead |
| Lead sources | Manbalarni boshqarish | SalesHead |
| Team management | Jamoani boshqarish | SalesHead |
| Sales scripts | Skriptlarni sozlash | SalesHead |

---

## 3. DATABASE STRUKTURASI

### 3.1 Asosiy jadvallar

#### `leads` - Lidlar

| Maydon | Turi | Tavsif |
|--------|------|--------|
| uuid | UUID | Primary key |
| business_id | UUID | Biznes FK |
| source_id | UUID | Manba FK |
| assigned_to | UUID | Operator FK |
| name | string | Ism |
| email | string | Email |
| phone | string | Telefon |
| phone2 | string | Qo'shimcha telefon |
| company | string | Kompaniya |
| birth_date | date | Tug'ilgan sana |
| region | string | Viloyat |
| district | string | Tuman |
| address | text | Manzil |
| gender | string | Jinsi |
| status | string | Holat (pipeline stage) |
| lost_reason | string | Yo'qotish sababi |
| lost_reason_details | text | Batafsil sabab |
| score | integer | Ball |
| estimated_value | decimal | Taxminiy qiymat |
| data | json | Qo'shimcha ma'lumotlar |
| notes | text | Izohlar |
| last_contacted_at | timestamp | Oxirgi aloqa |
| converted_at | timestamp | Konversiya vaqti |

#### `tasks` - Vazifalar

| Maydon | Turi | Tavsif |
|--------|------|--------|
| uuid | UUID | Primary key |
| business_id | UUID | Biznes FK |
| lead_id | UUID | Lid FK |
| user_id | UUID | Yaratuvchi FK |
| assigned_to | UUID | Bajaruvchi FK |
| title | string | Sarlavha |
| description | text | Tavsif |
| type | string | Turi |
| priority | string | Muhimlik |
| status | string | Holat |
| due_date | datetime | Muddat |
| reminder_at | datetime | Eslatma vaqti |
| completed_at | datetime | Bajarilgan vaqt |
| result | text | Natija |

#### `call_logs` - Qo'ng'iroqlar

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK |
| lead_id | UUID | Lid FK |
| user_id | UUID | Operator FK |
| provider | string | Provayder |
| provider_call_id | string | Tashqi ID |
| direction | string | Yo'nalish |
| from_number | string | Kimdan |
| to_number | string | Kimga |
| status | string | Holat |
| duration | integer | Davomiylik (sek) |
| wait_time | integer | Kutish vaqti |
| recording_url | string | Yozuv URL |
| notes | text | Izohlar |
| metadata | json | Qo'shimcha |
| started_at | timestamp | Boshlanish |
| answered_at | timestamp | Javob vaqti |
| ended_at | timestamp | Tugash |

#### `pipeline_stages` - Pipeline bosqichlari

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK |
| name | string | Nomi |
| slug | string | Slug |
| color | string | Rang |
| order | integer | Tartib |
| is_system | boolean | Tizim bosqichimi |
| is_won | boolean | Yutilgan |
| is_lost | boolean | Yo'qotilgan |
| is_active | boolean | Faolmi |

#### `lead_sources` - Lid manbalari

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK (null = global) |
| code | string | Kod |
| name | string | Nomi |
| category | string | Kategoriya |
| icon | string | Ikonka |
| color | string | Rang |
| is_paid | boolean | Pullikmi |
| default_cost | decimal | Standart narx |
| is_trackable | boolean | Kuzatiladimi |
| sort_order | integer | Tartib |
| is_active | boolean | Faolmi |

#### `lead_activities` - Lid faoliyati

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| lead_id | UUID | Lid FK |
| user_id | UUID | Foydalanuvchi FK |
| type | string | Turi |
| title | string | Sarlavha |
| description | text | Tavsif |
| changes | json | O'zgarishlar |
| metadata | json | Qo'shimcha |

**Activity turlari:**
- `created` - Yaratildi
- `updated` - Yangilandi
- `status_changed` - Status o'zgardi
- `note_added` - Izoh qo'shildi
- `assigned` - Biriktirildi
- `contacted` - Bog'lanildi
- `task_created` - Vazifa yaratildi
- `task_completed` - Vazifa bajarildi

#### `business_user` - Jamoa a'zolari

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK |
| user_id | UUID | Foydalanuvchi FK |
| role | string | Rol |
| department | string | Bo'lim |
| permissions | json | Ruxsatlar |
| invited_by | UUID | Taklif qiluvchi |
| invitation_token | string | Taklif tokeni |
| invitation_expires_at | timestamp | Muddati |
| invited_at | timestamp | Taklif vaqti |
| accepted_at | timestamp | Qabul vaqti |
| joined_at | timestamp | Qo'shilgan vaqt |

#### `sales` - Sotuvlar

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK |
| order_id | UUID | Buyurtma FK |
| product_id | UUID | Mahsulot FK |
| customer_id | UUID | Mijoz FK |
| marketing_channel_id | UUID | Marketing kanal FK |
| amount | decimal | Summa |
| cost | decimal | Xarajat |
| profit | decimal | Foyda |
| currency | string | Valyuta |
| sale_date | date | Sotuv sanasi |

#### `sales_metrics` - Sotuv metrikalari

| Maydon | Turi | Tavsif |
|--------|------|--------|
| id | bigint | Primary key |
| business_id | UUID | Biznes FK |
| monthly_lead_volume | string | Oylik lid soni |
| lead_sources | json | Manbalar |
| lead_quality | string | Lid sifati |
| monthly_sales_volume | string | Oylik sotuv |
| avg_deal_size | decimal | O'rtacha bitim |
| sales_cycle | string | Sotuv sikli |
| sales_team_type | string | Jamoa turi |
| sales_tools | json | Vositalar |
| sales_challenges | json | Muammolar |
| additional_data | json | Qo'shimcha |

---

## 4. API ENDPOINTS

### 4.1 SalesHead Routes

**Base URL:** `/sales-head`
**Middleware:** `auth`, `sales.head`

#### Leads

| Method | URL | Controller | Vazifasi |
|--------|-----|------------|----------|
| GET | `/leads` | `LeadController@index` | Lidlar ro'yxati |
| GET | `/leads/{lead}` | `LeadController@show` | Lid ko'rish |
| PUT | `/leads/{lead}` | `LeadController@update` | Lid tahrirlash |
| POST | `/leads/{lead}/assign` | `LeadController@assign` | Biriktirish |
| POST | `/leads/{lead}/status` | `LeadController@updateStatus` | Status o'zgartirish |
| POST | `/leads/{lead}/mark-lost` | `LeadController@markLost` | Yo'qotish |
| GET | `/leads/{lead}/tasks` | `LeadController@getTasks` | Vazifalar |
| GET | `/leads/{lead}/activities` | `LeadController@getActivities` | Faoliyat |
| POST | `/leads/{lead}/notes` | `LeadController@addNote` | Eslatma |
| GET | `/leads/{lead}/calls` | `LeadController@getCalls` | Qo'ng'iroqlar |
| POST | `/leads/{lead}/sync-calls` | `LeadController@syncLeadCalls` | PBX sync |

#### Calls

| Method | URL | Controller | Vazifasi |
|--------|-----|------------|----------|
| GET | `/calls` | `CallController@index` | Qo'ng'iroqlar ro'yxati |
| GET | `/calls/{call}` | `CallController@show` | Qo'ng'iroq ko'rish |
| PATCH | `/calls/{call}/status` | `CallController@updateStatus` | Status yangilash |
| GET | `/calls/{call}/recording` | `CallController@getRecording` | Yozuvni olish |

#### Deals

| Method | URL | Controller | Vazifasi |
|--------|-----|------------|----------|
| GET | `/deals` | `DealController@index` | Bitimlar ro'yxati |
| GET | `/deals/{deal}` | `DealController@show` | Bitim ko'rish |

### 4.2 Operator Routes

**Base URL:** `/operator`
**Middleware:** `auth`, `operator`

| Method | URL | Controller | Vazifasi |
|--------|-----|------------|----------|
| GET | `/leads` | `LeadController@index` | Mening lidlarim |
| GET | `/leads/{lead}` | `LeadController@show` | Lid ko'rish |
| POST | `/leads/{lead}/status` | `LeadController@updateStatus` | Status |
| POST | `/leads/{lead}/note` | `LeadController@addNote` | Eslatma |
| POST | `/leads/{lead}/call` | `LeadController@logCall` | Qo'ng'iroq qayd |

### 4.3 API Webhooks

**Base URL:** `/api/webhooks`

| Method | URL | Vazifasi |
|--------|-----|----------|
| POST | `/pbx/*` | OnlinePBX webhook |
| POST | `/moizvonki/*` | MoiZvonki webhook |
| POST | `/utel/*` | UTEL webhook |
| POST | `/sipuni/*` | Sipuni webhook |

### 4.4 Phone API

| Method | URL | Vazifasi |
|--------|-----|----------|
| POST | `/api/call` | Qo'ng'iroq qilish |
| POST | `/api/call/{lead}` | Lidga qo'ng'iroq |
| GET | `/api/lead/{lead}/history` | Call tarixi |

---

## 5. HOZIRDA YO'Q LEKIN KERAK BO'LISHI MUMKIN

### 5.1 Yo'q funksionallar

#### Avtomatlashtirish

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Lead auto-assignment | Yukga qarab avtomatik biriktirish | 🔴 Yuqori |
| Auto follow-up reminders | Avtomatik eslatmalar | 🔴 Yuqori |
| Lead scoring automation | AI bilan ball berish | 🟡 O'rta |
| Workflow automation | If-then qoidalar | 🟡 O'rta |
| Auto-response | Avtomatik javob | 🟡 O'rta |

#### Kommunikatsiya

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| SMS integration | SMS yuborish/qabul qilish | 🔴 Yuqori |
| WhatsApp integration | WhatsApp bot | 🔴 Yuqori |
| Telegram integration | Telegram bot | 🔴 Yuqori |
| Email integration | Email yuborish/qabul qilish | 🟡 O'rta |
| In-app chat | Ichki chat | 🟢 Past |

#### Pipeline va Bitimlar

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Multiple pipelines | Har xil turdagi pipeline | 🟡 O'rta |
| Deal model | Alohida Deal entity | 🔴 Yuqori |
| Products catalog | Mahsulotlar ro'yxati | 🔴 Yuqori |
| Quotation/Proposal | Taklif yaratish | 🔴 Yuqori |
| Invoice generation | Hisob-faktura | 🟡 O'rta |
| Contract management | Shartnomalar | 🟡 O'rta |

#### Hisobotlar va Tahlil

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Funnel conversion | Voronka tahlili | 🔴 Yuqori |
| Sales forecast | Sotuv prognozi | 🟡 O'rta |
| Revenue tracking | Daromad kuzatish | 🔴 Yuqori |
| Lead source ROI | Manba ROI | 🔴 Yuqori |
| Custom reports | Maxsus hisobotlar | 🟡 O'rta |
| Export to Excel | Excel eksport | 🟡 O'rta |

#### Gamification

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Leaderboard | Reyting taxtasi | 🟡 O'rta |
| Badges/Achievements | Yutuqlar | 🟢 Past |
| Bonus calculation | Bonus hisoblash | 🟡 O'rta |
| Commission tracking | Komissiya | 🟡 O'rta |

#### Mobile va Integratsiya

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Mobile app | Mobil ilova (operators) | 🔴 Yuqori |
| Calendar sync | Google/Outlook sync | 🟡 O'rta |
| Payment tracking | To'lov kuzatish | 🔴 Yuqori |
| 1C integration | 1C bilan integratsiya | 🟡 O'rta |

#### AI va Smart Features

| Funksiya | Tavsif | Ahamiyati |
|----------|--------|-----------|
| Smart prioritization | AI bilan prioritet | 🟡 O'rta |
| Call transcript | Qo'ng'iroq matni | 🟡 O'rta |
| Sentiment analysis | Kayfiyat tahlili | 🟢 Past |
| Next best action | Keyingi qadam tavsiyasi | 🟡 O'rta |
| Duplicate detection | Dublikat aniqlash | 🔴 Yuqori |

### 5.2 To'ldirilmagan joylar

| Muammo | Tavsif | Yechim |
|--------|--------|--------|
| Deal vs Lead | Hozir faqat Lead bor, Deal alohida entity emas | Deal model yaratish |
| Revenue tracking | Sotuv summasi to'liq kuzatilmaydi | Sale modelni kengaytirish |
| Product catalog | Mahsulotlar ro'yxati yo'q | Product model yaratish |
| Quote/Invoice | Taklif va hisob-faktura yo'q | Quote, Invoice model |
| Commission | Operator komissiyasi hisobi yo'q | Commission model |
| Targets | Oylik maqsadlar tizimi yo'q | Target/Quota model |
| Territory | Hudud bo'yicha taqsimot yo'q | Territory model |
| Duplicate detection | Dublikat lidlarni aniqlash yo'q | Merge funksiyasi |

---

## 6. FAYL JOYLASHUVLARI

### 6.1 Models

```
app/Models/
├── Lead.php                 # Lidlar
├── Task.php                 # Vazifalar
├── CallLog.php              # Qo'ng'iroqlar
├── PipelineStage.php        # Pipeline bosqichlari
├── LeadSource.php           # Lid manbalari
├── LeadActivity.php         # Lid faoliyati
├── Sale.php                 # Sotuvlar
├── SalesMetrics.php         # Metrikalar
├── BusinessUser.php         # Jamoa
├── Role.php                 # Rollar
└── Permission.php           # Ruxsatlar
```

### 6.2 Controllers

```
app/Http/Controllers/
├── SalesHead/
│   ├── LeadController.php       # 34,606 bytes
│   ├── CallController.php       # 9,115 bytes
│   ├── DealController.php       # 351 bytes
│   ├── TaskController.php       # 10,624 bytes
│   ├── KpiController.php        # 24,891 bytes
│   ├── AnalyticsController.php  # 8,302 bytes
│   └── DashboardController.php  # 7,940 bytes
└── Operator/
    ├── LeadController.php       # Operator lidlari
    ├── TaskController.php       # 10,047 bytes
    └── TodoController.php       # 10,509 bytes
```

### 6.3 Middleware

```
app/Http/Middleware/
├── SalesHeadMiddleware.php      # Sales head tekshiruvi
├── OperatorMiddleware.php       # Operator tekshiruvi
└── CheckPermission.php          # Ruxsat tekshiruvi
```

### 6.4 Migrations

```
database/migrations/
├── 0001_01_01_000005_create_businesses_table.php
├── 2026_01_09_120000_add_team_fields_to_business_user_table.php
├── 2026_01_09_120000_create_tasks_table.php
├── 2026_01_18_125516_create_pipeline_stages_table.php
├── 2026_01_01_200000_create_lead_sources_table.php
├── 2026_01_01_200001_create_sales_channels_table.php
└── ... (boshqa lead-related migrations)
```

### 6.5 Frontend

```
resources/js/pages/
├── SalesHead/
│   ├── Dashboard.vue
│   ├── Leads/
│   │   ├── Index.vue        # Kanban board
│   │   └── Show.vue         # Lead detail
│   ├── Calls/
│   ├── Deals/
│   ├── Tasks/
│   ├── Analytics/
│   ├── KPI/
│   ├── Performance/
│   ├── Team/
│   ├── Reports/
│   └── Settings/
└── Operator/
    ├── Dashboard.vue
    ├── Leads/
    ├── Tasks/
    ├── Todos/
    └── KPI/
```

### 6.6 Seeders

```
database/seeders/
└── RolesAndPermissionsSeeder.php    # Rollar va ruxsatlar
```

---

## Xulosa

BiznesPilot CRM tizimining Sotuv bo'limi asosiy funksionallarni o'z ichiga oladi:
- Lead boshqaruvi (Kanban board)
- Task boshqaruvi
- Qo'ng'iroq integratsiyasi (PBX)
- Pipeline boshqaruvi
- Jamoa boshqaruvi

Rivojlantirish uchun ustuvor yo'nalishlar:
1. 🔴 Deal model va revenue tracking
2. 🔴 SMS/WhatsApp/Telegram integratsiya
3. 🔴 Funnel va conversion hisobotlari
4. 🔴 Mobile app
5. 🔴 Duplicate detection

---

*Ushbu hujjat avtomatik tahlil asosida yaratilgan.*
