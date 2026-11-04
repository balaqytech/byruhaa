<?php

namespace App\Http\Resources\Store;

use App\Modules\Store\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    public ?array $quote = null;

    /** @param array<string, mixed> $quote */
    public function withQuote(array $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cart_token' => $this->token,
            'items' => $this->when($this->relationLoaded('items'), fn (): mixed => CartItemResource::collection($this->items)),
            'quote' => $this->quote ?? [],
        ];
    }
}
