<?php

namespace App\Services;

use App\Models\BookingFamilyMember;
use App\Models\EventContract;
use App\Support\ContractVariables;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class ContractRenderer
{
    public function html(BookingFamilyMember $bookingFamilyMember): string
    {
        $bookingFamilyMember->loadMissing(['booking.customer', 'booking.event', 'familyMember']);
        $event = clone $bookingFamilyMember->booking->event;

        $event->setAttribute(
            'contract_terms_html',
            ContractVariables::render((string) $event->contract_terms_html, $bookingFamilyMember),
        );

        return view('contracts.event', [
            'bookingFamilyMember' => $bookingFamilyMember,
            'booking' => $bookingFamilyMember->booking,
            'customer' => $bookingFamilyMember->booking->customer,
            'event' => $event,
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
        ], [], [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'xbriyaz',
            'default_font_size' => 13,
            'margin_left' => 16,
            'margin_right' => 16,
            'margin_top' => 18,
            'margin_bottom' => 18,
            'margin_header' => 7,
            'margin_footer' => 7,
            'orientation' => 'P',
            'title' => 'عقد مشاركة - '.$contract->bookingFamilyMember->booking->reference,
            'author' => 'بيرحاء للفعاليات',
            'auto_language_detection' => true,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ])->output();
    }
}
