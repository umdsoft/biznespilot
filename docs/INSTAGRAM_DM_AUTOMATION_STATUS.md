# Instagram DM Automation - Joriy Holat

**Sana:** 16.01.2026
**Status:** Meta App Review kutilmoqda

---

## Tugallangan ishlar

### 1. Backend kod
- [x] `InstagramWebhookController.php` - flow automations ulangan
- [x] `InstagramChatbotService.php` - DM yuborish logikasi
- [x] Keywords array handling tuzatilgan (string va array)
- [x] Variable replacements: `{full_name}`, `{username}`
- [x] Account lookup by `instagram_id` (webhook entry'dan)
- [x] Echo messages skip qilish (`is_echo: true`)

### 2. Database
- [x] `instagram_automations` - flow_data JSON saqlash
- [x] `instagram_integrations` - access_token saqlash
- [x] Page Access Token saqlangan (User Token emas)

### 3. Meta Developer Console
- [x] App yaratilgan: `BiznesPilot` (ID: 68556232115694)
- [x] Instagram Graph API ulangan
- [x] Webhook URL sozlangan
- [x] Privacy Policy sahifasi yaratilgan
- [x] App Review formalar to'ldirilgan:
  - [x] instagram_manage_messages use case
  - [x] instagram_basic
  - [x] Допустимое использование (Acceptable use)
  - [x] Обработка данных (Data processing)
  - [x] Инструкции для специалиста (Reviewer instructions)

---

## Kutilayotgan

### Meta Business Verification
- **Kompaniya:** PRAKTIKUM ACADEMY (OOO)
- **Status:** На проверке (Under review)
- **Kutish vaqti:** ~2 ish kuni
- **Biznes Portfolio:** UMDSOFT Official

### Meta App Review
- **Permission:** `instagram_manage_messages`
- **Status:** Biznes verifikatsiyasi tugashini kutmoqda
- **Kutish vaqti:** Verifikatsiyadan keyin ~1-2 hafta

---

## Xato va yechimi

### Hozirgi xato:
```
"(#3) Application does not have the capability to make this API call."
```

### Sababi:
`instagram_manage_messages` permission hali **Advanced Access** emas. Faqat **Standard Access** (development mode) bor.

### Yechimi:
Meta App Review tasdiqlangandan keyin xato yo'qoladi.

---

## Test qilish uchun ma'lumotlar

### Instagram Account:
- **Username:** praktikum_academy
- **Instagram ID:** 17841463054464066
- **Account ID (DB):** fddc6cb7-a778-41b5-b55a-a25f0bf2f3f3

### Automation:
- **ID:** f35e34d1-6f92-4295-be9e-e93bec5cfbb3
- **Trigger:** DM Keyword ("salom", "+")
- **Action:** Send DM

### Business:
- **ID:** 41078070-0dae-410f-9c83-0c866e1f7551

### Facebook Page:
- **Name:** Praktikum Academy
- **Page ID:** 176342112237567

---

## Keyingi qadamlar (1-2 kundan keyin)

1. **Meta'dan email kutish** - biznes verifikatsiyasi tugaganda xabar keladi

2. **App Review yuborish:**
   - developers.facebook.com → Мои приложения → BiznesPilot
   - Проверка приложения → Запросы
   - "Отправить на проверку" tugmasini bosish

3. **App Review tasdiqlangandan keyin:**
   - Instagram DM automation ishlaydi
   - Test qilish: praktikum_academy'ga "salom" yuborish

---

## Muhim fayllar

| Fayl | Vazifasi |
|------|----------|
| `app/Http/Controllers/InstagramWebhookController.php` | Webhook qabul qilish |
| `app/Services/InstagramChatbotService.php` | Flow automation bajarish |
| `app/Models/InstagramAutomation.php` | Automation model |
| `app/Models/InstagramAccount.php` | Account model |
| `resources/views/privacy-policy.blade.php` | Privacy Policy sahifasi |

---

## Log tekshirish

```bash
# Laravel loglarini ko'rish
tail -f storage/logs/laravel.log | grep -E "(Instagram|DM|automation)"
```

Muvaffaqiyatli flow:
```
Flow automation triggered (keyword match)
Executing flow node (trigger_keyword_dm)
Executing flow node (action_send_dm)
DM sent successfully  # <- Bu App Review'dan keyin ko'rinadi
```

---

## Aloqa

Meta App Review bilan muammo bo'lsa:
- https://developers.facebook.com/support/
- App Review rejection sabablarini tekshirish
