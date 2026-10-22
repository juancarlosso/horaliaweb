<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AppAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_administrator_can_log_in_restore_session_and_revoke_only_current_token(): void
    {
        [$user, $company] = $this->administrator();
        $otherToken = $user->createToken('another-device', ['horalia-app', 'empresa:' . $company->id])->plainTextToken;

        $login = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.empresa.id', $company->id)
            ->assertJsonPath('data.empresa.nombre', $company->razon_social)
            ->assertJsonPath('data.empresa.direccion', $company->direccion)
            ->assertJsonPath('data.empresa.telefono', $company->telefono)
            ->assertJsonMissingPath('data.user.password');

        $token = $login->json('data.token');
        $this->withToken($token)->getJson('/api/app/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.empresa.id', $company->id);

        $this->withToken($token)->postJson('/api/app/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(1, $user->tokens()->count());
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/app/me')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken($otherToken)->getJson('/api/app/me')->assertOk();
    }

    public function test_login_rejects_bad_credentials_with_the_same_response(): void
    {
        [$user] = $this->administrator();

        $wrongPassword = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $unknownEmail = $this->postJson('/api/app/login', [
            'email' => 'unknown@example.test',
            'password' => 'wrong-password',
        ]);

        $wrongPassword->assertUnauthorized()->assertExactJson($unknownEmail->json());
    }

    public function test_login_returns_validation_errors_for_missing_and_invalid_fields(): void
    {
        $this->postJson('/api/app/login', [])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/app/login', [
            'email' => 'not-an-email',
            'password' => 'secret',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_login_rejects_users_without_company_administrator_permissions(): void
    {
        $company = $this->company();

        $nonAdministrator = User::factory()->create([
            'profile' => 3,
            'password' => Hash::make('correct-password'),
        ]);
        $nonAdministrator->empresas()->attach($company->id, ['control_total' => true]);

        $noControl = User::factory()->create([
            'profile' => 2,
            'password' => Hash::make('correct-password'),
        ]);
        $noControl->empresas()->attach($company->id, ['control_total' => false]);

        foreach ([$nonAdministrator, $noControl] as $user) {
            $this->postJson('/api/app/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertForbidden()->assertJsonPath('success', false);
        }
    }

    public function test_login_rejects_inactive_company_and_inactive_linked_user(): void
    {
        [$inactiveCompanyUser] = $this->administrator(false);
        [$inactivePersonalUser, $activeCompany] = $this->administrator();
        $personal = Personal::create([
            'codigo' => 'INACTIVE-1',
            'nombre' => 'Administrador inactivo',
            'email' => $inactivePersonalUser->email,
            'activo' => false,
            'empresa_id' => $activeCompany->id,
        ]);
        $inactivePersonalUser->update(['personal_id' => $personal->id]);

        foreach ([$inactiveCompanyUser, $inactivePersonalUser] as $user) {
            $this->postJson('/api/app/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertForbidden();
        }
    }

    public function test_me_rejects_invalid_and_revoked_tokens(): void
    {
        $this->withToken('not-a-real-token')->getJson('/api/app/me')->assertUnauthorized();

        [$user] = $this->administrator();
        $token = $user->createToken('revoked-device', ['horalia-app'])->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($token)->getJson('/api/app/me')->assertUnauthorized();

        $expiredToken = $user->createToken(
            'expired-device',
            ['horalia-app', 'empresa:' . $user->empresas()->first()->id],
            now()->subMinute()
        )->plainTextToken;
        Auth::forgetGuards();
        $this->withToken($expiredToken)->getJson('/api/app/me')->assertUnauthorized();
    }

    public function test_mobile_session_survives_inactivity_and_still_ends_on_explicit_logout(): void
    {
        [$user] = $this->administrator();
        $token = $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk()->json('data.token');

        $this->assertNull($user->tokens()->latest('id')->firstOrFail()->expires_at);
        $this->travel(45)->days();

        $this->withToken($token)->getJson('/api/app/me')->assertOk();
        $this->withToken($token)->postJson('/api/app/logout')->assertOk();
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/app/me')->assertUnauthorized();
    }

    public function test_login_is_rate_limited_for_repeated_attempts(): void
    {
        [$user] = $this->administrator();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/app/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/app/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_existing_web_login_still_uses_the_session_flow(): void
    {
        [$user] = $this->administrator();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);
    }

    private function administrator(bool $companyActive = true): array
    {
        $company = $this->company($companyActive);
        $user = User::factory()->create([
            'profile' => 2,
            'password' => Hash::make('correct-password'),
        ]);
        $user->empresas()->attach($company->id, ['control_total' => true]);

        return [$user, $company];
    }

    private function company(bool $active = true): Empresa
    {
        return Empresa::create([
            'rfc' => strtoupper(fake()->unique()->bothify('????######???')),
            'razon_social' => 'Empresa Horalia',
            'direccion' => 'Calle de prueba 123',
            'telefono' => '5500000000',
            'activa' => $active,
        ]);
    }
}
