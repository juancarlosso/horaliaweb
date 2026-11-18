<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\IntentoPago;
use App\Models\TransferenciaPago;
use App\Services\EmployeeSubscriptionPricing;
use App\Services\PaymentFolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class TransferenciaPagoController extends Controller
{
    public function uploadForm(Request $request, int $empresa, EmployeeSubscriptionPricing $pricing)
    {
        $company = $this->companyForUser($request, $empresa);
        abort_unless($company->metodo_pago === 'TRANSFERENCIA' && $company->fecha_renovacion && $company->fecha_renovacion->toDateString() <= today()->toDateString(), 404);
        $cycle = $company->fecha_renovacion->toDateString();
        $amount = $pricing->prepareRenewal($company, $cycle);
        $transfer = TransferenciaPago::firstOrCreate(['empresa_id' => $company->id, 'fecha_renovacion' => $cycle], [
            'cantidad' => $amount, 'concepto' => 'HORALIA '.$company->referenciaTransferencia(), 'estado' => 'pendiente_de_pago',
        ]);
        $requiresConsent = !DB::table('transferencia_condiciones_aceptadas')->where('empresa_id', $company->id)->where('version', config('constantes.pagos_transferencia.terminos_version'))->exists();
        return view('payments.transferencia', ['transferencia' => $transfer->load('comprobanteActual'), 'empresa' => $company, 'requiresConsent' => $requiresConsent, 'navigation' => DashboardController::navigation(), 'activeSection' => 'payment-history']);
    }

    public function upload(Request $request, int $empresa)
    {
        $company = $this->companyForUser($request, $empresa);
        abort_unless($company->metodo_pago === 'TRANSFERENCIA' && $company->fecha_renovacion && $company->fecha_renovacion->toDateString() <= today()->toDateString(), 404);
        $requiresConsent = !DB::table('transferencia_condiciones_aceptadas')->where('empresa_id', $company->id)->where('version', config('constantes.pagos_transferencia.terminos_version'))->exists();
        $request->validate([
            'terms_accepted' => $requiresConsent ? ['accepted'] : ['nullable'],
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('constantes.pagos_transferencia.max_kb', 10240)],
        ]);
        $file = $request->file('comprobante');
        $mime = $file->getMimeType();
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 422, 'El tipo real del archivo no es válido.');
        $cycle = $company->fecha_renovacion->toDateString();
        $disk = Storage::disk('wasabi');
        $path = $disk->putFile('transferencias/'.$company->id.'/'.$cycle, $file, ['visibility' => 'private']);
        if (!$path || !$disk->exists($path)) abort(503, 'No fue posible guardar el comprobante.');
        try {
            $record = DB::transaction(function () use ($company, $cycle, $request, $file, $mime, $path, $requiresConsent) {
                $locked = Empresa::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->metodo_pago === 'TRANSFERENCIA' && $locked->fecha_renovacion?->toDateString() === $cycle, 409);
                $transfer = TransferenciaPago::query()->where('empresa_id', $locked->id)->whereDate('fecha_renovacion', $cycle)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($transfer->estado, ['pendiente_de_pago', 'rechazado'], true), 409, 'Ya hay un comprobante pendiente o el periodo ya fue confirmado.');
                $transfer->forceFill(['estado' => 'pendiente_de_confirmar', 'usuario_carga_id' => $request->user()->id, 'cargado_en' => now(), 'motivo_rechazo' => null, 'revisado_por' => null, 'revisado_en' => null, 'correo_admin_enviado_en' => null])->save();
                $proof = $transfer->comprobantes()->create(['usuario_id' => $request->user()->id, 'ruta_privada' => $path, 'nombre_original' => $file->getClientOriginalName(), 'mime' => $mime, 'tamano' => $file->getSize()]);
                DB::table('transferencia_auditoria')->insert(['transferencia_pago_id' => $transfer->id, 'usuario_id' => $request->user()->id, 'accion' => 'comprobante_cargado', 'datos' => json_encode(['comprobante_id' => $proof->id, 'condiciones_version' => config('constantes.pagos_transferencia.terminos_version')]), 'created_at' => now(), 'updated_at' => now()]);
                if ($requiresConsent) DB::table('transferencia_condiciones_aceptadas')->insert(['transferencia_pago_id' => $transfer->id, 'empresa_id' => $locked->id, 'usuario_id' => $request->user()->id, 'version' => config('constantes.pagos_transferencia.terminos_version'), 'texto' => config('constantes.pagos_transferencia.terminos'), 'aceptado_en' => now(), 'created_at' => now(), 'updated_at' => now()]);
                return $transfer->load('empresa', 'comprobanteActual', 'usuarioCarga');
            });
        } catch (Throwable $e) { $disk->delete($path); throw $e; }
        if ($record->correo_admin_enviado_en === null && filter_var(config('constantes.pagos_transferencia.correo_admin'), FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to(config('constantes.pagos_transferencia.correo_admin'))->queue(new \App\Mail\TransferenciaPendienteMail($record));
                $record->forceFill(['correo_admin_enviado_en' => now()])->save();
            } catch (Throwable $e) { Log::error('No se pudo notificar transferencia pendiente.', ['transferencia_id' => $record->id]); }
        }
        return redirect()->route('payment-history.index')->with('status', 'Comprobante recibido. El pago quedó pendiente de confirmar; su envío no renueva el servicio.');
    }

    public function adminIndex(Request $request)
    {
        $this->adminOnly($request);
        $filters = $request->validate(['estado' => ['nullable', Rule::in(['pendiente_de_confirmar', 'rechazado', 'confirmado', 'todas'])], 'buscar' => ['nullable', 'string', 'max:100']]);
        $state = $filters['estado'] ?? 'pendiente_de_confirmar';
        $records = TransferenciaPago::with('empresa:id,nombre', 'comprobanteActual', 'usuarioCarga:id,name')
            ->whereIn('estado', $state === 'todas' ? ['pendiente_de_confirmar', 'rechazado', 'confirmado'] : [$state])
            ->when($filters['buscar'] ?? null, function ($query, $search) {
                $query->where(fn ($query) => $query->where('concepto', 'like', '%'.$search.'%')->orWhereHas('empresa', fn ($query) => $query->where('nombre', 'like', '%'.$search.'%')));
            })
            ->latest('cargado_en')->get();
        return view('payments.transferencias-admin', ['records' => $records, 'navigation' => DashboardController::navigation(), 'activeSection' => 'transferencias-admin']);
    }

    public function reviewFile(Request $request, int $proof)
    {
        $this->adminOnly($request);
        $record = \App\Models\TransferenciaComprobante::findOrFail($proof);
        return $this->streamProof($record, $request->boolean('descargar'));
    }

    public function review(Request $request, int $transferencia, PaymentFolioService $folios)
    {
        $this->adminOnly($request);
        $data = $request->validate([
            'accion' => ['required', Rule::in(['confirmar', 'rechazar'])],
            'motivo' => ['required_if:accion,rechazar', 'nullable', 'string', 'max:2000'],
            'importe_recibido' => ['required_if:accion,confirmar', 'nullable', 'numeric', 'min:0.01'],
            'concepto_recibido' => ['required_if:accion,confirmar', 'nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);
        $result = DB::transaction(function () use ($request, $transferencia, $data, $folios) {
            $transfer = TransferenciaPago::query()->whereKey($transferencia)->lockForUpdate()->with('empresa')->firstOrFail();
            abort_unless($transfer->estado === 'pendiente_de_confirmar', 409, 'La solicitud ya fue revisada.');
            $empresa = Empresa::query()->whereKey($transfer->empresa_id)->lockForUpdate()->firstOrFail();
            $transfer->forceFill(['revisado_por' => $request->user()->id, 'revisado_en' => now(), 'observaciones' => $data['observaciones'] ?? null])->save();
            if ($data['accion'] === 'rechazar') {
                $transfer->forceFill(['estado' => 'rechazado', 'motivo_rechazo' => $data['motivo']])->save();
                DB::table('transferencia_auditoria')->insert(['transferencia_pago_id' => $transfer->id, 'usuario_id' => $request->user()->id, 'accion' => 'rechazado', 'datos' => json_encode(['motivo' => $data['motivo']]), 'created_at' => now(), 'updated_at' => now()]);
                return ['status' => 'rechazado'];
            }
            abort_unless($empresa->metodo_pago === 'TRANSFERENCIA' && $empresa->fecha_renovacion?->toDateString() === $transfer->fecha_renovacion->toDateString(), 409, 'El periodo ya no corresponde a la renovación actual.');
            abort_unless($transfer->comprobanteActual()->exists(), 409, 'La solicitud no tiene comprobante para revisar.');
            $amountMatches = (int) round(((float) $data['importe_recibido']) * 100) === (int) round(((float) $transfer->cantidad) * 100);
            $conceptMatches = hash_equals((string) $transfer->concepto, trim((string) $data['concepto_recibido']));
            if (!$amountMatches || !$conceptMatches) {
                DB::table('transferencia_auditoria')->insert(['transferencia_pago_id' => $transfer->id, 'usuario_id' => $request->user()->id, 'accion' => 'confirmacion_con_discrepancia', 'datos' => json_encode(['importe_verificado' => $data['importe_recibido'], 'concepto_verificado' => $data['concepto_recibido'], 'importe_coincide' => $amountMatches, 'concepto_coincide' => $conceptMatches]), 'created_at' => now(), 'updated_at' => now()]);
                return ['status' => 'discrepancia'];
            }
            $existing = IntentoPago::query()->where('empresa_id', $empresa->id)->whereDate('fecha_renovacion', $transfer->fecha_renovacion)->where('resultado', 'exitoso')->exists();
            abort_unless(!$existing, 409, 'Este periodo ya tiene un pago confirmado.');
            $attempt = IntentoPago::query()->firstOrCreate(['idempotency_key' => 'horalia:transfer:'.$transfer->id], [
                'empresa_id' => $empresa->id, 'fecha_renovacion' => $transfer->fecha_renovacion, 'origen' => 'transferencia', 'concepto' => $transfer->concepto,
                'cantidad' => $transfer->cantidad, 'moneda' => 'mxn', 'resultado' => 'exitoso', 'intentado_en' => now(), 'descripcion' => 'Transferencia confirmada manualmente por superadministración.',
            ]);
            abort_unless($attempt->resultado === 'exitoso', 409);
            $folios->assign($attempt);
            $newDate = \App\Services\MembershipRenewalDate::next($empresa);
            $empresa->forceFill(['fecha_renovacion' => $newDate, 'intentos' => 0, 'activa' => true, 'cancelar_al_renovar' => false])->save();
            $transfer->forceFill(['estado' => 'confirmado'])->save();
            DB::table('transferencia_auditoria')->insert(['transferencia_pago_id' => $transfer->id, 'usuario_id' => $request->user()->id, 'accion' => 'confirmado', 'datos' => json_encode(['intento_pago_id' => $attempt->id, 'importe_verificado' => $data['importe_recibido'], 'concepto_verificado' => $data['concepto_recibido'], 'nueva_fecha' => $newDate]), 'created_at' => now(), 'updated_at' => now()]);
            return ['status' => 'confirmado', 'empresa' => $empresa, 'new_date' => $newDate, 'attempt' => $attempt];
        }, 3);
        if ($result['status'] === 'confirmado') {
            foreach ($result['empresa']->usuarios()->where('profile', 2)->whereNotNull('email')->get() as $admin) {
                try { Mail::to($admin->email)->queue(new \App\Mail\TransferenciaConfirmadaMail($result['empresa'], $result['attempt'], $result['new_date'])); }
                catch (Throwable $e) { Log::error('No se pudo encolar confirmación de transferencia.', ['transferencia_id' => $transferencia, 'user_id' => $admin->id]); }
            }
        } elseif ($result['status'] === 'rechazado') {
            foreach (TransferenciaPago::findOrFail($transferencia)->empresa->usuarios()->where('profile', 2)->whereNotNull('email')->get() as $admin) {
                try { Mail::to($admin->email)->queue(new \App\Mail\TransferenciaRechazadaMail(TransferenciaPago::findOrFail($transferencia))); } catch (Throwable $e) { Log::error('No se pudo notificar rechazo de transferencia.', ['transferencia_id' => $transferencia]); }
            }
        } else {
            return back()->withErrors(['transferencia' => 'El importe recibido o el concepto no coincide exactamente. No se renovó la suscripción; rechaza el comprobante o solicita una aclaración.']);
        }
        return back()->with('status', $result['status'] === 'confirmado' ? 'Pago confirmado y suscripción renovada.' : 'Comprobante rechazado; el cliente puede reemplazarlo para el mismo periodo.');
    }

    private function companyForUser(Request $request, int $id): Empresa
    {
        $user = $request->user(); abort_unless(in_array((int) $user->profile, [1, 2], true), 403);
        return (int) $user->profile === 1 ? Empresa::findOrFail($id) : $user->empresas()->where('empresas.id', $id)->firstOrFail();
    }
    private function adminOnly(Request $request): void { abort_unless((int) $request->user()->profile === 1, 403); }
    private function streamProof(\App\Models\TransferenciaComprobante $proof, bool $download)
    {
        $disk = Storage::disk('wasabi'); abort_unless($disk->exists($proof->ruta_privada), 404);
        $stream = $disk->readStream($proof->ruta_privada); abort_unless(is_resource($stream), 404);
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($proof->nombre_original));
        return response()->stream(function () use ($stream): void { fpassthru($stream); fclose($stream); }, 200, ['Content-Type' => $proof->mime, 'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$safeName.'"', 'X-Content-Type-Options' => 'nosniff', 'Cross-Origin-Resource-Policy' => 'same-origin', 'Content-Security-Policy' => "sandbox; default-src 'none'", 'Cache-Control' => 'private, no-store']);
    }
}
