<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->string('folio', 25)->nullable()->unique();
        });

        DB::table('horas_extras')->select('id')->whereNull('folio')->chunkById(250, function ($registros) {
            foreach ($registros as $registro) {
                $letras = chr(random_int(65, 90)).chr(random_int(65, 90));
                $folio = 'HE-'.$letras.str_pad((string) $registro->id, 5, '0', STR_PAD_LEFT);

                DB::table('horas_extras')->where('id', $registro->id)->whereNull('folio')->update(['folio' => $folio]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('horas_extras', function (Blueprint $table) {
            $table->dropUnique(['folio']);
            $table->dropColumn('folio');
        });
    }
};
