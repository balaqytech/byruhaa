<?php

namespace App\Modules\Events\Models;

use App\Modules\Events\States\Contract\ContractState;
use App\Modules\Events\States\Contract\Signed;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Database\Factories\EventContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $booking_family_member_id
 * @property ContractState $state
 * @property string $contract_html
 * @property array<string, mixed>|null $participant_extra_answers
 * @property Carbon|null $participant_extra_completed_at
 * @property string|null $signature_path
 * @property string|null $signed_name
 * @property string|null $signed_ip
 * @property Carbon|null $signed_at
 * @property Carbon|null $superseded_at
 */
#[Fillable(['booking_family_member_id', 'state', 'contract_html', 'participant_extra_answers', 'participant_extra_completed_at', 'signature_path', 'signed_name', 'signed_ip', 'signed_at', 'superseded_at'])]
class EventContract extends Model
{
    /** @use HasFactory<EventContractFactory> */
    use HasFactory, HasStates;

    protected static function newFactory(): EventContractFactory
    {
        return EventContractFactory::new();
    }

    /**
     * @return BelongsTo<BookingFamilyMember, $this>
     */
    public function bookingFamilyMember(): BelongsTo
    {
        return $this->belongsTo(BookingFamilyMember::class);
    }

    public function sign(string $signatureDataUrl, string $signedName, ?string $ipAddress = null): void
    {
        if (! preg_match('/^data:image\/png;base64,(?<payload>.+)$/', $signatureDataUrl, $matches)) {
            throw ValidationException::withMessages([
                'signature' => __('ui.messages.signature_must_be_png'),
            ]);
        }

        $signature = base64_decode($matches['payload'], true);

        if ($signature === false) {
            throw ValidationException::withMessages([
                'signature' => __('ui.messages.invalid_signature'),
            ]);
        }

        $path = "contracts/signatures/{$this->id}.png";

        Storage::disk('local')->put($path, $signature);

        $this->forceFill([
            'signature_path' => $path,
            'signed_name' => $signedName,
            'signed_ip' => $ipAddress,
            'signed_at' => now(),
        ])->save();

        $this->state->transitionTo(Signed::class);

        $booking = $this->bookingFamilyMember()
            ->with('booking.familyMembers.contract')
            ->first()
            ?->booking;

        if ($booking?->hasSignedContracts()) {
            app(ByruhaaWebhookSender::class)->sendBookingContractsSigned($booking);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => ContractState::class,
            'participant_extra_answers' => 'array',
            'participant_extra_completed_at' => 'datetime',
            'signed_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }
}
