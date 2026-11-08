<?php

namespace App\Http\Controllers;

use App\Helper\Wasabi;
use App\Models\Empresa;
use App\Models\Personal;
use App\Models\PersonalDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PersonalDocumentoController extends Controller
{
    public static function catalog(): array
    {
        return self::CATALOG;
    }

    private const CATALOG = [
        'Identidad' => ['INE (frente)', 'INE (reverso)', 'Pasaporte', 'Acta de nacimiento', 'CURP', 'RFC', 'Comprobante de domicilio', 'Número de Seguro Social (NSS)', 'Constancia de Situación Fiscal'],
        'Laboral' => ['Solicitud de empleo', 'Currículum Vitae', 'Contrato individual de trabajo', 'Aviso de privacidad firmado', 'Reglamento interno firmado', 'Carta de confidencialidad (NDA)', 'Descripción de puesto firmada', 'Carta responsiva de equipo', 'Carta de no competencia', 'Carta de antecedentes no penales'],
        'Administrativo' => ['Alta IMSS (AFIL 01)', 'Modificación de salario (AFIL 02)', 'Baja IMSS (AFIL 03)', 'Recibos de nómina firmados', 'Comprobante INFONAVIT', 'Comprobante FONACOT'],
        'Estudios' => ['Certificado de estudios', 'Título profesional', 'Cédula profesional', 'Diplomas de cursos', 'Certificaciones técnicas', 'Constancia DC-3 (STPS)'],
        'Seguridad e Higiene' => ['Inducción de seguridad firmada', 'Examen médico de ingreso', 'Exámenes médicos periódicos', 'Bitácora de entrega de EPP', 'Evaluación NOM-035', 'Capacitación de brigadas'],
        'Operativos' => ['Licencia de conducir', 'Tarjetón de conductor', 'Carta de no infracciones', 'Certificado médico para manejo', 'Prueba antidopaje'],
        'Otros' => ['Fotografías', 'Firma digitalizada', 'Comprobantes varios', 'Correspondencia relevante', 'Documentos internos de RH', 'Permisos especiales / autorizaciones', 'Otro documento'],
    ];

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

    public function store(Request $request, int $personal)
    {
        $employee = $this->employee($request, $personal);
        $validated = $request->validate([
            'tipo' => ['required', 'string', 'max:100', function ($attribute, $value, $fail) {
                if (! in_array($value, collect(self::CATALOG)->flatten()->all(), true)) {
                    $fail('Selecciona un tipo de documento válido.');
                }
            }],
            'nombre_personalizado' => ['required_if:tipo,Otro documento', 'nullable', 'string', 'max:255'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.(int) config('filesystems.personal_document_max_kb', 10240)],
        ]);
        $file = $request->file('archivo');
        $nombreOriginal = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $nombreOriginal = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $nombreOriginal);
        abort_if(!$nombreOriginal || $nombreOriginal === '.' || $nombreOriginal === '..', 422, 'El nombre del archivo no es válido.');
        $mime = (string) $file->getMimeType();
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true), 422, 'El contenido del archivo no es un formato permitido.');
        $path = null;
        try {
            $path = Wasabi::uploadWithOriginalName('personal/expedientes/empresa-'.$employee->empresa_id.'/personal-'.$employee->id, $file, $nombreOriginal);
            if (! $path || ! Storage::disk('wasabi')->exists($path)) {
                throw new \RuntimeException('Wasabi no confirmó la carga.');
            }
            PersonalDocumento::create([
                'empresa_id' => $employee->empresa_id, 'personal_id' => $employee->id,
                'tipo' => $validated['tipo'], 'nombre_personalizado' => $validated['tipo'] === 'Otro documento' ? $validated['nombre_personalizado'] : null,
                'ruta_privada' => $path,
                'nombre_original' => Str::limit($nombreOriginal, 255, ''),
                'mime' => $mime, 'tamano' => $file->getSize(), 'cargado_por' => $request->user()->id,
            ]);
        } catch (Throwable $e) {
            if ($path) {
                try {
                    Storage::disk('wasabi')->delete($path);
                } catch (Throwable) {
                }
            }
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

    public function download(Request $request, int $personal, int $documento)
    {
        $doc = $this->document($request, $personal, $documento);
        $contents = Storage::disk('wasabi')->get($doc->ruta_privada);

        return response($contents, 200, [
            'Content-Type' => $doc->mime,
            'Content-Length' => (string) $doc->tamano,
            'Content-Disposition' => 'attachment; filename="'.addcslashes(preg_replace('/[\\r\\n\\x00-\\x1F\\x7F]/u', '', $doc->nombre_original), '"\\').'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, int $personal, int $documento)
    {
        $doc = $this->document($request, $personal, $documento);
        $doc->update(['eliminado_por' => $request->user()->id]);
        $doc->delete();

        return back()->with('status', 'Documento eliminado del expediente.')->with('activePersonalTab', 'documentos');
    }
}
