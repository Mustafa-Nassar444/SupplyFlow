<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    public function test_pages_render_with_one_local_tabler_bundle_and_no_fake_authentication(): void
    {
        foreach (['/dashboard', '/dashboard/examples', '/dashboard/examples/form'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertSee('lang="en" dir="ltr"', false)
                ->assertSee('/vendor/tabler/css/tabler.min.css', false)
                ->assertDontSee('cdn.jsdelivr.net')
                ->assertDontSee('Logout');
            $this->assertSame(1, substr_count($response->getContent(), '/vendor/tabler/js/tabler.js'));
            $this->withSession(['locale' => 'ar'])->get($path)->assertOk()
                ->assertSee('lang="ar" dir="rtl"', false)
                ->assertSee('/vendor/tabler/css/tabler.rtl.min.css', false);
            $this->withSession(['locale' => 'en']);
        }
    }

    public function test_language_switch_preserves_page_filters_and_session(): void
    {
        $this->post('/dashboard/language?search=demo&page=2', ['locale' => 'ar', 'page' => 'dashboard/examples'])
            ->assertRedirect('/dashboard/examples?page=2&search=demo')->assertSessionHas('locale', 'ar');
        $this->get('/dashboard/examples?page=2&search=demo')->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('/vendor/tabler/css/tabler.rtl.min.css', false)
            ->assertSee('مثال القائمة')->assertSee('<bdi dir="ltr">DEMO-001</bdi>', false);
    }

    public function test_invalid_locales_fall_back_and_external_redirects_are_rejected(): void
    {
        $this->post('/dashboard/language', ['locale' => 'fr', 'page' => 'dashboard'])
            ->assertRedirect('/dashboard')->assertSessionHas('locale', 'en');
        $this->withSession(['locale' => 'invalid'])->get('/dashboard')->assertSee('lang="en"', false);
        $this->post('/dashboard/language', ['locale' => 'ar', 'page' => 'https://example.test'])->assertNotFound();
    }

    public function test_example_form_returns_localized_errors_and_old_input_without_database_access(): void
    {
        $this->withSession(['locale' => 'ar'])->from('/dashboard/examples/form')
            ->post('/dashboard/examples/form', ['name' => 'Example', 'email' => 'invalid', 'category' => 'bad'])
            ->assertRedirect('/dashboard/examples/form')->assertSessionHasErrors(['email', 'category', 'acknowledge'])
            ->assertSessionHasInput('name', 'Example');
        $this->get('/dashboard/examples/form')->assertOk()->assertSee('أدخل قيمة صحيحة')->assertSee('value="Example"', false);
    }

    public function test_valid_example_only_flashes_success(): void
    {
        $this->post('/dashboard/examples/form', [
            'name' => 'Example', 'email' => 'demo@example.test', 'category' => 'sample', 'acknowledge' => '1',
        ])->assertRedirect(route('dashboard.examples.form'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->get('/dashboard/examples/form')->assertSee('No data was saved.');
    }
}
