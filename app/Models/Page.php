<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'banner_title', 'banner_subtitle', 'sections', 'meta_title', 'meta_description', 'meta_keywords', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sections' => 'array',
    ];

    /** Privacy/Terms-style pages: { last_updated, sections[{title, content}], footer_* }. */
    public static function contentBlockSlugs(): array
    {
        return ['terms', 'privacy', 'cookie-policy', 'help', 'returns-refunds', 'shipping'];
    }

    /** About/Contact/How-it-works: custom JSON keys (mission, stats, contact_cards, etc.). */
    public static function structuredSlugs(): array
    {
        return ['about', 'contact', 'how-it-works'];
    }

    public static function sectionBasedSlugs(): array
    {
        return array_merge(self::contentBlockSlugs(), self::structuredSlugs());
    }

    public function isContentBlockPage(): bool
    {
        return in_array($this->slug, self::contentBlockSlugs(), true);
    }

    public function isStructuredPage(): bool
    {
        return in_array($this->slug, self::structuredSlugs(), true);
    }

    public function isSectionBased(): bool
    {
        return $this->isContentBlockPage() || $this->isStructuredPage();
    }

    /**
     * Raw sections JSON as an associative array (About, Contact, How-it-works, etc.).
     */
    public function decodedSections(): array
    {
        $raw = $this->sections;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * Sections payload for admin forms and public API.
     */
    public function sectionsPayload(): array
    {
        if ($this->isStructuredPage()) {
            return $this->decodedSections();
        }
        if ($this->isContentBlockPage()) {
            return $this->normalizedContentBlockSections();
        }

        return [];
    }

    /**
     * Normalize Privacy/Terms-style sections for admin forms and public API.
     */
    public function normalizedSections(): array
    {
        return $this->normalizedContentBlockSections();
    }

    /**
     * @return array{last_updated: string, sections: array<int, array{title: string, content: string}>, footer_contact_text: string, footer_copyright: string}
     */
    protected function normalizedContentBlockSections(): array
    {
        $raw = $this->decodedSections();

        $list = $raw['sections'] ?? null;
        if ($list === null && isset($raw[0]) && is_array($raw[0])) {
            $list = $raw;
        }
        if (! is_array($list)) {
            $list = [];
        }

        $normalizedList = [];
        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = trim((string) ($item['title'] ?? ''));
            $content = (string) ($item['content'] ?? $item['description'] ?? $item['body'] ?? '');
            if ($title === '' && trim(strip_tags($content)) === '') {
                continue;
            }
            $normalizedList[] = [
                'title' => $title,
                'content' => $content,
            ];
        }

        if ($normalizedList === [] && filled($this->content)) {
            $normalizedList[] = [
                'title' => $this->banner_title ?: $this->title ?: 'Overview',
                'content' => (string) $this->content,
            ];
        }

        return [
            'last_updated' => (string) ($raw['last_updated'] ?? ''),
            'sections' => array_values($normalizedList),
            'footer_contact_text' => (string) ($raw['footer_contact_text'] ?? ''),
            'footer_copyright' => (string) ($raw['footer_copyright'] ?? ''),
        ];
    }
}
