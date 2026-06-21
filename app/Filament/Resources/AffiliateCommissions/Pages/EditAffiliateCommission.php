<?php

namespace App\Filament\Resources\AffiliateCommissions\Pages;

use App\Filament\Resources\AffiliateCommissions\AffiliateCommissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateCommission extends EditRecord
{
    protected static string $resource = AffiliateCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
