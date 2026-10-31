<?php

namespace App\Modules\Store\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['store_order_refund_request_id', 'order_item_id', 'quantity', 'amount_baisa', 'subtotal_baisa', 'vat_baisa'])]
class OrderRefundLine extends Model
{
    protected $table = 'store_order_refund_lines';

    /** @return BelongsTo<OrderRefundRequest, $this> */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(OrderRefundRequest::class, 'store_order_refund_request_id');
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
