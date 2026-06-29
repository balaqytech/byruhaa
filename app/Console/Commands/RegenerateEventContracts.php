<?php

namespace App\Console\Commands;

use App\Models\EventContract;
use App\Services\ContractRenderer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contracts:regenerate {ids* : IDs of event contracts to regenerate}')]
#[Description('Regenerate event contract HTML for the provided event contract IDs')]
class RegenerateEventContracts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ContractRenderer $contractRenderer): int
    {
        $ids = collect($this->argument('ids'))
            ->map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            $this->error('Please provide at least one contract ID.');

            return self::FAILURE;
        }

        $contracts = EventContract::query()
            ->with(['bookingFamilyMember.booking.customer', 'bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember'])
            ->whereIn('id', $ids)
            ->get();

        if ($contracts->isEmpty()) {
            $this->error('No event contracts were found for the provided IDs.');

            return self::FAILURE;
        }

        $regenerated = 0;

        foreach ($contracts as $contract) {
            $bookingFamilyMember = $contract->bookingFamilyMember;

            if ($bookingFamilyMember === null) {
                $this->warn("Contract {$contract->id} is missing its booking family member; skipped.");

                continue;
            }

            $contract->forceFill([
                'contract_html' => $contractRenderer->html($bookingFamilyMember),
                'updated_at' => now(),
            ])->save();

            $this->info("Regenerated contract {$contract->id}.");
            $regenerated++;
        }

        $this->info("Regenerated {$regenerated} contract(s).");

        return self::SUCCESS;
    }
}
