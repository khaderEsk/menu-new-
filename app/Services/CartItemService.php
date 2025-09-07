<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Item;
use App\Models\Size;
use App\Models\Topping;

class CartItemService
{
    public function addItemToCart($cartId, $itemId, $sizeId, array $toppingIds = [], $quantity = 1)
    {


        $item = Item::findOrFail($itemId);
        $size = Size::query()->where('id', $sizeId)->where('item_id', $itemId)->firstOrFail();
        $toppings = Topping::whereIn('id', $toppingIds)->where('item_id', $itemId)->get();
        $cart = Cart::firstOrNew(
            ['id' => $cartId],
            [
                'restaurant_id' => $item->restaurant->id,
                'customer_id'=> auth()->id(),
                'user_id'=>auth()->id(),
            ]
        );
        $cart->save();
        $toppingsTotal = $toppings->sum('price');
        $itemTotal = ($size->price ?? $item->price + $toppingsTotal) * $quantity;
        $this->createOrUpdate($cart->id, $item->id, $itemTotal, $quantity, $size->id, $toppings);
        $cart->total_price = $cart->items()->sum('price');

        $cart->save();
    }

    public function calculateCartTotal($cartId)
    {
        $cart = Cart::with('items.size', 'items.toppings')->findOrFail($cartId);

        $total = 0;
        foreach ($cart->items as $item) {
            $unitPrice = $item->size->price + $item->toppings->sum('price');
            $total += $unitPrice * $item->quantity;
        }
        return $total;
    }

    public function createOrUpdate(
        int $cartItemId,
        int $cartId,
        int $itemId,
        float $price,
        int $quantity,
        int $sizeId = null,
        array $toppingIds = []
    ) {
        $cartItem = CartItem::createOrUpdate(['id' => $cartItemId], [
            'cart_id' => $cartId,
            'item_id' => $itemId,
            'size_id' => $sizeId,
            'quantity' => $quantity,
            'price' => $price
        ]);
        $cartItem->toppings()->attach($toppingIds);
        return $cartItem;
    }
}
