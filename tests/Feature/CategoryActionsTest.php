<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\LanguagesSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguagesSeeder::class);
    }

    private function category(): Category
    {
        return Category::create(['ar' => ['name' => 'الفئة الأصلية'], 'en' => ['name' => 'Original category']]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_single_dashboard_uses_tabler_and_has_one_shared_delete_form(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $response = $this->actingAs($this->admin())->get(route('dashboard'))->assertOk()->assertViewIs('dashboard.index');
        $response->assertSee('/vendor/tabler/js/tabler.js', false)->assertDontSee("You're logged in!");
        $this->assertSame(1, substr_count($response->getContent(), 'id="dashboard-delete-form"'));
        $response->assertSee('name="_method" value="DELETE"', false)->assertSee(route('dashboard.categories.index'), false);
    }

    public function test_admin_list_has_working_edit_and_delete_targets(): void
    {
        $category = $this->category();
        $this->actingAs($this->admin())->get(route('dashboard.categories.index'))->assertOk()
            ->assertSee(route('dashboard.categories.edit', $category), false)
            ->assertSee('data-delete-url="'.route('dashboard.categories.destroy', $category).'"', false)
            ->assertDontSee('demo-details');
    }

    public function test_edit_prefills_translations_and_update_preserves_inactive_translations(): void
    {
        $category = $this->category();
        $category->translateOrNew('fr')->name = 'Existing French';
        $category->save();
        $this->actingAs($this->admin())->get(route('dashboard.categories.edit', $category))->assertOk()
            ->assertSee('value="Original category"', false)->assertSee('الفئة الأصلية')
            ->assertSee('name="_method" value="PUT"', false)->assertDontSee('name="name_fr"', false);
        $this->putJson(route('dashboard.categories.update', $category), [
            'name_ar' => 'الفئة المعدلة', 'name_en' => 'Updated category',
        ])->assertOk()->assertJsonPath('message', __('categories.updated'));
        $category->refresh();
        $this->assertSame('Updated category', $category->translate('en')->name);
        $this->assertSame('الفئة المعدلة', $category->translate('ar')->name);
        $this->assertSame('Existing French', $category->translate('fr')->name);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_invalid_update_changes_nothing(): void
    {
        $category = $this->category();
        $this->actingAs($this->admin())->putJson(route('dashboard.categories.update', $category), [
            'name_en' => 'Changed', 'name_fr' => 'Inactive',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name_ar', 'name_fr']);
        $this->assertSame('Original category', $category->fresh()->translate('en')->name);
    }

    public function test_non_admins_cannot_see_or_invoke_mutation_actions(): void
    {
        $category = $this->category();
        foreach (['sales', 'warehouse'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('dashboard.categories.index'))->assertOk()
                ->assertDontSee('data-delete-url=', false)->assertDontSee(route('dashboard.categories.edit', $category), false);
            $this->get(route('dashboard.categories.edit', $category))->assertForbidden();
            $this->putJson(route('dashboard.categories.update', $category), [])->assertForbidden();
            $this->deleteJson(route('dashboard.categories.destroy', $category))->assertForbidden();
        }
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_delete_removes_category_and_its_translations_only(): void
    {
        $category = $this->category();
        $other = $this->category();
        $this->actingAs($this->admin())->deleteJson(route('dashboard.categories.destroy', $category))->assertOk()
            ->assertJsonPath('message', __('categories.deleted'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('category_translations', ['category_id' => $category->id]);
        $this->assertDatabaseHas('categories', ['id' => $other->id]);
        $this->assertDatabaseCount('category_translations', 2);
        $this->deleteJson(route('dashboard.categories.destroy', $category))->assertNotFound();
    }

    public function test_restricting_foreign_key_prevents_deleting_a_referenced_category(): void
    {
        Schema::create('category_test_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
        });
        $category = $this->category();
        DB::table('category_test_references')->insert(['category_id' => $category->id]);
        $this->actingAs($this->admin())->deleteJson(route('dashboard.categories.destroy', $category))
            ->assertUnprocessable()->assertJsonValidationErrors('delete');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseCount('category_translations', 2);
    }

    public function test_normal_update_and_delete_have_html_redirect_fallbacks(): void
    {
        $category = $this->category();
        $this->actingAs($this->admin())->put(route('dashboard.categories.update', $category), [
            'name_ar' => 'اسم', 'name_en' => 'Name',
        ])->assertRedirect(route('dashboard.categories.index'))->assertSessionHas('success');
        $this->delete(route('dashboard.categories.destroy', $category))
            ->assertRedirect(route('dashboard.categories.index'))->assertSessionHas('success');
    }

    public function test_language_switch_accepts_category_edit_page_and_filters(): void
    {
        $category = $this->category();
        $this->actingAs($this->admin())->post(route('dashboard.locale').'?filter=test', [
            'locale' => 'ar', 'page' => 'dashboard/categories/'.$category->id.'/edit',
        ])->assertRedirect('/dashboard/categories/'.$category->id.'/edit?filter=test')->assertSessionHas('locale', 'ar');
    }
}
