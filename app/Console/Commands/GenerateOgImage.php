<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateOgImage extends Command
{
    protected $signature = 'seo:generate-og-image';
    protected $description = 'Generate Open Graph image for social media sharing';

    public function handle(): int
    {
        if (!extension_loaded('gd')) {
            $this->error('GD extension is not installed.');
            return self::FAILURE;
        }

        $width = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);

        // Colors
        $bgStart = imagecolorallocate($img, 37, 99, 235);  // blue-600
        $bgEnd = imagecolorallocate($img, 79, 70, 229);     // indigo-600
        $white = imagecolorallocate($img, 255, 255, 255);
        $lightBlue = imagecolorallocate($img, 191, 219, 254); // blue-200
        $darkOverlay = imagecolorallocate($img, 30, 58, 138); // dark blue

        // Gradient background
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int)(37 + ($ratio * (79 - 37)));
            $g = (int)(99 + ($ratio * (70 - 99)));
            $b = (int)(235 + ($ratio * (229 - 235)));
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $color);
        }

        // Decorative circles
        imagefilledellipse($img, 100, 100, 300, 300, imagecolorallocatealpha($img, 255, 255, 255, 115));
        imagefilledellipse($img, 1100, 530, 250, 250, imagecolorallocatealpha($img, 255, 255, 255, 115));
        imagefilledellipse($img, 900, 80, 180, 180, imagecolorallocatealpha($img, 255, 255, 255, 120));

        // Bottom gradient bar
        for ($y = $height - 8; $y < $height; $y++) {
            $barColor = imagecolorallocate($img, 245, 158, 11); // amber-500
            imageline($img, 0, $y, $width, $y, $barColor);
        }

        // Text — use built-in font (5 is largest built-in)
        $font5 = 5;
        $font4 = 4;
        $font3 = 3;

        // Logo icon area (bolt)
        imagefilledellipse($img, 600, 180, 100, 100, imagecolorallocatealpha($img, 255, 255, 255, 90));
        // Lightning bolt shape
        $bolt = [585, 150, 610, 150, 600, 175, 620, 175, 590, 210, 600, 185, 580, 185];
        imagefilledpolygon($img, $bolt, $white);

        // "BiznesPilot AI" title
        $title = 'BiznesPilot AI';
        $titleWidth = imagefontwidth($font5) * strlen($title);
        // Scale up title using multiple passes for bold effect
        $x = ($width - $titleWidth * 3) / 2;
        for ($scale = 0; $scale < 3; $scale++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    imagestring($img, $font5, $x + $scale * $titleWidth / 3 * 0 + $dx, 250 + $dy, $title, $white);
                }
            }
        }

        // Actually, let's use a simpler approach with imagestring centered
        $titleLen = strlen($title);
        $charW5 = imagefontwidth($font5);
        $titleX = ($width - $titleLen * $charW5) / 2;
        // Write title larger by repeating with offsets (bold effect)
        for ($ox = -1; $ox <= 1; $ox++) {
            for ($oy = -1; $oy <= 1; $oy++) {
                imagestring($img, $font5, $titleX + $ox, 255 + $oy, $title, $white);
            }
        }

        // Tagline UZ
        $taglineUz = "O'zbekistondagi #1 biznes boshqaruv platformasi";
        $tagUzX = ($width - strlen($taglineUz) * imagefontwidth($font4)) / 2;
        imagestring($img, $font4, $tagUzX, 300, $taglineUz, $lightBlue);

        // Tagline RU
        $taglineRu = "Platforma upravleniya biznesom #1 v Uzbekistane";
        $tagRuX = ($width - strlen($taglineRu) * imagefontwidth($font3)) / 2;
        imagestring($img, $font3, $tagRuX, 330, $taglineRu, $lightBlue);

        // Feature boxes
        $features = ['Marketing', 'Sotuv (CRM)', 'Moliya', 'HR', 'AI 24/7', 'Telegram Bot'];
        $boxW = 160;
        $boxH = 45;
        $startX = ($width - (count($features) * ($boxW + 15) - 15)) / 2;
        $boxY = 390;

        foreach ($features as $i => $feature) {
            $bx = $startX + $i * ($boxW + 15);
            // Rounded rectangle (approximate with filled rect + small ellipses)
            $boxBg = imagecolorallocatealpha($img, 255, 255, 255, 100);
            imagefilledrectangle($img, $bx, $boxY, $bx + $boxW, $boxY + $boxH, $boxBg);
            // Text centered in box
            $fw = imagefontwidth($font3) * strlen($feature);
            $fx = $bx + ($boxW - $fw) / 2;
            $fy = $boxY + ($boxH - imagefontheight($font3)) / 2;
            imagestring($img, $font3, $fx, $fy, $feature, $white);
        }

        // URL at bottom
        $url = 'biznespilot.uz';
        $urlX = ($width - strlen($url) * imagefontwidth($font4)) / 2;
        imagestring($img, $font4, $urlX, 510, $url, $lightBlue);

        // Save as JPG (better compression for social media)
        $outputPath = public_path('images/og-image.jpg');

        if (!is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        imagejpeg($img, $outputPath, 90);
        imagedestroy($img);

        // Also create PNG version
        $img2 = imagecreatefromjpeg($outputPath);
        imagepng($img2, public_path('images/og-image.png'), 6);
        imagedestroy($img2);

        $this->info('OG images generated:');
        $this->info("  - {$outputPath}");
        $this->info('  - ' . public_path('images/og-image.png'));

        return self::SUCCESS;
    }
}
