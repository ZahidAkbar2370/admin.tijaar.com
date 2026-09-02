<?php

namespace App\Support;

class SeoTextHelper
{
    public static function defaultRobotsTxt(string $siteUrl): string
    {
        $siteUrl = rtrim($siteUrl, '/');

        return <<<TXT
User-agent: *
Allow: /
Disallow: /customer/
Disallow: /seller/
Disallow: /checkout
Disallow: /cart
Disallow: /login
Disallow: /register
Disallow: /auth/

Sitemap: {$siteUrl}/sitemap.xml
TXT;
    }

    public static function defaultLlmTxt(string $siteName, string $siteUrl, string $description = ''): string
    {
        $siteUrl = rtrim($siteUrl, '/');
        $desc = trim($description) ?: 'Tijaar is Pakistan\'s multi-seller marketplace. Shop from verified sellers with secure payments, buyer protection, and fast courier delivery or become a verified seller and reach buyers nationwide.';

        return <<<TXT
# {$siteName}

> {$desc}

## About
{$siteName} is an online shopping marketplace where buyers and sellers connect across Pakistan.

## Canonical site
{$siteUrl}

## Key pages
- Home: {$siteUrl}/
- Shop: {$siteUrl}/shop
- Blog: {$siteUrl}/blogs
- Sellers: {$siteUrl}/sellers
- Contact: {$siteUrl}/contact
- FAQs: {$siteUrl}/faqs

## Policies
- Terms: {$siteUrl}/terms
- Privacy: {$siteUrl}/privacy
- Returns: {$siteUrl}/returns-refunds
- Shipping: {$siteUrl}/shipping

## Contact
Support and business inquiries: see {$siteUrl}/contact
TXT;
    }

    public static function resolve(string $stored, string $default): string
    {
        $text = trim($stored) !== '' ? $stored : $default;

        return trim($text);
    }

    public static function applyPlaceholders(string $text, array $vars): string
    {
        $siteUrl = rtrim((string) ($vars['site_url'] ?? ''), '/');
        $replacements = [
            '{site_url}' => $siteUrl,
            '{site_name}' => (string) ($vars['site_name'] ?? ''),
            '{meta_description}' => (string) ($vars['meta_description'] ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
