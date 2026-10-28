<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AjaxFormsTest extends TestCase
{
    public function test_create_returns_the_json_contract_when_requested(): void
    {
        $this->postJson(route('dashboard.examples.store'), [
            'name' => 'Example', 'email' => 'demo@example.test', 'category' => 'sample', 'acknowledge' => '1',
        ])->assertOk()->assertJsonPath('message', __('ui.success'))
            ->assertJsonPath('redirect_url', route('dashboard.examples.form'));
    }

    public function test_create_validation_is_localized_json(): void
    {
        $this->withSession(['locale' => 'ar'])->postJson(route('dashboard.examples.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'category', 'acknowledge'])
            ->assertJsonPath('errors.name.0', 'أدخل قيمة صحيحة ضمن الحد الموضح.');
    }

    public function test_post_method_spoofing_accepts_put_and_patch_with_files_without_saving(): void
    {
        foreach (['PUT', 'PATCH'] as $method) {
            $this->post(route('dashboard.examples.update'), [
                '_method' => $method, 'name' => 'Example', 'items' => [['quantity' => 2]],
                'attachment' => UploadedFile::fake()->create('sample.txt', 1, 'text/plain'),
            ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()->assertJsonStructure(['message', 'redirect_url']);
        }
    }

    public function test_nested_quantity_and_file_size_are_validated(): void
    {
        $this->post(route('dashboard.examples.update'), [
            '_method' => 'PATCH', 'name' => 'Example', 'items' => [['quantity' => 0]],
            'attachment' => UploadedFile::fake()->create('large.txt', 1025),
        ], ['Accept' => 'application/json'])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity', 'attachment']);
    }

    public function test_shared_script_is_loaded_once_with_translations_and_no_page_submit_handler(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $response = $this->withSession(['locale' => $locale])->get(route('dashboard.examples.form'))->assertOk();
            $this->assertSame(1, substr_count($response->getContent(), '/js/ajax-forms.js'));
            $response->assertSee('window.SupplyFlowAjaxMessages', false)->assertDontSee('/js/dashboard-example.js', false)
                ->assertSee('js-ajax-form')->assertSee('name="_method" value="PATCH"', false);
        }
    }
}
