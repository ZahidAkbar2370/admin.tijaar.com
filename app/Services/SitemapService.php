<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SitemapOverride;
use App\Models\SitemapStaticUrl;
use App\Support\SitemapXmlHelper;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SitemapService
{
    public const DEFAULT_PRODUCTS_PER_FILE = 5000;

    public function siteUrl(): string
    {
        return rtrim((string) config('app.frontend_url', 'https://www.tijaar.com'), '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $stored = Setting::get('sitemap_config');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;
        $defaults = [
            'enable_static' => true,
            'enable_categories' => true,
            'enable_products' => true,
            'products_per_file' => self::DEFAULT_PRODUCTS_PER_FILE,
            'product_changefreq' => 'daily',
            'product_priority' => 0.8,
            'category_changefreq' => 'daily',
            'category_priority' => 0.8,
        ];

        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_merge($defaults, $decoded);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function saveConfig(array $config): void
    {
        $current = $this->config();
        $merged = array_merge($current, [
            'enable_static' => (bool) ($config['enable_static'] ?? $current['enable_static']),
            'enable_categories' => (bool) ($config['enable_categories'] ?? $current['enable_categories']),
            'enable_products' => (bool) ($config['enable_products'] ?? $current['enable_products']),
            'products_per_file' => max(100, min(5000, (int) ($config['products_per_file'] ?? $current['products_per_file']))),
            'product_changefreq' => (string) ($config['product_changefreq'] ?? $current['product_changefreq']),
            'product_priority' => (float) ($config['product_priority'] ?? $current['product_priority']),
            'category_changefreq' => (string) ($config['category_changefreq'] ?? $current['category_changefreq']),
            'category_priority' => (float) ($config['category_priority'] ?? $current['category_priority']),
        ]);

        Setting::set('sitemap_config', json_encode($merged));
    }

    public function publishedProductsQuery()
    {
        return Product::query()
            ->published()
            ->fromActiveSellers()
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function publishedProductCount(): int
    {
        return $this->publishedProductsQuery()->count();
    }

    public function productFileCount(): int
    {
        $config = $this->config();
        if (! $config['enable_products']) {
            return 0;
        }

        $perFile = (int) $config['products_per_file'];
        $total = $this->publishedProductCount();

        return $total > 0 ? (int) ceil($total / $perFile) : 0;
    }

    /**
     * @return list<array{loc: string, lastmod: string}>
     */
    public function indexEntries(): array
    {
        $config = $this->config();
        $base = $this->siteUrl();
        $entries = [];

        if ($config['enable_static']) {
            $lastmod = SitemapStaticUrl::enabled()->max('updated_at');
            $entries[] = [
                'loc' => "{$base}/sitemap-static.xml",
                'lastmod' => $this->formatDate($lastmod),
            ];
        }

        if ($config['enable_categories']) {
            $lastmod = Category::active()->max('updated_at');
            $entries[] = [
                'loc' => "{$base}/sitemap-categories.xml",
                'lastmod' => $this->formatDate($lastmod),
            ];
        }

        if ($config['enable_products']) {
            $fileCount = $this->productFileCount();
            $lastmod = $this->publishedProductsQuery()->max('updated_at');
            $lastmodStr = $this->formatDate($lastmod);

            for ($page = 1; $page <= $fileCount; $page++) {
                $entries[] = [
                    'loc' => "{$base}/sitemap-products-{$page}.xml",
                    'lastmod' => $lastmodStr,
                ];
            }
        }

        return $entries;
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: float}>
     */
    public function staticUrls(): array
    {
        $base = $this->siteUrl();

        return SitemapStaticUrl::enabled()->ordered()->get()->map(function (SitemapStaticUrl $row) use ($base) {
            $path = '/' . ltrim($row->path, '/');
            if ($path !== '/') {
                $path = rtrim($path, '/');
            }

            return [
                'loc' => $base . ($path === '/' ? '/' : $path),
                'lastmod' => $this->formatDate($row->updated_at),
                'changefreq' => $row->changefreq,
                'priority' => (float) $row->priority,
            ];
        })->values()->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: float}>
     */
    public function categoryUrls(): array
    {
        $config = $this->config();
        $base = $this->siteUrl();
        $categories = Category::active()
            ->with('parent:id,slug')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'parent_id', 'updated_at']);

        return $categories->map(function (Category $category) use ($base, $config) {
            if ($category->parent_id && $category->parent) {
                $path = '/category/' . $category->parent->slug . '/' . $category->slug;
            } else {
                $path = '/category/' . $category->slug;
            }

            return [
                'loc' => $base . $path,
                'lastmod' => $this->formatDate($category->updated_at),
                'changefreq' => (string) $config['category_changefreq'],
                'priority' => (float) $config['category_priority'],
            ];
        })->values()->all();
    }

    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: float}>
     */
    public function productUrls(int $page): array
    {
        $config = $this->config();
        $perFile = (int) $config['products_per_file'];
        $base = $this->siteUrl();
        $offset = max(0, ($page - 1) * $perFile);

        $products = $this->publishedProductsQuery()
            ->orderBy('id')
            ->offset($offset)
            ->limit($perFile)
            ->get(['slug', 'updated_at']);

        return $products->map(function (Product $product) use ($base, $config) {
            return [
                'loc' => $base . '/product/' . $product->slug,
                'lastmod' => $this->formatDate($product->updated_at),
                'changefreq' => (string) $config['product_changefreq'],
                'priority' => (float) $config['product_priority'],
            ];
        })->values()->all();
    }

    public function formatDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }
        if ($value) {
            return Carbon::parse($value)->toDateString();
        }

        return now()->toDateString();
    }

    /**
     * @return array<string, int|bool>
     */
    public function stats(): array
    {
        return [
            'static_count' => SitemapStaticUrl::enabled()->count(),
            'category_count' => Category::active()->count(),
            'product_count' => $this->publishedProductCount(),
            'product_files' => $this->productFileCount(),
            'enable_static' => (bool) $this->config()['enable_static'],
            'enable_categories' => (bool) $this->config()['enable_categories'],
            'enable_products' => (bool) $this->config()['enable_products'],
        ];
    }

    public function getOverrideRecord(string $fileKey): ?SitemapOverride
    {
        return SitemapOverride::where('file_key', $fileKey)->first();
    }

    public function isManual(string $fileKey): bool
    {
        $record = $this->getOverrideRecord($fileKey);

        return $record?->isManual() ?? false;
    }

    /**
     * @return array{source: string, xml?: string, entries?: list<array{loc: string, lastmod: string}>}
     */
    public function resolveIndexPayload(): array
    {
        if ($this->isManual('index')) {
            return [
                'source' => 'manual',
                'xml' => trim((string) $this->getOverrideRecord('index')->manual_xml),
            ];
        }

        return [
            'source' => 'auto',
            'entries' => $this->indexEntries(),
        ];
    }

    /**
     * @return array{source: string, xml?: string, urls?: list<array<string, mixed>>}
     */
    public function resolveStaticPayload(): array
    {
        if ($this->isManual('static')) {
            return [
                'source' => 'manual',
                'xml' => trim((string) $this->getOverrideRecord('static')->manual_xml),
            ];
        }

        return [
            'source' => 'auto',
            'urls' => $this->staticUrls(),
        ];
    }

    /**
     * @return array{source: string, xml?: string, urls?: list<array<string, mixed>>}
     */
    public function resolveCategoriesPayload(): array
    {
        if ($this->isManual('categories')) {
            return [
                'source' => 'manual',
                'xml' => trim((string) $this->getOverrideRecord('categories')->manual_xml),
            ];
        }

        return [
            'source' => 'auto',
            'urls' => $this->categoryUrls(),
        ];
    }

    /**
     * @return array{source: string, xml?: string, urls?: list<array<string, mixed>>, page: int, total_files?: int}|null
     */
    public function resolveProductsPayload(int $page): ?array
    {
        $page = max(1, $page);
        $key = "products-{$page}";

        if ($this->isManual($key)) {
            return [
                'source' => 'manual',
                'xml' => trim((string) $this->getOverrideRecord($key)->manual_xml),
                'page' => $page,
            ];
        }

        $fileCount = $this->productFileCount();
        if ($fileCount === 0) {
            return [
                'source' => 'auto',
                'urls' => [],
                'page' => $page,
                'total_files' => 0,
            ];
        }

        if ($page > $fileCount) {
            return null;
        }

        return [
            'source' => 'auto',
            'urls' => $this->productUrls($page),
            'page' => $page,
            'total_files' => $fileCount,
        ];
    }

    public function generateAutoXml(string $fileKey): string
    {
        return match ($fileKey) {
            'index' => SitemapXmlHelper::sitemapIndex($this->indexEntries()),
            'static' => SitemapXmlHelper::urlSet($this->staticUrls()),
            'categories' => SitemapXmlHelper::urlSet($this->categoryUrls()),
            default => $this->generateAutoProductXml($fileKey),
        };
    }

    private function generateAutoProductXml(string $fileKey): string
    {
        if (! preg_match('/^products-(\d+)$/', $fileKey, $matches)) {
            return SitemapXmlHelper::urlSet([]);
        }

        $page = (int) $matches[1];
        $fileCount = $this->productFileCount();
        if ($fileCount === 0 || $page > $fileCount) {
            return SitemapXmlHelper::urlSet([]);
        }

        return SitemapXmlHelper::urlSet($this->productUrls($page));
    }

    public function maxManualProductPage(): int
    {
        $max = 0;
        SitemapOverride::where('file_key', 'like', 'products-%')
            ->pluck('file_key')
            ->each(function (string $key) use (&$max) {
                if (preg_match('/^products-(\d+)$/', $key, $matches)) {
                    $max = max($max, (int) $matches[1]);
                }
            });

        return $max;
    }

    /**
     * @return list<array{key: string, label: string, path: string, url: string, mode: string, manual_xml: string, auto_xml: string}>
     */
    public function listAdminFiles(): array
    {
        $base = $this->siteUrl();
        $definitions = [
            ['key' => 'index', 'label' => 'sitemap.xml', 'path' => '/sitemap.xml'],
            ['key' => 'static', 'label' => 'sitemap-static.xml', 'path' => '/sitemap-static.xml'],
            ['key' => 'categories', 'label' => 'sitemap-categories.xml', 'path' => '/sitemap-categories.xml'],
        ];

        $maxPage = max($this->productFileCount(), $this->maxManualProductPage(), 1);
        for ($i = 1; $i <= $maxPage; $i++) {
            $definitions[] = [
                'key' => "products-{$i}",
                'label' => "sitemap-products-{$i}.xml",
                'path' => "/sitemap-products-{$i}.xml",
            ];
        }

        return array_map(function (array $file) use ($base) {
            $override = $this->getOverrideRecord($file['key']);

            return [
                'key' => $file['key'],
                'label' => $file['label'],
                'path' => $file['path'],
                'url' => $base . $file['path'],
                'mode' => $override?->mode ?? 'auto',
                'manual_xml' => $override?->manual_xml ?? '',
                'auto_xml' => $this->generateAutoXml($file['key']),
            ];
        }, $definitions);
    }

    public function saveOverride(string $fileKey, string $mode, ?string $manualXml = null): void
    {
        $this->assertValidFileKey($fileKey);

        if ($mode === 'manual') {
            $manualXml = trim((string) $manualXml);
            if ($manualXml === '') {
                throw ValidationException::withMessages([
                    'manual_xml' => 'Manual XML is required when manual mode is enabled.',
                ]);
            }
            if (! str_contains($manualXml, '<?xml')) {
                throw ValidationException::withMessages([
                    'manual_xml' => 'XML must start with a valid XML declaration.',
                ]);
            }
            if ($fileKey === 'index' && ! str_contains($manualXml, '<sitemapindex')) {
                throw ValidationException::withMessages([
                    'manual_xml' => 'Index sitemap must contain a <sitemapindex> root element.',
                ]);
            }
            if ($fileKey !== 'index' && ! str_contains($manualXml, '<urlset')) {
                throw ValidationException::withMessages([
                    'manual_xml' => 'This sitemap must contain a <urlset> root element.',
                ]);
            }

            SitemapOverride::updateOrCreate(
                ['file_key' => $fileKey],
                ['mode' => 'manual', 'manual_xml' => $manualXml]
            );

            return;
        }

        SitemapOverride::where('file_key', $fileKey)->delete();
    }

    public function assertValidFileKey(string $fileKey): void
    {
        if (! preg_match('/^(index|static|categories|products-\d+)$/', $fileKey)) {
            throw ValidationException::withMessages([
                'file_key' => 'Invalid sitemap file key.',
            ]);
        }
    }
}
