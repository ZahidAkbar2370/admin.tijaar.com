<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'phone')) {
            DB::table('users')->where('phone', '')->update(['phone' => null]);

            $dupes = DB::table('users')
                ->select('phone')
                ->whereNotNull('phone')
                ->groupBy('phone')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('phone');

            foreach ($dupes as $phone) {
                $ids = DB::table('users')->where('phone', $phone)->orderBy('id')->pluck('id');
                $ids->shift();
                if ($ids->isNotEmpty()) {
                    DB::table('users')->whereIn('id', $ids)->update(['phone' => null]);
                }
            }

            $hasUnique = collect(DB::select("SHOW INDEX FROM users WHERE Column_name = 'phone' AND Non_unique = 0"))
                ->isNotEmpty();
            if (! $hasUnique) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('phone');
                });
            }
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropUnique(['phone']);
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }
        if (Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
