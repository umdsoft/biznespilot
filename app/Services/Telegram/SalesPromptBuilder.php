<?php

namespace App\Services\Telegram;

use App\Models\Business;
use App\Models\DreamBuyer;
use App\Models\Offer;
use App\Models\SalesScript;
use App\Models\Store\TelegramStore;
use App\Models\TelegramBusinessConnection;
use App\Models\TelegramFunnel;
use App\Models\TelegramFunnelStep;

/**
 * Builds a rich, context-aware system prompt for the Telegram Business Sales Bot.
 *
 * Composes prompt from multiple reusable sources (DRY):
 *   - Business (name, industry, description)
 *   - DreamBuyer (ICP, pain points, language style)
 *   - SalesScript stages (greeting → discovery → presentation → objection → close)
 *   - Offer (value proposition, bonuses, guarantees)
 *   - Knowledge base (products, FAQ, contact)
 *   - Connection persona (owner tone)
 */
class SalesPromptBuilder
{
    public function build(TelegramBusinessConnection $connection): string
    {
        $business = $connection->telegramBot->business;

        $parts = [];
        $parts[] = $this->personaSection($connection, $business);
        $parts[] = $this->businessContextSection($business);

        if ($dreamBuyer = $this->getDreamBuyer($business)) {
            $parts[] = $this->dreamBuyerSection($dreamBuyer);
        }

        if ($offer = $connection->primaryOffer) {
            $parts[] = $this->offerSection($offer);
        }

        if ($script = $connection->salesScript) {
            $parts[] = $this->salesScriptSection($script);
        }

        // Funnel = business's own step-by-step sales flow
        if ($funnel = $connection->funnel) {
            $parts[] = $this->funnelSection($funnel);
        }

        if ($kb = $connection->knowledge_base) {
            $parts[] = $this->knowledgeBaseSection($kb);
        }

        $parts[] = $this->catalogSection($business);
        $parts[] = $this->rulesSection($connection);
        $parts[] = $this->leadCaptureSection();

        return implode("\n\n", array_filter($parts));
    }

    // =============================================
    // SECTIONS
    // =============================================

    protected function personaSection(TelegramBusinessConnection $c, $business): string
    {
        $ownerName = $c->owner_first_name ?? 'egasi';
        $businessName = $business->name ?? 'biznes';

        $persona = $c->persona_prompt
            ?: "Sen {$ownerName} — {$businessName} egasi. "
            ."O'zbek tilida tabiiy va iliq gaplashasan — aynan oddiy biznesmen kabi. "
            .'Qisqa, aniq va samimiy javoblar berasan. Rasmiy emas, inson kabi.';

        return "# PERSONA\n{$persona}\n\n"
            ."MUHIM: Ingliz yoki ruscha so'zlarni tarjima qilmasdan to'g'ridan-to'g'ri ishlatmay, "
            ."haqiqiy O'zbek gapida natural ishla. Masalan:\n"
            ."- YOMON: \"pitsa bordi\" (grammatika xato)\n"
            ."- YAXSHI: \"Ha, pitsa bor\"\n"
            ."- YOMON: \"chizmali pitsalar\" (tarjima xato)\n"
            ."- YAXSHI: \"turli xil pitsalar\"\n"
            ."- YOMON: \"Qaysi pitsa yoqadi sizga?\" (kalka)\n"
            ."- YAXSHI: \"Qanday pitsa buyurtma qilmoqchisiz?\"";
    }

    protected function businessContextSection($business): string
    {
        $lines = [];
        $lines[] = 'Biznes nomi: '.($business->name ?? '-');
        if ($business->industry) {
            $lines[] = 'Soha: '.$business->industry;
        }
        if ($business->description) {
            $lines[] = "Ta'rif: ".$business->description;
        }
        if ($business->target_audience) {
            $lines[] = 'Maqsadli auditoriya: '.$business->target_audience;
        }
        if ($business->phone) {
            $lines[] = 'Telefon: '.$business->phone;
        }
        if ($business->address) {
            $lines[] = 'Manzil: '.$business->address;
        }

        return "# BIZNES MA'LUMOTLARI\n".implode("\n", $lines);
    }

    protected function dreamBuyerSection(DreamBuyer $db): string
    {
        $lines = [];
        if ($db->pain_points) {
            $lines[] = 'Mijoz og\'riqlari: '.$this->asList($db->pain_points);
        }
        if ($db->goals) {
            $lines[] = 'Mijoz maqsadlari: '.$this->asList($db->goals);
        }
        if ($db->objections) {
            $lines[] = "Kutilayotgan e'tirozlar: ".$this->asList($db->objections);
        }
        if ($db->buying_triggers) {
            $lines[] = 'Sotib olish triggerlari: '.$this->asList($db->buying_triggers);
        }
        if ($langStyle = $db->language_style ?? null) {
            $lines[] = "Muloqot uslubi: {$langStyle}";
        }

        if (empty($lines)) {
            return '';
        }

        return "# IDEAL MIJOZ (Dream Buyer)\n".implode("\n", $lines);
    }

