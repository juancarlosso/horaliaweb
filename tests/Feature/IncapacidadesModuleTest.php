<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empresa;
use App\Models\Incapacidad;
use App\Models\IncapacidadComprobante;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Models\User;
use App\Services\IncapacidadesService;
use App\Services\ReporteIncidenciasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IncapacidadesModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $_SERVER['HTTP_HOST']='localhost'; $this->travelTo(now()->setDate(2026,11,8)->setTime(12,0)); }

    public function test_snapshot_is_frozen_and_report_uses_only_snapshot_workdays(): void
    {
        [$company,$employee]=$this->employee();
        $employee->horarios()->create(['dia'=>1,'entrada'=>'08:00','salida'=>'17:00']);
        $user=User::factory()->create(['profile'=>2]); $user->empresas()->attach($company->id,['control_total'=>true]);
        $data=['empresa_id'=>$company->id,'personal_id'=>$employee->id,'fecha_inicio'=>'2026-11-02','fecha_fin'=>'2026-11-03','motivo'=>'Enfermedad general'];
        $record=app(IncapacidadesService::class)->register($data,$user->id);
        $this->assertSame(1,$record->jornadas_afectadas);
        $this->assertSame(['2026-11-02'],collect($record->snapshot['fechas'])->where('laborable',true)->pluck('fecha')->all());
        $employee->horarios()->delete();
        Asistencia::query()->create(['empresa_id' => $company->id, 'personal_id'=>$employee->id,'fecha'=>'2026-11-02','llegada'=>'2026-11-02 08:05:00']);
        $rows=app(ReporteIncidenciasService::class)->generate($company->id,'2026-11-02','2026-11-03',['incapacidades','incapacidades_trabajadas','posibles_faltas','salidas_pendientes']);
        $this->assertContains('incapacidades_trabajadas',$rows->where('fecha','2026-11-02')->pluck('tipo')->all());
        $this->assertContains('salidas_pendientes',$rows->where('fecha','2026-11-02')->pluck('tipo')->all());
        $this->assertNotContains('incapacidades',$rows->pluck('tipo')->all());
        $this->assertNotContains('incapacidades',$rows->where('fecha','2026-11-03')->pluck('tipo')->all());
    }

    public function test_overlap_excludes_canceled_records_and_cancel_keeps_snapshot(): void
    {
        [$company,$employee]=$this->employee(); $employee->horarios()->create(['dia'=>1,'entrada'=>'08:00','salida'=>'17:00']);
        $user=User::factory()->create(['profile'=>2]); $service=app(IncapacidadesService::class);
        $data=['empresa_id'=>$company->id,'personal_id'=>$employee->id,'fecha_inicio'=>'2026-11-02','fecha_fin'=>'2026-11-02','motivo'=>'Maternidad'];
        $first=$service->register($data,$user->id);
        try {$service->register($data,$user->id);$this->fail('Se esperaba rechazar traslape.');} catch(ValidationException $e) {$this->assertArrayHasKey('fecha_inicio',$e->errors());}
        $first->update(['estado'=>'cancelada']);
        $second=$service->register($data,$user->id);
        $this->assertSame('vigente',$second->estado); $this->assertSame(1,$first->fresh()->snapshot['jornadas_afectadas']);
    }

    public function test_no_entry_is_incapacity_not_absence_and_canceled_record_no_longer_classifies(): void
    {
        [$company,$employee]=$this->employee(); $employee->horarios()->create(['dia'=>1,'entrada'=>'08:00','salida'=>'17:00']);
        $user=User::factory()->create(['profile'=>2]);
        $record=app(IncapacidadesService::class)->register(['empresa_id'=>$company->id,'personal_id'=>$employee->id,'fecha_inicio'=>'2026-11-02','fecha_fin'=>'2026-11-02','motivo'=>'Enfermedad general'],$user->id);
        $types=['incapacidades','incapacidades_trabajadas','posibles_faltas'];
        $rows=app(ReporteIncidenciasService::class)->generate($company->id,'2026-11-02','2026-11-02',$types);
        $this->assertSame(['incapacidades'],$rows->pluck('tipo')->all());
        $record->update(['estado'=>'cancelada']);
        $rows=app(ReporteIncidenciasService::class)->generate($company->id,'2026-11-02','2026-11-02',$types);
        $this->assertSame(['posibles_faltas'],$rows->pluck('tipo')->all());
    }

    public function test_superadmin_can_view_every_company_while_company_admin_manages_only_its_own(): void
    {
        [$company,$employee]=$this->employee();
        [$otherCompany,$otherEmployee]=$this->employee();
        $admin=User::factory()->create(['profile'=>2]); $admin->empresas()->attach($company->id,['control_total'=>true]);
        $this->actingAs($admin)->get(route('incapacidades.index'))->assertOk()->assertSee('class="catalog-page"',false)->assertSee('class="catalog-toolbar"',false)->assertSee('class="card-hr"',false)->assertSee('Listado de incapacidades');
        $this->get(route('incapacidades.create'))->assertOk()->assertSee('class="page-inner"',false)->assertSee('class="card-bd"',false)->assertSee('class="form-label-hr"',false);
        $this->get(route('reportes.incidencias',['empresa_id'=>$company->id,'desde'=>'2026-11-02','hasta'=>'2026-11-03','tipos'=>['incapacidades']]))->assertOk()->assertSee('Incapacidad');
        $super=User::factory()->create(['profile'=>1]);
        $other=User::factory()->create(['profile'=>2]); $other->empresas()->attach($company->id,['control_total'=>false]);
        $this->actingAs($other)->get(route('incapacidades.index'))->assertForbidden();
        $record=Incapacidad::create(['empresa_id'=>$company->id,'personal_id'=>$employee->id,'registrado_por_user_id'=>$admin->id,'fecha_inicio'=>'2026-11-02','fecha_fin'=>'2026-11-02','motivo'=>'Maternidad','jornadas_afectadas'=>0,'snapshot'=>['fechas'=>[]],'snapshot_generado_en'=>now(),'estado'=>'vigente']);
        Incapacidad::create(['empresa_id'=>$otherCompany->id,'personal_id'=>$otherEmployee->id,'registrado_por_user_id'=>$admin->id,'fecha_inicio'=>'2026-11-03','fecha_fin'=>'2026-11-03','motivo'=>'Enfermedad general','jornadas_afectadas'=>0,'snapshot'=>['fechas'=>[]],'snapshot_generado_en'=>now(),'estado'=>'vigente']);
        Storage::fake('wasabi'); Storage::disk('wasabi')->put('privado/prueba.pdf','pdf');
        $file=IncapacidadComprobante::create(['incapacidad_id'=>$record->id,'ruta_privada'=>'privado/prueba.pdf','nombre_original'=>'prueba.pdf','mime'=>'application/pdf','tamano'=>3,'cargado_por_user_id'=>$admin->id]);
        $this->actingAs($super)->get(route('incapacidades.index'))->assertOk()->assertSee($employee->nombre)->assertSee($otherEmployee->nombre);
        $this->get(route('incapacidades.create'))->assertForbidden();
        $this->delete(route('incapacidades.cancel',$record))->assertForbidden();
        $this->get(route('incapacidades.comprobantes.show',[$record,$file]))->assertOk();
        $this->actingAs($admin)->get(route('incapacidades.index'))->assertOk()->assertSee($employee->nombre)->assertDontSee($otherEmployee->nombre)->assertSee('vacation-detail-row',false)->assertSee('btn-hr btn-outline-hr btn-sm-hr',false)->assertSee("['Observaciones',info.observaciones||'—']",false)->assertDontSee("Observaciones: '+info.observaciones",false)->assertSee('Descargar',false);
        $this->actingAs($other)->get(route('incapacidades.comprobantes.show',[$record,$file]))->assertNotFound();
        $this->actingAs($admin)->get(route('incapacidades.comprobantes.show',[$record,$file]))->assertOk()->assertHeader('Cache-Control','no-store, private');
    }

    public function test_report_is_closed_to_profiles_outside_one_and_two(): void
    {
        [$company,$employee]=$this->employee(); $user=User::factory()->create(['profile'=>3,'personal_id'=>$employee->id]);
        $this->actingAs($user)->get(route('reportes.incidencias'))->assertForbidden();
        $this->get(route('reportes.incidencias.exportar',['empresa_id'=>$company->id,'desde'=>'2026-11-02','hasta'=>'2026-11-03','tipos'=>['incapacidades']]))->assertForbidden();
    }

    public function test_http_upload_accepts_three_optional_files_and_rejects_four(): void
    {
        Storage::fake('wasabi'); [$company,$employee]=$this->employee(); $employee->horarios()->create(['dia'=>1,'entrada'=>'08:00','salida'=>'17:00']);
        $user=User::factory()->create(['profile'=>2]); $user->empresas()->attach($company->id,['control_total'=>true]);
        $payload=['empresa_id'=>$company->id,'personal_id'=>$employee->id,'fecha_inicio'=>'2026-11-02','fecha_fin'=>'2026-11-02','motivo'=>'Riesgo de trabajo'];
        $files=[UploadedFile::fake()->create('uno.pdf',5,'application/pdf'),UploadedFile::fake()->create('dos.pdf',5,'application/pdf'),UploadedFile::fake()->create('tres.pdf',5,'application/pdf')];
        $this->actingAs($user)->post(route('incapacidades.store'),$payload+['comprobantes'=>$files])->assertRedirect();
        $incapacidad = Incapacidad::sole();
        $comprobantes = $incapacidad->comprobantes()->with('documentoPersonal')->get();
        $this->assertCount(3, $comprobantes);
        $this->assertDatabaseCount('personal_documentos', 3);
        $this->assertCount(3, Storage::disk('wasabi')->allFiles());
        foreach ($comprobantes as $comprobante) {
            $this->assertSame('Incapacidad', $comprobante->documentoPersonal->tipo);
            $this->assertEquals($employee->id, $comprobante->documentoPersonal->personal_id);
            $this->assertSame($comprobante->ruta_privada, $comprobante->documentoPersonal->ruta_privada);
        }
        $this->post(route('incapacidades.store'),$payload+['comprobantes'=>[...$files,UploadedFile::fake()->create('cuatro.pdf',5,'application/pdf')]])->assertSessionHasErrors('comprobantes');

        $comprobante = $comprobantes->first();
        $documento = $comprobante->documentoPersonal;
        $this->get(route('incapacidades.comprobantes.download', [$incapacidad, $comprobante]))->assertOk();
        $this->get(route('personal.documentos.download', [$employee, $documento]))->assertOk();
        $this->delete(route('personal.documentos.destroy', [$employee, $documento]))->assertRedirect();
        $this->assertSoftDeleted($documento);
        $this->assertSoftDeleted($comprobante);
        $this->assertNull($comprobante->fresh()->personal_documento_id);
        $this->get(route('incapacidades.comprobantes.download', [$incapacidad, $comprobante]))->assertNotFound();
        $this->assertSame(2, $incapacidad->comprobantes()->count());
        $this->assertSame('vigente', $incapacidad->fresh()->estado);
        Storage::disk('wasabi')->assertExists($comprobante->ruta_privada);
    }

    public function test_asocia_comprobantes_historicos_sin_duplicar_archivos_ni_registros(): void
    {
        Storage::fake('wasabi');
        [$empresa, $empleado] = $this->employee();
        $admin = User::factory()->create(['profile' => 2]);
        $admin->empresas()->attach($empresa->id, ['control_total' => true]);
        $incapacidad = Incapacidad::create([
            'empresa_id' => $empresa->id, 'personal_id' => $empleado->id,
            'registrado_por_user_id' => $admin->id, 'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-03',
            'motivo' => 'Maternidad', 'estado' => 'cancelada', 'jornadas_afectadas' => 0,
            'snapshot' => ['fechas' => []], 'snapshot_generado_en' => now(),
        ]);
        $ruta = 'privado/comprobante-historico.pdf';
        Storage::disk('wasabi')->put($ruta, 'PDF histórico');
        $comprobante = $incapacidad->comprobantes()->create([
            'ruta_privada' => $ruta, 'nombre_original' => 'histórico.pdf', 'mime' => 'application/pdf',
            'tamano' => strlen('PDF histórico'), 'cargado_por_user_id' => $admin->id,
        ]);
        $segundoComprobante = $incapacidad->comprobantes()->create([
            'ruta_privada' => $ruta, 'nombre_original' => 'histórico.pdf', 'mime' => 'application/pdf',
            'tamano' => strlen('PDF histórico'), 'cargado_por_user_id' => $admin->id,
        ]);

        $migracion = require database_path('migrations/2026_11_23_161000_backfill_incapacidad_personal_documentos.php');
        $migracion->up();
        $migracion->up();

        $documento = PersonalDocumento::sole();
        $this->assertSame('Incapacidad', $documento->tipo);
        $this->assertEquals($empleado->id, $documento->personal_id);
        $this->assertEquals($admin->id, $documento->cargado_por);
        $this->assertEquals($comprobante->created_at, $documento->created_at);
        $this->assertSame($ruta, $documento->ruta_privada);
        $this->assertEquals($documento->id, $comprobante->fresh()->personal_documento_id);
        $this->assertEquals($documento->id, $segundoComprobante->fresh()->personal_documento_id);
        $this->assertSame('cancelada', $incapacidad->fresh()->estado);
        $this->assertSame([$ruta], Storage::disk('wasabi')->allFiles());
        $this->actingAs($admin)->get(route('personal.documentos.download', [$empleado, $documento]))
            ->assertOk()->assertContent('PDF histórico');
    }

    private function employee(): array
    {
        $n=Empresa::count()+1; $company=Empresa::create(['rfc'=>'INCA'.str_pad((string)$n,3,'0',STR_PAD_LEFT).'AAA','razon_social'=>'Empresa Incapacidad '.$n,'direccion'=>'Calle prueba 1','telefono'=>'5500000000','activa'=>true]);
        $employee=Personal::create(['empresa_id'=>$company->id,'nombre'=>'Colaborador '.$n,'pin'=>str_pad((string)(Personal::count()+1),5,'0',STR_PAD_LEFT),'activo'=>true,'laborados'=>'1@2@3@4@5']);
        return [$company,$employee];
    }
}
