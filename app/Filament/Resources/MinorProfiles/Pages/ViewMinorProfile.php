<?php

namespace App\Filament\Resources\MinorProfiles\Pages;

use App\Filament\Resources\MinorProfiles\MinorProfileResource;
use App\Filament\Resources\Wallets\WalletResource;
use App\Modules\Finance\Models\Wallet;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMinorProfile extends ViewRecord
{
    protected static string $resource = MinorProfileResource::class;

    protected function getHeaderActions(): array
    {
        $wallet = Wallet::query()->where('minor_profile_id', $this->getRecord()->getKey())->first();

        if ($wallet === null || ! WalletResource::canView($wallet)) {
            return [];
        }

        return [
            Action::make('wallet')->label(__('admin_minors.view_wallet'))
                ->url(WalletResource::getUrl('view', ['record' => $wallet])),
        ];
    }
}
