<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SitemapStaticUrl;
use App\Services\SitemapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SitemapController extends Controller
{
    public function __construct(private SitemapService $sitemap) {}

    public function index(): View
    {
        $config = $this->sitemap->config();
        $stats = $this->sitemap->stats();
        $staticUrls = SitemapStaticUrl::ordered()->get();
        $indexEntries = $this->sitemap->indexEntries();
        $frontendBase = $this->sitemap->siteUrl();
        $sitemapFiles = $this->sitemap->listAdminFiles();

        return view('admin.sitemap.index', compact(
            'config',
            'stats',
            'staticUrls',
            'indexEntries',
            'frontendBase',
            'sitemapFiles'
        ));
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $request->validate([
            'enable_static' => 'nullable|boolean',
            'enable_categories' => 'nullable|boolean',
            'enable_products' => 'nullable|boolean',
            'products_per_file' => 'nullable|integer|min:100|max:5000',
            'product_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'product_priority' => 'nullable|numeric|min:0|max:1',
            'category_changefreq' => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'category_priority' => 'nullable|numeric|min:0|max:1',
        ]);

        $this->sitemap->saveConfig([
            'enable_static' => $request->boolean('enable_static'),
            'enable_categories' => $request->boolean('enable_categories'),
            'enable_products' => $request->boolean('enable_products'),
            'products_per_file' => $request->input('products_per_file'),
            'product_changefreq' => $request->input('product_changefreq'),
            'product_priority' => $request->input('product_priority'),
            'category_changefreq' => $request->input('category_changefreq'),
            'category_priority' => $request->input('category_priority'),
        ]);

        return redirect()->back()->with('success', 'Sitemap settings saved.');
    }

    public function updateOverride(Request $request): RedirectResponse
    {
        $request->validate([
            'file_key' => ['required', 'string', 'regex:/^(index|static|categories|products-\d+)$/'],
            'mode' => 'required|in:auto,manual',
            'manual_xml' => 'nullable|string|max:500000',
        ]);

        $this->sitemap->saveOverride(
            $request->input('file_key'),
            $request->input('mode'),
            $request->input('manual_xml')
        );

        return redirect()->back()->with('success', 'Sitemap file saved.');
    }

    public function storeStatic(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'path' => 'required|string|max:255',
            'changefreq' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'priority' => 'required|numeric|min:0|max:1',
            'is_enabled' => 'nullable|boolean',
        ]);

        $path = '/' . ltrim(trim($data['path']), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $maxSort = (int) SitemapStaticUrl::max('sort_order');

        SitemapStaticUrl::create([
            'path' => $path,
            'changefreq' => $data['changefreq'],
            'priority' => $data['priority'],
            'is_enabled' => $request->boolean('is_enabled', true),
            'sort_order' => $maxSort + 1,
        ]);

        return redirect()->back()->with('success', 'Static URL added.');
    }

    public function updateStatic(Request $request, SitemapStaticUrl $sitemapStaticUrl): RedirectResponse
    {
        $data = $request->validate([
            'path' => 'required|string|max:255',
            'changefreq' => 'required|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'priority' => 'required|numeric|min:0|max:1',
            'is_enabled' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = '/' . ltrim(trim($data['path']), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $sitemapStaticUrl->update([
            'path' => $path,
            'changefreq' => $data['changefreq'],
            'priority' => $data['priority'],
            'is_enabled' => $request->boolean('is_enabled'),
            'sort_order' => $data['sort_order'] ?? $sitemapStaticUrl->sort_order,
        ]);

        return redirect()->back()->with('success', 'Static URL updated.');
    }

    public function destroyStatic(SitemapStaticUrl $sitemapStaticUrl): RedirectResponse
    {
        $sitemapStaticUrl->delete();

        return redirect()->back()->with('success', 'Static URL removed.');
    }
}
