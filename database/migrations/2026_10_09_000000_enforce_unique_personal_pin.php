<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair duplicates first in case an installation does not have the
        // unique index created by the original PIN migration.
        DB::table('personal')
            ->select('pin')
            ->whereNotNull('pin')
            ->groupBy('pin')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('pin')
            ->each(function (string $duplicatePin): void {
                $duplicateIds = DB::table('personal')
                    ->where('pin', $duplicatePin)
                    ->orderBy('id')
                    ->skip(1)
                    ->pluck('id');

                foreach ($duplicateIds as $id) {
                    do {
                        $pin = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                    } while (DB::table('personal')->where('pin', $pin)->exists());

                    DB::table('personal')->where('id', $id)->update(['pin' => $pin]);
                }
            });

        if (!Schema::hasIndex('personal', ['pin'], 'unique')) {
            Schema::table('personal', function (Blueprint $table): void {
                $table->unique('pin', 'personal_pin_unique');
            });
        }
    }

    public function down(): void
    {
        // PIN uniqueness is a data invariant and may already have been
        // provided by the original migration, so rolling this repair back
        // must not remove that existing constraint.
    }
};
