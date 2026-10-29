<?php

namespace Tests\Feature;

use Tests\TestCase;

class ImplementationArticleTest extends TestCase
{
    public function test_implementation_article_is_public_and_has_search_metadata_and_real_cta(): void
    {
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $response = $this->get('/blog/como-implementar-reloj-checador-empleados');
        if ($previousHost === null) {
            unset($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = $previousHost;
        }

        $response->assertOk()
            ->assertSee('<link rel="canonical" href="https://horalia.mx/blog/como-implementar-reloj-checador-empleados">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('href="/#implementacion-sin-resistencia"', false)
            ->assertSee('Cómo implementar un reloj checador en tu empresa sin generar resistencia entre tus empleados')
            ->assertSee('href="' . route('registro.create') . '"', false);
    }
}
