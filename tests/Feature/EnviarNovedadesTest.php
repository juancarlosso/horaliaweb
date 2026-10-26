<?php

namespace Tests\Feature;

use App\Mail\NovedadesSistemaMail;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnviarNovedadesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_administrators_do_not_receive_historical_novelties(): void
    {
        Mail::fake();
        $existing = $this->administrator('anterior@example.test', '2026-10-01 10:00:00');
        $this->administrator('nuevo@example.test', '2026-10-21 10:00:00');
        $this->administrator('mismo-dia@example.test', '2026-10-20 08:00:00');

        $this->artisan('novedades:enviar', ['archivo' => '261020', '--dry-run' => true])
            ->expectsOutput('261020.md: 1 destinatario(s) pendiente(s).')
            ->assertExitCode(0);

        $this->artisan('novedades:enviar', ['archivo' => '261020'])->assertExitCode(0);

        Mail::assertSent(NovedadesSistemaMail::class, 1);
        Mail::assertSent(NovedadesSistemaMail::class, fn (NovedadesSistemaMail $mail) => $mail->hasTo($existing->email));
        $this->assertDatabaseCount('novedades_enviadas', 1);

        $this->artisan('novedades:enviar', ['archivo' => '261020'])->assertExitCode(0);
        Mail::assertSent(NovedadesSistemaMail::class, 1);
    }

    public function test_timestamped_novelty_reaches_only_accounts_created_before_publication(): void
    {
        Mail::fake();
        $before = $this->administrator('antes@example.test', '2026-10-20 10:00:00');
        $this->administrator('despues@example.test', '2026-10-20 13:00:00');
        $path = base_path('documentacion/novedades/2610201200-prueba.md');
        file_put_contents($path, '# Novedad de prueba');

        try {
            $this->artisan('novedades:enviar', ['archivo' => '2610201200-prueba'])->assertExitCode(0);
            Mail::assertSent(NovedadesSistemaMail::class, 1);
            Mail::assertSent(NovedadesSistemaMail::class, fn (NovedadesSistemaMail $mail) => $mail->hasTo($before->email));
        } finally {
            unlink($path);
        }
    }

    private function administrator(string $email, string $createdAt): User
    {
        $company = Empresa::query()->create([
            'nombre' => 'Empresa '.$email,
            'rfc' => 'AAA'.strtoupper(substr(hash('sha256', $email), 0, 10)),
        ]);
        $user = User::factory()->create([
            'email' => $email,
            'profile' => 2,
            'created_at' => $createdAt,
        ]);
        $user->empresas()->attach($company->id);

        return $user;
    }
}
