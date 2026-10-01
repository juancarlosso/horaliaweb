<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->decimal('precio', 10, 2)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_renovacion')->nullable();
        });

        DB::table('empresas')->orderBy('id')->chunkById(500, function ($empresas) {
            foreach ($empresas as $empresa) {
                $fechaInicio = Carbon::parse($empresa->created_at ?? now())->startOfDay();

                DB::table('empresas')
                    ->where('id', $empresa->id)
                    ->update([
                        'precio' => config('constantes.precio_mensual_por_empresa_mxn'),
                        'fecha_inicio' => $fechaInicio->toDateString(),
                        'fecha_renovacion' => $fechaInicio->copy()->addMonthNoOverflow()->toDateString(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['precio', 'fecha_inicio', 'fecha_renovacion']);
        });
    }
};
