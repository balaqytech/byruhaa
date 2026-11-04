<?php

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Events\WalletWithdrawalStateChanged;
use App\Modules\Identity\Models\Customer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\Support\HumanReference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property Carbon|null $paid_at
 * @property Carbon|null $approved_at
 */
#[Fillable(['wallet_id', 'guardian_customer_id', 'reference', 'operation_key', 'amount_baisa', 'currency', 'status', 'method', 'bank_account', 'bank_name', 'bank_beneficiary_name', 'payout_reference', 'evidence_path', 'review_note', 'approved_by_user_id', 'paid_by_user_id', 'approved_at', 'paid_at'])]
#[Hidden(['bank_account'])]
class WalletWithdrawalRequest extends Model implements AuditableContract
{
    use AuditableTrait;

    /** @var list<string> */
    protected $auditInclude = ['wallet_id', 'guardian_customer_id', 'reference', 'amount_baisa', 'currency', 'status', 'method', 'bank_name', 'bank_beneficiary_name', 'payout_reference', 'evidence_path', 'review_note', 'approved_by_user_id', 'paid_by_user_id', 'approved_at', 'paid_at'];

    protected static function booted(): void
    {
        static::creating(function (self $withdrawal): void {
            $withdrawal->reference ??= HumanReference::next('wallet_withdrawal');
        });
        static::created(function (self $withdrawal): void {
            app(ByruhaaWebhookSender::class)->sendUchatWalletWithdrawal($withdrawal);
            WalletWithdrawalStateChanged::dispatch($withdrawal);
        });
        static::updated(function (self $withdrawal): void {
            if ($withdrawal->wasChanged('status')) {
                app(ByruhaaWebhookSender::class)->sendUchatWalletWithdrawal($withdrawal);
                WalletWithdrawalStateChanged::dispatch($withdrawal);
            }
        });
    }

    /** @return BelongsTo<Wallet, $this> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'guardian_customer_id');
    }

    /** @return HasMany<WalletWithdrawalAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(WalletWithdrawalAllocation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_baisa' => 'integer',
            'bank_account' => 'encrypted',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
