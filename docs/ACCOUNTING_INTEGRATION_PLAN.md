# BiznesPilot - Buxgalteriya Integratsiya Rejasi

> **Yaratilgan:** 2026-01-19
> **Maqsad:** Soliq hisobotlari va bank integratsiyasi uchun to'liq arxitektura

---

## Mundarija

1. [Umumiy Ko'rinish](#1-umumiy-korinish)
2. [Soliq Hujjatlari Integratsiyasi](#2-soliq-hujjatlari-integratsiyasi)
3. [Bank Integratsiyalari](#3-bank-integratsiyalari)
4. [To'lov Tizimlari](#4-tolov-tizimlari)
5. [Dual Bank Sync Tizimi](#5-dual-bank-sync-tizimi)
6. [Bank Vyipiska Parser](#6-bank-vyipiska-parser)
7. [Avtomatik Matching Algoritmi](#7-avtomatik-matching-algoritmi)
8. [Qarzdorlik Kuzatuvi](#8-qarzdorlik-kuzatuvi)
9. [Ma'lumotlar Modeli](#9-malumotlar-modeli)
10. [Kategoriyalash Tizimi](#10-kategoriyalash-tizimi)
11. [Hisobotlar](#11-hisobotlar)
12. [Konfiguratsiya](#12-konfiguratsiya)
13. [Amalga Oshirish Bosqichlari](#13-amalga-oshirish-bosqichlari)

---

## 1. Umumiy Ko'rinish

### Arxitektura

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         BIZNESPILOT BUXGALTERIYA                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  SOLIQ HUJJATLARI           BANK HISOBI              MIJOZ TO'LOVI     │
│  ────────────────           ───────────              ─────────────      │
│  • Faktura.uz               • API (avtomatik)        • Click            │
│  • Didox.uz                 • Excel import (qo'lda)  • Payme            │
│                                                      • Uzum             │
│                                                                         │
│                         ┌─────────────────┐                             │
│                         │  YAGONA BAZASI  │                             │
│                         └────────┬────────┘                             │
│                                  │                                      │
│              ┌───────────────────┼───────────────────┐                  │
│              │                   │                   │                  │
│              ▼                   ▼                   ▼                  │
│       ┌───────────┐       ┌───────────┐       ┌───────────┐            │
│       │ Matching  │       │ Qarzdorlik│       │ Hisobotlar│            │
│       │ Algoritmi │       │ Kuzatuvi  │       │ Generator │            │
│       └───────────┘       └───────────┘       └───────────┘            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Asosiy Maqsadlar

| # | Maqsad | Tavsif |
|---|--------|--------|
| 1 | Avtomatlashtirish | Qo'lda kiritishni minimumga tushirish |
| 2 | Shaffoflik | Real-time moliyaviy holat |
| 3 | Integratsiya | Soliq va bank tizimlari bilan bog'lanish |
| 4 | Hisobotlar | Bir tugmada soliq hisobotlari |

---

## 2. Soliq Hujjatlari Integratsiyasi

### 2.1 Faktura.uz API

**Hujjatlar:** https://api.faktura.uz/help/

#### Autentifikatsiya

```
POST https://account.faktura.uz/token

Parameters:
- grant_type: password
- username: {username}
- password: {password}
- client_id: {client_id}
- client_secret: {client_secret}

Response:
- access_token (valid: 604,799 seconds / ~7 kun)
- refresh_token
- token_type: Bearer
```

#### Asosiy Endpointlar

| Endpoint | Method | Tavsif |
|----------|--------|--------|
| `/Api/Document/GetDocuments` | GET | Hujjatlarni olish (filter bilan) |
| `/Api/Document/ImportDocumentRegister` | POST | Ko'p hujjatni import qilish |
| `/Api/Document/GetDocumentTypes` | GET | 29+ hujjat turlari ro'yxati |
| `/Api/Document/GetDocumentStatuses` | GET | Hujjat statuslari |
| `/Api/CheckCompanyExist/{inn}` | GET | Kompaniya INN tekshirish |
| `/Api/Document/SignDocument` | POST | Hujjatni imzolash |
| `/Api/VerifySignature` | POST | PKCS7 imzoni tekshirish |
| `/Api/DownloadArchive/{uniqueId}` | GET | ZIP arxiv yuklab olish |
| `/Api/Company/GetCompanyBranchs/{inn}` | GET | Filiallar ro'yxati |

#### Qo'llab-quvvatlanadigan Hujjat Turlari

- Hisob-fakturalar (schyot-faktura)
- Dalolatnomalar (akt)
- Akt-faktura kombinatsiyasi
- Oldindan to'lov fakturalari
- Material hisobotlari
- Ishonchnomalar
- To'lov topshiriqnomalari

### 2.2 Didox.uz API

**Hujjatlar:** https://api-docs.didox.uz/ru/home

> ⚠️ Hujjatlarga kirish cheklangan. Shartnoma tuzilgandan keyin API credentials olinadi.

#### Kutilayotgan Imkoniyatlar

- OAuth2 autentifikatsiya
- Hisob-faktura yaratish/qabul qilish
- ERI bilan imzolash
- Soliq qo'mitasiga yuborish

### 2.3 ERI (Elektron Raqamli Imzo)

Soliq hujjatlari uchun ERI majburiy. Provayderlar:
- E-IMZO (asosiy)
- Boshqa sertifikatlangan provayderlar

---

## 3. Bank Integratsiyalari

### 3.1 API Mavjud Banklar

| Bank | API Portal | Imkoniyatlar | Status |
|------|------------|--------------|--------|
| **Aloqa Bank** | [aloqabusiness.uz](https://aloqabusiness.uz/ru/products/payments/Online-payment/) | To'lov API, **Vyipiska API**, Ekvayring | ✅ Tayyor |
| **Kapitalbank** | [kapitalbank.uz](https://www.kapitalbank.uz/en/corporate/services/kapital-api/) | Kapital API, Open Data | ✅ Tayyor |
| **Anor Bank** | [anorbank.uz](https://www.anorbank.uz/en/business/anor-api/) | Anor API (biznes uchun) | ✅ Tayyor |
| **Tenge Bank** | [api.tengebank.uz](https://api.tengebank.uz/) | API endpoint mavjud | ✅ Tayyor |
| **NBU** | Shartnoma kerak | BankID, iBank (cheklangan) | ⚠️ Cheklangan |

### 3.2 API Yo'q Banklar (Excel Import)

| Bank | Import Formati |
|------|----------------|
| Ipoteka Bank | Excel/CSV |
| Hamkorbank | Excel/CSV |
| InfinBank | Excel/CSV |
| Xalq Banki | Excel/CSV |
| Asakabank | Excel/CSV |

### 3.3 Aloqa Bank API (Batafsil)

#### Vyipiska API (Statement)

```
Imkoniyatlar:
- Real-time hisob harakatlari
- Tranzaksiya sanasi va vaqti
- Kontragent nomi va INN
- Summa (kirim/chiqim)
- To'lov maqsadi
```

#### To'lov API

```
Imkoniyatlar:
- Bank rekvizitlari orqali avtomatik to'lov
- Kerakli ma'lumotlar: Hisob raqam, MFO, to'lov maqsadi
- B2B to'lovlarni avtomatlashtirish
```

---

## 4. To'lov Tizimlari

> ⚠️ **Muhim:** To'lov tizimlari (Click, Payme, Uzum) - bu mijozdan pul olish uchun, bank hisobi uchun emas!

### 4.1 Farq

```
BANK API                          TO'LOV TIZIMI
─────────                         ──────────────
Biznes hisobi                     Mijozdan pul olish
├── Vyipiska (statement)          ├── Karta orqali to'lov
├── To'lov yuborish               ├── QR kod
├── Balans tekshirish             └── Online checkout
└── Kontragent ma'lumotlari
```

### 4.2 Mavjud To'lov Tizimlari

| Tizim | API | Hujjatlar |
|-------|-----|-----------|
| **Click** | ✅ | [github.com/click-llc](https://github.com/click-llc) |
| **Payme** | ✅ | [business.payme.uz](https://business.payme.uz/en) |
| **Uzum** | ✅ | [developer.uzumbank.uz](https://developer.uzumbank.uz/en/merchant/) |
| **Atmos** | ✅ | PayTechUZ orqali |

### 4.3 PayTechUZ - Yagona SDK

**Hujjatlar:** [docs.pay-tech.uz](https://docs.pay-tech.uz/)

```bash
pip install paytechuz
```

Bir SDK orqali Click, Payme, Atmos bilan ishlash mumkin.

---

## 5. Dual Bank Sync Tizimi

### 5.1 Arxitektura

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         BIZNESPILOT BANK SYNC                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│              ┌──────────────────┴──────────────────┐                   │
│              │                                     │                    │
│              ▼                                     ▼                    │
│    ┌─────────────────────┐              ┌─────────────────────┐        │
│    │   🔄 AUTO SYNC      │              │   📄 MANUAL IMPORT  │        │
│    │   (API orqali)      │              │   (Excel/CSV)       │        │
│    ├─────────────────────┤              ├─────────────────────┤        │
│    │ • Aloqa Bank        │              │ • Ipoteka Bank      │        │
│    │ • Kapitalbank       │              │ • Hamkorbank        │        │
│    │ • Anor Bank         │              │ • InfinBank         │        │
│    │ • Tenge Bank        │              │ • Xalq Banki        │        │
│    │                     │              │ • Asakabank         │        │
│    │ Har 1 soatda yoki   │              │ • Boshqa banklar    │        │
│    │ real-time webhook   │              │                     │        │
│    └─────────────────────┘              └─────────────────────┘        │
│              │                                     │                    │
│              └──────────────────┬──────────────────┘                   │
│                                 │                                       │
│                                 ▼                                       │
│                   ┌─────────────────────────┐                          │
│                   │  YAGONA TRANZAKSIYA     │                          │
│                   │  BAZASI                 │                          │
│                   └─────────────────────────┘                          │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Foydalanuvchi Interfeysi

```
┌─────────────────────────────────────────────────────────────────────────┐
│  🏦 BANK HISOBLARIM                                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  │ 🟢 Aloqa Bank        │ API   │ Auto-sync │ 5 min oldin    │        │
│  │ 🟢 Kapitalbank       │ API   │ Auto-sync │ 12 min oldin   │        │
│  │ 🟡 Ipoteka Bank      │ Excel │ Manual    │ 2 kun oldin    │        │
│  │ 🟡 Hamkorbank        │ Excel │ Manual    │ 1 kun oldin    │        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Bank Vyipiska Parser

### 6.1 Muammo

Har bir bank o'z formatida Excel eksport qiladi:

```
KAPITALBANK:                          HAMKORBANK:
┌────────┬────────┬────────┐          ┌──────┬───────┬───────┬─────────┐
│ Sana   │ Debet  │ Kredit │          │ Дата │ Приход│ Расход│ ИНН     │
└────────┴────────┴────────┘          └──────┴───────┴───────┴─────────┘
```

### 6.2 Yechim: Bank Profile System

Har bir bank uchun ustun mapping:

```php
// Bank Profile konfiguratsiya namunasi
$bankProfiles = [
    'kapitalbank' => [
        'date_column' => 'A', // yoki 'Sana'
        'date_format' => 'DD.MM.YYYY',
        'debit_column' => 'C', // yoki 'Debet'
        'credit_column' => 'D', // yoki 'Kredit'
        'counterparty_column' => 'E',
        'inn_column' => 'F',
        'purpose_column' => 'G',
        'header_row' => 5,
        'amount_format' => '1 000 000,00',
    ],
    'hamkorbank' => [
        'date_column' => 'Дата',
        'date_format' => 'DD.MM.YYYY',
        'income_column' => 'Приход',
        'expense_column' => 'Расход',
        'inn_column' => 'ИНН',
        'purpose_column' => 'Назначение платежа',
        'header_row' => 3,
    ],
    // ... boshqa banklar
];
```

### 6.3 Parser Algoritmi

```
1. BANK ANIQLASH
   ├── Fayl nomi bo'yicha
   ├── Header ustunlari bo'yicha (avtomatik)
   └── Foydalanuvchi tanlovi bo'yicha

2. MA'LUMOTLARNI O'QISH
   ├── Header qatorini topish
   ├── Ustunlarni mapping qilish
   └── Qatorlarni parse qilish

3. HAR BIR QATOR UCHUN:
   ├── Sanani parse qil → 2026-01-15
   ├── Summani parse qil → 5000000.00
   │   └── "5 000 000,00" → 5000000.00
   ├── INN ajrat → 123456789
   ├── Kontragent nomi → "ALFA LLC"
   └── To'lov maqsadi → "Shartnoma 15, faktura 001"

4. STRUKTURAGA AYLANTIRISH
   └── Yagona Transaction modeli
```

---

## 7. Avtomatik Matching Algoritmi

### 7.1 Matching Jarayoni

```
BANK TRANZAKSIYA:
┌─────────────────────────────────────────────────────────────┐
│ 15.01.2026 │ -10,000,000 │ Alfa LLC │ INN: 123456789       │
│ Maqsad: "Oplata za tovar po dogovoru 15, schet-faktura 001"│
└─────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────┐
│  QIDIRUV BOSQICHLARI:                                       │
│                                                             │
│  1️⃣ INN bo'yicha kontragent top                            │
│     └── 123456789 → Alfa LLC ✅                             │
│                                                             │
│  2️⃣ Maqsaddan faktura raqamini ajrat                       │
│     └── "schet-faktura 001" → Faktura #001                  │
│     └── "dogovoru 15" → Shartnoma #15                       │
│                                                             │
│  3️⃣ Fakturani bazadan qidir                                │
│     └── Kontragent + Faktura raqam + Summa                  │
│                                                             │
│  4️⃣ NATIJA: 95% ishonchlilik bilan bog'landi              │
└─────────────────────────────────────────────────────────────┘
```

### 7.2 Regex Patternlar

```php
// Faktura raqamini ajratish
$patterns = [
    '/faktura\s*[#№]?\s*(\d+)/iu',
    '/schet[- ]?faktura\s*[#№]?\s*(\d+)/iu',
    '/sf\s*[#№]?\s*(\d+)/iu',
    '/счет[- ]?фактура\s*[#№]?\s*(\d+)/iu',
];

// Shartnoma raqamini ajratish
$contractPatterns = [
    '/dogovor[ua]?\s*[#№]?\s*(\d+)/iu',
    '/shartnoma\s*[#№]?\s*(\d+)/iu',
    '/договор[а]?\s*[#№]?\s*(\d+)/iu',
];
```

### 7.3 Matching Holatlari

| Status | Tavsif | Harakat |
|--------|--------|---------|
| ✅ **AUTO_MATCHED** | INN + Summa + Faktura = 100% | Avtomatik bog'lanadi |
| 🟡 **SUGGESTED** | INN + Summa mos, faktura yo'q | Taklif qilinadi |
| 🟠 **MULTIPLE** | Bir nechta mos faktura | Tanlash kerak |
| ❌ **NOT_FOUND** | Mos kelmadi | Qo'lda kiritish |

---

## 8. Qarzdorlik Kuzatuvi

### 8.1 Debitor vs Kreditor

```
KREDITORLIK (Biz qarzdormiz)        DEBITORLIK (Bizga qarzdorlar)
─────────────────────────────        ────────────────────────────
Faktura KELDI → To'lamaguncha        Faktura KETDI → To'lamaguncha
QARZ bo'lib turadi                   QARZ bo'lib turadi
```

### 8.2 Faktura Holati Kuzatuvi

```
FAKTURA #001:
├── Jami summa:     50,000,000
├── To'langan:      35,000,000
│   ├── 10.01: +20,000,000 (Bank)
│   └── 15.01: +15,000,000 (Bank)
├── Qoldiq:         15,000,000
└── Status:         QISMAN_TOLANGAN (70%)
```

### 8.3 Qarzdorlik Hisoboti

```
KREDITORLIK:
├── Jami:           51,500,000
├── Muddati o'tgan: 12,500,000 🔴
├── 1-7 kun:        25,000,000 🟠
├── 8-15 kun:        6,000,000 🟡
└── 15+ kun:         8,000,000 🟢

DEBITORLIK:
├── Jami:           98,000,000
├── Muddati o'tgan: 48,000,000 🔴
└── ...
```

---

## 9. Ma'lumotlar Modeli

### 9.1 Kontragentlar

```php
counterparties:
├── id
├── name              // Rasmiy nomi
├── short_name        // Qisqa nomi
├── inn               // STIR/INN
├── oked              // Faoliyat turi
├── bank_account      // Hisob raqam
├── bank_mfo          // MFO
├── bank_name         // Bank nomi
├── legal_address     // Yuridik manzil
├── actual_address    // Faktik manzil
├── director_name     // Rahbar
├── accountant_name   // Bosh buxgalter
├── phone
├── email
├── credit_limit      // Qarz chegarasi
├── payment_terms     // To'lov muddati (kun)
└── timestamps
```

### 9.2 Hisob-fakturalar

```php
invoices:
├── id
├── number            // Faktura raqami
├── date              // Sana
├── counterparty_id   // Kontragent
├── contract_id       // Shartnoma
├── type              // kirim | chiqim
├── subtotal          // QQSsiz summa
├── vat_amount        // QQS
├── total_amount      // Jami
├── paid_amount       // To'langan (computed)
├── remaining_amount  // Qoldiq (computed)
├── due_date          // To'lov muddati
├── status            // pending | partial | paid | overdue
├── faktura_uz_id     // Tashqi ID
├── didox_id          // Tashqi ID
├── signed_at         // ERI bilan imzolangan
└── timestamps
```

### 9.3 Bank Tranzaksiyalar

```php
bank_transactions:
├── id
├── bank_account_id   // Qaysi hisob
├── date              // Operatsiya sanasi
├── amount            // Summa
├── type              // income | expense
├── counterparty_id   // Kontragent
├── counterparty_inn  // INN (import vaqtida)
├── purpose           // To'lov maqsadi
├── category_id       // Kategoriya
├── source            // api | import
├── external_id       // Bank statement ID
├── raw_data          // Original JSON/row
├── match_status      // auto | suggested | manual | unmatched
├── match_confidence  // 0-100%
└── timestamps
```

### 9.4 To'lovlar (Faktura bog'lanishi)

```php
invoice_payments:
├── id
├── invoice_id
├── bank_transaction_id
├── amount
├── payment_date
└── timestamps
```

### 9.5 Bank Profillari

```php
bank_profiles:
├── id
├── bank_name
├── bank_code         // kapitalbank, hamkorbank, etc.
├── column_mapping    // JSON: ustun mapping
├── date_format
├── amount_format
├── header_row
├── encoding
└── timestamps
```

---

## 10. Kategoriyalash Tizimi

### 10.1 Xarajat Kategoriyalari

```
OPERATSION                    SOLIQ VA MAJBURIY
├── Tovar sotib olish         ├── QQS
├── Xizmatlar                 ├── Foyda solig'i
├── Arenda/Ijara              ├── Mol-mulk solig'i
├── Kommunal to'lovlar        ├── INPS (pensiya)
└── Aloqa xizmatlari          └── Boshqa soliqlar

ISH HAQI                      MOLIYAVIY
├── Ish haqi                  ├── Kredit to'lovi
├── Bonus/Mukofot             ├── Foiz to'lovi
└── Xizmat safari             └── Valyuta ayirboshlash

INVESTITSIYA                  BOSHQA
├── Uskunalar                 └── Boshqa xarajatlar
├── Transport
└── Ta'mirlash
```

### 10.2 Avtomatik Aniqlash Qoidalari

```php
$categoryRules = [
    // To'lov maqsadidan
    'arenda|ijara' => 'rent',
    'kommunal|gaz|suv|elektr' => 'utilities',
    'ish haqi|oylik|zp|zarplat' => 'salary',
    'qqs|soliq|budget|nalog' => 'tax',
    'kredit|foiz|procent' => 'loan',
    'tovar|mahsulot|produkt' => 'goods',

    // Kontragent INN bo'yicha
    'soliq_inspeksiya_inn' => 'tax',
    'hududgaz_inn' => 'utilities',
];
```

---

## 11. Hisobotlar

### 11.1 Kunlik Hisobot

```
├── Kassa qoldig'i
├── Bank qoldig'i
├── Kecha kirim/chiqim
├── Bugungi kutilayotgan to'lovlar
├── Muddati o'tgan qarzdorliklar
└── Soliq muddatlari (yaqinlashayotgan)
```

### 11.2 Haftalik Hisobot

```
├── Debitorlik holati
├── Kreditorlik holati
├── Cashflow tahlili
├── Top 10 xarajat
└── Fakturalar statusi
```

### 11.3 Oylik Soliq Hisobotlari

```
├── QQS hisobi (kirim - chiqim)
├── QQS deklaratsiya (avtomatik shakllangan)
├── Fakturalar reestri
├── Xarid daftari
└── Sotish daftari
```

### 11.4 Choraklik/Yillik

```
├── Balans (forma 1)
├── Foyda/Zarar (forma 2)
├── Pul oqimi (forma 4)
└── Statistik hisobotlar
```

---

## 12. Konfiguratsiya

### 12.1 .env Sozlamalari

```env
# ===== SOLIQ HUJJATLARI =====
FAKTURA_BASE_URL=https://api.faktura.uz
FAKTURA_AUTH_URL=https://account.faktura.uz/token
FAKTURA_CLIENT_ID=
FAKTURA_CLIENT_SECRET=
FAKTURA_USERNAME=
FAKTURA_PASSWORD=

DIDOX_BASE_URL=https://api.didox.uz
DIDOX_CLIENT_ID=
DIDOX_CLIENT_SECRET=
DIDOX_USERNAME=
DIDOX_PASSWORD=

# ===== BANKLAR (API) =====
# Aloqa Bank
ALOQA_BASE_URL=https://api.aloqabusiness.uz
ALOQA_CLIENT_ID=
ALOQA_CLIENT_SECRET=

# Kapitalbank
KAPITAL_BASE_URL=https://api.kapitalbank.uz
KAPITAL_API_KEY=

# Anor Bank
ANOR_BASE_URL=https://api.anorbank.uz
ANOR_CLIENT_ID=
ANOR_CLIENT_SECRET=

# Tenge Bank
TENGE_BASE_URL=https://api.tengebank.uz
TENGE_CLIENT_ID=
TENGE_CLIENT_SECRET=

# ===== TO'LOV TIZIMLARI =====
# Click
CLICK_MERCHANT_ID=
CLICK_SERVICE_ID=
CLICK_SECRET_KEY=

# Payme
PAYME_MERCHANT_ID=
PAYME_SECRET_KEY=

# Atmos
ATMOS_STORE_ID=
ATMOS_SECRET_KEY=

# ===== BANK SYNC SOZLAMALARI =====
BANK_SYNC_INTERVAL=3600  # sekundlarda (1 soat)
BANK_IMPORT_REMINDER_DAYS=2  # Ogohlantirish
```

---

## 13. Amalga Oshirish Bosqichlari

### Faza 1: Asos (1-2 oy)

- [ ] Ma'lumotlar modeli yaratish (migrations)
- [ ] Kontragentlar CRUD
- [ ] Fakturalar CRUD
- [ ] Bank hisoblar CRUD
- [ ] Bank tranzaksiyalar CRUD

### Faza 2: Import Tizimi (2-3 hafta)

- [ ] Bank profile system
- [ ] Excel/CSV parser
- [ ] Import UI
- [ ] Avtomatik bank aniqlash

### Faza 3: Matching Algoritmi (2-3 hafta)

- [ ] INN bo'yicha kontragent matching
- [ ] Faktura raqami ajratish (regex)
- [ ] Summa bo'yicha matching
- [ ] Confidence score hisoblash
- [ ] Manual matching UI

### Faza 4: Qarzdorlik Kuzatuvi (1-2 hafta)

- [ ] Faktura to'lov holati
- [ ] Debitorlik hisoboti
- [ ] Kreditorlik hisoboti
- [ ] Ogohlantirish tizimi

### Faza 5: Bank API Integratsiya (3-4 hafta)

- [ ] Aloqa Bank API
- [ ] Kapitalbank API
- [ ] Anor Bank API
- [ ] Auto-sync scheduler

### Faza 6: Soliq Integratsiya (3-4 hafta)

- [ ] Faktura.uz API
- [ ] Didox.uz API
- [ ] ERI integratsiya
- [ ] Avtomatik hisobotlar

### Faza 7: To'lov Tizimlari (2 hafta)

- [ ] Click integratsiya
- [ ] Payme integratsiya
- [ ] PayTechUZ SDK

---

## Manbalar

### Rasmiy Hujjatlar

| Tizim | URL |
|-------|-----|
| Faktura.uz | https://api.faktura.uz/help/ |
| Didox.uz | https://api-docs.didox.uz/ru/home |
| Aloqa Bank | https://aloqabusiness.uz/ru/products/payments/Online-payment/ |
| Kapitalbank | https://www.kapitalbank.uz/en/corporate/services/kapital-api/ |
| Anor Bank | https://www.anorbank.uz/en/business/anor-api/ |
| Tenge Bank | https://api.tengebank.uz/ |
| PayTechUZ | https://docs.pay-tech.uz/ |
| Click | https://github.com/click-llc |
| Payme | https://business.payme.uz/en |

### PDF Hujjatlar

| Hujjat | Yo'l |
|--------|------|
| Aloqa Bank API | https://aloqabusiness.uz/upload/iblock/eed/.../tekh_dokumenttsiya_Oplata_po_Bankovskim_rekvizitam.pdf |

---

## Changelog

| Sana | O'zgarish |
|------|-----------|
| 2026-01-19 | Dastlabki versiya yaratildi |

---

> **Eslatma:** Bu hujjat loyiha rivojlanishi bilan yangilanib boradi.
