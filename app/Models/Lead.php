<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    /**
     * Yo'qotilgan lid sabablari
     */
    public const LOST_REASONS = [
        'price' => 'Narx qimmat',
        'competitor' => 'Raqobatchini tanladi',
        'no_budget' => 'Byudjet yo\'q',
        'no_need' => 'Ehtiyoj yo\'q',
        'no_response' => 'Javob bermadi',
        'wrong_contact' => 'Noto\'g\'ri kontakt',
        'low_quality' => 'Sifatsiz lid',
        'timing' => 'Vaqt mos kelmadi',
        'other' => 'Boshqa sabab',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    /**
     * Viloyatlar ro'yxati
     */
    public const REGIONS = [
        'toshkent_shahar' => 'Toshkent shahri',
        'toshkent_viloyati' => 'Toshkent viloyati',
        'andijon' => 'Andijon viloyati',
        'buxoro' => 'Buxoro viloyati',
        'fargona' => 'Farg\'ona viloyati',
        'jizzax' => 'Jizzax viloyati',
        'xorazm' => 'Xorazm viloyati',
        'namangan' => 'Namangan viloyati',
        'navoiy' => 'Navoiy viloyati',
        'qashqadaryo' => 'Qashqadaryo viloyati',
        'samarqand' => 'Samarqand viloyati',
        'sirdaryo' => 'Sirdaryo viloyati',
        'surxondaryo' => 'Surxondaryo viloyati',
        'qoraqalpogiston' => 'Qoraqalpog\'iston Respublikasi',
    ];

    /**
     * Tumanlar ro'yxati viloyatlar bo'yicha
     */
    public const DISTRICTS = [
        'toshkent_shahar' => [
            'bektemir' => 'Bektemir tumani',
            'chilonzor' => 'Chilonzor tumani',
            'yashnobod' => 'Yashnobod tumani',
            'mirobod' => 'Mirobod tumani',
            'mirzo_ulugbek' => 'Mirzo Ulug\'bek tumani',
            'sergeli' => 'Sergeli tumani',
            'shayxontohur' => 'Shayxontohur tumani',
            'olmazor' => 'Olmazor tumani',
            'uchtepa' => 'Uchtepa tumani',
            'yakkasaroy' => 'Yakkasaroy tumani',
            'yunusobod' => 'Yunusobod tumani',
            'yangihayot' => 'Yangihayot tumani',
        ],
        'toshkent_viloyati' => [
            'angren' => 'Angren shahri',
            'olmaliq' => 'Olmaliq shahri',
            'chirchiq' => 'Chirchiq shahri',
            'nurafshon' => 'Nurafshon shahri',
            'bekobod' => 'Bekobod tumani',
            'bostonliq' => 'Bo\'stonliq tumani',
            'chinoz' => 'Chinoz tumani',
            'qibray' => 'Qibray tumani',
            'oqqorgon' => 'Oqqo\'rg\'on tumani',
            'parkent' => 'Parkent tumani',
            'piskent' => 'Piskent tumani',
            'quyi_chirchiq' => 'Quyi Chirchiq tumani',
            'orta_chirchiq' => 'O\'rta Chirchiq tumani',
            'toshkent' => 'Toshkent tumani',
            'zangiota' => 'Zangiota tumani',
            'yuqori_chirchiq' => 'Yuqori Chirchiq tumani',
        ],
        'andijon' => [
            'andijon_shahri' => 'Andijon shahri',
            'xonobod' => 'Xonobod shahri',
            'andijon' => 'Andijon tumani',
            'asaka' => 'Asaka tumani',
            'baliqchi' => 'Baliqchi tumani',
            'boz' => 'Bo\'z tumani',
            'buloqboshi' => 'Buloqboshi tumani',
            'izboskan' => 'Izboskan tumani',
            'jalaquduq' => 'Jalaquduq tumani',
            'marhamat' => 'Marhamat tumani',
            'oltinkol' => 'Oltinko\'l tumani',
            'paxtaobod' => 'Paxtaobod tumani',
            'qorgontepa' => 'Qo\'rg\'ontepa tumani',
            'shahrixon' => 'Shahrixon tumani',
            'ulugnar' => 'Ulug\'nor tumani',
            'xojaobod' => 'Xo\'jaobod tumani',
        ],
        'buxoro' => [
            'buxoro_shahri' => 'Buxoro shahri',
            'kogon' => 'Kogon shahri',
            'buxoro' => 'Buxoro tumani',
            'gijduvon' => 'G\'ijduvon tumani',
            'jondor' => 'Jondor tumani',
            'kogon_tumani' => 'Kogon tumani',
            'olot' => 'Olot tumani',
            'peshku' => 'Peshku tumani',
            'qorakol' => 'Qorako\'l tumani',
            'qorovulbozor' => 'Qorovulbozor tumani',
            'romitan' => 'Romitan tumani',
            'shofirkon' => 'Shofirkon tumani',
            'vobkent' => 'Vobkent tumani',
        ],
        'fargona' => [
            'fargona_shahri' => 'Farg\'ona shahri',
            'qoqon' => 'Qo\'qon shahri',
            'marg\'ilon' => 'Marg\'ilon shahri',
            'quvasoy' => 'Quvasoy shahri',
            'beshariq' => 'Beshariq tumani',
            'bogdod' => 'Bog\'dod tumani',
            'buvayda' => 'Buvayda tumani',
            'dangara' => 'Dang\'ara tumani',
            'fargona' => 'Farg\'ona tumani',
            'furqat' => 'Furqat tumani',
            'oltiariq' => 'Oltiariq tumani',
            'qoshtegan' => 'Qo\'shtepa tumani',
            'quva' => 'Quva tumani',
            'rishton' => 'Rishton tumani',
            'sox' => 'So\'x tumani',
            'toshloq' => 'Toshloq tumani',
            'uchkoprik' => 'Uchko\'prik tumani',
            'uzbekiston' => 'O\'zbekiston tumani',
            'yozyovon' => 'Yozyovon tumani',
        ],
        'jizzax' => [
            'jizzax_shahri' => 'Jizzax shahri',
            'arnasoy' => 'Arnasoy tumani',
            'baxmal' => 'Baxmal tumani',
            'dostlik' => 'Do\'stlik tumani',
            'forish' => 'Forish tumani',
            'gallaorol' => 'Gallaorol tumani',
            'sharof_rashidov' => 'Sharof Rashidov tumani',
            'mirzachol' => 'Mirzacho\'l tumani',
            'paxtakor' => 'Paxtakor tumani',
            'yangiobod' => 'Yangiobod tumani',
            'zomin' => 'Zomin tumani',
            'zarbdor' => 'Zarbdor tumani',
        ],
        'xorazm' => [
            'urganch_shahri' => 'Urganch shahri',
            'xiva' => 'Xiva shahri',
            'bogot' => 'Bog\'ot tumani',
            'gurlan' => 'Gurlan tumani',
            'xonqa' => 'Xonqa tumani',
            'xiva_tumani' => 'Xiva tumani',
            'xazarasp' => 'Xazarasp tumani',
            'qoshkopir' => 'Qo\'shko\'pir tumani',
            'shovot' => 'Shovot tumani',
            'urganch' => 'Urganch tumani',
            'yangiariq' => 'Yangiariq tumani',
            'yangibozor' => 'Yangibozor tumani',
            'tuproqqala' => 'Tuproqqal\'a tumani',
        ],
        'namangan' => [
            'namangan_shahri' => 'Namangan shahri',
            'chortoq' => 'Chortoq tumani',
            'chust' => 'Chust tumani',
            'kosonsoy' => 'Kosonsoy tumani',
            'mingbuloq' => 'Mingbuloq tumani',
            'namangan' => 'Namangan tumani',
            'norin' => 'Norin tumani',
            'pop' => 'Pop tumani',
            'toraqorgon' => 'To\'raqo\'rg\'on tumani',
            'uchqorgon' => 'Uchqo\'rg\'on tumani',
            'uychi' => 'Uychi tumani',
            'yangiqorgon' => 'Yangiqo\'rg\'on tumani',
        ],
        'navoiy' => [
            'navoiy_shahri' => 'Navoiy shahri',
            'zarafshon' => 'Zarafshon shahri',
            'karmana' => 'Karmana tumani',
            'konimex' => 'Konimex tumani',
            'navbahor' => 'Navbahor tumani',
            'nurota' => 'Nurota tumani',
            'qiziltepa' => 'Qiziltepa tumani',
            'tomdi' => 'Tomdi tumani',
            'uchquduq' => 'Uchquduq tumani',
            'xatirchi' => 'Xatirchi tumani',
        ],
        'qashqadaryo' => [
            'qarshi_shahri' => 'Qarshi shahri',
            'shahrisabz' => 'Shahrisabz shahri',
            'chiroqchi' => 'Chiroqchi tumani',
            'dehqonobod' => 'Dehqonobod tumani',
            'guzor' => 'G\'uzor tumani',
            'kasbi' => 'Kasbi tumani',
            'kitob' => 'Kitob tumani',
            'koson' => 'Koson tumani',
            'mirishkor' => 'Mirishkor tumani',
            'muborak' => 'Muborak tumani',
            'nishon' => 'Nishon tumani',
            'qamashi' => 'Qamashi tumani',
            'qarshi' => 'Qarshi tumani',
            'shahrisabz_tumani' => 'Shahrisabz tumani',
            'yakkabog' => 'Yakkabog\' tumani',
        ],
        'samarqand' => [
            'samarqand_shahri' => 'Samarqand shahri',
            'kattaqorgon' => 'Kattaqo\'rg\'on shahri',
            'bulungur' => 'Bulung\'ur tumani',
            'ishtixon' => 'Ishtixon tumani',
            'jomboy' => 'Jomboy tumani',
            'kattaqorgon_tumani' => 'Kattaqo\'rg\'on tumani',
            'qoshrabot' => 'Qo\'shrabot tumani',
            'narpay' => 'Narpay tumani',
            'nurobod' => 'Nurobod tumani',
            'oqdaryo' => 'Oqdaryo tumani',
            'pastdargom' => 'Pastdarg\'om tumani',
            'paxtachi' => 'Paxtachi tumani',
            'payariq' => 'Payariq tumani',
            'samarqand' => 'Samarqand tumani',
            'toyloq' => 'Toyloq tumani',
            'urgut' => 'Urgut tumani',
        ],
        'sirdaryo' => [
            'guliston' => 'Guliston shahri',
            'yangiyer' => 'Yangiyer shahri',
            'shirin' => 'Shirin shahri',
            'boyovut' => 'Boyovut tumani',
            'guliston_tumani' => 'Guliston tumani',
            'mirzaobod' => 'Mirzaobod tumani',
            'oqoltin' => 'Oqoltin tumani',
            'sardoba' => 'Sardoba tumani',
            'sayxunobod' => 'Sayxunobod tumani',
            'sirdaryo' => 'Sirdaryo tumani',
            'xovos' => 'Xovos tumani',
        ],
        'surxondaryo' => [
            'termiz' => 'Termiz shahri',
            'angor' => 'Angor tumani',
            'bandixon' => 'Bandixon tumani',
            'boysun' => 'Boysun tumani',
            'denov' => 'Denov tumani',
            'jarqorgon' => 'Jarqo\'rg\'on tumani',
            'qiziriq' => 'Qiziriq tumani',
            'qumqorgon' => 'Qumqo\'rg\'on tumani',
            'muzrabot' => 'Muzrabot tumani',
            'oltinsoy' => 'Oltinsoy tumani',
            'sariosiyo' => 'Sariosiyo tumani',
            'sherobod' => 'Sherobod tumani',
            'shorchi' => 'Sho\'rchi tumani',
            'termiz_tumani' => 'Termiz tumani',
            'uzun' => 'Uzun tumani',
        ],
        'qoraqalpogiston' => [
            'nukus' => 'Nukus shahri',
            'amudaryo' => 'Amudaryo tumani',
            'beruniy' => 'Beruniy tumani',
            'chimboy' => 'Chimboy tumani',
            'ellikqala' => 'Ellikqal\'a tumani',
            'kegeyli' => 'Kegeyli tumani',
            'moynoq' => 'Mo\'ynoq tumani',
            'nukus_tumani' => 'Nukus tumani',
            'qongirot' => 'Qo\'ng\'irot tumani',
            'qoraozak' => 'Qorao\'zak tumani',
            'shumanay' => 'Shumanay tumani',
            'taxtakopir' => 'Taxtako\'pir tumani',
            'tortkol' => 'To\'rtko\'l tumani',
            'xojayli' => 'Xo\'jayli tumani',
        ],
    ];

    protected $fillable = [
        'uuid',
        'business_id',
        'source_id',
        'assigned_to',
        'name',
        'email',
        'phone',
        'phone2',
        'company',
        'birth_date',
        'region',
        'district',
        'address',
        'gender',
        'status',
        'lost_reason',
        'lost_reason_details',
        'score',
        'estimated_value',
        'data',
        'notes',
        'last_contacted_at',
        'converted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'estimated_value' => 'decimal:2',
        'data' => 'array',
        'birth_date' => 'date',
        'last_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    /**
     * Get the lead source for the lead.
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    /**
     * Get the user assigned to the lead.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the customer converted from this lead.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the form submissions for this lead.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LeadFormSubmission::class);
    }

    /**
     * Get the tasks for this lead.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the activities for this lead.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    /**
     * Get all phone calls for this lead.
     */
    public function calls(): HasMany
    {
        return $this->hasMany(CallLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get call statistics for this lead.
     */
    public function getCallStats(): array
    {
        // Use direct query to avoid relationship ordering issues with aggregates
        $stats = \App\Models\CallLog::query()
            ->where('lead_id', $this->id)
            ->where('business_id', $this->business_id)
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(CASE WHEN direction = "outbound" THEN 1 ELSE 0 END) as outbound_calls,
                SUM(CASE WHEN direction = "inbound" THEN 1 ELSE 0 END) as inbound_calls,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as answered_calls,
                SUM(CASE WHEN status IN ("missed", "no_answer") THEN 1 ELSE 0 END) as missed_calls,
                SUM(COALESCE(duration, 0)) as total_duration,
                AVG(CASE WHEN duration > 0 THEN duration ELSE NULL END) as avg_duration
            ')
            ->first();

        $totalCalls = (int) ($stats->total_calls ?? 0);
        $answeredCalls = (int) ($stats->answered_calls ?? 0);
        $totalDuration = (int) ($stats->total_duration ?? 0);

        return [
            'total_calls' => $totalCalls,
            'outbound_calls' => (int) ($stats->outbound_calls ?? 0),
            'inbound_calls' => (int) ($stats->inbound_calls ?? 0),
            'answered_calls' => $answeredCalls,
            'missed_calls' => (int) ($stats->missed_calls ?? 0),
            'total_duration' => $totalDuration,
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            'avg_duration' => round($stats->avg_duration ?? 0),
            'answer_rate' => $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0,
        ];
    }

    /**
     * Format duration in seconds to human readable format.
     */
    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return '0:'.str_pad($seconds, 2, '0', STR_PAD_LEFT);
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes < 60) {
            return $minutes.':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours.':'.str_pad($mins, 2, '0', STR_PAD_LEFT).':'.str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get last call for this lead.
     */
    public function getLastCall(): ?CallLog
    {
        return $this->calls()->first();
    }

    /**
     * Get formatted total call duration.
     */
    public function getFormattedCallDuration(): string
    {
        $totalSeconds = $this->calls()->sum('duration') ?? 0;

        if ($totalSeconds < 60) {
            return $totalSeconds.' sek';
        }

        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        if ($minutes < 60) {
            return $minutes.' min '.$seconds.' sek';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return $hours.' soat '.$mins.' min';
    }

    /**
     * Scope: Filter by assigned operator.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: Unassigned leads.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }
}
