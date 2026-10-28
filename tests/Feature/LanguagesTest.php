<?php

namespace Tests\Feature;

use App\Http\Traits\TranslationTrait;
use App\Models\Language;
use App\Models\User;
use App\Services\Languages\LanguageCatalog;
use App\Services\Languages\UpdateLanguage;
use Astrotomic\Translatable\Locales;
use Database\Seeders\LanguagesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LanguagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguagesSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function language(string $code): Language
    {
        return Language::where('code', $code)->firstOrFail();
    }

    public function test_seeder_order_and_preservation(): void
    {
        $this->assertSame(['ar', 'en', 'fr'], app(LanguageCatalog::class)->all()->pluck('code')->all());
        app(UpdateLanguage::class)->execute($this->language('ar')->id, 'default', true);
        app(UpdateLanguage::class)->execute($this->language('en')->id, 'active', false);
        $this->seed(LanguagesSeeder::class);
        $this->assertSame(3, Language::count());
        $this->assertFalse($this->language('en')->active);
        $this->assertSame('ar', app(LanguageCatalog::class)->default()->code);
        foreach (['zz', 'aa'] as $code) {
            Language::create(['name' => $code, 'code' => $code, 'direction' => 'ltr', 'sort_order' => 100]);
        }
        $this->assertSame(['ar', 'en', 'fr', 'aa', 'zz'], app(LanguageCatalog::class)->all()->pluck('code')->all());
    }

    public function test_single_active_default_and_rejected_transitions(): void
    {
        $this->actingAs($this->admin());
        $ar = $this->language('ar');
        $en = $this->language('en');
        $fr = $this->language('fr');
        $this->patchJson(route('languages.update', $ar), ['field' => 'default', 'value' => 1])->assertOk()->assertJsonCount(3, 'languages');
        $this->assertSame($ar->id, app(LanguageCatalog::class)->defaultId());
        $this->assertSame(1, DB::table('language_settings')->count());
        $this->patchJson(route('languages.update', $ar), ['field' => 'active', 'value' => 0])->assertUnprocessable()->assertJsonValidationErrors('active');
        $this->patchJson(route('languages.update', $ar), ['field' => 'default', 'value' => 0])->assertUnprocessable();
        $this->patchJson(route('languages.update', $fr), ['field' => 'default', 'value' => 1])->assertUnprocessable();
        $this->patchJson(route('languages.update', $fr), ['field' => 'active', 'value' => 1])->assertUnprocessable()->assertJsonValidationErrors('active');
        $this->patchJson(route('languages.update', $en), ['field' => 'default', 'value' => 1])->assertOk();
        $this->assertSame($en->id, app(LanguageCatalog::class)->defaultId());
    }

    public function test_permissions_on_page_snapshot_and_mutation(): void
    {
        $this->getJson(route('languages.index'))->assertUnauthorized();
        foreach (['sales', 'warehouse'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('languages.index'))->assertForbidden();
            $this->getJson(route('languages.index'))->assertForbidden();
            $this->patchJson(route('languages.update', $this->language('ar')), ['field' => 'default', 'value' => 1])->assertForbidden();
        }
        $this->actingAs($this->admin())->get(route('languages.index'))->assertOk()->assertSee('languages-panel');
    }

    public function test_invalid_input_and_missing_language(): void
    {
        $this->actingAs($this->admin());
        $this->patchJson(route('languages.update', $this->language('ar')), ['field' => 'code', 'value' => 'bad'])->assertUnprocessable()->assertJsonValidationErrors(['field', 'value']);
        $this->patchJson('/dashboard/languages/99999', ['field' => 'active', 'value' => 1])->assertNotFound();
    }

    public function test_switcher_session_fallback_direction_and_translation_fallback_are_separate(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('dashboard.locale').'?filter=test', ['locale' => 'ar', 'page' => 'dashboard/languages'])->assertRedirect('/dashboard/languages?filter=test');
        $this->get(route('languages.index'))->assertSee('lang="ar" dir="rtl"', false);
        app(UpdateLanguage::class)->execute($this->language('ar')->id, 'active', false);
        $this->get(route('languages.index'))->assertSee('lang="en" dir="ltr"', false)->assertSessionHas('locale', 'en');
        $this->post(route('dashboard.locale'), ['locale' => 'fr', 'page' => 'dashboard/languages'])->assertSessionHas('locale', 'en');
        $this->assertSame('en', config('app.fallback_locale'));
        $this->assertSame('en', config('translatable.fallback_locale'));
    }

    public function test_trait_and_astrotomic_share_order_cache_and_invalidate(): void
    {
        $helper = new class
        {
            use TranslationTrait;
        };
        $this->assertSame(['ar', 'en'], $helper->activeLanguageCodes());
        $locales = app(Locales::class);
        $this->assertSame(['ar', 'en'], $locales->all());
        DB::enableQueryLog();
        DB::flushQueryLog();
        $helper->activeLanguages();
        $helper->activeLanguageCodes();
        $locales->all();
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
        app(UpdateLanguage::class)->execute($this->language('ar')->id, 'active', false);
        $this->assertSame(['en'], $helper->activeLanguageCodes());
        $this->assertSame(['en'], $locales->all());
        $this->assertFalse($locales->has('ar'));
        $this->assertArrayHasKey('name_en', $helper->translationRules(['name' => ['required', 'string']]));
        $model = new class
        {
            public array $translations = [];

            public function translateOrNew($code)
            {
                return $this->translations[$code] ??= new \stdClass;
            }
        };
        $model->translations['fr'] = (object) ['name' => 'Existing inactive translation'];
        $helper->storeTranslates($model, Request::create('/', 'POST', ['name_en' => 'Hello']), ['name']);
        $this->assertSame('Existing inactive translation', $model->translations['fr']->name);
        $this->assertSame('Hello', $model->translations['en']->name);
        try {
            $helper->storeTranslates($model, Request::create('/', 'POST', ['name_en' => 'Changed', 'name_ar' => 'مرفوض']), ['name']);
            $this->fail('Inactive translation accepted');
        } catch (ValidationException) {
            $this->assertSame('Hello', $model->translations['en']->name);
            $this->assertArrayNotHasKey('ar', $model->translations);
        }
        $this->assertTrue(trait_exists(\App\Traits\TranslationTrait::class));
    }

    public function test_sort_changes_invalidate_request_cache(): void
    {
        app(LanguageCatalog::class)->codes();
        $this->language('en')->update(['sort_order' => 1]);
        $this->language('ar')->update(['sort_order' => 2]);
        $this->assertSame(['en', 'ar'], app(LanguageCatalog::class)->codes());
    }

    public function test_database_rejects_second_settings_row(): void
    {
        $this->expectException(QueryException::class);
        DB::table('language_settings')->insert(['id' => 2, 'default_language_id' => $this->language('ar')->id]);
    }

    public function test_navigation_link_is_admin_only(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->assertSee(route('languages.index'), false);
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales)->get(route('dashboard'))->assertOk()->assertDontSee(route('languages.index'), false);
    }

    public function test_public_registration_remains_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
