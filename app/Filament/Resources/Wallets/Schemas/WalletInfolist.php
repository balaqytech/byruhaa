<?php

namespace App\Filament\Resources\Wallets\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\MinorProfiles\MinorProfileResource;
use App\Modules\Finance\Models\Wallet;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WalletInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin_wallets.wallet'))->columnSpanFull()->columns(3)->schema([
                TextEntry::make('id')->label(__('admin_wallets.id')),
                TextEntry::make('minorProfile.familyMember.name')->label(__('admin_wallets.minor'))->url(fn (Wallet $record): string => MinorProfileResource::getUrl('view', ['record' => $record->minor_profile_id])),
                TextEntry::make('minorProfile.member_code')->label(__('admin_wallets.member_code'))->copyable(),
                TextEntry::make('minorProfile.familyMember.customer.name')->label(__('admin_wallets.guardian'))->url(fn (Wallet $record): string => CustomerResource::getUrl('edit', ['record' => $record->minorProfile->familyMember->customer_id])),
                TextEntry::make('minorProfile.familyMember.customer.phone_number')->label(__('admin.fields.phone_number')),
                TextEntry::make('balance_baisa')->label(__('admin_wallets.balance'))->money('OMR', divideBy: 1000)->helperText(__('admin_wallets.balance_help')),
                TextEntry::make('status')->label(__('admin.fields.status'))->badge()->formatStateUsing(fn (string $state): string => __('admin_wallets.statuses.'.$state)),
                TextEntry::make('created_at')->label(__('admin.fields.created_at'))->dateTime(),
            ]),
        ]);
    }
}
