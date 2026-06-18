<?php

namespace App\Models;

use App\States\Contract\ContractState;
use App\States\Contract\Signed;
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
 * @property string|null $signature_path
 * @property string|null $signed_name
 * @property string|null $signed_ip
 * @property Carbon|null $signed_at
 */
#[Fillable(['booking_family_member_id', 'state', 'contract_html', 'signature_path', 'signed_name', 'signed_ip', 'signed_at'])]
class EventContract extends Model
{
    /** @use HasFactory<EventContractFactory> */
    use HasFactory, HasStates;

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
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => ContractState::class,
            'signed_at' => 'datetime',
        ];
    }
}
