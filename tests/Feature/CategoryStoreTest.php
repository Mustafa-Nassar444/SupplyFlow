<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\User;
use App\Services\Languages\LanguageCatalog;
use App\Services\Languages\UpdateLanguage;
use Database\Seeders\LanguagesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryStoreTest extends TestCase
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

    public function test_ajax_saves_both_translations_and_reads_them_through_astrotomic(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('dashboard.categories.store'), [
            'name_ar' => 'أدوات مكتبية', 'name_en' => 'Stationery', 'id' => 999,
        ])->assertCreated()->assertJsonPath('message', __('categories.created'));

        $category = Category::findOrFail($response->json('category_id'));
        $this->assertNotSame(999, $category->id);
        $this->assertSame('أدوات مكتبية', $category->translate('ar')->name);
        $this->assertSame('Stationery', $category->translate('en')->name);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('category_translations', 2);
    }

    public function test_localized_required_string_and_max_messages_use_flat_field_names(): void
    {
        $this->actingAs($this->admin())->withSession(['locale' => 'ar']);
        $response = $this->postJson(route('dashboard.categories.store'), ['name_en' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors(['name_ar', 'name_en']);
        $this->assertStringContainsString('مطلوب', $response->json('errors.name_ar.0'));
        $this->assertStringContainsString('نصًا', $response->json('errors.name_en.0'));
        $this->withSession(['locale' => 'en'])->postJson(route('dashboard.categories.store'), [
            'name_ar' => 'اسم', 'name_en' => str_repeat('a', 256),
        ])->assertUnprocessable()->assertJsonPath('errors.name_en.0', 'The Name — English must not exceed 255 characters.');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_inactive_unknown_and_nested_translation_payloads_are_rejected(): void
    {
        $this->actingAs($this->admin());
        foreach ([['name_fr' => 'French'], ['name_fr' => null], ['name_zz' => 'Unknown'], ['name' => ['ar' => 'Old format']]] as $extra) {
            $this->postJson(route('dashboard.categories.store'), array_merge([
                'name_ar' => 'فئة', 'name_en' => 'Category',
            ], $extra))->assertUnprocessable()->assertJsonValidationErrors(array_keys($extra));
        }
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('category_translations', 0);
    }

    public function test_only_currently_active_languages_are_required_and_saved(): void
    {
        $ar = app(LanguageCatalog::class)->all()->firstWhere('code', 'ar');
        app(UpdateLanguage::class)->execute($ar->id, 'active', false);
        $this->actingAs($this->admin())->postJson(route('dashboard.categories.store'), ['name_en' => 'English only'])->assertCreated();
        $this->assertDatabaseCount('category_translations', 1);
        $this->assertDatabaseHas('category_translations', ['locale' => 'en', 'name' => 'English only']);
    }

    public function test_guest_sales_and_warehouse_cannot_create(): void
    {
        $this->postJson(route('dashboard.categories.store'), [])->assertUnauthorized();
        foreach (['sales', 'warehouse'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('dashboard.categories.create'))->assertForbidden();
            $this->postJson(route('dashboard.categories.store'), ['name_ar' => 'فئة', 'name_en' => 'Category'])->assertForbidden();
        }
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_html_submission_flashes_success_and_returns_to_form(): void
    {
        $this->actingAs($this->admin())->post(route('dashboard.categories.store'), [
            'name_ar' => 'فئة', 'name_en' => 'Category',
        ])->assertRedirect(route('dashboard.categories.create'))->assertSessionHas('success', __('categories.created'));
    }

    public function test_category_list_reads_the_selected_translation(): void
    {
        $this->actingAs($this->admin())->postJson(route('dashboard.categories.store'), [
            'name_ar' => 'مستلزمات مكتبية', 'name_en' => 'Office supplies',
        ])->assertCreated();
        $this->post(route('dashboard.locale'), ['locale' => 'en', 'page' => 'dashboard/categories'])->assertRedirect(route('dashboard.categories.index'));
        $this->get(route('dashboard.categories.index'))->assertOk()->assertSee('Office supplies');
        $this->post(route('dashboard.locale'), ['locale' => 'ar', 'page' => 'dashboard/categories'])->assertRedirect(route('dashboard.categories.index'));
        $this->get(route('dashboard.categories.index'))->assertOk()->assertSee('مستلزمات مكتبية');
    }

    public function test_translation_failure_rolls_back_parent_and_all_translations(): void
    {
        CategoryTranslation::creating(function ($translation) {
            if ($translation->locale === 'en') {
                throw new \RuntimeException('Simulated translation failure');
            }
        });
        $this->actingAs($this->admin())->postJson(route('dashboard.categories.store'), [
            'name_ar' => 'فئة', 'name_en' => 'Category',
        ])->assertStatus(500);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('category_translations', 0);
    }

    public function test_translation_locale_is_unique_per_category(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('dashboard.categories.store'), [
            'name_ar' => 'فئة', 'name_en' => 'Category',
        ])->assertCreated();
        $this->expectException(QueryException::class);
        DB::table('category_translations')->insert([
            'category_id' => $response->json('category_id'), 'locale' => 'en', 'name' => 'Duplicate',
        ]);
    }
}
