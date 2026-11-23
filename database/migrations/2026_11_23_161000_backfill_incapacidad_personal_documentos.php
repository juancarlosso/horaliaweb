<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('incapacidad_comprobantes as comprobante')
            ->join('incapacidades as incapacidad', 'incapacidad.id', '=', 'comprobante.incapacidad_id')
            ->whereNull('comprobante.personal_documento_id')
            ->whereNull('comprobante.deleted_at')
            ->select('comprobante.*', 'incapacidad.empresa_id', 'incapacidad.personal_id')
            ->chunkById(250, function ($comprobantes) {
                DB::transaction(function () use ($comprobantes) {
                    foreach ($comprobantes as $comprobante) {
                        $this->asociar($comprobante);
                    }
                });
            }, 'comprobante.id', 'id');
    }

    private function asociar(object $comprobante): void
    {
        $documentos = DB::table('personal_documentos');
        $documento = $documentos->where('ruta_privada', $comprobante->ruta_privada)->lockForUpdate()->first();

        if ($documento && ((int) $documento->empresa_id !== (int) $comprobante->empresa_id
            || (int) $documento->personal_id !== (int) $comprobante->personal_id || $documento->hora_extra_id !== null)) {
            throw new RuntimeException("El comprobante de incapacidad {$comprobante->id} comparte la ruta con un documento de otro expediente u otra autorización.");
        }

        $vinculo = DB::table('incapacidad_comprobantes')
            ->where('id', $comprobante->id)->whereNull('personal_documento_id')->whereNull('deleted_at');
        if ($documento?->deleted_at !== null) {
            $vinculo->update(['deleted_at' => $documento->deleted_at]);

            return;
        }

        $id = $documento?->id ?? DB::table('personal_documentos')->insertGetId([
            'tipo' => 'Incapacidad',
            'empresa_id' => $comprobante->empresa_id, 'personal_id' => $comprobante->personal_id,
            'ruta_privada' => $comprobante->ruta_privada, 'nombre_original' => $comprobante->nombre_original,
            'mime' => $comprobante->mime, 'tamano' => $comprobante->tamano,
            'cargado_por' => $comprobante->cargado_por_user_id,
            'created_at' => $comprobante->created_at, 'updated_at' => $comprobante->updated_at,
        ]);

        $vinculo->update(['personal_documento_id' => $id]);
    }

    public function down(): void
    {
        // Conservar los documentos incorporados al expediente al revertir el vínculo.
    }
};
