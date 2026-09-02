<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\KycDocumentRules;
use App\Support\UploadHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivateSellerController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $prefs = $user->preferences ?? [];
        $kyc = $prefs['private_seller_kyc'] ?? null;

        return response()->json([
            'success' => true,
            'is_private_seller' => (bool) ($user->is_private_seller ?? false),
            'kyc_status' => $user->private_seller_kyc_status,
            'kyc' => $kyc ? [
                'document_type' => $kyc['document_type'] ?? KycDocumentRules::DOCUMENT_GOVT_ID,
                'cnic' => $kyc['cnic'] ?? null,
                'licence_number' => $kyc['licence_number'] ?? null,
                'phone' => $kyc['phone'] ?? null,
                'address' => $kyc['address'] ?? null,
                'city' => $kyc['city'] ?? null,
                'bank_name' => $kyc['bank_name'] ?? null,
                'bank_account_number' => $kyc['bank_account_number'] ?? null,
                'bank_account_holder' => $kyc['bank_account_holder'] ?? null,
                'rejection_reason' => $kyc['rejection_reason'] ?? null,
                'id_front_url' => ! empty($kyc['id_front_path']) ? UploadHelper::url($kyc['id_front_path']) : null,
                'id_back_url' => ! empty($kyc['id_back_path']) ? UploadHelper::url($kyc['id_back_path']) : null,
                'submitted_at' => $kyc['submitted_at'] ?? null,
            ] : null,
            'rejection_reason' => is_array($kyc) ? ($kyc['rejection_reason'] ?? null) : null,
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'customer') {
            return response()->json(['success' => false, 'message' => 'Only customers can apply as private sellers.'], 403);
        }

        if (($user->is_private_seller ?? false) && $user->private_seller_kyc_status === 'approved') {
            return response()->json([
                'success' => true,
                'message' => 'Already an approved private seller.',
                'is_private_seller' => true,
                'kyc_status' => 'approved',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Private seller accounts must be created through registration. Customer accounts cannot convert.',
        ], 403);
    }
}
