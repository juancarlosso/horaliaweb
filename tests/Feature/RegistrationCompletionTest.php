<?php

namespace Tests\Feature;

use App\Services\RegisterCompanyService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class RegistrationCompletionTest extends TestCase
{
    public function test_confirmation_requires_a_pending_registration_authorization(): void
    {
        $response = $this->get('/registro/completado');

        $response->assertRedirect('/login');
    }

    public function test_successful_registration_redirects_to_the_one_time_confirmation(): void
    {
        config(['services.meta.pixel_id' => '1629742415604987']);
        config(['services.cloudflare.turnstile_sitekey' => null, 'services.cloudflare.turnstile_secretkey' => null]);

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('rfc')->nullable()->unique();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        $this->mock(RegisterCompanyService::class)
            ->shouldReceive('register')
            ->once();

        $this->post('/registro', [
            'razon_social' => 'Empresa de prueba',
            'rfc' => '',
            'name' => 'Persona de prueba',
            'email' => 'persona@example.test',
            'password' => 'password-seguro',
            'password_confirmation' => 'password-seguro',
            'terms' => '1',
        ])->assertRedirect('/registro/completado');

        $this->get('/registro/completado')
            ->assertOk()
            ->assertSee("window.fbq('track', 'CompleteRegistration'", false);

        $this->get('/registro/completado')->assertRedirect('/login');
    }

    public function test_confirmation_consumes_authorization_and_emits_registration_once(): void
    {
        config(['services.meta.pixel_id' => '1629742415604987']);

        $response = $this->withSession([
            'registro_confirmacion_autorizada' => true,
            'registro_event_id' => 'test-event-id',
        ])->get('/registro/completado');

        $response->assertOk()
            ->assertSee("fbq('track', 'PageView')", false)
            ->assertSee("window.fbq('track', 'CompleteRegistration'", false)
            ->assertSee('test-event-id', false)
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->get('/registro/completado')->assertRedirect('/login');
    }

    public function test_pixel_is_rendered_once_on_public_entry_pages(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        config(['services.meta.pixel_id' => '1629742415604987']);

        foreach (['/' => 'inicio', '/registro' => 'registro'] as $uri => $page) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertSee("fbq('init', \"1629742415604987\")", false)
                ->assertSee("fbq('track', 'PageView')", false);

            $this->assertSame(1, substr_count($response->getContent(), "fbq('track', 'PageView')"), $page);
        }
    }
}
