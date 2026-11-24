<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incapacidad_comprobantes', function (Blueprint $table) {
            $table->foreignId('personal_documento_id')->nullable()->constrained('personal_documentos')->cascadeOnDelete();
            $table->index('personal_documento_id', 'incapacidad_comprobantes_documento_idx');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (DB::table('incapacidad_comprobantes')->whereNotNull('deleted_at')->exists()) {
            throw new RuntimeException('Revertir esta migración volvería a mostrar comprobantes eliminados del expediente.');
        }

        Schema::table('incapacidad_comprobantes', function (Blueprint $table) {
            $table->dropForeign(['personal_documento_id']);
            $table->dropIndex('incapacidad_comprobantes_documento_idx');
            $table->dropColumn(['personal_documento_id', 'deleted_at']);
        });
    }
};
