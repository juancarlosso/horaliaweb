<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\ChecadorSesion;
use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\Personal;
use App\Models\User;
use App\Services\EmployeeSubscriptionPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeTerminationAndSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_termination_preserves_employee_user_and_attendance_and_blocks_old_session(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = $this->company('AAA010101AAA', 'Empresa de prueba');
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Colaborador', 'pin' => '12345', 'activo' => true]);
        $employeeUser = User::factory()->create(['profile' => 3, 'personal_id' => $employee->id]);
        Asistencia::query()->create(['personal_id' => $employee->id, 'fecha' => '2026-10-12']);

        $this->actingAs($administrator)->delete(route('personal.destroy', $employee))->assertRedirect(route('personal.index', ['empresa_id' => $company->id]));

        $this->assertDatabaseHas('personal', ['id' => $employee->id, 'activo' => 0]);
        $this->assertNotNull($employee->fresh()->fecha_baja);
        $this->assertDatabaseHas('users', ['id' => $employeeUser->id, 'personal_id' => $employee->id]);
        $this->assertDatabaseCount('asistencias', 1);

        $this->actingAs($employeeUser)->get(route('home'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', ['id' => $employeeUser->id, 'personal_id' => $employee->id]);

        $this->post('/login', ['email' => $employeeUser->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $token = str_repeat('k', 48);
        ChecadorSesion::query()->create([
            'empresa_id' => $company->id,
            'user_id' => $administrator->id,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'activated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);
        $this->withHeader('X-Checador-Token', $token)
            ->postJson(route('checador.verify-pin'), ['pin' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('pin');
    }

    public function test_subscription_counts_inactive_employees_only_in_their_own_company(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = $this->company('BBB010101BBB', 'Empresa propia');
        $otherCompany = $this->company('CCC010101CCC', 'Empresa ajena');
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Activo', 'activo' => true]);
        Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Baja', 'activo' => false]);
        Personal::query()->create(['empresa_id' => $otherCompany->id, 'nombre' => 'Otra empresa', 'activo' => true]);

        $this->actingAs($administrator)
            ->get(route('subscription.index', ['empresa_id' => $company->id]))
            ->assertOk()
            ->assertSee('Empleados registrados')
            ->assertSee('>2</div>', false)
            ->assertSee('$150.00 MXN');
    }

    public function test_company_deletion_cannot_cascade_delete_registered_employees(): void
    {
        $administrator = User::factory()->create(['profile' => 2]);
        $company = $this->company('DDD010101DDD', 'Empresa con personal');
        $administrator->empresas()->attach($company->id, ['control_total' => true]);
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Conservar', 'activo' => false]);

        $this->actingAs($administrator)->delete(route('empresas.destroy', $company))->assertRedirect(route('empresas.index'));

        $this->assertDatabaseHas('personal', ['id' => $employee->id, 'empresa_id' => $company->id]);
        $this->assertDatabaseHas('empresas', ['id' => $company->id]);
    }

    public function test_renewal_price_uses_company_headcount_and_keeps_an_existing_cycle_amount(): void
    {
        config(['constantes.precio_mensual_por_empresa_mxn' => 150]);
        $company = $this->company('EEE010101EEE', 'Empresa por bloques');
        $otherCompany = $this->company('FFF010101FFF', 'Otra empresa');
        $centers = [];
        foreach (['Centro Norte', 'Centro Sur'] as $centerName) {
            $centers[] = DB::table('centros')->insertGetId([
                'empresa_id' => $company->id,
                'nombre' => $centerName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($index = 0; $index < 201; $index++) {
            Personal::query()->create([
                'empresa_id' => $company->id,
                'centro_id' => $centers[$index % 2],
                'nombre' => 'Colaborador ' . $index,
                'activo' => $index % 2 === 0,
            ]);
        }
        Personal::query()->create(['empresa_id' => $otherCompany->id, 'nombre' => 'No contar']);

        $pricing = app(EmployeeSubscriptionPricing::class);
        $this->assertSame('300.00', $pricing->prepareRenewal($company, '2026-10-12'));
        $this->assertDatabaseHas('empresas', ['id' => $company->id, 'precio' => '300.00']);

        IntentoPago::query()->create([
            'empresa_id' => $company->id,
            'fecha_renovacion' => '2026-10-12',
            'origen' => 'automatico',
            'concepto' => 'Membresía',
            'cantidad' => '300.00',
            'moneda' => 'mxn',
            'resultado' => 'pendiente',
            'idempotency_key' => 'frozen-renewal-test',
            'intentado_en' => now(),
        ]);
        Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Alta posterior']);

        $this->assertSame('300.00', $pricing->prepareRenewal($company->fresh(), '2026-10-12'));
        $this->assertDatabaseHas('intentos_pago', ['idempotency_key' => 'frozen-renewal-test', 'cantidad' => '300.00']);
    }

    private function company(string $rfc, string $name): Empresa
    {
        return Empresa::query()->create(['rfc' => $rfc, 'razon_social' => $name]);
    }
}
