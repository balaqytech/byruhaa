<?php

use App\Modules\Store\Models\Order;
use App\Support\HumanReference;
use Illuminate\Support\Facades\DB;

test('legacy numeric references advance the shared sequence before new orders are created', function (): void {
    Order::factory()->create(['reference' => '10000008']);

    $migration = require database_path('migrations/2026_10_02_114431_sync_human_reference_sequences_with_existing_references.php');
    $migration->up();

    expect(HumanReference::next('order'))->toBe('10000009')
        ->and(DB::table('human_reference_sequences')->where('type', 'legacy_reference_sync')->count())->toBe(1);
});
