<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sotuv skripti — biznes o'z operatorlariga yozadigan skript.
 *
 * Tarkibi:
 *   - 7 ta bosqich (stages JSON)
 *   - Har bosqichda: required_phrases, forbidden_phrases, tips, example
 *   - Ideal davomiylik va talk ratio
 */
class SalesScript extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'description', 'script_type',
        'stages', 'global_required_phrases', 'global_forbidden_phrases',
        'ideal_duration_min', 'ideal_duration_max',
        'ideal_talk_ratio_min', 'ideal_talk_ratio_max',
        'is_active', 'is_default', 'created_by',
    ];

    protected $casts = [
        'stages' => 'array',
        'global_required_phrases' => 'array',
        'global_forbidden_phrases' => 'array',
        'ideal_duration_min' => 'integer',
        'ideal_duration_max' => 'integer',
        'ideal_talk_ratio_min' => 'decimal:2',
        'ideal_talk_ratio_max' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public const STAGES = [
        'greeting' => 'Salomlashish',
        'discovery' => 'Ehtiyoj aniqlash',
        'presentation' => 'Taqdimot',
        'objection_handling' => 'E\'tirozlarni hal qilish',
        'closing' => 'Yopish',
        'rapport' => 'Munosabat qurish',
        'cta' => 'Keyingi qadam',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(CallAnalysis::class, 'script_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, string $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Barcha majburiy frazalarni ro'yxat qilish (global + stage'lardan)
     */
    public function getAllRequiredPhrases(): array
    {
        $phrases = $this->global_required_phrases ?? [];
        foreach (($this->stages ?? []) as $stage) {
            $phrases = array_merge($phrases, $stage['required'] ?? []);
        }
        return array_values(array_unique(array_filter($phrases)));
    }

    /**
     * Barcha taqiqlangan frazalarni ro'yxat qilish
     */
    public function getAllForbiddenPhrases(): array
    {
        $phrases = $this->global_forbidden_phrases ?? [];
        foreach (($this->stages ?? []) as $stage) {
            $phrases = array_merge($phrases, $stage['forbidden'] ?? []);
        }
        return array_values(array_unique(array_filter($phrases)));
    }

    /**
     * Standart O'zbek tili skript shablonini qaytarish
     */
    public static function getDefaultTemplate(): array
    {
        return [
            'greeting' => [
                'required' => ['assalomu alaykum', 'biznes nomi', 'ismim'],
                'forbidden' => ['hello', 'alyo'],
                'tips' => ['Ismini tanishtir', 'Biznes nomini ayt', 'Mijoz ismini so\'ra'],
                'example' => 'Assalomu alaykum, men [Ismingiz], [Biznes nomi] dan. Qanday yordam bera olaman?',
            ],
            'discovery' => [
                'required' => ['nima kerak', 'maqsadingiz', 'qanday natija'],
                'forbidden' => ['keling narx aytay'],
                'tips' => ['3-5 ta ochiq savol bering', 'Mijoz muammosini chuqur aniqlashtiring'],
                'example' => 'Ayting-chi, sizga aynan qanday yechim kerak?',
            ],
            'presentation' => [
                'required' => ['foyda', 'natija', 'misol'],
                'forbidden' => [],
                'tips' => ['Mijoz muammosiga yechim bog\'lang', 'Aniq raqam/misol keltiring'],
                'example' => 'Bizning yechim sizga [foyda] beradi, masalan [misol]...',
            ],
            'objection_handling' => [
                'required' => ['tushunaman', 'ammo', 'misol'],
                'forbidden' => ['yo\'q', 'bo\'lmaydi', 'ishonmayman'],
                'tips' => ['E\'tirozni tinglang', 'Misol bilan tushuntiring'],
                'example' => 'Sizning xavotiringizni tushunaman. Ammo mana bu holda...',
            ],
            'closing' => [
                'required' => ['kelishdik', 'buyurtma', 'boshlaymiz'],
                'forbidden' => ['o\'ylab ko\'ring'],
                'tips' => ['Aniq harakatga chaqiring', 'Shubhaga o\'rin qoldirmang'],
                'example' => 'Kelishdikmi? Bugunoq boshlab beraman.',
            ],
            'rapport' => [
                'required' => [],
                'forbidden' => ['shoshiling', 'tez bo\'ling'],
                'tips' => ['Mijoz ismini ishlating', 'Ijobiy til ishlating'],
                'example' => '[Ism] aka, bu siz uchun juda mos yechim.',
            ],
            'cta' => [
                'required' => ['ertaga', 'bog\'lanamiz', 'yuboraman'],
                'forbidden' => [],
                'tips' => ['Aniq sana/vaqt kelishib oling', 'Keyingi qadamni aniq ayting'],
                'example' => 'Ertaga soat 14:00 da qayta bog\'lanaman.',
            ],
        ];
    }
}
