<?php

namespace App\Mail;

class EmailSubject
{
    public static function prefix(string $subject): string
    {
        $prefix = config('app.name', 'Laravel').' | ';

        return str_starts_with($subject, $prefix) ? $subject : $prefix.$subject;
    }
}
