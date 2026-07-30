<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameEventTiers('umrah-2026', [
            'الباكورة' => 'الباقة الأولى',
        ]);
        $this->renameEventTiers('after-twelfth-2026', [
            'الباكورة الأولى' => 'الباقة الأولى',
            'الباكورة الثانية' => 'الباقة الثانية',
            'الباكورة الثالثة' => 'الباقة الثالثة',
            'الباكورة الرابعة' => 'الباقة الرابعة',
            'الباكورة الخامسة' => 'الباقة الخامسة',
        ]);
    }

    public function down(): void
    {
        $this->renameEventTiers('umrah-2026', [
            'الباقة الأولى' => 'الباكورة',
        ]);
        $this->renameEventTiers('after-twelfth-2026', [
            'الباقة الأولى' => 'الباكورة الأولى',
            'الباقة الثانية' => 'الباكورة الثانية',
            'الباقة الثالثة' => 'الباكورة الثالثة',
            'الباقة الرابعة' => 'الباكورة الرابعة',
            'الباقة الخامسة' => 'الباكورة الخامسة',
        ]);
    }

    /** @param array<string, string> $labels */
    private function renameEventTiers(string $eventSlug, array $labels): void
    {
        foreach ($labels as $currentLabel => $newLabel) {
            DB::table('event_price_tiers')
                ->whereIn('event_id', DB::table('events')->select('id')->where('slug', $eventSlug))
                ->where('name', $currentLabel)
                ->update(['name' => $newLabel]);
        }
    }
};
