<?php

namespace App\Support;

class SitemapXmlHelper
{
    /**
     * @param  list<array{loc: string, lastmod: string}>  $entries
     */
    public static function sitemapIndex(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($entries as $entry) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . self::escape($entry['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . self::escape($entry['lastmod']) . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * @param  list<array{loc: string, lastmod: string, changefreq?: string, priority?: float|int|string}>  $urls
     */
    public static function urlSet(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . self::escape($url['loc']) . "</loc>\n";
            $xml .= '    <lastmod>' . self::escape($url['lastmod']) . "</lastmod>\n";
            if (! empty($url['changefreq'])) {
                $xml .= '    <changefreq>' . self::escape((string) $url['changefreq']) . "</changefreq>\n";
            }
            if (isset($url['priority']) && $url['priority'] !== '') {
                $xml .= '    <priority>' . self::escape((string) $url['priority']) . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
