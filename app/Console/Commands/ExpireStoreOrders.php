<?php

namespace App\Console\Commands;

use App\Modules\Store\Actions\ExpireInventoryReservations;
use App\Modules\Store\Actions\ExpirePendingOrders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-store-orders')]
#[Description('Expire pending store orders and their inventory reservations')]
class ExpireStoreOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ExpirePendingOrders $expirePendingOrders, ExpireInventoryReservations $expireInventoryReservations): int
    {
        $orders = $expirePendingOrders->execute();
        $reservations = $expireInventoryReservations->execute();
        $this->info("Expired {$orders} order(s) and {$reservations} inventory reservation(s).");

        return self::SUCCESS;
    }
}
