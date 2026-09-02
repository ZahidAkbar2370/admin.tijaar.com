<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Resolve duplicate names before unique index (append -{id} to later duplicates)
        $dupes = DB::table('stores')
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($dupes as $name) {
            $ids = DB::table('stores')->where('name', $name)->orderBy('id')->pluck('id');
            foreach ($ids->slice(1) as $id) {
                DB::table('stores')->where('id', $id)->update([
                    'name' => $name . '-' . $id,
                ]);
            }
        }

        Schema::table('stores', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
