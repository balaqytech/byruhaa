<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $booking = $this->getRecord();

        if (! $booking instanceof Booking) {
            return;
        }

        $booking->load([
            'customer',
            'event',
            'reviewer',
            'familyMembers.familyMember',
            'familyMembers.contract',
            'paymentSchedule.installments.payments.refunds',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
