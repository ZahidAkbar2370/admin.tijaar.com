<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\BrandLogo;
use App\Support\CourierCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin → Courier: enable/disable couriers and optional brand logos for checkout/tracking UI.
 */
class CourierSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.courier.index', [
            'couriers' => CourierCatalog::all(),
        ]);
    }

    public function updateEnabled(Request $request, string $provider): JsonResponse
    {
        $providerKey = $this->resolveProvider($provider);
        if (! $providerKey) {
            return response()->json(['success' => false, 'message' => 'Courier not found.'], 404);
        }

        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $enabled = $request->boolean('enabled');
        $meta = CourierCatalog::PROVIDERS[$providerKey];
        Setting::set($meta['setting_key'], $enabled ? '1' : '0');

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'logo_url' => BrandLogo::courier($providerKey),
            'message' => $meta['label'].' '.($enabled ? 'enabled.' : 'disabled.'),
        ]);
    }

    public function uploadLogo(Request $request, string $provider): JsonResponse
    {
        $providerKey = $this->resolveProvider($provider);
        if (! $providerKey) {
            return response()->json(['success' => false, 'message' => 'Courier not found.'], 404);
        }

        $request->validate([
            'logo' => 'required|file|mimes:jpeg,jpg,png,webp,svg|max:2048',
        ]);

        BrandLogo::storeCourierLogo($providerKey, $request->file('logo'));
        $meta = CourierCatalog::PROVIDERS[$providerKey];

        return response()->json([
            'success' => true,
            'logo_url' => BrandLogo::courier($providerKey),
            'has_custom_logo' => true,
            'message' => $meta['label'].' logo updated.',
        ]);
    }

    public function removeLogo(string $provider): JsonResponse
    {
        $providerKey = $this->resolveProvider($provider);
        if (! $providerKey) {
            return response()->json(['success' => false, 'message' => 'Courier not found.'], 404);
        }

        BrandLogo::removeCourierLogo($providerKey);
        $meta = CourierCatalog::PROVIDERS[$providerKey];

        return response()->json([
            'success' => true,
            'logo_url' => BrandLogo::courier($providerKey),
            'has_custom_logo' => false,
            'message' => $meta['label'].' logo reset to default.',
        ]);
    }

    private function resolveProvider(string $provider): ?string
    {
        $providerKey = CourierCatalog::normalize($provider);

        return array_key_exists($providerKey, CourierCatalog::PROVIDERS) ? $providerKey : null;
    }
}
