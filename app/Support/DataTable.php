<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DataTable
{
    public static function lengths(): array
    {
        $lengths = array_unique([10, 25, (int) config('constantes.itemsPorPagina'), 100]);
        sort($lengths);

        return $lengths;
    }

    public static function options(Request $request, array $sortable): array
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in(self::lengths())],
            'sort' => ['nullable', Rule::in($sortable)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        return [
            (int) ($validated['per_page'] ?? config('constantes.itemsPorPagina')),
            $validated['sort'] ?? null,
            $validated['direction'] ?? 'asc',
        ];
    }
}
