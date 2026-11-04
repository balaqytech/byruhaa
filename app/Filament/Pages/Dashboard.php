<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('cashierWorkspace')
                ->label('نقطة البيع')
                ->url(route('cashier.login'))
                ->openUrlInNewTab(),
            Action::make('baristaWorkspace')
                ->label('شاشة الباريستا')
                ->url(route('barista.login'))
                ->openUrlInNewTab(),
            Action::make('pickupWorkspace')
                ->label('نقطة التسليم')
                ->url(route('pickup.login'))
                ->openUrlInNewTab(),
        ];
    }
}
