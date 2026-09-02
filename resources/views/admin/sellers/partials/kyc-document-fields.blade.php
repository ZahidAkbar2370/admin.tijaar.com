@php
    $documentType = old('document_type', $seller->kyc_document_type ?? \App\Support\KycDocumentRules::DOCUMENT_GOVT_ID);
    $idNumber = old('kyc_id_number', $seller->kyc_id_number ?? '');
    $isLicence = $documentType === \App\Support\KycDocumentRules::DOCUMENT_LICENCE;
@endphp

<div class="border-t border-gray-100 pt-6" x-data="{ documentType: @js($documentType) }">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Identity document</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Document type</label>
            <select name="document_type" x-model="documentType" class="{{ $inputClass }}">
                <option value="govt_id">Govt ID (CNIC)</option>
                <option value="licence">Licence</option>
            </select>
        </div>
        <div x-show="documentType === 'govt_id'" x-cloak>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">CNIC number</label>
            <input type="text" name="cnic" value="{{ old('cnic', $isLicence ? '' : $idNumber) }}" class="{{ $inputClass }}" placeholder="35202-1234567-1">
        </div>
        <div x-show="documentType === 'licence'" x-cloak>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Licence number</label>
            <input type="text" name="licence_number" value="{{ old('licence_number', $isLicence ? $idNumber : '') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Front image</label>
            <input type="file" name="id_front" accept=".jpg,.jpeg,.png" class="text-sm">
            @if($seller->kyc_id_front_path)
                <a href="{{ \App\Support\UploadHelper::url($seller->kyc_id_front_path) }}" target="_blank" class="block text-xs text-primary mt-2">View front</a>
            @elseif($seller->kyc_document_path)
                <a href="{{ \App\Support\UploadHelper::url($seller->kyc_document_path) }}" target="_blank" class="block text-xs text-primary mt-2">View legacy document</a>
            @endif
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Back image</label>
            <input type="file" name="id_back" accept=".jpg,.jpeg,.png" class="text-sm">
            @if($seller->kyc_id_back_path)
                <a href="{{ \App\Support\UploadHelper::url($seller->kyc_id_back_path) }}" target="_blank" class="block text-xs text-primary mt-2">View back</a>
            @endif
        </div>
    </div>
</div>
