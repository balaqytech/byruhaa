<?php

namespace App\Console\Commands;

use App\Actions\ImportMigrantsEventBookings as ImportMigrantsEventBookingsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('events:import-migrants-2026 {path : Path to the XLSX workbook} {--temporary-password= : Shared temporary password for imported customers}')]
#[Description('Import the legacy Migrants 2026 event bookings workbook')]
class ImportMigrantsEventBookings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImportMigrantsEventBookingsAction $importMigrantsEventBookings): int
    {
        $path = (string) $this->argument('path');
        $temporaryPassword = (string) $this->option('temporary-password');

        try {
            $result = $importMigrantsEventBookings->execute($path, $temporaryPassword);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported {$result['bookings']} booking(s), {$result['participants']} participant(s), and {$result['payments']} paid payment(s).");
        $this->info("Queued {$result['approval_webhooks']} booking approval webhook(s).");

        return self::SUCCESS;
    }
}
