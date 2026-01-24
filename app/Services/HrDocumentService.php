<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class HrDocumentService
{
    /**
     * Document types.
     */
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_OFFER_LETTER = 'offer_letter';
    public const TYPE_NDA = 'nda';
    public const TYPE_TERMINATION = 'termination';
    public const TYPE_WARNING = 'warning';
    public const TYPE_PROMOTION = 'promotion';
    public const TYPE_SALARY_CHANGE = 'salary_change';
    public const TYPE_REFERENCE = 'reference';
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_VACATION_REQUEST = 'vacation_request';
    public const TYPE_SICK_LEAVE = 'sick_leave';

    /**
     * Default templates for common HR documents.
     */
    protected array $templates = [
        self::TYPE_CONTRACT => [
            'name_uz' => 'Mehnat shartnomasi',
            'name_en' => 'Employment Contract',
            'required_fields' => ['employee_name', 'position', 'salary', 'start_date'],
        ],
        self::TYPE_OFFER_LETTER => [
            'name_uz' => 'Ish taklifi xati',
            'name_en' => 'Offer Letter',
            'required_fields' => ['candidate_name', 'position', 'salary', 'start_date'],
        ],
        self::TYPE_NDA => [
            'name_uz' => 'Maxfiylik shartnomasi',
            'name_en' => 'Non-Disclosure Agreement',
            'required_fields' => ['employee_name', 'position'],
        ],
        self::TYPE_TERMINATION => [
            'name_uz' => 'Ishdan bo\'shatish buyrug\'i',
            'name_en' => 'Termination Notice',
            'required_fields' => ['employee_name', 'position', 'last_day', 'reason'],
        ],
        self::TYPE_WARNING => [
            'name_uz' => 'Ogohlantirnoma',
            'name_en' => 'Warning Letter',
            'required_fields' => ['employee_name', 'position', 'warning_reason', 'warning_date'],
        ],
        self::TYPE_PROMOTION => [
            'name_uz' => 'Lavozimga tayinlash buyrug\'i',
            'name_en' => 'Promotion Letter',
            'required_fields' => ['employee_name', 'old_position', 'new_position', 'effective_date'],
        ],
        self::TYPE_SALARY_CHANGE => [
            'name_uz' => 'Maosh o\'zgarishi haqida xat',
            'name_en' => 'Salary Change Letter',
            'required_fields' => ['employee_name', 'position', 'old_salary', 'new_salary', 'effective_date'],
        ],
        self::TYPE_REFERENCE => [
            'name_uz' => 'Tavsifnoma',
            'name_en' => 'Reference Letter',
            'required_fields' => ['employee_name', 'position', 'employment_period'],
        ],
        self::TYPE_CERTIFICATE => [
            'name_uz' => 'Ma\'lumotnoma',
            'name_en' => 'Employment Certificate',
            'required_fields' => ['employee_name', 'position', 'employment_start'],
        ],
        self::TYPE_VACATION_REQUEST => [
            'name_uz' => 'Ta\'til arizasi',
            'name_en' => 'Vacation Request',
            'required_fields' => ['employee_name', 'vacation_start', 'vacation_end', 'vacation_days'],
        ],
        self::TYPE_SICK_LEAVE => [
            'name_uz' => 'Kasallik varaqasi',
            'name_en' => 'Sick Leave Document',
            'required_fields' => ['employee_name', 'leave_start', 'leave_end'],
        ],
    ];

    /**
     * Generate a document from template.
     */
    public function generateDocument(
        Business $business,
        string $documentType,
        array $data,
        string $format = 'html'
    ): array {
        if (!isset($this->templates[$documentType])) {
            throw new \Exception('Noma\'lum hujjat turi');
        }

        $template = $this->templates[$documentType];

        // Validate required fields
        $this->validateRequiredFields($template['required_fields'], $data);

        // Merge with business data
        $documentData = $this->prepareDocumentData($business, $data);

        // Generate content
        $content = $this->renderTemplate($documentType, $documentData);

        // Generate document number
        $documentNumber = $this->generateDocumentNumber($business, $documentType);

        $result = [
            'success' => true,
            'document_number' => $documentNumber,
            'document_type' => $documentType,
            'document_name' => $template['name_uz'],
            'content' => $content,
            'generated_at' => now()->toIso8601String(),
        ];

        if ($format === 'pdf') {
            $result['pdf_path'] = $this->generatePdf($business, $documentNumber, $content);
        }

        return $result;
    }

    /**
     * Validate required fields.
     */
    protected function validateRequiredFields(array $required, array $data): void
    {
        $missing = [];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \Exception('Majburiy maydonlar to\'ldirilmagan: ' . implode(', ', $missing));
        }
    }

    /**
     * Prepare document data with business info.
     */
    protected function prepareDocumentData(Business $business, array $data): array
    {
        return array_merge($data, [
            'business_name' => $business->name,
            'business_address' => $business->address,
            'business_phone' => $business->phone,
            'business_email' => $business->email,
            'director_name' => $business->owner?->name ?? 'Direktor',
            'current_date' => now()->format('d.m.Y'),
            'current_date_text' => $this->formatDateText(now()),
        ]);
    }

    /**
     * Render document template.
     */
    protected function renderTemplate(string $documentType, array $data): string
    {
        $templateContent = $this->getTemplateContent($documentType);

        // Replace placeholders
        foreach ($data as $key => $value) {
            $templateContent = str_replace("{{" . $key . "}}", $value ?? '', $templateContent);
        }

        return $templateContent;
    }

    /**
     * Get template content.
     */
    protected function getTemplateContent(string $documentType): string
    {
        return match ($documentType) {
            self::TYPE_CONTRACT => $this->getContractTemplate(),
            self::TYPE_OFFER_LETTER => $this->getOfferLetterTemplate(),
            self::TYPE_NDA => $this->getNdaTemplate(),
            self::TYPE_TERMINATION => $this->getTerminationTemplate(),
            self::TYPE_WARNING => $this->getWarningTemplate(),
            self::TYPE_PROMOTION => $this->getPromotionTemplate(),
            self::TYPE_SALARY_CHANGE => $this->getSalaryChangeTemplate(),
            self::TYPE_REFERENCE => $this->getReferenceTemplate(),
            self::TYPE_CERTIFICATE => $this->getCertificateTemplate(),
            self::TYPE_VACATION_REQUEST => $this->getVacationRequestTemplate(),
            self::TYPE_SICK_LEAVE => $this->getSickLeaveTemplate(),
            default => '',
        };
    }

    /**
     * Employment Contract Template.
     */
    protected function getContractTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mehnat shartnomasi</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 40px; }
        h1 { text-align: center; }
        .header { text-align: center; margin-bottom: 30px; }
        .parties { margin: 20px 0; }
        .section { margin: 15px 0; }
        .signatures { margin-top: 50px; display: flex; justify-content: space-between; }
        .signature-block { width: 45%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MEHNAT SHARTNOMASI</h1>
        <p>№ {{document_number}}</p>
        <p>{{current_date}}</p>
    </div>

    <div class="parties">
        <p><strong>{{business_name}}</strong> (keyingi o'rinlarda "Ish beruvchi") nomidan {{director_name}} bir tomondan va
        <strong>{{employee_name}}</strong> (keyingi o'rinlarda "Xodim") ikkinchi tomondan ushbu shartnomani tuzdilar.</p>
    </div>

    <div class="section">
        <h3>1. SHARTNOMA PREDMETI</h3>
        <p>1.1. Ish beruvchi Xodimni <strong>{{position}}</strong> lavozimiga qabul qiladi.</p>
        <p>1.2. Ish joyi: {{business_address}}</p>
        <p>1.3. Ishga kirish sanasi: {{start_date}}</p>
    </div>

    <div class="section">
        <h3>2. ISH HAQI VA TO'LOVLAR</h3>
        <p>2.1. Oylik ish haqi: {{salary}} so'm</p>
        <p>2.2. Ish haqi har oyning 10-sanasida to'lanadi.</p>
    </div>

    <div class="section">
        <h3>3. ISH VAQTI VA DAM OLISH</h3>
        <p>3.1. Ish vaqti: dushanbadan jumagacha, soat 09:00 dan 18:00 gacha</p>
        <p>3.2. Tushlik tanaffusi: soat 13:00 dan 14:00 gacha</p>
        <p>3.3. Yillik ta'til: 24 ish kuni</p>
    </div>

    <div class="section">
        <h3>4. TOMONLARNING MAJBURIYATLARI</h3>
        <p>4.1. Ish beruvchi:</p>
        <ul>
            <li>Xodimga mehnat sharoitlarini yaratish</li>
            <li>Ish haqini o'z vaqtida to'lash</li>
            <li>Mehnat qonunchiligiga rioya qilish</li>
        </ul>
        <p>4.2. Xodim:</p>
        <ul>
            <li>Lavozim majburiyatlarini bajarish</li>
            <li>Ichki tartib qoidalariga rioya qilish</li>
            <li>Tijorat sirini saqlash</li>
        </ul>
    </div>

    <div class="signatures">
        <div class="signature-block">
            <p><strong>ISH BERUVCHI:</strong></p>
            <p>{{business_name}}</p>
            <p>Direktor: {{director_name}}</p>
            <p>Imzo: _________________</p>
            <p>M.O.</p>
        </div>
        <div class="signature-block">
            <p><strong>XODIM:</strong></p>
            <p>{{employee_name}}</p>
            <p>Imzo: _________________</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Offer Letter Template.
     */
    protected function getOfferLetterTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ish taklifi</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 40px; }
        .header { margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .signature { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <p><strong>{{business_name}}</strong></p>
        <p>{{business_address}}</p>
        <p>Tel: {{business_phone}}</p>
        <p>{{current_date}}</p>
    </div>

    <div class="content">
        <p>Hurmatli {{candidate_name}},</p>

        <p>Sizni <strong>{{business_name}}</strong> kompaniyasida <strong>{{position}}</strong> lavozimiga taklif qilishdan mamnunmiz.</p>

        <p><strong>Lavozim tafsilotlari:</strong></p>
        <ul>
            <li>Lavozim: {{position}}</li>
            <li>Oylik maosh: {{salary}} so'm</li>
            <li>Ishga kirish sanasi: {{start_date}}</li>
            <li>Ish turi: To'liq ish kuni</li>
        </ul>

        <p>Ushbu taklif {{current_date}} sanasidan 7 kun ichida amal qiladi.</p>

        <p>Taklifimizni qabul qilsangiz, iltimos, ushbu xatning bir nusxasini imzolab qaytaring.</p>

        <p>Savollaringiz bo'lsa, biz bilan bog'laning.</p>
    </div>

    <div class="signature">
        <p>Hurmat bilan,</p>
        <p><strong>{{director_name}}</strong></p>
        <p>Direktor</p>
        <p>{{business_name}}</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Warning Letter Template.
     */
    protected function getWarningTemplate(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ogohlantirnoma</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .signatures { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>OGOHLANTIRNOMA</h2>
        <p>№ {{document_number}}</p>
        <p>{{warning_date}}</p>
    </div>

    <div class="content">
        <p><strong>Kimga:</strong> {{employee_name}}</p>
        <p><strong>Lavozim:</strong> {{position}}</p>

        <p>Ushbu xat orqali Sizga quyidagi sabab bo'yicha rasmiy ogohlantirish berilmoqda:</p>

        <p><strong>Sabab:</strong> {{warning_reason}}</p>

        <p>Ushbu holatning takrorlanishi intizomiy jazoga olib kelishi mumkin.</p>

        <p>Iltimos, ushbu ogohlantirishni qabul qilganingizni tasdiqlang.</p>
    </div>

    <div class="signatures">
        <p><strong>Direktor:</strong></p>
        <p>{{director_name}}</p>
        <p>Imzo: _________________</p>
        <br>
        <p><strong>Xodim:</strong></p>
        <p>{{employee_name}}</p>
        <p>Imzo: _________________</p>
        <p>Sana: _________________</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get other template methods (simplified for brevity).
     */
    protected function getNdaTemplate(): string
    {
        return $this->getGenericTemplate('MAXFIYLIK SHARTNOMASI', 'NDA shartnomasi');
    }

    protected function getTerminationTemplate(): string
    {
        return $this->getGenericTemplate('ISHDAN BO\'SHATISH BUYRUG\'I', 'Oxirgi ish kuni: {{last_day}}. Sabab: {{reason}}');
    }

    protected function getPromotionTemplate(): string
    {
        return $this->getGenericTemplate('LAVOZIMGA TAYINLASH BUYRUG\'I', 'Eski lavozim: {{old_position}}. Yangi lavozim: {{new_position}}. Kuchga kirish sanasi: {{effective_date}}');
    }

    protected function getSalaryChangeTemplate(): string
    {
        return $this->getGenericTemplate('MAOSH O\'ZGARISHI HAQIDA XAT', 'Eski maosh: {{old_salary}}. Yangi maosh: {{new_salary}}. Kuchga kirish sanasi: {{effective_date}}');
    }

    protected function getReferenceTemplate(): string
    {
        return $this->getGenericTemplate('TAVSIFNOMA', '{{employee_name}} {{employment_period}} davomida {{position}} lavozimida ishlagan.');
    }

    protected function getCertificateTemplate(): string
    {
        return $this->getGenericTemplate('MA\'LUMOTNOMA', '{{employee_name}} {{employment_start}} sanasidan beri {{position}} lavozimida ishlaydi.');
    }

    protected function getVacationRequestTemplate(): string
    {
        return $this->getGenericTemplate('TA\'TIL ARIZASI', 'Ta\'til davri: {{vacation_start}} dan {{vacation_end}} gacha ({{vacation_days}} kun)');
    }

    protected function getSickLeaveTemplate(): string
    {
        return $this->getGenericTemplate('KASALLIK VARAQASI', 'Kasallik davri: {{leave_start}} dan {{leave_end}} gacha');
    }

    protected function getGenericTemplate(string $title, string $content): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .content { margin: 20px 0; }
        .signatures { margin-top: 50px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{$title}</h2>
        <p>№ {{document_number}}</p>
        <p>{{current_date}}</p>
    </div>

    <div class="content">
        <p><strong>Kompaniya:</strong> {{business_name}}</p>
        <p><strong>Xodim:</strong> {{employee_name}}</p>
        <p><strong>Lavozim:</strong> {{position}}</p>
        <br>
        <p>{$content}</p>
    </div>

    <div class="signatures">
        <p><strong>Direktor:</strong> {{director_name}}</p>
        <p>Imzo: _________________</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Generate document number.
     */
    protected function generateDocumentNumber(Business $business, string $type): string
    {
        $prefix = match ($type) {
            self::TYPE_CONTRACT => 'MS',
            self::TYPE_OFFER_LETTER => 'IT',
            self::TYPE_NDA => 'NDA',
            self::TYPE_TERMINATION => 'IB',
            self::TYPE_WARNING => 'OG',
            self::TYPE_PROMOTION => 'LT',
            self::TYPE_SALARY_CHANGE => 'MO',
            self::TYPE_REFERENCE => 'TV',
            self::TYPE_CERTIFICATE => 'MA',
            self::TYPE_VACATION_REQUEST => 'TA',
            self::TYPE_SICK_LEAVE => 'KV',
            default => 'HR',
        };

        $year = now()->format('Y');
        $number = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$number}";
    }

    /**
     * Generate PDF from content.
     */
    protected function generatePdf(Business $business, string $documentNumber, string $content): string
    {
        // For now, save as HTML - PDF generation would require dompdf or similar
        $filename = "hr-documents/{$business->id}/{$documentNumber}.html";
        Storage::put($filename, $content);

        return $filename;
    }

    /**
     * Format date as text.
     */
    protected function formatDateText(Carbon $date): string
    {
        $months = [
            1 => 'yanvar', 2 => 'fevral', 3 => 'mart',
            4 => 'aprel', 5 => 'may', 6 => 'iyun',
            7 => 'iyul', 8 => 'avgust', 9 => 'sentabr',
            10 => 'oktabr', 11 => 'noyabr', 12 => 'dekabr',
        ];

        return $date->day . ' ' . $months[$date->month] . ' ' . $date->year . ' yil';
    }

    /**
     * Get available document types.
     */
    public function getDocumentTypes(): array
    {
        $types = [];
        foreach ($this->templates as $code => $template) {
            $types[$code] = [
                'code' => $code,
                'name_uz' => $template['name_uz'],
                'name_en' => $template['name_en'],
                'required_fields' => $template['required_fields'],
            ];
        }
        return $types;
    }
}
