<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\EventPriceTier;

final class BuildEventPriceTierOffer
{
    /**
     * @return array{
     *     current: array{id: int, name: string, price_baisa: int, currency: string, remaining_seats: int}|null,
     *     next: array{id: int, name: string, price_baisa: int, currency: string, remaining_seats: int}|null,
     *     is_sold_out: bool
     * }
     */
    public function handle(Event $event): array
    {
        $tiers = $event->priceTiers
            ->where('is_active', true)
            ->sortBy('position')
            ->values()
            ->map(fn (EventPriceTier $tier): array => [
                'id' => $tier->id,
                'name' => $tier->name,
                'price_baisa' => $tier->price_baisa,
                'currency' => $tier->currency,
                'remaining_seats' => max(0, $tier->seat_capacity - $tier->usedSeatsCount()),
            ]);

        if ($tiers->isEmpty()) {
            $remainingSeats = $event->remainingSeats();

            return [
                'current' => $remainingSeats > 0 ? [
                    'id' => 0,
                    'name' => 'السعر المعتمد',
                    'price_baisa' => $event->price_baisa,
                    'currency' => $event->currency,
                    'remaining_seats' => $remainingSeats,
                ] : null,
                'next' => null,
                'is_sold_out' => $remainingSeats === 0,
            ];
        }

        $currentIndex = $tiers->search(fn (array $tier): bool => $tier['remaining_seats'] > 0);

        if ($currentIndex === false) {
            return ['current' => null, 'next' => null, 'is_sold_out' => true];
        }

        return [
            'current' => $tiers->get($currentIndex),
            'next' => $tiers->get($currentIndex + 1),
            'is_sold_out' => false,
        ];
    }
}