    protected function offerSection(Offer $offer): string
    {
        $lines = [];
        $lines[] = 'Taklif nomi: '.$offer->name;
        if ($offer->value_proposition) {
            $lines[] = 'Qiymat: '.$offer->value_proposition;
        }
        if ($offer->pricing) {
            $lines[] = 'Narx: '.$offer->pricing;
        }
        if ($offer->guarantees) {
            $lines[] = 'Kafolatlar: '.$this->asList($offer->guarantees);
        }
        if ($offer->bonuses) {
            $lines[] = 'Bonuslar: '.$this->asList($offer->bonuses);
        }
        if ($offer->scarcity) {
            $lines[] = 'Cheklov: '.$offer->scarcity;
        }
        if ($offer->urgency) {
            $lines[] = 'Shoshilinchlik: '.$offer->urgency;
        }

        return "# ASOSIY TAKLIF (Main Offer)\n".implode("\n", $lines);
    }

    protected function salesScriptSection(SalesScript $script): string
    {
        $out = "# SOTUV SKRIPTI: {$script->name}\n";
        $out .= "Suhbatda quyidagi bosqichlarga rioya qil:\n\n";

        foreach ($script->stages ?? [] as $stageKey => $stage) {
            $label = SalesScript::STAGES[$stageKey] ?? $stageKey;
            $out .= "## {$label}\n";

            if (! empty($stage['example'])) {
                $out .= "Misol: {$stage['example']}\n";
            }
            if (! empty($stage['required'])) {
                $out .= 'Kerakli iboralar: '.implode(' | ', (array) $stage['required'])."\n";
            }
            if (! empty($stage['tips'])) {
                $out .= 'Maslahatlar: '.implode(' | ', (array) $stage['tips'])."\n";
            }
            $out .= "\n";
        }

        if (! empty($script->global_forbidden_phrases)) {
            $out .= "TAQIQLANGAN SO'ZLAR (hech qachon ishlatma): "
                .implode(', ', (array) $script->global_forbidden_phrases)."\n";
        }

        return trim($out);
    }

    protected function knowledgeBaseSection(array $kb): string
    {
        $out = "# BILIMLAR BAZASI\n";

        if (! empty($kb['products'])) {
            $out .= "\n## Mahsulotlar/Xizmatlar:\n";
            foreach ($kb['products'] as $p) {
                $name = $p['name'] ?? '-';
                $price = $p['price'] ?? null;
                $desc = $p['description'] ?? '';
                $out .= "- **{$name}**";
                if ($price) {
                    $out .= " — {$price}";
                }
                if ($desc) {
                    $out .= " ({$desc})";
                }
                $out .= "\n";
            }
        }

        if (! empty($kb['faq'])) {
            $out .= "\n## Tez-tez so'raladigan savollar:\n";
            foreach ($kb['faq'] as $f) {
                $out .= "- S: {$f['q']}\n  J: {$f['a']}\n";
            }
        }

        if (! empty($kb['payment_methods'])) {
            $out .= "\nTo'lov usullari: ".$this->asList($kb['payment_methods'])."\n";
        }

        if (! empty($kb['delivery'])) {
            $out .= "\nYetkazib berish: ".(is_array($kb['delivery']) ? json_encode($kb['delivery']) : $kb['delivery'])."\n";
        }

        return trim($out);
    }

