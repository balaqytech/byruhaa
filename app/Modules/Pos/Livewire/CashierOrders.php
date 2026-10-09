<?php

namespace App\Modules\Pos\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CashierOrders extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $cashier = Auth::guard('cashier')->user();

        abort_unless($cashier instanceof User && $cashier->can('Sell:Pos'), 403);

        $reference = trim($this->search);
        $orders = Order::query()
            ->whereNotNull('pos_cashier_user_id')
            ->whereBetween('created_at', [today()->startOfDay(), today()->endOfDay()])
            ->when($reference !== '', fn (Builder $query): Builder => $query->where('reference', $reference))
            ->where(function (Builder $query) use ($cashier, $reference): void {
                $query->where('pos_cashier_user_id', $cashier->id);

                if ($reference !== '' && $cashier->can('Reprint:PosReceipts')) {
                    $query->orWhereNotNull('paid_at');
                }
            })
            ->latest('created_at')
            ->simplePaginate(20);

        return view('livewire.store.cashier-orders', [
            'orders' => $orders,
        ])->layout('layouts.staff-workspace', ['workspace' => 'cashier']);
    }
}
