<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

class KycDocumentRules
{
    public const DOCUMENT_GOVT_ID = 'govt_id';

    public const DOCUMENT_LICENCE = 'licence';

    public const DOCUMENT_TYPES = [self::DOCUMENT_GOVT_ID, self::DOCUMENT_LICENCE];

    public static function documentTypeRule(bool $required = true): string
    {
        $prefix = $required ? 'required' : 'nullable';

        return $prefix.'|in:'.implode(',', self::DOCUMENT_TYPES);
    }

    public static function normalizeCnic(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if (strlen($digits) !== 13) {
            return null;
        }

        return substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1);
    }

    public static function isValidCnic(?string $value): bool
    {
        return self::normalizeCnic($value) !== null;
    }

    /** @return array<string, mixed> */
    public static function baseRules(bool $requireImages = true): array
    {
        $imageRule = ($requireImages ? 'required' : 'nullable').'|file|mimes:jpeg,png,jpg|max:5120';

        return [
            'document_type' => self::documentTypeRule($requireImages),
            'cnic' => 'nullable|string|max:20',
            'licence_number' => 'nullable|string|max:64',
            'id_front' => $imageRule,
            'id_back' => $imageRule,
            'bank_account_holder' => 'required|string|max:120',
            'bank_account_number' => 'required|string|max:64',
            'bank_name' => 'required|string|max:120',
            'bank_swift_code' => 'nullable|string|max:64',
        ];
    }

    public static function makeValidator(Request $request, bool $requireImages = true): ValidatorInstance
    {
        return Validator::make($request->all(), self::baseRules($requireImages))
            ->after(function (ValidatorInstance $validator) use ($request, $requireImages) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = (string) $request->input('document_type', '');
                if ($type === self::DOCUMENT_GOVT_ID) {
                    $cnic = self::normalizeCnic($request->input('cnic'));
                    if ($cnic === null) {
                        $validator->errors()->add('cnic', 'Enter a valid 13-digit CNIC (XXXXX-XXXXXXX-X).');
                    }
                } elseif ($type === self::DOCUMENT_LICENCE) {
                    if (trim((string) $request->input('licence_number', '')) === '') {
                        $validator->errors()->add('licence_number', 'Licence number is required.');
                    }
                } elseif ($requireImages) {
                    $validator->errors()->add('document_type', 'Select a document type.');
                }

                if ($requireImages) {
                    if (! $request->hasFile('id_front')) {
                        $validator->errors()->add('id_front', 'Front image is required.');
                    }
                    if (! $request->hasFile('id_back')) {
                        $validator->errors()->add('id_back', 'Back image is required.');
                    }
                }
            });
    }

    /** @return array{document_type: string, id_number: string, id_front_path: string, id_back_path: string} */
    public static function persistSellerDocuments(Request $request, string $storageDir = 'sellers/kyc'): array
    {
        $type = (string) $request->input('document_type');
        $idNumber = $type === self::DOCUMENT_GOVT_ID
            ? (self::normalizeCnic($request->input('cnic')) ?? '')
            : trim((string) $request->input('licence_number', ''));

        return [
            'document_type' => $type,
            'id_number' => $idNumber,
            'id_front_path' => UploadHelper::storePublic($request->file('id_front'), $storageDir),
            'id_back_path' => UploadHelper::storePublic($request->file('id_back'), $storageDir),
        ];
    }

    /** @param array<string, mixed> $kyc */
    public static function idNumberFromKyc(array $kyc): ?string
    {
        $type = $kyc['document_type'] ?? self::DOCUMENT_GOVT_ID;
        if ($type === self::DOCUMENT_LICENCE) {
            return $kyc['licence_number'] ?? null;
        }

        return $kyc['cnic'] ?? null;
    }

    public static function documentTypeLabel(?string $type): string
    {
        return match ($type) {
            self::DOCUMENT_LICENCE => 'Licence',
            self::DOCUMENT_GOVT_ID => 'Govt ID (CNIC)',
            default => '—',
        };
    }
}
