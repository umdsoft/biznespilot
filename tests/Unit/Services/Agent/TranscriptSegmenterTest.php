<?php

namespace Tests\Unit\Services\Agent;

use App\Services\Agent\CallCenter\Transcription\TranscriptSegmenter;
use PHPUnit\Framework\TestCase;

/**
 * Suhbat bosqichlarga ajratish testlari.
 */
class TranscriptSegmenterTest extends TestCase
{
    private TranscriptSegmenter $segmenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->segmenter = new TranscriptSegmenter();
    }

    /** @test */
    public function it_detects_opening_stage(): void
    {
        $transcript = "Assalomu alaykum, eshitaman sizni. Xizmatlarimiz haqida so'ramoqchi edim.";
        $segments = $this->segmenter->segment($transcript);

        $this->assertTrue($segments['opening']['detected']);
        $this->assertGreaterThan(0, $segments['opening']['sentence_count']);
    }

    /** @test */
    public function it_detects_presentation_stage(): void
    {
        $transcript = "Salom. Bizning kursimiz 3 oylik. Natija kafolatlanadi. Mahsulot sifatli.";
        $segments = $this->segmenter->segment($transcript);

        $this->assertTrue($segments['presentation']['detected']);
    }

    /** @test */
    public function it_detects_objection_handling(): void
    {
        $transcript = "Salom. Kurs yaxshi. Lekin juda qimmat ekan. O'ylab ko'raman.";
        $segments = $this->segmenter->segment($transcript);

        $this->assertTrue($segments['objection_handling']['detected']);
    }

    /** @test */
    public function it_detects_closing_stage(): void
    {
        $transcript = "Salom. Kurs yaxshi. Ro'yxatga yozilaman. To'lov qanday?";
        $segments = $this->segmenter->segment($transcript);

        $this->assertTrue($segments['closing']['detected']);
    }

    /** @test */
    public function it_returns_all_seven_stages(): void
    {
        $segments = $this->segmenter->segment("Salom. Kurs haqida.");

        $this->assertCount(7, $segments);
        $this->assertArrayHasKey('opening', $segments);
        $this->assertArrayHasKey('needs_discovery', $segments);
        $this->assertArrayHasKey('qualification', $segments);
        $this->assertArrayHasKey('presentation', $segments);
        $this->assertArrayHasKey('objection_handling', $segments);
        $this->assertArrayHasKey('closing', $segments);
        $this->assertArrayHasKey('next_steps', $segments);
    }

    /** @test */
    public function it_handles_empty_transcript(): void
    {
        $segments = $this->segmenter->segment("");

        $this->assertCount(7, $segments);
        foreach ($segments as $seg) {
            $this->assertFalse($seg['detected']);
        }
    }
}
