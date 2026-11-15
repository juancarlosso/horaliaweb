<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('transferencias_pago')) Schema::create('transferencias_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id');
            $table->foreign('empresa_id', 'fk_t_pago_empresa')->references('id')->on('empresas')->restrictOnDelete();
            $table->date('fecha_renovacion');
            $table->decimal('cantidad', 10, 2);
            $table->string('concepto', 64);
            $table->string('estado', 32)->default('pendiente_de_pago');
            $table->foreignId('usuario_carga_id')->nullable();
            $table->foreign('usuario_carga_id', 'fk_t_pago_usuario_carga')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('cargado_en')->nullable();
            $table->foreignId('revisado_por')->nullable();
            $table->foreign('revisado_por', 'fk_t_pago_revisor')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('correo_admin_enviado_en')->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'fecha_renovacion'], 'transferencia_empresa_periodo_uq');
            $table->index(['estado', 'fecha_renovacion']);
        });

        if (!Schema::hasTable('transferencia_comprobantes')) Schema::create('transferencia_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_pago_id');
            $table->foreign('transferencia_pago_id', 'fk_t_comp_pago')->references('id')->on('transferencias_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable();
            $table->foreign('usuario_id', 'fk_t_comp_usuario')->references('id')->on('users')->nullOnDelete();
            $table->string('ruta_privada', 1024);
            $table->string('nombre_original');
            $table->string('mime', 100);
            $table->unsignedBigInteger('tamano');
            $table->timestamps();
            $table->index(['transferencia_pago_id', 'id']);
        });

        if (!Schema::hasTable('transferencia_auditoria')) Schema::create('transferencia_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_pago_id');
            $table->foreign('transferencia_pago_id', 'fk_t_aud_pago')->references('id')->on('transferencias_pago')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable();
            $table->foreign('usuario_id', 'fk_t_aud_usuario')->references('id')->on('users')->nullOnDelete();
            $table->string('accion', 40);
            $table->json('datos')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('transferencia_condiciones_aceptadas')) Schema::create('transferencia_condiciones_aceptadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_pago_id');
            $table->foreign('transferencia_pago_id', 'fk_t_cond_pago')->references('id')->on('transferencias_pago')->restrictOnDelete();
            $table->foreignId('empresa_id');
            $table->foreign('empresa_id', 'fk_t_cond_empresa')->references('id')->on('empresas')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable();
            $table->foreign('usuario_id', 'fk_t_cond_usuario')->references('id')->on('users')->nullOnDelete();
            $table->string('version', 100);
            $table->text('texto');
            $table->timestamp('aceptado_en');
            $table->timestamps();
        });

        // MySQL can create a table before failing to add an overlong foreign-key
        // constraint. Keep this migration resumable after such a partial run.
        $ensureForeignKey = static function (string $tableName, string $column, string $name, string $parent, string $onDelete): void {
            $exists = collect(Schema::getForeignKeys($tableName))->contains(
                fn (array $key) => in_array($column, is_array($key['columns']) ? $key['columns'] : explode(',', (string) $key['columns']), true)
            );
            if ($exists) return;

            Schema::table($tableName, function (Blueprint $table) use ($column, $name, $parent, $onDelete): void {
                $foreign = $table->foreign($column, $name)->references('id')->on($parent);
                if ($onDelete === 'cascade') $foreign->cascadeOnDelete();
                elseif ($onDelete === 'set null') $foreign->nullOnDelete();
                else $foreign->restrictOnDelete();
            });
        };

        foreach ([
            ['transferencias_pago', 'empresa_id', 'fk_t_pago_empresa', 'empresas', 'restrict'],
            ['transferencias_pago', 'usuario_carga_id', 'fk_t_pago_usuario_carga', 'users', 'set null'],
            ['transferencias_pago', 'revisado_por', 'fk_t_pago_revisor', 'users', 'set null'],
            ['transferencia_comprobantes', 'transferencia_pago_id', 'fk_t_comp_pago', 'transferencias_pago', 'restrict'],
            ['transferencia_comprobantes', 'usuario_id', 'fk_t_comp_usuario', 'users', 'set null'],
            ['transferencia_auditoria', 'transferencia_pago_id', 'fk_t_aud_pago', 'transferencias_pago', 'restrict'],
            ['transferencia_auditoria', 'usuario_id', 'fk_t_aud_usuario', 'users', 'set null'],
            ['transferencia_condiciones_aceptadas', 'transferencia_pago_id', 'fk_t_cond_pago', 'transferencias_pago', 'restrict'],
            ['transferencia_condiciones_aceptadas', 'empresa_id', 'fk_t_cond_empresa', 'empresas', 'restrict'],
            ['transferencia_condiciones_aceptadas', 'usuario_id', 'fk_t_cond_usuario', 'users', 'set null'],
        ] as [$tableName, $column, $name, $parent, $onDelete]) {
            $ensureForeignKey($tableName, $column, $name, $parent, $onDelete);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_condiciones_aceptadas');
        Schema::dropIfExists('transferencia_auditoria');
        Schema::dropIfExists('transferencia_comprobantes');
        Schema::dropIfExists('transferencias_pago');
    }
};
