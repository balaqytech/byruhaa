<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class HumanReference
{
    public static function next(string $type): string
    {
        $sequence = DB::table('human_reference_sequences')->insertGetId([
            'type' => $type,
        ]);

        return (string) (10_000_000 + $sequence);
    }
}