    protected function catalogSection($business): string
    {
        $store = TelegramStore::where('business_id', $business->id)->first();
        if (! $store) {
            return '';
        }

        try {
            $products = $store->products()->limit(20)->get(['name', 'price', 'description']);
            if ($products->isEmpty()) {
                return '';
            }

            $lines = ["# TELEGRAM DO'KON KATALOGI"];
            foreach ($products as $p) {
                $lines[] = "- {$p->name} — {$p->price}";
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function rulesSection(TelegramBusinessConnection $c): string
    {
        return "# MUHIM QOIDALAR\n\n"
            ."## Til qoidalari (ENG MUHIM!)\n"
            ."- Mijoz qaysi tilda yozsa, SHU TILDA javob ber (o'zbek, rus, ingliz)\n"
            ."- O'zbek tilida yozganda — TO'G'RI va TABIIY grammatika ishlat\n"
            ."- \"Pitsa bordi\" emas, \"pitsa bor\"; \"chizmali\" emas, \"turli xil\"\n"
            ."- Mahalliy uslubda: \"aka\", \"opa\", \"hurmatli mijoz\" kabi murojaatlar\n"
            ."- Tarjima qilgandek gap tuzmasin, tabiiy O'zbek gapi bo'lsin\n\n"
            ."## Xatti-harakat\n"
            ."- Hech qachon \"Men AI/botman\" dema. Sen haqiqiy biznes egasisan.\n"
            ."- Javoblar QISQA (1-3 jumla), tabiiy, inson kabi\n"
            ."- Emoji'lardan 1-2 tadan ishlat, ortiq emas\n"
            ."- Narxni bilmasang \"Hozir tekshirib aytaman\" de, yolg'on aytma\n"
            ."- Mijoz qiziqsa — KONTAKT ma'lumotlarini (ism, telefon) so'ra\n"
            ."- Murakkab savollarda \"Mutaxassisimiz bog'lanadi\" deb javob ber\n"
            ."- Agar mijoz 'operator', 'inson', 'menejer' so'rasa — [HANDOFF] marker qo'sh\n\n"
            ."## Funnel rioya qilish\n"
            ."- Agar yuqorida SOTUV FUNNEL bosqichlari ko'rsatilgan bo'lsa — SHU TARTIB bilan suhbat ol\n"
            ."- Funnel bosqichini so'zma-so'z ko'chirma, lekin mohiyati va savollari aynan shu bo'lsin\n"
            ."- Funnel savolini sazmakor qilib so'raganda, mijoz javobiga asoslanib keyingi bosqichga o't";
    }

    protected function funnelSection(TelegramFunnel $funnel): string
    {
        $steps = $funnel->steps()->orderBy('step_order')->limit(15)->get();
        if ($steps->isEmpty()) {
            return '';
        }

        $out = "# SOTUV FUNNEL: {$funnel->name}\n";
        if ($funnel->description) {
            $out .= "Maqsad: {$funnel->description}\n";
        }
        $out .= "\nSuhbatni shu funnel bosqichlari asosida olib bor:\n\n";

        foreach ($steps as $i => $step) {
            $num = $i + 1;
            $out .= "## Bosqich {$num}: ".($step->name ?: 'Step '.$step->id)."\n";

            // Content can be JSON with {text, media, ...}
            $content = $step->content;
            if (is_array($content)) {
                if (! empty($content['text'])) {
                    $out .= "Xabar: {$content['text']}\n";
                }
                if (! empty($content['caption'])) {
                    $out .= "Caption: {$content['caption']}\n";
                }
            } elseif (is_string($content)) {
                $out .= "Xabar: {$content}\n";
            }

            // Keyboard options — turn into free-form answer variants
            if (! empty($step->keyboard)) {
                $buttons = [];
                foreach ((array) $step->keyboard as $row) {
                    foreach ((array) $row as $btn) {
                        if (! empty($btn['text'])) {
                            $buttons[] = $btn['text'];
                        }
                    }
                }
                if ($buttons) {
                    $out .= 'Variantlar: '.implode(' | ', $buttons)."\n";
                }
            }

            $out .= "\n";
        }

        return trim($out);
    }

    protected function leadCaptureSection(): string
    {
        return "# LEAD CAPTURE\n"
            ."Agar mijoz SOTIB OLMOQCHI yoki KONKRET QIZIQISH ko'rsatsa:\n"
            ."1. Uning ism va telefon raqamini so'ra\n"
            ."2. Nima olmoqchi ekanligini aniqlashtir\n"
            ."3. Javobingda OXIRIDA quyidagi markerni qo'sh (mijozga ko'rinmaydi, lekin tizim oladi):\n"
            .'   [LEAD:name=MIJOZ_ISMI;phone=TELEFON;product=MAHSULOT;intent=HOT/WARM/COLD;note=QISQA_NOTA]'."\n"
            ."Misol: [LEAD:name=Dilshod;phone=+998901234567;product=Pitsa Pepperoni;intent=HOT;note=Bugun buyurtma qilmoqchi]\n"
            ."Agar mijoz faqat ma'lumot so'rayotgan bo'lsa, marker qo'shma.";
    }

    // =============================================
    // HELPERS
    // =============================================

    protected function getDreamBuyer($business): ?DreamBuyer
    {
        return DreamBuyer::where('business_id', $business->id)
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();
    }

    protected function asList($value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            return implode(', ', array_map(
                fn ($v) => is_array($v) ? ($v['text'] ?? json_encode($v)) : (string) $v,
                $value
            ));
        }

        return (string) $value;
    }
}
