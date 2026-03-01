<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = $this->getUrls();

        $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $content .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml"' . "\n";
        $content .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $url) {
            $content .= $this->buildUrlEntry($url);
        }

        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    protected function getUrls(): array
    {
        $baseUrl = 'https://biznespilot.uz';
        $today = now()->format('Y-m-d');

        return [
            // Homepage — UZ (default)
            [
                'loc' => $baseUrl . '/',
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '1.0',
                'alternates' => [
                    ['hreflang' => 'uz', 'href' => $baseUrl . '/'],
                    ['hreflang' => 'ru', 'href' => $baseUrl . '/lang/ru'],
                    ['hreflang' => 'x-default', 'href' => $baseUrl . '/'],
                ],
                'image' => [
                    'loc' => $baseUrl . '/images/og-image.jpg',
                    'title' => 'BiznesPilot - Biznes Boshqaruv Platformasi',
                    'caption' => 'O\'zbekistondagi #1 biznes boshqaruv platformasi',
                ],
            ],
            // Homepage — RU
            [
                'loc' => $baseUrl . '/lang/ru',
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.9',
                'alternates' => [
                    ['hreflang' => 'uz', 'href' => $baseUrl . '/'],
                    ['hreflang' => 'ru', 'href' => $baseUrl . '/lang/ru'],
                    ['hreflang' => 'x-default', 'href' => $baseUrl . '/'],
                ],
            ],
            // Pricing
            [
                'loc' => $baseUrl . '/pricing',
                'lastmod' => $today,
                'changefreq' => 'weekly',
                'priority' => '0.9',
            ],
            // About
            [
                'loc' => $baseUrl . '/about',
                'lastmod' => $today,
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ],
            // Privacy Policy
            [
                'loc' => $baseUrl . '/privacy-policy',
                'lastmod' => $today,
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            // Terms of Service
            [
                'loc' => $baseUrl . '/terms',
                'lastmod' => $today,
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
        ];
    }

    protected function buildUrlEntry(array $url): string
    {
        $entry = "  <url>\n";
        $entry .= "    <loc>{$url['loc']}</loc>\n";

        if (isset($url['alternates'])) {
            foreach ($url['alternates'] as $alt) {
                $entry .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$alt['hreflang']}\" href=\"{$alt['href']}\"/>\n";
            }
        }

        $entry .= "    <lastmod>{$url['lastmod']}</lastmod>\n";
        $entry .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
        $entry .= "    <priority>{$url['priority']}</priority>\n";

        if (isset($url['image'])) {
            $entry .= "    <image:image>\n";
            $entry .= "      <image:loc>{$url['image']['loc']}</image:loc>\n";
            $entry .= "      <image:title>{$url['image']['title']}</image:title>\n";
            $entry .= "      <image:caption>{$url['image']['caption']}</image:caption>\n";
            $entry .= "    </image:image>\n";
        }

        $entry .= "  </url>\n";

        return $entry;
    }
}
