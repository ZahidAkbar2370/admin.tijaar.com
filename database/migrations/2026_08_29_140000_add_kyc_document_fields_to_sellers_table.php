<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            if (! Schema::hasColumn('sellers', 'kyc_document_type')) {
                $table->string('kyc_document_type')->nullable()->after('kyc_document_path');
            }
            if (! Schema::hasColumn('sellers', 'kyc_id_number')) {
                $table->string('kyc_id_number', 64)->nullable()->after('kyc_document_type');
            }
            if (! Schema::hasColumn('sellers', 'kyc_id_front_path')) {
                $table->string('kyc_id_front_path')->nullable()->after('kyc_id_number');
            }
            if (! Schema::hasColumn('sellers', 'kyc_id_back_path')) {
                $table->string('kyc_id_back_path')->nullable()->after('kyc_id_front_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            foreach (['kyc_id_back_path', 'kyc_id_front_path', 'kyc_id_number', 'kyc_document_type'] as $col) {
                if (Schema::hasColumn('sellers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
