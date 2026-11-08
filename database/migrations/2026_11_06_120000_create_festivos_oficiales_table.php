<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festivos_oficiales', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('conmemoracion', 150);
            $table->timestamps();
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festivos_oficiales');
    }
};
