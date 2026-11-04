<?php

namespace App\Modules\Pos\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Pos\Services\CashierOrderAccess;
use App\Modules\Store\Actions\ManageOrderRefund;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use App\Support\Money\MoneyFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class CashierOrders extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $refundOrderId = null;

    public string $refundAmountOmr = '';

    public string $refundReason = '';

    public ?int $payoutRefundId = null;

    public string $receiptReference = '';

    public bool $cashHandedOver = false;

    public string $refundOperationKey = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCashRefund(int $orderId, CashierOrderAccess $access): void
    {
        $order = Order::query()->findOrFail($orderId);
        abort_unless($access->canRefund($this->cashier(), $order), 404);

        $reserved = (int) $order->refundRequests()->whereIn('status', ['pending', 'approved', 'processing', 'manual_review', 'failed', 'paid'])->sum('amount_baisa');
        $this->refundOrderId = $order->id;
        $this->refundAmountOmr = number_format(($order->total_baisa - $reserved) / 1000, 3, '.', '');
        $this->refundReason = '';
        $this->payoutRefundId = null;
        $this->refundOperationKey = (string) Str::uuid();
        $this->resetValidation();
    }

    public function requestCashRefund(ManageOrderRefund $refunds, CashierOrderAccess $access): void
    {
        $order = Order::query()->findOrFail($this->refundOrderId);
        $cashier = $this->cashier();
        abort_unless($access->canRefund($cashier, $order), 404);

        $this->validate([
            'refundAmountOmr' => ['required', 'regex:/^\d+(?:\.\d{1,3})?$/'],
            'refundReason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $amountBaisa = MoneyFactory::omrStringToBaisa($this->refundAmountOmr);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['refundAmountOmr' => 'أدخل مبلغًا صحيحًا بالريال حتى ثلاث خانات عشرية.']);
        }

        $refund = $refunds->request($order, null, $cashier->id, $amountBaisa, $this->refundReason, $this->refundOperationKey);
        if ($refund->status === 'pending') {
            $refund = $refunds->approve($refund, $cashier->id);
        }

        $this->refundOrderId = null;
        $this->payoutRefundId = $refund->id;
        $this->receiptReference = '';
        $this->cashHandedOver = false;
        $this->dispatch('cash-refund-requested');
    }

    public function openCashPayout(int $refundId, CashierOrderAccess $access): void
    {
        $refund = OrderRefundRequest::query()->with('order')->findOrFail($refundId);
        abort_unless($refund->status === 'approved' && $access->canViewCashRefund($this->cashier(), $refund->order), 404);

        $this->refundOrderId = null;
        $this->payoutRefundId = $refund->id;
        $this->receiptReference = '';
        $this->cashHandedOver = false;
        $this->resetValidation();
    }

    public function confirmCashPayout(ManageOrderRefund $refunds, CashierOrderAccess $access): void
    {
        $refund = OrderRefundRequest::query()->with('order')->findOrFail($this->payoutRefundId);
        abort_unless($refund->status === 'approved' && $access->canViewCashRefund($this->cashier(), $refund->order), 404);

        $this->validate([
            'receiptReference' => ['required', 'string', 'max:100'],
            'cashHandedOver' => ['accepted'],
        ]);

        $refunds->completeCash($refund, $this->cashier()->id, trim($this->receiptReference));
        $this->payoutRefundId = null;
        $this->cashHandedOver = false;
        $this->dispatch('cash-refund-paid');
    }

    private function cashier(): User
    {
        $cashier = Auth::guard('cashier')->user();
        abort_unless($cashier instanceof User && $cashier->can('Sell:Pos'), 403);

        return $cashier;
    }

    public function render(): View
    {
        $cashier = $this->cashier();

        $reference = trim($this->search);
        $orders = Order::query()
            ->whereNotNull('pos_cashier_user_id')
            ->when($reference === '', fn (Builder $query): Builder => $query->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()]))
            ->when($reference !== '', fn (Builder $query): Builder => $query->where('reference', $reference))
            ->where(function (Builder $query) use ($cashier, $reference): void {
                $query->where('pos_cashier_user_id', $cashier->id);

                if ($reference !== '' && $cashier->can('Reprint:PosReceipts')) {
                    $query->orWhere(fn (Builder $other): Builder => $other->whereNotNull('paid_at')->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()]));
                }
                if ($reference !== '' && $cashier->can('RefundCash:Pos')) {
                    $query->orWhere(fn (Builder $other): Builder => $other
                        ->whereNotNull('paid_at')
                        ->where('payment_method', 'cash')
                        ->where(fn (Builder $eligible): Builder => $eligible
                            ->whereDoesntHave('statusHistory', fn (Builder $history): Builder => $history->where('to_status', 'completed'))
                            ->orWhereHas('statusHistory', fn (Builder $history): Builder => $history->where('to_status', 'completed')->where('created_at', '>=', now()->subHours(24)))
                            ->orWhereHas('refundRequests', fn (Builder $refund): Builder => $refund->where('status', 'approved'))));
                }
            })
            ->with(['refundRequests' => fn ($query) => $query->whereIn('status', ['pending', 'approved', 'processing', 'manual_review', 'failed', 'paid'])->latest('id')])
            ->latest('created_at')
            ->simplePaginate(20);

        return view('livewire.store.cashier-orders', [
            'orders' => $orders,
            'cashier' => $cashier,
            'cashierOrderAccess' => app(CashierOrderAccess::class),
            'payoutRefund' => $this->payoutRefundId === null ? null : OrderRefundRequest::query()->find($this->payoutRefundId),
        ])->layout('layouts.staff-workspace', ['workspace' => 'cashier']);
    }
}
