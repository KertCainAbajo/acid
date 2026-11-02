<?php

namespace App\Support;

/**
 * Makes exported CSV cells safe to open in spreadsheet apps.
 */
class CsvRow
{
    /**
     * Text starting with =, +, -, @, tab or carriage return is run as a formula by Excel and Sheets, so user-written values
     * (names, food names, notes) are prefixed with an apostrophe to keep them as plain text. Numbers are left untouched.
     *
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    public static function safe(array $values): array
    {
        return array_map(
            fn (mixed $value): mixed => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value,
            $values,
        );
    }
}
