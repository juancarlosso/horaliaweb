<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'personal_empresa_departamento_idx';

    public function up(): void
    {
        Schema::table('personal', function (Blueprint $table): void {
            $table->index(['empresa_id', 'departamento_id'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('personal', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
        });
    }
};
