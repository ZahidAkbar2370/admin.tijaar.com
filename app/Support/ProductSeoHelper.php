<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeoHelper
{
    /**
     * Resolve product SEO meta automatically from listing fields.
     * meta_title ← name; meta_description ← description; meta_keywords ← category, subcategory, brand, title.
     *
     * @param  array<string, mixed>  $input
     * @return array{meta_title: string, meta_description: ?string, meta_keywords: ?string}
     */
    public static function resolve(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $short = trim(strip_tags((string) ($input['short_description'] ?? '')));
        $long = trim(strip_tags((string) ($input['description'] ?? '')));
        $fallbackDesc = $short !== '' ? $short : $long;

        // Always derive from content (ignore client-submitted SEO for private listings automation)
        $forceAuto = ! empty($input['_auto_seo']);

        $metaTitle = $forceAuto ? '' : trim((string) ($input['meta_title'] ?? ''));
        $metaDescription = $forceAuto ? '' : trim((string) ($input['meta_description'] ?? ''));
        $metaKeywords = $forceAuto ? '' : trim((string) ($input['meta_keywords'] ?? ''));

        if ($metaTitle === '') {
            $metaTitle = $name;
        }
        if ($metaDescription === '') {
            $metaDescription = $fallbackDesc !== '' ? Str::limit($fallbackDesc, 160, '') : null;
        } else {
            $metaDescription = Str::limit($metaDescription, 500, '');
        }
        if ($metaKeywords === '') {
            $metaKeywords = self::buildKeywords($input, $name);
        } else {
            $metaKeywords = Str::limit($metaKeywords, 500, '');
        }

        return [
            'meta_title' => $metaTitle !== '' ? $metaTitle : $name,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'meta_keywords' => $metaKeywords !== '' ? $metaKeywords : null,
        ];
    }

    /**
     * Build unique slug from name (category-safe). Appends -2, -3… if taken.
     */
    public static function uniqueSlug(string $name, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $i = 2;
        while (
            Product::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
            if ($i > 5000) {
                $slug = $base.'-'.Str::lower(Str::random(6));
                break;
            }
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    protected static function buildKeywords(array $input, string $name): ?string
    {
        $parts = [];
        $categoryId = (int) ($input['category_id'] ?? 0);
        if ($categoryId > 0) {
            $cat = Category::with('parent')->find($categoryId);
            if ($cat) {
                if ($cat->parent) {
                    $parts[] = $cat->parent->name;
                }
                $parts[] = $cat->name;
            }
        }
        $brandId = (int) ($input['brand_id'] ?? 0);
        if ($brandId > 0) {
            $brand = Brand::find($brandId);
            if ($brand?->name) {
                $parts[] = $brand->name;
            }
        }
        if ($name !== '') {
            $parts[] = $name;
        }

        $parts = array_values(array_unique(array_filter(array_map(
            fn ($p) => trim((string) $p),
            $parts
        ))));

        if ($parts === []) {
            return null;
        }

        return Str::limit(implode(', ', $parts), 500, '');
    }
}
