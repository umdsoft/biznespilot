<?php

namespace App\Observers;

use App\Models\ContentIdea;
use App\Models\ContentPost;

class ContentPostObserver
{
    /**
     * ContentPost yaratilganda — g'oyalar bankiga qo'shish
     */
    public function created(ContentPost $post): void
    {
        $this->syncToIdeasBank($post);
    }

    /**
     * ContentPost yangilanganda — g'oyalar bankini yangilash
     */
    public function updated(ContentPost $post): void
    {
        // Faqat title yoki content o'zgarganda
        if ($post->wasChanged(['title', 'content', 'content_type'])) {
            $this->syncToIdeasBank($post);
        }
    }

    /**
     * ContentPost dan g'oya yaratish (0 token — AI chaqirilmaydi)
     */
    protected function syncToIdeasBank(ContentPost $post): void
    {
        if (empty($post->title) || empty($post->business_id)) {
            return;
        }

        // Kontentdan birinchi jumlani hook sifatida olish
        $hook = $this->extractFirstSentence($post->content ?? '');

        // content_type mapping: ContentPost → ContentIdea
        $ideaContentType = $this->mapContentType($post->content_type, $post->format);

        // purpose aniqlash: content_type dan
        $purpose = $this->detectPurpose($post->content_type, $post->content ?? '');

        // Dublikat tekshirish — bir xil sarlavha bo'lmasin
        $exists = ContentIdea::where('business_id', $post->business_id)
            ->where('title', $post->title)
            ->exists();

        if ($exists) {
            return;
        }

        ContentIdea::create([
            'business_id' => $post->business_id,
            'created_by_user_id' => $post->user_id,
            'title' => $post->title,
            'description' => $hook,
            'content_type' => $ideaContentType,
            'purpose' => $purpose,
            'category' => $post->content_type, // educational, promotional...
            'suggested_hashtags' => $post->hashtags,
            'is_active' => true,
        ]);
    }

    /**
     * Kontentdan birinchi jumlani olish (hook)
     */
    protected function extractFirstSentence(string $content): string
    {
        // "KONTENT:" yoki boshqa sarlavhalardan keyin birinchi real jumlani olish
        $clean = preg_replace('/^(KONTENT|SENARIY|MATN|POST)\s*:?\s*/iu', '', trim($content));
        $clean = trim($clean);

        if (empty($clean)) {
            return '';
        }

        // Birinchi jumlani topish (. ! ? bilan tugaydigan)
        if (preg_match('/^(.+?[.!?])\s/u', $clean, $match)) {
            return mb_substr($match[1], 0, 255);
        }

        // Birinchi qator
        $firstLine = strtok($clean, "\n");
        return mb_substr($firstLine, 0, 255);
    }

    /**
     * ContentPost content_type → ContentIdea content_type
     */
    protected function mapContentType(?string $contentType, ?string $format): string
    {
        // format ustunlik oladi (aniqroq)
        if ($format) {
            return match ($format) {
                'short_video', 'long_video' => 'reel',
                'carousel' => 'carousel',
                'single_image', 'text_post' => 'post',
                'story' => 'story',
                'live' => 'reel',
                'poll' => 'post',
                default => 'post',
            };
        }

        return 'post';
    }

    /**
     * Kontent turidan maqsadni aniqlash
     */
    protected function detectPurpose(?string $contentType, string $content): string
    {
        if ($contentType) {
            return match ($contentType) {
                'educational' => 'educate',
                'promotional' => 'sell',
                'inspirational' => 'inspire',
                'entertaining' => 'entertain',
                'behind_scenes' => 'engage',
                'ugc' => 'engage',
                default => 'engage',
            };
        }

        // Kontent matnidan maqsadni aniqlash (oddiy keyword analiz)
        $lower = mb_strtolower($content);
        if (preg_match('/chegirma|aksiya|sotib|xarid|narx|buyurtma/u', $lower)) return 'sell';
        if (preg_match('/bilasizmi|qanday|maslahat|yo\'l|sirlar|xato/u', $lower)) return 'educate';
        if (preg_match('/ilhom|muvaffaq|orzu|kuch|isho/u', $lower)) return 'inspire';

        return 'engage';
    }
}
