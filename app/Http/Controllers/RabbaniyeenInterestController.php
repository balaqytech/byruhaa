<?php

namespace App\Http\Controllers;

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Http\Requests\StoreRabbaniyeenInterestRequest;
use App\Modules\Events\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RabbaniyeenInterestController extends Controller
{
    public function __invoke(StoreRabbaniyeenInterestRequest $request, Event $event): RedirectResponse
    {
        abort_unless(
            $event->landing_page_key === 'rabbaniyeen-v1'
                && $event->status === EventStatus::Published
                && $event->enrollment_status === EventEnrollmentStatus::InterestOpen,
            404,
        );

        $data = $request->validated();
        $timestamp = now();

        DB::table('event_interest_leads')->upsert([
            [
                'event_id' => $event->id,
                'reference_code' => 'RB-'.Str::upper(Str::random(10)),
                'guardian_name' => trim($data['guardian_name']),
                'whatsapp_number' => $data['whatsapp_number'],
                'wilaya' => trim($data['wilaya']),
                'student_grade' => $data['student_grade'],
                'recitation_level' => $data['recitation_level'] ?? null,
                'preferred_track' => $data['preferred_track'] ?? null,
                'note' => $data['note'] ?? null,
                'consent_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ], ['event_id', 'whatsapp_number'], [
            'guardian_name', 'wilaya', 'student_grade', 'recitation_level',
            'preferred_track', 'note', 'consent_at', 'updated_at',
        ]);

        $reference = DB::table('event_interest_leads')
            ->where('event_id', $event->id)
            ->where('whatsapp_number', $data['whatsapp_number'])
            ->value('reference_code');

        return redirect()->to(route('events.show', $event).'#sajjil')
            ->with('rabbaniyeen_interest_reference', $reference);
    }
}
