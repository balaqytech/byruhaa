<?php

namespace App\Filament\Resources\MinorProfiles\Schemas;

use App\Filament\Resources\Customers\CustomerResource;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MinorProfileInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin_minors.label'))->columnSpanFull()->columns(2)->schema([
                TextEntry::make('member_code')->label(__('admin_minors.member_code'))->copyable(),
                TextEntry::make('familyMember.name')->label(__('admin_minors.name')),
                TextEntry::make('familyMember.birth_date')->label(__('admin_minors.birth_date'))->date(),
                TextEntry::make('familyMember.customer.name')->label(__('admin_minors.guardian'))
                    ->url(fn (MinorProfile $record): ?string => CustomerResource::canEdit($record->guardian())
                        ? CustomerResource::getUrl('edit', ['record' => $record->guardian()]) : null),
                TextEntry::make('familyMember.customer.phone_number')->label(__('admin_minors.guardian_phone')),
                TextEntry::make('status')->label(__('admin_minors.status'))->badge()
                    ->formatStateUsing(fn (MinorProfileStatus $state): string => __('admin_minors.statuses.'.$state->value)),
                IconEntry::make('wallet_spending_enabled')->label(__('admin_minors.wallet_spending_enabled'))->boolean(),
                IconEntry::make('direct_payment_enabled')->label(__('admin_minors.direct_payment_enabled'))->boolean(),
                TextEntry::make('activated_at')->label(__('admin_minors.activated_at'))->dateTime()->placeholder('-'),
                TextEntry::make('suspended_at')->label(__('admin_minors.suspended_at'))->dateTime()->placeholder('-'),
                TextEntry::make('invalidated_at')->label(__('admin_minors.invalidated_at'))->dateTime()->placeholder('-'),
                TextEntry::make('invalidation_reason')->label(__('admin_minors.invalidation_reason'))->placeholder('-'),
                TextEntry::make('deletion_requested_at')->label(__('admin_minors.deletion_requested_at'))->dateTime()->placeholder('-'),
                TextEntry::make('created_at')->label(__('admin_minors.created_at'))->dateTime(),
            ]),
        ]);
    }
}
