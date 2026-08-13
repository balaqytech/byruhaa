<?php

namespace App\Http\Resources\Store;

use App\Modules\Store\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cart_token' => $this->token,
            'items' => $this->when($this->relationLoaded('items'), fn (): mixed => CartItemResource::collection($this->items)),
        ];
    }
}
