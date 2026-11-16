<?php

namespace App\Support;

use Illuminate\Support\Str;

class GraphoriaAvatar
{
    public static function url(?string $name): string
    {
        $initials = Str::of($name ?? '')->squish()->explode(' ')->take(2)
            ->map(fn (string $word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode(' ');

        return 'https://graphoria.dev/avatar?name=' . urlencode($initials ?: 'P') . '&background=4f6ef7&color=ffffff&size=128';
    }
}
