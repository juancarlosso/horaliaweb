<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeleteCompanyCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
        Storage::fake('wasabi');
        Storage::fake('public');
        Storage::fake('local');
        config(['services.stripe.key' => 'sk_test_company_delete']);
        Http::fake(['https://api.stripe.com/v1/customers/cus_company_delete' => Http::response(['id' => 'cus_company_delete', 'deleted' => true])]);
    }

    public function test_deleting_company_removes_local_company_data_and_its_stripe_customer(): void
    {
        $admin = User::factory()->create(['profile' => 1]);
        $company = Empresa::query()->create([
            'rfc' => 'AAA010101AAA',
            'razon_social' => 'Empresa para eliminar',
            'logo' => 'empresas/logos/company-delete.png',
            'stripe_customer_id' => 'cus_company_delete',
        ]);
        Storage::disk('public')->put('empresas/logos/company-delete.png', 'logo');

        $personalId = DB::table('personal')->insertGetId([
            'empresa_id' => $company->id,
            'nombre' => 'Empleado de prueba',
            'foto' => 'personal/perfiles/empresa-' . $company->id . '/employee.png',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::disk('wasabi')->put('personal/perfiles/empresa-' . $company->id . '/employee.png', 'foto');
        $employee = User::factory()->create(['profile' => 3, 'personal_id' => $personalId]);
        $employee->empresas()->attach($company->id, ['control_total' => false]);

        $attendanceId = DB::table('asistencias')->insertGetId([
            'personal_id' => $personalId,
            'fecha' => today()->toDateString(),
            'foto_entrada' => 'asistencias/empresa-' . $company->id . '/in.jpg',
            'foto_salida' => 'asistencias/empresa-' . $company->id . '/out.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::disk('wasabi')->put('asistencias/empresa-' . $company->id . '/in.jpg', 'foto');
        Storage::disk('wasabi')->put('asistencias/empresa-' . $company->id . '/out.jpg', 'foto');

        $attempt = IntentoPago::query()->create([
            'empresa_id' => $company->id,
            'fecha_renovacion' => today(),
            'origen' => 'automatico',
            'concepto' => 'Membresía',
            'cantidad' => '150.00',
            'moneda' => 'mxn',
            'resultado' => 'exitoso',
            'idempotency_key' => Str::uuid()->toString(),
            'intentado_en' => now(),
        ]);
        DB::table('facturas')->insert([
            'intento_pago_id' => $attempt->id,
            'usuario_id' => $admin->id,
            'serie' => 'H',
            'folio' => 1234,
            'estado' => 'timbrada',
            'modo_sifei' => 'produccion',
            'uso_cfdi' => 'G03',
            'forma_pago_sat' => '04',
            'correo_facturacion' => 'facturas@example.test',
            'subtotal' => '129.31',
            'iva' => '20.69',
            'total' => '150.00',
            'xml_path' => 'facturas/H/H-1234.xml',
            'xml_staging_path' => 'facturas/staging/1/H-1234.xml',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Storage::disk('wasabi')->put('facturas/H/H-1234.xml', 'xml');
        Storage::disk('local')->put('facturas/staging/1/H-1234.xml', 'xml');
        DB::table('payment_method_consents')->insert([
            'empresa_id' => $company->id,
            'user_id' => $admin->id,
            'stripe_payment_method_id' => 'pm_company_delete',
            'terms_version' => 'v1',
            'consent_text' => 'consentimiento',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('empresas.index'))
            ->assertOk()
            ->assertSee('todos sus datos relacionados de Horalia')
            ->assertSee('movimientos que Stripe o el SAT deban conservar pueden permanecer', false)
            ->assertSee('Eliminando empresa')
            ->assertSee('fa-spinner fa-spin');

        $this->actingAs($admin)->delete(route('empresas.destroy', $company))->assertRedirect(route('empresas.index'));

        $this->assertDatabaseMissing('empresas', ['id' => $company->id]);
        $this->assertDatabaseMissing('personal', ['id' => $personalId]);
        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
        $this->assertDatabaseMissing('asistencias', ['id' => $attendanceId]);
        $this->assertDatabaseMissing('intentos_pago', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('facturas', ['intento_pago_id' => $attempt->id]);
        $this->assertDatabaseMissing('payment_method_consents', ['empresa_id' => $company->id]);
        $this->assertDatabaseMissing('users_empresas', ['empresa_id' => $company->id]);
        Storage::disk('public')->assertMissing('empresas/logos/company-delete.png');
        Storage::disk('wasabi')->assertMissing('personal/perfiles/empresa-' . $company->id . '/employee.png');
        Storage::disk('wasabi')->assertMissing('asistencias/empresa-' . $company->id . '/in.jpg');
        Storage::disk('wasabi')->assertMissing('asistencias/empresa-' . $company->id . '/out.jpg');
        Storage::disk('wasabi')->assertMissing('facturas/H/H-1234.xml');
        Storage::disk('local')->assertMissing('facturas/staging/1/H-1234.xml');
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/customers/cus_company_delete'));
    }
}
