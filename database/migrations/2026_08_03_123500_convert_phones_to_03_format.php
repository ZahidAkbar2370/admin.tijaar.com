<?php

use App\Support\PhoneHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            DB::table('users')->whereNotNull('phone')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = PhoneHelper::normalize($row->phone);
                    if ($normalized && $normalized !== $row->phone) {
                        DB::table('users')->where('id', $row->id)->update(['phone' => $normalized]);
                    }
                }
            });

            // Private seller KYC phone inside preferences JSON
            DB::table('users')->whereNotNull('preferences')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $prefs = json_decode($row->preferences ?? '', true);
                    if (! is_array($prefs) || empty($prefs['private_seller_kyc']['phone'])) {
                        continue;
                    }
                    $old = $prefs['private_seller_kyc']['phone'];
                    $normalized = PhoneHelper::normalize($old);
                    if ($normalized && $normalized !== $old) {
                        $prefs['private_seller_kyc']['phone'] = $normalized;
                        DB::table('users')->where('id', $row->id)->update([
                            'preferences' => json_encode($prefs),
                        ]);
                    }
                }
            });
        }

        if (Schema::hasTable('addresses') && Schema::hasColumn('addresses', 'phone')) {
            DB::table('addresses')->whereNotNull('phone')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = PhoneHelper::normalize($row->phone);
                    if ($normalized && $normalized !== $row->phone) {
                        DB::table('addresses')->where('id', $row->id)->update(['phone' => $normalized]);
                    }
                }
            });
        }

        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'phone')) {
            DB::table('stores')->whereNotNull('phone')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $normalized = PhoneHelper::normalize($row->phone);
                    if ($normalized && $normalized !== $row->phone) {
                        DB::table('stores')->where('id', $row->id)->update(['phone' => $normalized]);
                    }
                }
            });
        }

        // WaChat sender: keep international 923… for Waghl if convertible
        if (Schema::hasTable('settings')) {
            $sender = DB::table('settings')->where('key', 'wachat_sender')->value('value');
            if ($sender) {
                $intl = PhoneHelper::toInternational($sender);
                if ($intl && $intl !== $sender) {
                    DB::table('settings')->where('key', 'wachat_sender')->update(['value' => $intl]);
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible data normalization — no-op
    }
};
