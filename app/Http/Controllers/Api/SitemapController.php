<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(private SitemapService $sitemap) {}

    public function indexJson(): JsonResponse
    {
        $payload = $this->sitemap->resolveIndexPayload();

        return response()->json(array_merge([
            'success' => true,
            'site_url' => $this->sitemap->siteUrl(),
            'stats' => $this->sitemap->stats(),
            'config' => $this->sitemap->config(),
        ], $payload));
    }

    public function staticJson(): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $this->sitemap->resolveStaticPayload()));
    }

    public function categoriesJson(): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $this->sitemap->resolveCategoriesPayload()));
    }

    public function productsJson(int $page): JsonResponse
    {
        $payload = $this->sitemap->resolveProductsPayload($page);

        if ($payload === null) {
            return response()->json(['success' => false, 'message' => 'Sitemap page not found'], 404);
        }

        return response()->json(array_merge(['success' => true], $payload));
    }

    private function xmlResponse(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
