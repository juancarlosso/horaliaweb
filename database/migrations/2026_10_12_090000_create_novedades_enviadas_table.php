<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('novedades_enviadas', function (Blueprint $table) {
            $table->id();
            $table->string('identificador', 120);
            $table->string('email', 254);
            $table->char('email_hash', 64);
            $table->timestamp('enviado_at')->useCurrent();
            $table->timestamps();
            $table->unique(['identificador', 'email_hash'], 'novedades_enviadas_identificador_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('novedades_enviadas');
    }
};
