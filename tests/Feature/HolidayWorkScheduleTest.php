<?php

namespace Tests\Feature;

use App\Models\Centro;
use App\Models\Empresa;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\Personal;
use App\Models\User;
use App\Services\RegistroAsistenciaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HolidayWorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_holiday_work_is_false_by_default_and_saved_independently_per_workday(): void
    {
        $company = Empresa::query()->create([
            'rfc' => 'HORA010101AAA',
            'razon_social' => 'Empresa Horalia',
            'direccion' => 'Calle de prueba 123',
            'telefono' => '5500000000',
            'activa' => true,
        ]);
        $center = Centro::query()->create([
            'empresa_id' => $company->id,
            'nombre' => 'Centro de prueba',
            'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332',
            'timezone' => 'America/Mexico_City',
        ]);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id,
            'centro_id' => $center->id,
            'nombre' => 'Colaborador de prueba',
            'pin' => '12345',
            'activo' => true,
            'laborados' => '1@2',
        ]);

        $monday = $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '17:00:00']);
        $tuesday = $personal->horarios()->create(['dia' => 2, 'entrada' => '08:00:00', 'salida' => '17:00:00']);

        $this->assertFalse($monday->fresh()->trabaja_festivos);
        $this->assertFalse($tuesday->fresh()->trabaja_festivos);

        $monday->update(['trabaja_festivos' => true, 'modalidad' => 'HOME_OFFICE']);

        $this->assertTrue($monday->fresh()->trabaja_festivos);
        $this->assertSame('HOME_OFFICE', $monday->fresh()->modalidad);
        $this->assertFalse($tuesday->fresh()->trabaja_festivos);
    }

    public function test_official_and_company_holidays_block_entry_when_switch_is_off_and_allow_it_when_on(): void
    {
        [$company, $personal, $monday] = $this->employeeWithMondaySchedule();
        $date = '2026-07-06';
        $this->travelTo(Carbon::parse($date.' 09:00:00', 'America/Mexico_City'));
        FestivoOficial::query()->create(['fecha' => $date, 'conmemoracion' => 'Festivo oficial']);
        FestivoEmpresa::query()->create(['empresa_id' => $company->id, 'fecha' => $date, 'conmemoracion' => 'Aniversario empresarial']);

        $registration = app(RegistroAsistenciaService::class);
        $this->assertSame(['Festivo oficial', 'Aniversario empresarial'], $registration->holidayCommemorations($personal, $date)->all());
        $this->assertCount(2, $registration->holidayCommemorations($personal, $date));
        try {
            $registration->preview($personal);
            $this->fail('La vista previa debe impedir una entrada en festivo si el switch está apagado.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('día festivo', $exception->errors()['pin'][0]);
        }

        try {
            $registration->register($personal, '127.0.0.1', null, null, 'entrada');
            $this->fail('El registro directo también debe impedir una entrada en festivo.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('día festivo', $exception->errors()['pin'][0]);
        }

        $monday->update(['trabaja_festivos' => true]);
        $this->assertSame('entrada', $registration->preview($personal));
        $this->assertSame('entrada', $registration->register($personal, '127.0.0.1', null, null, 'entrada'));
        $this->assertDatabaseHas('asistencias', ['personal_id' => $personal->id, 'fecha' => $date.' 00:00:00']);
    }

    public function test_company_holiday_does_not_apply_to_another_company(): void
    {
        [$firstCompany, $firstEmployee] = $this->employeeWithMondaySchedule();
        [, $secondEmployee] = $this->employeeWithMondaySchedule('HORA010101AAB');
        $date = '2026-07-06';
        $this->travelTo(Carbon::parse($date.' 09:00:00', 'America/Mexico_City'));
        FestivoEmpresa::query()->create(['empresa_id' => $firstCompany->id, 'fecha' => $date, 'conmemoracion' => 'Día de la empresa']);

        $registration = app(RegistroAsistenciaService::class);
        $this->assertSame(['Día de la empresa'], $registration->holidayCommemorations($firstEmployee, $date)->all());
        $this->assertSame([], $registration->holidayCommemorations($secondEmployee, $date)->all());
        $this->assertSame('entrada', $registration->preview($secondEmployee));
    }

    public function test_attendance_page_shows_holiday_warning_and_disables_entry_when_switch_is_off(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        [$company, $personal] = $this->employeeWithMondaySchedule();
        $date = '2026-07-06';
        $this->travelTo(Carbon::parse($date.' 09:00:00', 'America/Mexico_City'));
        FestivoOficial::query()->create(['fecha' => $date, 'conmemoracion' => 'Conmemoración de prueba']);
        $user = User::factory()->create(['profile' => 3, 'personal_id' => $personal->id]);
        $user->empresas()->attach($company->id, ['control_total' => false]);

        $response = $this->withServerVariables(['HTTP_HOST' => 'localhost'])
            ->actingAs($user)->get(route('asistencia.index'));

        $response->assertOk()
            ->assertSee('Hoy es día festivo.')
            ->assertSee('Conmemoración de prueba')
            ->assertSee('Hoy es festivo y no tienes habilitado trabajar en días festivos.')
            ->assertSee('Registrar entrada');
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>.*Registrar entrada/s', $response->getContent());
    }

    private function employeeWithMondaySchedule(string $rfc = 'HORA010101AAA'): array
    {
        $company = Empresa::query()->create([
            'rfc' => $rfc,
            'razon_social' => 'Empresa Horalia',
            'direccion' => 'Calle de prueba 123',
            'telefono' => '5500000000',
            'activa' => true,
        ]);
        $center = Centro::query()->create([
            'empresa_id' => $company->id,
            'nombre' => 'Centro de prueba',
            'activo' => true,
            'geolocalizacion' => '19.4326,-99.1332',
            'timezone' => 'America/Mexico_City',
        ]);
        $personal = Personal::query()->create([
            'empresa_id' => $company->id,
            'centro_id' => $center->id,
            'nombre' => 'Colaborador de prueba',
            'pin' => str_pad((string) (Personal::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'activo' => true,
            'laborados' => '1',
        ]);
        $schedule = $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00:00', 'salida' => '17:00:00']);

        return [$company, $personal, $schedule];
    }
}
