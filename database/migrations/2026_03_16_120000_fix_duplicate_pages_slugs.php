<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pages with leading slash: if a page with normalized slug already exists, delete this duplicate; otherwise normalize.
        $pages = DB::table('pages')->where('slug', 'like', '/%')->get();
        foreach ($pages as $page) {
            $normalized = ltrim($page->slug, '/');
            $exists = DB::table('pages')->where('slug', $normalized)->where('id', '!=', $page->id)->exists();
            if ($exists) {
                DB::table('pages')->where('id', $page->id)->delete();
            } else {
                DB::table('pages')->where('id', $page->id)->update(['slug' => $normalized]);
            }
        }

        // Remove duplicate slugs: keep the row with the smallest id for each slug
        $duplicates = DB::table('pages')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicates as $slug) {
            $idToKeep = DB::table('pages')->where('slug', $slug)->orderBy('id')->value('id');
            if ($idToKeep !== null) {
                DB::table('pages')->where('slug', $slug)->where('id', '!=', $idToKeep)->delete();
            }
        }
    }

    public function down(): void
    {
        // No reversible change
    }
};
