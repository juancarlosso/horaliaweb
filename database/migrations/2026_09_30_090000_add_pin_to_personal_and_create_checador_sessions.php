<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table) {
            $table->string('pin', 5)->nullable()->unique()->after('codigo');
        });

        DB::table('personal')->whereNull('pin')->orderBy('id')->chunkById(250, function ($employees) {
            foreach ($employees as $employee) {
                do {
                    $pin = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                } while (DB::table('personal')->where('pin', $pin)->exists());

                DB::table('personal')->where('id', $employee->id)->update(['pin' => $pin]);
            }
        });

        Schema::create('checador_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('centro_id')->constrained('centros')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('token');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('activation_ip', 45)->nullable();
            $table->string('activation_user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'centro_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checador_sesiones');
        Schema::table('personal', function (Blueprint $table) {
            $table->dropUnique(['pin']);
            $table->dropColumn('pin');
        });
    }
};
