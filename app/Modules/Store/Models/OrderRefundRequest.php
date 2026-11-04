<?php

namespace App\Modules\Store\Models;

use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Store\Events\OrderRefundStateChanged;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\Support\HumanReference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['order_id', 'customer_id', 'requested_by_user_id', 'approved_by_user_id', 'paid_by_user_id', 'payment_refund_id', 'reference', 'operation_key', 'status', 'reason', 'amount_baisa', 'subtotal_baisa', 'vat_baisa', 'currency', 'payout_reference', 'evidence_path', 'review_note', 'admin_window_override', 'approved_at', 'paid_at'])]
class OrderRefundRequest extends Model implements AuditableContract
{
    use AuditableTrait;

    /** @var list<string> */
    protected $auditInclude = ['order_id', 'customer_id', 'reference', 'amount_baisa', 'subtotal_baisa', 'vat_baisa', 'status', 'reason', 'payout_reference', 'review_note', 'approved_by_user_id', 'paid_by_user_id', 'approved_at', 'paid_at'];

    protected $table = 'store_order_refund_requests';

    protected static function booted(): void
    {
        static::creating(function (self $refund): void {
            $refund->reference ??= HumanReference::next('store_refund');
        });
        static::created(function (self $refund): void {
            app(ByruhaaWebhookSender::class)->sendUchatOrderRefund($refund);
            OrderRefundStateChanged::dispatch($refund);
        });
        static::updated(function (self $refund): void {
            if ($refund->wasChanged('status')) {
                app(ByruhaaWebhookSender::class)->sendUchatOrderRefund($refund);
                OrderRefundStateChanged::dispatch($refund);
            }
        });
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<PaymentRefund, $this> */
    public function paymentRefund(): BelongsTo
    {
        return $this->belongsTo(PaymentRefund::class);
    }

    /** @return HasMany<OrderRefundLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderRefundLine::class, 'store_order_refund_request_id');
    }

    protected function casts(): array
    {
        return [
            'amount_baisa' => 'integer',
            'subtotal_baisa' => 'integer',
            'vat_baisa' => 'integer',
            'admin_window_override' => 'boolean',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
