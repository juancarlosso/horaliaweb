<?php

namespace App\Http\Controllers;

use App\Helper\Wasabi;
use App\Models\Empresa;
use App\Models\IncapacidadComprobante;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use App\Services\PersonalDocumentosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PersonalDocumentoController extends Controller
{
    private function employee(Request $request, int $personal): Personal
    {
        $manageable = $request->user()->empresas()->wherePivot('control_total', true)->pluck('empresas.id')->map(fn ($id) => (int) $id)->all();
        if ((int) $request->user()->profile === 1) {
            $manageable = Empresa::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        abort_if(! $manageable, 403);

        return Personal::query()->whereIn('empresa_id', $manageable)->findOrFail($personal);
    }

    private function document(Request $request, int $personal, int $documento): PersonalDocumento
    {
        $employee = $this->employee($request, $personal);

        return PersonalDocumento::query()->where('empresa_id', $employee->empresa_id)->where('personal_id', $employee->id)->findOrFail($documento);
    }

    public function store(Request $request, int $personal, PersonalDocumentosService $documentos)
    {
        $employee = $this->employee($request, $personal);
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (! in_array($value, collect(config('constantes.tipos_documento_personal'))->flatten()->all(), true)) {
                    $fail('Selecciona un tipo de documento válido.');
                }
            }],
            'nombre_personalizado' => ['required_if:tipo,Otro documento', 'nullable', 'string', 'max:255'],
            'archivo' => PersonalDocumentosService::reglasArchivo(),
        ]);
        try {
            $documentos->guardar($employee, $request->file('archivo'), $request->user()->id, [
                'tipo' => $validated['tipo'],
                'nombre_personalizado' => $validated['tipo'] === 'Otro documento' ? $validated['nombre_personalizado'] : null,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['archivo' => 'No se pudo guardar el documento. Inténtalo de nuevo.']);
        }

        return back()->with('status', 'Documento cargado correctamente.')->with('activePersonalTab', 'documentos');
    }

    public function show(Request $request, int $personal, int $documento)
    {
        $doc = $this->document($request, $personal, $documento);
        abort_unless(Storage::disk('wasabi')->exists($doc->ruta_privada), 404);
        $url = Wasabi::url($doc->ruta_privada);
        abort_unless($url, 503, 'No fue posible generar el acceso temporal al documento.');

        return redirect()->away($url);
    }

    public function download(Request $request, int $personal, int $documento, PersonalDocumentosService $documentos)
    {
        return $documentos->descargar($this->document($request, $personal, $documento));
    }

    public function destroy(Request $request, int $personal, int $documento)
    {
        $doc = $this->document($request, $personal, $documento);
        DB::transaction(function () use ($doc, $request) {
            $doc->update(['eliminado_por' => $request->user()->id, 'hora_extra_id' => null]);
            $doc->delete();
            IncapacidadComprobante::where('personal_documento_id', $doc->id)
                ->update(['personal_documento_id' => null, 'deleted_at' => $doc->deleted_at]);
        });

        return back()->with('status', 'Documento eliminado del expediente.')->with('activePersonalTab', 'documentos');
    }
}
