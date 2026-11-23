<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_documentos', function (Blueprint $table) {
            $table->foreignId('hora_extra_id')->nullable()->unique()->constrained('horas_extras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_documentos', function (Blueprint $table) {
            $table->dropForeign(['hora_extra_id']);
            $table->dropUnique(['hora_extra_id']);
            $table->dropColumn('hora_extra_id');
        });
    }
};
