<?php

namespace App\Services;

use App\Models\BookingFamilyMember;
use App\Models\EventContract;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ContractRenderer
{
    public function html(BookingFamilyMember $bookingFamilyMember): string
    {
        $bookingFamilyMember->loadMissing(['booking.customer', 'booking.event', 'familyMember']);

        return view('contracts.event', [
            'bookingFamilyMember' => $bookingFamilyMember,
            'booking' => $bookingFamilyMember->booking,
            'customer' => $bookingFamilyMember->booking->customer,
            'event' => $bookingFamilyMember->booking->event,
            'familyMember' => $bookingFamilyMember->familyMember,
            'contract' => $bookingFamilyMember->contract,
        ])->render();
    }

    public function pdf(EventContract $contract): string
    {
        $contract->loadMissing('bookingFamilyMember.booking.customer', 'bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember');

        return PDF::loadView('contracts.event-pdf', [
            'contract' => $contract,
            'bookingFamilyMember' => $contract->bookingFamilyMember,
            'booking' => $contract->bookingFamilyMember->booking,
            'customer' => $contract->bookingFamilyMember->booking->customer,
            'event' => $contract->bookingFamilyMember->booking->event,
            'familyMember' => $contract->bookingFamilyMember->familyMember,
        ])->output();
    }
}
