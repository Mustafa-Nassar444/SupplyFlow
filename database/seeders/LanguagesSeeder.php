<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Services\Languages\LanguageCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ([['العربية', 'ar', 'rtl', true, 1], ['English', 'en', 'ltr', true, 2], ['Français', 'fr', 'ltr', false, 3]] as [$name, $code, $direction, $active, $order]) {
                Language::firstOrCreate(['code' => $code], ['name' => $name, 'direction' => $direction, 'active' => $active, 'sort_order' => $order]);
            }
            $initial = Language::where('code', in_array(config('app.locale'), ['ar', 'en'], true) ? config('app.locale') : 'ar')->firstOrFail();
            DB::table('language_settings')->insertOrIgnore(['id' => 1, 'default_language_id' => $initial->id]);
        }, 3);
        app(LanguageCatalog::class)->forget();
        app(LanguageCatalog::class)->synchronizeAstrotomic();
    }
}
