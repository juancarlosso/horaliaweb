<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\Personal;
use App\Models\User;
use App\Services\VacacionesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VacacionesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function test_calculation_uses_employee_weekdays_weekend_shifts_and_holiday_work_setting(): void
    {
        [$company, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $day) $employee->horarios()->create(['dia' => $day, 'entrada' => '08:00', 'salida' => '17:00']);
        FestivoOficial::query()->create(['fecha' => '2026-11-16', 'conmemoracion' => 'Festivo oficial']);
        $service = app(VacacionesService::class);
        $calculation = $service->calculate($employee, '2026-11-16', '2026-11-20');
        $this->assertSame(4, $calculation['total']);
        $this->assertFalse(collect($calculation['dias'])->firstWhere('fecha', '2026-11-16')['cuenta']);
        $employee->horarios()->where('dia', 1)->update(['trabaja_festivos' => true]);
        $this->assertSame(5, $service->calculate($employee, '2026-11-16', '2026-11-20')['total']);

        $weekend = $this->employee('6@7')[1];
        foreach ([6, 7] as $day) $weekend->horarios()->create(['dia' => $day, 'entrada' => '08:00', 'salida' => '17:00']);
        $this->assertSame(2, $service->calculate($weekend, '2026-11-14', '2026-11-15')['total']);
    }

    public function test_company_holidays_only_exclude_scheduled_days_when_employee_has_rest(): void
    {
        [$company, $employee] = $this->employee('1');
        $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        FestivoEmpresa::query()->create(['empresa_id' => $company->id, 'fecha' => '2026-11-16', 'conmemoracion' => 'Aniversario']);
        $service = app(VacacionesService::class);
        $this->assertSame(0, $service->calculate($employee, '2026-11-16', '2026-11-16')['total']);
        $employee->horarios()->where('dia', 1)->update(['trabaja_festivos' => true]);
        $this->assertSame(1, $service->calculate($employee, '2026-11-16', '2026-11-16')['total']);
    }

    public function test_counted_dates_are_persisted_and_stay_unchanged_after_schedule_edits_and_cancellation_allows_reuse(): void
    {
        [$company, $employee] = $this->employee('1@2');
        $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $employee->horarios()->create(['dia' => 2, 'entrada' => '08:00', 'salida' => '17:00']);
        $user = User::factory()->create(['profile' => 1]);
        $service = app(VacacionesService::class);
        $data = ['empresa_id' => $company->id, 'personal_id' => $employee->id, 'autorizado_por_personal_id' => $employee->id, 'fecha_inicio' => '2026-11-16', 'fecha_fin' => '2026-11-17'];
        $record = $service->register($data, $user->id);
        $this->assertSame(['2026-11-16', '2026-11-17'], $record->fechas()->pluck('fecha')->map(fn($v) => substr((string)$v, 0, 10))->all());
        $employee->horarios()->delete();
        $this->assertSame(2, $record->fresh()->total_dias);
        $this->assertCount(2, $record->fresh()->fechas);
        try { $service->register($data, $user->id); $this->fail('Se esperaba rechazar traslape.'); }
        catch (ValidationException $exception) { $this->assertArrayHasKey('fecha_inicio', $exception->errors()); }
        $record->update(['estado' => 'cancelado', 'cancelado_por_user_id' => $user->id, 'cancelado_en' => now()]);
        $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $employee->horarios()->create(['dia' => 2, 'entrada' => '08:00', 'salida' => '17:00']);
        $replacement = $service->register($data, $user->id);
        $this->assertSame('registrado', $replacement->estado);
    }

    public function test_period_crossing_year_counts_days_and_empty_period_is_rejected(): void
    {
        [, $employee] = $this->employee('4@5');
        foreach ([4, 5] as $day) $employee->horarios()->create(['dia' => $day, 'entrada' => '08:00', 'salida' => '17:00']);
        $calculation = app(VacacionesService::class)->calculate($employee, '2026-12-31', '2027-01-01');
        $this->assertSame(2, $calculation['total']);
        $this->assertSame(['2026-12-31', '2027-01-01'], $calculation['fechas_contabilizadas']);
        $empty = $this->employee('');
        $user = User::factory()->create(['profile' => 1]);
        try {
            app(VacacionesService::class)->register(['empresa_id' => $empty[0]->id, 'personal_id' => $empty[1]->id, 'autorizado_por_personal_id' => $empty[1]->id, 'fecha_inicio' => '2026-11-16', 'fecha_fin' => '2026-11-16'], $user->id);
            $this->fail('Se esperaba rechazar periodos sin días contabilizables.');
        } catch (ValidationException $exception) { $this->assertArrayHasKey('fecha_inicio', $exception->errors()); }
    }

    public function test_period_across_multiple_weeks_excludes_weekends_but_counts_every_scheduled_weekday(): void
    {
        [, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $day) $employee->horarios()->create(['dia' => $day, 'entrada' => '08:00', 'salida' => '17:00']);
        $calculation = app(VacacionesService::class)->calculate($employee, '2026-11-16', '2026-11-27');
        $this->assertSame(10, $calculation['total']);
        $this->assertCount(12, $calculation['dias']);
        $this->assertCount(10, $calculation['fechas_contabilizadas']);
    }

    public function test_vacation_snapshot_persists_included_excluded_days_and_modal_displays_the_breakdown(): void
    {
        [$company, $employee] = $this->employee('1@2@3@4@5');
        foreach (range(1, 5) as $day) $employee->horarios()->create(['dia' => $day, 'entrada' => '08:00', 'salida' => '17:00']);
        FestivoOficial::query()->create(['fecha' => '2026-11-16', 'conmemoracion' => 'Revolución Mexicana']);
        $user = User::factory()->create(['profile' => 1]);
        $record = app(VacacionesService::class)->register([
            'empresa_id' => $company->id, 'personal_id' => $employee->id,
            'autorizado_por_personal_id' => $employee->id, 'fecha_inicio' => '2026-11-16', 'fecha_fin' => '2026-11-20',
        ], $user->id);

        $this->assertSame(5, $record->detalles()->count());
        $holiday = $record->detalles()->whereDate('fecha', '2026-11-16')->firstOrFail();
        $this->assertFalse($holiday->cuenta);
        $this->assertSame('Festivo con descanso', $holiday->motivo);
        $this->assertSame(['Revolución Mexicana'], $holiday->festivos);
        $this->assertSame(4, $record->fechas()->count());

        $this->actingAs($user)->get(route('vacaciones.index', ['empresa_id' => $company->id]))
            ->assertOk()->assertDontSee('Ver fechas contabilizadas')
            ->assertSee('data-vacation-info=', false)
            ->assertSee(config('constantes.tipo_iconos').' fa-circle-info', false)
            ->assertSee('Información del registro')
            ->assertSee('2026-11-16', false);
    }

    public function test_company_holiday_is_scoped_to_its_company(): void
    {
        [$first, $firstEmployee] = $this->employee('1');
        [$second, $secondEmployee] = $this->employee('1');
        $firstEmployee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $secondEmployee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        FestivoEmpresa::query()->create(['empresa_id' => $first->id, 'fecha' => '2026-11-16', 'conmemoracion' => 'Local']);
        $service = app(VacacionesService::class);
        $this->assertSame(0, $service->calculate($firstEmployee, '2026-11-16', '2026-11-16')['total']);
        $this->assertSame(1, $service->calculate($secondEmployee, '2026-11-16', '2026-11-16')['total']);
    }

    public function test_vacation_list_filters_records_by_the_selected_employee(): void
    {
        [$company, $firstEmployee] = $this->employee('1');
        $secondEmployee = Personal::query()->create([
            'empresa_id' => $company->id, 'nombre' => 'Otro colaborador',
            'pin' => str_pad((string)(Personal::query()->count() + 1), 5, '0', STR_PAD_LEFT),
            'activo' => true, 'laborados' => '1',
        ]);
        $firstEmployee->update(['nombre' => 'Colaborador filtrado']);
        $firstEmployee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $secondEmployee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $user = User::factory()->create(['profile' => 1]);
        $service = app(VacacionesService::class);
        foreach ([[$firstEmployee, '2026-11-16'], [$secondEmployee, '2026-11-23']] as [$employee, $date]) {
            $service->register([
                'empresa_id' => $company->id, 'personal_id' => $employee->id,
                'autorizado_por_personal_id' => $employee->id, 'fecha_inicio' => $date, 'fecha_fin' => $date,
            ], $user->id);
        }

        $response = $this->actingAs($user)->get(route('vacaciones.index', [
            'empresa_id' => $company->id, 'personal_id' => $firstEmployee->id,
        ]))->assertOk()->assertSee('Colaborador filtrado')->assertSee('Personal')->assertSee('personal_id');
        $html = $response->getContent();
        $tableStart = strpos($html, '<table class="bs-table hover">');
        $tableEnd = strpos($html, '</table>', $tableStart);
        $table = substr($html, $tableStart, $tableEnd - $tableStart);
        $this->assertStringNotContainsString('Otro colaborador', $table);
    }

    public function test_http_registration_ignores_client_total_and_cancel_preserves_history(): void
    {
        [$company, $employee] = $this->employee('1');
        $employee->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $user = User::factory()->create(['profile' => 1]);
        $this->actingAs($user)->get(route('vacaciones.create', ['empresa_id' => $company->id]))
            ->assertOk()->assertSee('Personal que autorizó')->assertSee('Selecciona colaborador y fechas')
            ->assertSee('class="text-danger" aria-hidden="true">*</span>', false)->assertDontSee('Dato informativo')
            ->assertSee("title:'¿Registrar vacaciones?'", false)->assertDontSee("confirm('¿Confirmas registrar", false)
            ->assertSee(config('constantes.tipo_iconos').' fa-umbrella-beach', false)->assertSee(config('constantes.tipo_iconos').' fa-house', false)->assertSee(config('constantes.tipo_iconos').' fa-building', false)
            ->assertSee('fa-calendar-clock')->assertDontSee('partials.icon');
        $this->actingAs($user)->postJson(route('vacaciones.preview'), [
            'empresa_id' => $company->id, 'personal_id' => $employee->id,
            'fecha_inicio' => '2026-11-16', 'fecha_fin' => '2026-11-16', 'total_dias' => 0,
        ])->assertOk()->assertJsonPath('total', 1);
        $this->actingAs($user)->post(route('vacaciones.store'), [
            'empresa_id' => $company->id, 'personal_id' => $employee->id,
            'autorizado_por_personal_id' => $employee->id, 'fecha_inicio' => '2026-11-16',
            'fecha_fin' => '2026-11-16', 'total_dias' => 99, 'estado' => 'cancelado',
        ])->assertRedirect(route('vacaciones.index', ['empresa_id' => $company->id]));
        $listResponse = $this->get(route('vacaciones.index', ['empresa_id' => $company->id]))->assertOk();
        $this->assertSame(1, substr_count($listResponse->getContent(), 'El periodo vacacional se registró correctamente.'));
        $listResponse->assertSee('class="catalog-page"', false)
            ->assertSee('Listado de periodos vacacionales')
            ->assertSee('class="bs-table hover"', false)
            ->assertSee('class="catalog-toolbar"', false);
        $record = \App\Models\Vacacion::query()->firstOrFail();
        $this->assertSame(1, $record->total_dias);
        $this->assertSame('registrado', $record->estado);
        $this->actingAs($user)->delete(route('vacaciones.cancel', $record))->assertRedirect();
        $record->refresh();
        $this->assertSame('cancelado', $record->estado);
        $this->assertNotNull($record->cancelado_en);
        $this->assertSame(1, $record->fechas()->count());
    }

    public function test_users_without_company_management_cannot_open_vacations_module(): void
    {
        [$company, $employee] = $this->employee('1');
        $user = User::factory()->create(['profile' => 3, 'personal_id' => $employee->id]);
        $user->empresas()->attach($company->id, ['control_total' => false]);
        $this->actingAs($user)->get(route('vacaciones.index'))->assertForbidden();
    }

    public function test_sidebar_uses_font_awesome_icons_for_all_three_profiles(): void
    {
        config(['constantes.tipo_iconos' => 'fa-duotone']);
        [$company, $employee] = $this->employee('1');
        foreach ([1, 2, 3] as $profile) {
            $user = User::factory()->create(['profile' => $profile, 'personal_id' => $profile === 3 ? $employee->id : null]);
            if ($profile > 1) $user->empresas()->attach($company->id, ['control_total' => $profile === 2]);
            $html = $this->actingAs($user)->get(route('profile.edit'))->assertOk()->getContent();
            $start = strpos($html, '<nav class="sidebar');
            $end = strpos($html, '<main class="page-content">', $start);
            $sidebar = substr($html, $start, $end - $start);
            $this->assertStringNotContainsString('<svg', $sidebar, "El perfil {$profile} aún muestra SVG en el menú.");
            $this->assertSame(substr_count($sidebar, '<i class='), substr_count($sidebar, config('constantes.tipo_iconos')), "El perfil {$profile} tiene iconos fuera de tipo_iconos.");
        }
    }

    private function employee(string $laborados): array
    {
        $suffix = str_pad((string)(Empresa::query()->count() + 1), 3, '0', STR_PAD_LEFT);
        $company = Empresa::query()->create(['rfc' => 'VACA'.$suffix.'AAA', 'razon_social' => 'Empresa Vacaciones '.$suffix, 'direccion' => 'Calle prueba 1', 'telefono' => '5500000000', 'activa' => true]);
        $employee = Personal::query()->create(['empresa_id' => $company->id, 'nombre' => 'Colaborador '.$suffix, 'pin' => str_pad((string)(Personal::query()->count()+1), 5, '0', STR_PAD_LEFT), 'activo' => true, 'laborados' => $laborados]);
        return [$company, $employee];
    }
}
