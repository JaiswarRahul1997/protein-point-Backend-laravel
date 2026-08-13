<?php

namespace App\Http\Controllers\Api;

use Admin\Models\Customer;
use Admin\Models\Order;
use Admin\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.flavor' => ['nullable', 'string', 'max:100'],
            'items.*.selections' => ['nullable', 'array'],
            'items.*.selections.*' => ['nullable', 'string', 'max:100'],
        ]);

        $productIds = collect($data['items'])->pluck('product_id')->unique()->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('status', 'enabled')
            ->with(['attributeOptions.attribute'])
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            return response()->json([
                'message' => 'One or more products are unavailable.',
            ], 422);
        }

        $order = DB::transaction(function () use ($data, $products) {
            $customer = Customer::upsertFromCheckout($data);

            $lines = [];
            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price;
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;

                $selections = collect($item['selections'] ?? [])
                    ->filter(fn ($value) => filled($value))
                    ->all();

                if ($selections === []) {
                    if (filled($item['size'] ?? null)) {
                        $selections['size'] = $item['size'];
                    }
                    if (filled($item['flavor'] ?? null)) {
                        $selections['flavor'] = $item['flavor'];
                    }
                }

                $attributeMap = collect($product->frontendAttributes())->keyBy('code');
                $options = collect($selections)->map(function ($value, $code) use ($attributeMap) {
                    $attribute = $attributeMap->get($code);
                    if (! $attribute) {
                        return ucfirst((string) $code).': '.$value;
                    }

                    $optionLabel = collect($attribute['options'])->firstWhere('value', $value)['label'] ?? $value;

                    return $attribute['label'].': '.$optionLabel;
                })->filter()->implode(' · ');

                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $options !== '' ? $product->name.' ('.$options.')' : $product->name,
                    'product_sku' => $product->sku,
                    'product_thumbnail' => $product->thumbnail,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => 'PP-'.strtoupper(Str::random(8)),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'total' => round($subtotal, 2),
            ]);

            $order->items()->createMany($lines);

            return $order->load('items');
        });

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total' => (float) $order->total,
                'total_formatted' => '₹'.number_format((float) $order->total, 0),
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'items' => $order->items->map(fn ($item) => [
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
            ],
        ], 201);
    }
}
