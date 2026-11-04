<?php

namespace App\Modules\Pos\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\Accepted;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\ReadyForPickup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BaristaBoard extends Component
{
    public function acceptScheduled(int $orderId, ChangeOrderState $changeOrderState): void
    {
        $barista = $this->authorizePreparation();
        $order = Order::query()->findOrFail($orderId);

        if ($order->status->getValue() !== 'confirmed' || $order->pickup_type !== 'scheduled') {
            throw ValidationException::withMessages(['status' => 'تغيّرت حالة الطلب؛ حدّث الشاشة.']);
        }

        $changeOrderState->execute($order, Accepted::class, $barista->id);
    }

    public function startPreparing(int $orderId, ChangeOrderState $changeOrderState): void
    {
        $barista = $this->authorizePreparation();
        $order = Order::query()->findOrFail($orderId);

        if (! in_array($order->status->getValue(), ['confirmed', 'accepted'], true)
            || ($order->status->getValue() === 'confirmed' && $order->pickup_type === 'scheduled')
            || ! $order->canStartScheduledPreparation()) {
            throw ValidationException::withMessages(['status' => 'تغيّرت حالة الطلب؛ حدّث الشاشة.']);
        }

        $changeOrderState->execute($order, Preparing::class, $barista->id);
    }

    public function markReady(int $orderId, ChangeOrderState $changeOrderState): void
    {
        $barista = $this->authorizePreparation();
        $order = Order::query()->findOrFail($orderId);

        if ($order->status->getValue() !== 'preparing') {
            throw ValidationException::withMessages(['status' => 'تغيّرت حالة الطلب؛ حدّث الشاشة.']);
        }

        $changeOrderState->execute($order, ReadyForPickup::class, $barista->id);
    }

    public function render(): View
    {
        $barista = Auth::guard('barista')->user();

        abort_unless($barista instanceof User && $barista->can('View:BaristaBoard'), 403);

        $orders = Order::query()
            ->with('items')
            ->whereIn('status', ['confirmed', 'accepted', 'preparing', 'ready_for_pickup'])
            ->orderBy('created_at')
            ->get();

        $lanes = [
            'new' => $orders->filter(fn (Order $order): bool => in_array($order->status->getValue(), ['confirmed', 'accepted'], true) && $order->canStartScheduledPreparation()),
            'upcoming' => $orders->filter(fn (Order $order): bool => in_array($order->status->getValue(), ['confirmed', 'accepted'], true) && ! $order->canStartScheduledPreparation()),
            'preparing' => $orders->filter(fn (Order $order): bool => $order->status->getValue() === 'preparing'),
            'ready' => $orders->filter(fn (Order $order): bool => $order->status->getValue() === 'ready_for_pickup'),
        ];

        return view('livewire.store.barista-board', [
            'lanes' => $lanes,
            'canPrepare' => $barista->can('Prepare:BaristaOrders'),
            'preparationLeadMinutes' => Order::SCHEDULED_PREPARATION_LEAD_MINUTES,
        ])
            ->layout('layouts.staff-workspace', ['workspace' => 'barista']);
    }

    private function authorizePreparation(): User
    {
        $barista = Auth::guard('barista')->user();

        abort_unless($barista instanceof User && $barista->can('View:BaristaBoard') && $barista->can('Prepare:BaristaOrders'), 403);

        return $barista;
    }
}
