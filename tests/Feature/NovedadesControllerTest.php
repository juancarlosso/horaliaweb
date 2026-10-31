<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovedadesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        Carbon::setTestNow('2026-10-29 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_list_shows_all_published_novelties_without_email_or_registration_filters(): void
    {
        $user = $this->account('admin@example.test', '2030-10-01 10:00:00', 3);
        $futurePath = base_path('documentacion/novedades/261030-futura.md');
        file_put_contents($futurePath, "# Novedad futura\n\nTodavía no debe aparecer.");

        try {
            $response = $this->actingAs($user)->get(route('novedades.index'));
            $response
                ->assertOk()
                ->assertSee('Novedades de Horalia')
                ->assertSee('Continuar leyendo')
                ->assertSee('target="_blank" rel="noopener noreferrer"', false)
                ->assertSee('Nuevo artículo: Cómo implementar un reloj checador sin generar resistencia')
                ->assertDontSee('Novedad futura');
            $this->assertSame(5, substr_count($response->getContent(), 'class="news-card"'));
            $this->assertDoesNotMatchRegularExpression('/<a class="news-read"[^>]*target="_blank"/', $response->getContent());
        } finally {
            unlink($futurePath);
        }

        $this->get(route('novedades.show', '261029'))->assertOk();
    }

    public function test_detail_renders_markdown_and_opens_links_safely(): void
    {
        $user = $this->account('detail@example.test', '2026-10-01 10:00:00');
        $path = base_path('documentacion/novedades/261028-prueba.md');
        file_put_contents($path, "# Novedad **segura**\n\nTexto con [enlace](https://example.test) y [enlace inseguro](javascript:alert%281%29).\n\n<script>alert(1)</script>\n");

        try {
            $response = $this->actingAs($user)->get(route('novedades.show', '261028-prueba'));
            $response
                ->assertOk()
                ->assertSee('<strong>segura</strong>', false)
                ->assertSee('target="_blank" rel="noopener noreferrer"', false)
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('href="javascript:', false);
            $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        } finally {
            unlink($path);
        }
    }

    public function test_list_is_paged_in_ten_item_chunks(): void
    {
        $user = $this->account('pages@example.test', '2026-10-01 10:00:00');
        $paths = [];
        foreach (range(1, 12) as $day) {
            $name = sprintf('2610%02d-prueba.md', $day + 1);
            $paths[] = base_path('documentacion/novedades/'.$name);
            file_put_contents(end($paths), "# Novedad {$day}\n\nResumen de prueba.");
        }

        try {
            $firstPage = $this->actingAs($user)->get(route('novedades.index'));
            $firstPage->assertOk()->assertSee('Novedad 12')->assertDontSee('Novedad 1</h2>');
            $this->assertSame(10, substr_count($firstPage->getContent(), 'class="news-card"'));
            $this->get(route('novedades.index', ['page' => 2]))
                ->assertOk()
                ->assertSee('Novedad 1');
        } finally {
            foreach ($paths as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_authenticated_users_can_view_novelties_regardless_of_profile(): void
    {
        $user = User::factory()->create(['profile' => 3, 'created_at' => '2026-10-01 10:00:00']);
        $this->actingAs($user)->get(route('novedades.show', '261029'))->assertOk();
    }

    public function test_novelty_is_visible_even_when_its_email_was_not_sent_to_the_user(): void
    {
        $user = $this->account('unsent@example.test', '2026-10-01 10:00:00');
        $path = base_path('documentacion/novedades/261028-sin-correo.md');
        file_put_contents($path, "# Novedad sin correo\n\nDebe aparecer en el historial.");

        try {
            $this->actingAs($user)->get(route('novedades.index'))->assertOk()->assertSee('Novedad sin correo');
            $this->get(route('novedades.show', '261028-sin-correo'))->assertOk();
        } finally {
            unlink($path);
        }
    }

    public function test_dashboard_footer_renders_one_random_promotion_with_safe_links(): void
    {
        $user = $this->account('footer@example.test', '2026-10-01 10:00:00');
        $headlines = array_column(config('constantes.promociones_footer'), 'headline');
        $seen = [];

        foreach (range(1, 40) as $_) {
            $content = $this->actingAs($user)->get(route('novedades.index'))->assertOk()->getContent();
            $matches = array_values(array_filter($headlines, fn (string $headline) => str_contains($content, $headline)));

            $this->assertCount(1, $matches);
            $this->assertSame(1, substr_count($content, 'class="app-footer-cta"'));
            $this->assertSame(2, substr_count($content, 'target="_blank" rel="noopener noreferrer"'));
            $this->assertStringContainsString('href="https://wa.me/525513447932?text=Quiero%20hablar%20de%20mi%20proyecto"', $content);
            $this->assertStringNotContainsString('Horalia es un producto de', $content);
            $seen[$matches[0]] = true;
        }

        $this->assertCount(5, $seen);
    }

    public function test_checador_administration_page_does_not_render_the_promotional_footer(): void
    {
        $user = $this->account('checador-footer@example.test', '2026-10-01 10:00:00');
        $company = Empresa::query()->create([
            'rfc' => 'FTR010101AAA',
            'razon_social' => 'Empresa para validar footer',
            'activa' => true,
        ]);
        $user->empresas()->attach($company->id, ['control_total' => true]);

        $this->actingAs($user)
            ->get(route('checador.index'))
            ->assertOk()
            ->assertDontSee('class="app-footer"', false)
            ->assertDontSee('Horalia es un producto de');
    }

    public function test_promotional_footer_is_hidden_from_non_company_admin_profiles(): void
    {
        foreach ([1, 3] as $profile) {
            $user = $this->account("profile-{$profile}-footer@example.test", '2026-10-01 10:00:00', $profile);

            $this->actingAs($user)
                ->get(route('novedades.index'))
                ->assertOk()
                ->assertDontSee('class="app-footer"', false);
        }
    }

    private function account(string $email, string $createdAt, int $profile = 2): User
    {
        return User::factory()->create(['email' => $email, 'profile' => $profile, 'created_at' => $createdAt]);
    }
}
