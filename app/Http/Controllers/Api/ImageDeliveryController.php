<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImageDeliveryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageDeliveryController extends Controller
{
    public function __construct(private readonly ImageDeliveryService $imageDelivery)
    {
    }

    public function show(Request $request): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $path = (string) $request->query('path', '');
        $width = (int) $request->query('w', 800);
        $quality = (int) $request->query('q', 82);
        $format = strtolower((string) $request->query('fmt', 'webp'));

        if ($path === '') {
            return response()->json(['success' => false, 'message' => 'Missing path'], 400);
        }

        try {
            $result = $this->imageDelivery->deliver($path, $width, $quality, $format);
        } catch (\RuntimeException $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            return response()->json(['success' => false, 'message' => $e->getMessage()], $status);
        }

        return response()->file($result['path'], [
            'Content-Type' => $result['mime'],
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
