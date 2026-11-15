<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Incapacidad;
use App\Models\IncapacidadComprobante;
use App\Models\Personal;
use App\Services\IncapacidadesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncapacidadesController extends Controller
{
    private function companyIds(Request $request, bool $manageableOnly = false): array
    {
        if ((int) $request->user()->profile === 1) {
            abort_if($manageableOnly, 403);
            return Empresa::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        abort_unless((int) $request->user()->profile === 2, 403);
        return $request->user()->empresas()->wherePivot('control_total', true)->pluck('empresas.id')->map(fn ($id) => (int) $id)->all();
    }

    public function index(Request $request)
    {
        $ids = $this->companyIds($request); abort_if(!$ids, 403);
        $companies = Empresa::query()->whereIn('id', $ids)->orderBy('nombre')->get();
        $companyId = $request->integer('empresa_id') ?: ($companies->count() === 1 ? (int) $companies->first()->id : null);
        abort_if($companyId && !in_array($companyId, $ids, true), 404);
        $personal = Personal::whereIn('empresa_id', $ids)->when($companyId, fn ($q) => $q->where('empresa_id', $companyId))->with('empresa:id,nombre')->orderBy('nombre')->get(['id','empresa_id','nombre']);
        $personalId = $request->integer('personal_id') ?: null;
        abort_if($personalId && !$personal->contains('id', $personalId), 404);
        $records = Incapacidad::with(['empresa','personal','registradoPor','canceladoPor','comprobantes'])->whereIn('empresa_id', $ids)->when($companyId, fn ($q) => $q->where('empresa_id', $companyId))->when($personalId, fn ($q) => $q->where('personal_id', $personalId))->orderByDesc('fecha_inicio')->paginate(config('constantes.itemsPorPagina'))->withQueryString();
        return view('incapacidades.index', ['navigation'=>DashboardController::navigation(),'activeSection'=>'incapacidades','companies'=>$companies,'companyId'=>$companyId,'personal'=>$personal,'personalId'=>$personalId,'records'=>$records,'canManage'=>(int)$request->user()->profile===2]);
    }

    public function create(Request $request)
    {
        $ids = $this->companyIds($request, true); abort_if(!$ids, 403);
        $companies = Empresa::query()->whereIn('id', $ids)->orderBy('nombre')->get();
        $companyId = $request->integer('empresa_id') ?: ($companies->count() === 1 ? (int) $companies->first()->id : null);
        abort_if($companyId && !in_array($companyId, $ids, true), 404);
        return view('incapacidades.form', ['navigation'=>DashboardController::navigation(),'activeSection'=>'incapacidades','companies'=>$companies,'companyId'=>$companyId,'personal'=>$companyId ? Personal::where('empresa_id',$companyId)->where('activo',true)->orderBy('nombre')->get(['id','nombre']) : collect()]);
    }

    public function store(Request $request, IncapacidadesService $service)
    {
        $ids = $this->companyIds($request, true);
        $data = $request->validate(['empresa_id'=>['required','integer'],'personal_id'=>['required','integer'],'fecha_inicio'=>['required','date'],'fecha_fin'=>['required','date','after_or_equal:fecha_inicio'],'motivo'=>['required','in:Enfermedad general,Riesgo de trabajo,Maternidad'],'observaciones'=>['nullable','string','max:5000'],'comprobantes'=>['nullable','array','max:3'],'comprobantes.*'=>['file','mimes:pdf,jpg,jpeg,png,webp','max:'.(int)config('filesystems.personal_document_max_kb',10240)]]);
        abort_unless(in_array((int)$data['empresa_id'],$ids,true),403);
        $employee = Personal::where('empresa_id',$data['empresa_id'])->where('activo',true)->findOrFail($data['personal_id']);
        $vacationOverlap = \App\Models\Vacacion::where('personal_id',$employee->id)->where('estado','registrado')->whereDate('fecha_inicio','<=',$data['fecha_fin'])->whereDate('fecha_fin','>=',$data['fecha_inicio'])->exists();
        $service->register($data,(int)$request->user()->id,$request->file('comprobantes',[]));
        $redirect = redirect()->route('incapacidades.index',['empresa_id'=>$data['empresa_id']])->with('success','La incapacidad se registró correctamente.');
        return $vacationOverlap ? $redirect->with('warning','El periodo coincide con vacaciones registradas. Se conservan ambos registros sin cambios automáticos.') : $redirect;
    }

    public function cancel(Request $request, int $incapacidad)
    {
        $record = Incapacidad::whereIn('empresa_id',$this->companyIds($request, true))->findOrFail($incapacidad);
        if ($record->estado === 'vigente') $record->update(['estado'=>'cancelada','cancelado_por_user_id'=>$request->user()->id,'cancelado_en'=>now()]);
        return back()->with('success','La incapacidad fue cancelada. El historial y sus comprobantes se conservaron.');
    }

    private function authorizedFile(Request $request, int $incapacidad, int $comprobante): IncapacidadComprobante
    {
        $file = IncapacidadComprobante::where('incapacidad_id',$incapacidad)->whereKey($comprobante)->whereHas('incapacidad',fn($q)=>$q->whereIn('empresa_id',$this->companyIds($request)))->firstOrFail();
        return $file;
    }

    public function showFile(Request $request, int $incapacidad, int $comprobante)
    {
        $file = $this->authorizedFile($request,$incapacidad,$comprobante); abort_unless(Storage::disk('wasabi')->exists($file->ruta_privada),404);
        return response(Storage::disk('wasabi')->get($file->ruta_privada),200,['Content-Type'=>$file->mime,'Content-Length'=>(string)$file->tamano,'Content-Disposition'=>'inline; filename="'.addcslashes($file->nombre_original,'"\\').'"','X-Content-Type-Options'=>'nosniff','Cross-Origin-Resource-Policy'=>'same-origin','Cache-Control'=>'private, no-store']);
    }

    public function downloadFile(Request $request, int $incapacidad, int $comprobante)
    {
        $file = $this->authorizedFile($request,$incapacidad,$comprobante); abort_unless(Storage::disk('wasabi')->exists($file->ruta_privada),404);
        return response(Storage::disk('wasabi')->get($file->ruta_privada),200,['Content-Type'=>$file->mime,'Content-Length'=>(string)$file->tamano,'Content-Disposition'=>'attachment; filename="'.addcslashes($file->nombre_original,'"\\').'"','X-Content-Type-Options'=>'nosniff','Cross-Origin-Resource-Policy'=>'same-origin','Cache-Control'=>'private, no-store']);
    }
}
