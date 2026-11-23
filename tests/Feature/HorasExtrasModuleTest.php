<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Centro;
use App\Models\Empresa;
use App\Models\FestivoEmpresa;
use App\Models\FestivoOficial;
use App\Models\HoraExtra;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HorasExtrasModuleTest extends TestCase
{
    use RefreshDatabase;

    private const FECHA = '2026-11-02';

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->travelTo(now()->setDate(2026, 11, 10));
    }

    public static function jornadas(): array
    {
        return [
            'salida posterior' => [true, false, null, '08:00', '17:00', '2026-11-02 19:15', 135, ['Tiempo adicional']],
            'salida exacta' => [true, false, null, '08:00', '17:00', '2026-11-02 17:00', null, []],
            'salida anterior' => [true, false, null, '08:00', '17:00', '2026-11-02 16:59', null, []],
            'festivo oficial no laborable' => [true, false, 'oficial', '08:00', '17:00', '2026-11-02 17:00', 540, ['Festivo trabajado']],
            'descanso' => [false, false, null, '08:00', '17:00', '2026-11-02 17:00', 540, ['Descanso trabajado']],
            'festivo empresarial y descanso' => [false, false, 'empresa', '08:00', '17:00', '2026-11-02 17:00', 540, ['Festivo trabajado', 'Descanso trabajado']],
            'festivo programado informativo' => [true, true, 'oficial', '08:00', '17:00', '2026-11-02 17:00', 0, ['Festivo trabajado']],
            'festivo programado con tiempo adicional' => [true, true, 'empresa', '08:00', '17:00', '2026-11-02 18:00', 60, ['Festivo trabajado', 'Tiempo adicional']],
            'nocturno' => [true, false, null, '22:00', '06:00', '2026-11-03 07:15', 75, ['Tiempo adicional']],
            'sin salida' => [true, false, null, '08:00', '17:00', null, null, []],
            'salida en el futuro' => [true, false, null, '08:00', '17:00', '2026-11-11 07:00', null, []],
            'salida inválida' => [true, false, null, '08:00', '17:00', '2026-11-02 07:59', null, []],
        ];
    }

    #[DataProvider('jornadas')]
    public function test_busca_jornadas_consolidadas(
        bool $laborable, bool $trabajaFestivos, ?string $festivo, string $inicio, string $fin,
        ?string $salida, ?int $detectados, array $condiciones
    ): void {
        [$empresa, $personal, $admin] = $this->empresa();
        $personal->update(['laborados' => $laborable ? '1' : '2']);
        $personal->horarios()->create(['dia' => 1, 'entrada' => $inicio, 'salida' => $fin, 'trabaja_festivos' => $trabajaFestivos]);
        if ($festivo === 'oficial') {
            FestivoOficial::create(['fecha' => self::FECHA, 'conmemoracion' => 'Festivo oficial de prueba']);
        }
        if ($festivo === 'empresa') {
            FestivoEmpresa::create(['empresa_id' => $empresa->id, 'fecha' => self::FECHA, 'conmemoracion' => 'Festivo empresarial']);
        }
        $this->asistencia($personal, self::FECHA, self::FECHA.' '.$inicio, $salida);

        $respuesta = $this->actingAs($admin)->get($this->buscador($empresa))->assertOk();
        $jornadas = $respuesta->viewData('jornadas');
        if ($detectados === null) {
            $this->assertCount(0, $jornadas);
            $respuesta->assertSee('No se encontraron jornadas');

            return;
        }

        $this->assertCount(1, $jornadas);
        $this->assertSame($detectados, $jornadas->first()['minutos']);
        $this->assertSame($condiciones, $jornadas->first()['condiciones']);
        $this->assertSame(self::FECHA, $jornadas->first()['fecha']);
        if ($detectados === 0) {
            $respuesta->assertSee('Festivo programado sin tiempo adicional')->assertDontSee('data-bs-target="#registroHorasExtras"', false);
        }
        if ($inicio === '22:00') {
            $this->assertSame('02/11/2026 22:00 a 03/11/2026 06:00', $jornadas->first()['programado']);
        }
    }

    public function test_calcula_con_instantes_utc_entre_centros_aun_con_una_pausa_abierta(): void
    {
        [$empresa, $personal, $admin] = $this->empresa();
        $personal->horarios()->create(['dia' => 1, 'entrada' => '22:00', 'salida' => '06:00']);
        $asistencia = $this->asistencia($personal, self::FECHA, '2026-11-02 22:00', '2026-11-03 05:15');
        $asistencia->update([
            'timezone_entrada' => 'America/Mexico_City', 'timezone_salida' => 'America/Tijuana',
            'llegada_utc' => '2026-11-03 04:00:00', 'salida_utc' => '2026-11-03 13:15:00',
        ]);
        $asistencia->pausas()->create(['tipo' => 'comida', 'inicio' => '2026-11-03 01:00:00', 'inicio_utc' => '2026-11-03 07:00:00']);
        $jornada = $this->actingAs($admin)->get($this->buscador($empresa))->assertOk()->viewData('jornadas')->sole();
        $this->assertSame(75, $jornada['minutos']);
        $this->assertStringContainsString('03/11/2026 05:15', $jornada['salida']);

        $this->post(route('horas-extras.store'), [
            'empresa_id' => $empresa->id, 'asistencia_id' => $asistencia->id, 'revision' => $jornada['revision'],
            'horas' => 1, 'minutos' => 15, 'autorizador_personal_id' => $this->responsable($empresa)->id, 'motivo' => 'Cierre operativo',
            'observaciones' => 'Se verificó el horario.',
        ])->assertRedirect();
        $this->assertDatabaseHas('horas_extras', ['asistencia_id' => $asistencia->id, 'detectados' => 75]);
    }

    public function test_limita_la_busqueda_a_90_dias_incluyendo_ambos_extremos(): void
    {
        [$empresa, $personal, $admin] = $this->empresa();
        $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $this->asistencia($personal, self::FECHA, '2026-11-02 08:00', '2026-11-02 18:15');

        $filtros = ['empresa_id' => $empresa->id, 'desde' => '2026-08-05', 'hasta' => self::FECHA];
        $respuesta = $this->actingAs($admin)->get(route('horas-extras.create', $filtros))->assertOk();
        $this->assertSame(self::FECHA, $respuesta->viewData('jornadas')->sole()['fecha']);

        $filtros['desde'] = '2026-08-04';
        $this->getJson(route('horas-extras.create', $filtros))
            ->assertUnprocessable()->assertJsonValidationErrors('hasta');
    }

    public function test_filtra_por_empleados_activos_de_la_empresa(): void
    {
        [$empresa, $empleado, $admin] = $this->empresa();
        [$otraEmpresa, $ajeno] = $this->empresa();
        $otro = $empleado->replicate()->fill(['pin' => '90876', 'nombre' => 'Segundo empleado']);
        $otro->save();
        $inactivo = $empleado->replicate()->fill(['pin' => '90877', 'nombre' => 'Empleado inactivo', 'activo' => false]);
        $inactivo->save();

        foreach ([$empleado, $otro, $inactivo, $ajeno] as $persona) {
            $persona->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
            $this->asistencia($persona, self::FECHA, '2026-11-02 08:00', '2026-11-02 18:00');
        }

        $this->actingAs($admin)->getJson(route('horas-extras.empleados', $empresa))
            ->assertOk()->assertExactJson([
                ['id' => $empleado->id, 'nombre' => $empleado->nombre],
                ['id' => $otro->id, 'nombre' => $otro->nombre],
            ]);
        $this->getJson(route('horas-extras.empleados', $otraEmpresa))->assertNotFound();

        $url = $this->buscador($empresa);
        $respuesta = $this->get($url)->assertOk()->assertSee('Todos los Empleados');
        $this->assertCount(2, $respuesta->viewData('jornadas'));
        $respuesta = $this->get($url.'&personal_id='.$otro->id)->assertOk();
        $this->assertSame($otro->nombre, $respuesta->viewData('jornadas')->sole()['empleado']);
        $this->assertSame($otro->id, $respuesta->viewData('empleado'));

        foreach ([$inactivo, $ajeno] as $persona) {
            $this->getJson($url.'&personal_id='.$persona->id)->assertUnprocessable()
                ->assertJsonValidationErrors('personal_id');
        }
    }

    public function test_excluye_autorizaciones_de_la_misma_fecha_guardadas_sin_hora(): void
    {
        [$empresa, , , , $captura] = $this->captura();
        $this->post(route('horas-extras.store'), $captura)->assertRedirect();
        DB::table('horas_extras')->where('asistencia_id', $captura['asistencia_id'])->update(['fecha' => self::FECHA]);

        $this->get($this->buscador($empresa))->assertOk()->assertSee('No se encontraron jornadas');
        $this->postJson(route('horas-extras.store'), $captura)->assertUnprocessable()
            ->assertJsonValidationErrors(['jornada' => 'Esta jornada ya tiene una autorización registrada.']);
    }

    public static function autorizaciones(): array
    {
        return [
            'menor' => [75, 'Se autoriza el periodo solicitado.'],
            'igual' => [135, 'Se verificó el horario.'],
            'mayor' => [180, 'Autorización por cobertura de servicio.'],
        ];
    }

    #[DataProvider('autorizaciones')]
    public function test_registra_menor_igual_o_mayor_y_conserva_historial_y_pdf(int $minutos, string $observaciones): void
    {
        [$empresa, $personal, $admin, $asistencia, $captura] = $this->captura();
        $responsable = $this->responsable($empresa);
        $captura = array_replace($captura, [
            'horas' => intdiv($minutos, 60), 'minutos' => $minutos % 60,
            'autorizador_personal_id' => $responsable->id, 'observaciones' => $observaciones,
        ]);
        $original = $asistencia->fresh()->getRawOriginal();
        $this->post(route('horas-extras.store'), $captura)->assertRedirect(route('horas-extras.index', [
            'empresa_id' => $empresa->id, 'mes' => 11, 'anio' => 2026,
        ]));
        $registro = HoraExtra::sole();
        $this->assertMatchesRegularExpression('/^HE-[A-Z]{2}[0-9]{5,}$/', $registro->folio);
        $this->assertStringEndsWith(str_pad((string) $registro->id, 5, '0', STR_PAD_LEFT), $registro->folio);
        $this->assertSame(135, $registro->detectados);
        $this->assertSame($minutos, $registro->autorizados);
        $this->assertEquals($responsable->id, $registro->autorizador_personal_id);
        $this->assertEquals($admin->id, $registro->capturista_id);
        $this->assertSame($original, $asistencia->fresh()->getRawOriginal());
        $this->get($this->buscador($empresa))->assertOk()->assertSee('No se encontraron jornadas');
        $this->postJson(route('horas-extras.store'), $captura)->assertUnprocessable()->assertJsonValidationErrors('jornada');
        $this->assertDatabaseCount('horas_extras', 1);

        $personal->update(['nombre' => 'Nombre actualizado']);
        $personal->horarios()->update(['salida' => '20:00']);
        $responsable->update(['nombre' => 'Autorizador actualizado']);
        $detalle = $this->get(route('horas-extras.show', $registro))->assertOk()->assertSee('17:00')
            ->assertDontSee('Nombre actualizado')->assertDontSee('Autorizador actualizado')->assertSee('tab=documentos')
            ->assertSee($registro->folio);
        $this->assertSame($registro->jornada['empleado'], $detalle->viewData('registro')->jornada['empleado']);
        $pdf = $this->get(route('horas-extras.pdf', $registro))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $html = view('horas-extras.pdf', compact('registro'))->render();
        $this->assertStringContainsString('02/11/2026 17:00', $html);
        foreach (['entrada', 'salida', 'registro_local'] as $campo) {
            $this->assertStringNotContainsString($registro->jornada[$campo], $html);
            $this->assertStringContainsString(substr($registro->jornada[$campo], 0, 19), $html);
        }
        $this->assertStringContainsString('Firma de quien autoriza', $html);
        $this->assertStringContainsString($registro->folio, $html);
    }

    public function test_valida_motivo_observaciones_responsable_tiempo_y_jornada_vigente(): void
    {
        [$empresa, $personal, , $asistencia, $captura] = $this->captura();
        [, $ajeno] = $this->empresa();
        foreach ([
            ['motivo', ['motivo' => '  ']],
            ['horas', ['horas' => 0, 'minutos' => 0]],
            ['minutos', ['minutos' => 60]],
            ['observaciones', ['observaciones' => null]],
            ['observaciones', ['horas' => 3, 'observaciones' => ' ']],
            ['autorizador_personal_id', ['autorizador_personal_id' => $ajeno->id]],
            ['autorizador_personal_id', ['autorizador_personal_id' => $this->responsable($empresa, false)->id]],
            ['autorizador_personal_id', ['autorizador_personal_id' => $personal->id]],
        ] as [$campo, $cambios]) {
            $this->postJson(route('horas-extras.store'), array_replace($captura, $cambios))
                ->assertUnprocessable()->assertJsonValidationErrors($campo);
        }

        $asistencia->update(['salida' => null]);
        $this->postJson(route('horas-extras.store'), $captura)->assertUnprocessable()->assertJsonValidationErrors('jornada');
        $asistencia->update(['salida' => '2026-11-02 19:16:00']);
        $this->postJson(route('horas-extras.store'), $captura)->assertUnprocessable()->assertJsonValidationErrors('jornada');
        $this->assertDatabaseCount('horas_extras', 0);
    }

    public function test_permisos_filtros_e_inmutabilidad(): void
    {
        [$empresa, , $admin, , $captura] = $this->captura();
        $this->post(route('horas-extras.store'), $captura)->assertRedirect();
        $registro = HoraExtra::sole();
        [$otra, , $otro] = $this->empresa();
        $this->actingAs($otro)->get(route('horas-extras.index'))->assertOk()->assertSee('No hay autorizaciones');
        $this->get($this->buscador($empresa))->assertNotFound();
        $this->get(route('horas-extras.show', $registro))->assertNotFound();
        $this->get(route('horas-extras.pdf', $registro))->assertNotFound();
        $this->postJson(route('horas-extras.store'), $captura)->assertNotFound();
        $this->postJson(route('horas-extras.store'), array_replace($captura, ['empresa_id' => $otra->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('jornada');

        $this->actingAs($this->admin($empresa, false))->get(route('horas-extras.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['profile' => 3]))->get(route('horas-extras.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['profile' => 1]))->get(route('horas-extras.index'))->assertOk()->assertSee($registro->jornada['empleado']);
        $this->get(route('horas-extras.create'))->assertForbidden();
        $this->get(route('horas-extras.index', ['empresa_id' => $otra->id]))->assertOk()->assertSee('No hay autorizaciones');

        $this->actingAs($admin)->get(route('horas-extras.index', ['mes' => 11, 'anio' => 2026]))->assertOk()->assertSee($registro->jornada['empleado']);
        $this->get(route('horas-extras.index', ['mes' => 10, 'anio' => 2026]))->assertOk()->assertSee('No hay autorizaciones');
        $this->get(route('horas-extras.index', ['mes' => 11, 'anio' => 2025]))->assertOk()->assertSee('No hay autorizaciones');
        $this->patch(route('horas-extras.show', $registro), ['motivo' => 'Cambio'])->assertMethodNotAllowed();
        $this->delete(route('horas-extras.show', $registro))->assertMethodNotAllowed();
        $this->assertSame($captura['motivo'], $registro->fresh()->motivo);

        $duplicado = $registro->getRawOriginal();
        unset($duplicado['id']);
        $duplicado['asistencia_id'] += 100;
        $duplicado['folio'] = 'HE-XX00101';
        $this->expectException(UniqueConstraintViolationException::class);
        DB::table('horas_extras')->insert($duplicado);
    }

    public function test_asocia_constancia_al_expediente_y_permite_otra_despues_de_eliminarla(): void
    {
        Storage::fake('wasabi');
        [$empresa, $empleado, $admin, , $captura] = $this->captura();
        $this->post(route('horas-extras.store'), $captura)->assertRedirect();
        $registro = HoraExtra::sole();
        $datosOriginales = $registro->getRawOriginal();
        $detalle = route('horas-extras.show', $registro);
        $ruta = route('horas-extras.constancia.store', $registro);
        $descargaFirmada = route('horas-extras.constancia.download', $registro);
        $descargaPdf = route('horas-extras.pdf', $registro);
        $historial = route('horas-extras.index');
        $contenido = "%PDF-1.4\nConstancia firmada de prueba\n%%EOF";
        $pdf = fn () => UploadedFile::fake()->createWithContent('constancia-firmada.pdf', $contenido);

        $this->get($detalle)->assertOk()->assertSee('Subir constancia firmada');
        $this->get($historial)->assertOk()->assertSee($descargaPdf)->assertDontSee($descargaFirmada);
        $this->from($detalle)->post($ruta, ['archivo' => UploadedFile::fake()->create('formato.txt')])
            ->assertRedirect($detalle)->assertSessionHasErrors('archivo');
        $this->post($ruta, ['archivo' => $pdf()])->assertRedirect($detalle)->assertSessionHas('constancia_cargada');

        $documento = $registro->constanciaFirmada()->sole();
        $this->assertSame('Autorización de Horas Extra', $documento->tipo);
        $this->assertEquals($empleado->id, $documento->personal_id);
        $this->assertEquals($empresa->id, $documento->empresa_id);
        Storage::disk('wasabi')->assertExists($documento->ruta_privada);
        $this->get($detalle)->assertOk()->assertSee('Descargar constancia firmada')
            ->assertDontSee('Subir constancia firmada');
        $this->get($historial)->assertOk()
            ->assertSee('class="btn-hr btn-success-hr btn-sm-hr" href="'.$descargaFirmada.'"', false)
            ->assertDontSee($descargaPdf);
        $this->get($descargaFirmada)->assertOk()->assertDownload('constancia-firmada.pdf')->assertContent($contenido);
        $this->get(route('personal.documentos.download', [$empleado, $documento]))->assertOk();
        $this->post($ruta, ['archivo' => $pdf()])->assertRedirect($detalle)->assertSessionHasErrors('archivo');
        $this->assertDatabaseCount('personal_documentos', 1);

        [, , $otroAdmin] = $this->empresa();
        $this->actingAs($otroAdmin)->post($ruta, ['archivo' => $pdf()])->assertNotFound();
        $this->get($descargaFirmada)->assertNotFound();
        $this->actingAs(User::factory()->create(['profile' => 1]))->post($ruta, ['archivo' => $pdf()])->assertForbidden();
        $this->actingAs($admin)->delete(route('personal.documentos.destroy', [$empleado, $documento]))->assertRedirect();
        $this->assertSoftDeleted($documento);
        $this->assertNull(PersonalDocumento::withTrashed()->findOrFail($documento->id)->hora_extra_id);
        $this->assertSame($datosOriginales, $registro->fresh()->getRawOriginal());
        $this->get($detalle)->assertOk()->assertSee('Subir constancia firmada');
        $this->get($descargaFirmada)->assertNotFound();
        $this->get($historial)->assertOk()->assertSee($descargaPdf)->assertDontSee($descargaFirmada);
        $this->post($ruta, ['archivo' => $pdf()])->assertRedirect($detalle)->assertSessionHas('constancia_cargada');
        $this->assertNotEquals($documento->id, $registro->constanciaFirmada()->sole()->id);
    }

    private function captura(): array
    {
        [$empresa, $personal, $admin] = $this->empresa();
        $personal->horarios()->create(['dia' => 1, 'entrada' => '08:00', 'salida' => '17:00']);
        $asistencia = $this->asistencia($personal, self::FECHA, '2026-11-02 08:00', '2026-11-02 19:15');
        $jornada = $this->actingAs($admin)->get($this->buscador($empresa))->assertOk()->viewData('jornadas')->sole();

        return [$empresa, $personal, $admin, $asistencia, [
            'empresa_id' => $empresa->id, 'asistencia_id' => $asistencia->id,
            'revision' => $jornada['revision'], 'horas' => 2, 'minutos' => 15,
            'autorizador_personal_id' => $this->responsable($empresa)->id, 'motivo' => 'Cierre operativo',
            'observaciones' => 'Se verificó el horario.',
        ]];
    }

    private function empresa(): array
    {
        $numero = Empresa::count() + 1;
        $empresa = Empresa::create(['rfc' => 'HEX010101AA'.$numero, 'nombre' => 'Empresa '.$numero, 'razon_social' => 'Empresa de prueba '.$numero]);
        $centro = Centro::create(['empresa_id' => $empresa->id, 'nombre' => 'Centro de prueba', 'timezone' => 'America/Mexico_City']);
        $personal = Personal::create([
            'empresa_id' => $empresa->id, 'centro_id' => $centro->id, 'nombre' => 'Empleado de prueba '.$numero,
            'pin' => '1020'.$numero, 'laborados' => '1', 'codigo' => 'H00'.$numero,
        ]);

        return [$empresa, $personal, $this->admin($empresa)];
    }

    private function admin(Empresa $empresa, bool $controlTotal = true): User
    {
        $admin = User::factory()->create(['profile' => 2]);
        $admin->empresas()->attach($empresa->id, ['control_total' => $controlTotal]);

        return $admin;
    }

    private function responsable(Empresa $empresa, bool $activo = true): Personal
    {
        return Personal::create(['empresa_id' => $empresa->id, 'nombre' => 'Empleado responsable', 'activo' => $activo]);
    }

    private function asistencia(Personal $personal, string $fecha, string $llegada, ?string $salida): Asistencia
    {
        return Asistencia::create([
            'empresa_id' => $personal->empresa_id, 'personal_id' => $personal->id,
            'fecha' => $fecha, 'llegada' => $llegada, 'salida' => $salida,
        ]);
    }

    private function buscador(Empresa $empresa): string
    {
        return route('horas-extras.create', ['empresa_id' => $empresa->id, 'desde' => self::FECHA, 'hasta' => self::FECHA]);
    }
}
