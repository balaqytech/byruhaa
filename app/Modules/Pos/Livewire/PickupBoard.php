<?php

namespace App\Modules\Pos\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Completed;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PickupBoard extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function completeOrder(int $orderId, ChangeOrderState $changeOrderState): void
    {
        $attendant = $this->authorizeCompletion();
        $order = Order::query()->findOrFail($orderId);

        if ($order->status->getValue() !== 'ready_for_pickup') {
            throw ValidationException::withMessages(['status' => 'تغيّرت حالة الطلب؛ حدّث الشاشة.']);
        }

        $changeOrderState->execute($order, Completed::class, $attendant->id);
    }

    public function render(): View
    {
        $attendant = Auth::guard('pickup')->user();

        abort_unless($attendant instanceof User && $attendant->can('View:PickupBoard'), 403);

        return view('livewire.store.pickup-board', [
            'orders' => Order::query()
                ->with('items')
                ->where('status', 'ready_for_pickup')
                ->when(trim($this->search) !== '', fn (Builder $query): Builder => $query->where('reference', trim($this->search)))
                ->orderBy('created_at')
                ->paginate(24),
            'canComplete' => $attendant->can('Complete:PickupOrders'),
        ])->layout('layouts.staff-workspace', ['workspace' => 'pickup']);
    }

    private function authorizeCompletion(): User
    {
        $attendant = Auth::guard('pickup')->user();

        abort_unless($attendant instanceof User && $attendant->can('View:PickupBoard') && $attendant->can('Complete:PickupOrders'), 403);

        return $attendant;
    }
}
